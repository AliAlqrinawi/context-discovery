<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Adapters\Filesystem;

use ContextDiscovery\Adapters\Filesystem\LocalSourceRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * This adapter *is* the filesystem boundary, so it is the one place a test has to touch a real
 * filesystem: root-scoping, symlink resolution and byte-ordered listing cannot be observed
 * through an in-memory double. Everything is created under a temporary directory and removed
 * again; no test reads the project or the machine.
 */
#[CoversClass(LocalSourceRepository::class)]
final class LocalSourceRepositoryTest extends TestCase
{
    private string $root;
    private string $outside;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/context-discovery-' . uniqid('', true);

        $this->root = $base . '/repo';
        $this->outside = $base . '/outside';

        mkdir($this->root . '/app/Services', 0o777, true);
        mkdir($this->root . '/app/Models', 0o777, true);
        mkdir($this->outside, 0o777, true);

        file_put_contents($this->root . '/composer.json', '{"name":"acme/app"}');
        file_put_contents($this->root . '/app/Services/Thing.php', "<?php\nclass Thing {}\n");
        file_put_contents($this->root . '/app/Services/Other.php', "<?php\nclass Other {}\n");
        file_put_contents($this->root . '/app/Models/Account.php', "<?php\nclass Account {}\n");
        file_put_contents($this->root . '/app/README.md', "not php\n");
        file_put_contents($this->outside . '/secret.txt', "private\n");
    }

    protected function tearDown(): void
    {
        $this->remove(dirname($this->root));
    }

    // ---------------------------------------------------------------- reading

    public function testReadsAFileInsideTheRoot(): void
    {
        self::assertSame(
            "<?php\nclass Thing {}\n",
            $this->repository()->text('app/Services/Thing.php')
        );
    }

    public function testCarriageReturnsAreNormalisedSoACrlfCheckoutSlicesIdentically(): void
    {
        file_put_contents($this->root . '/app/Crlf.php', "<?php\r\nclass Crlf {}\r\n");

        self::assertSame("<?php\nclass Crlf {}\n", $this->repository()->text('app/Crlf.php'));
    }

    public function testAMissingFileIsNullNotAnError(): void
    {
        self::assertNull($this->repository()->text('app/Nope.php'));
        self::assertFalse($this->repository()->exists('app/Nope.php'));
    }

    public function testExistsAnswersForFilesOnly(): void
    {
        $repository = $this->repository();

        self::assertTrue($repository->exists('app/Services/Thing.php'));
        self::assertFalse($repository->exists('app/Services'), 'a directory is not a readable file');
    }

    public function testAnUnreadableRootIsRefusedUpFront(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not a readable directory');

        new LocalSourceRepository($this->root . '/does-not-exist');
    }

    // ---------------------------------------------------------------- root scoping

    #[DataProvider('escapingPaths')]
    public function testAPathThatLeavesTheRootIsRefusedAndNeverRead(string $path): void
    {
        $repository = $this->repository();

        self::assertNull($repository->text($path), 'text() must refuse ' . $path);
        self::assertFalse($repository->exists($path), 'exists() must refuse ' . $path);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function escapingPaths(): iterable
    {
        yield 'parent traversal' => ['../outside/secret.txt'];
        yield 'nested parent traversal' => ['app/../../outside/secret.txt'];
        yield 'absolute unix path' => ['/etc/hosts'];
        yield 'absolute windows path' => ['C:\\Windows\\win.ini'];
        yield 'unc path' => ['\\\\server\\share'];
        yield 'empty path' => [''];
    }

    public function testASymlinkPointingOutsideTheRootIsRefused(): void
    {
        symlink($this->outside . '/secret.txt', $this->root . '/app/leak.txt');

        $repository = $this->repository();

        self::assertNull($repository->text('app/leak.txt'));
        self::assertFalse($repository->exists('app/leak.txt'));
    }

    public function testASymlinkedDirectoryPointingOutsideTheRootIsRefused(): void
    {
        symlink($this->outside, $this->root . '/app/elsewhere');

        self::assertNull($this->repository()->text('app/elsewhere/secret.txt'));
    }

    // ---------------------------------------------------------------- listing

    public function testFilesUnderIsRecursiveRepositoryRelativeAndExtensionFiltered(): void
    {
        self::assertSame(
            [
                'app/Models/Account.php',
                'app/Services/Other.php',
                'app/Services/Thing.php',
            ],
            $this->repository()->filesUnder('app/', 'php')
        );
    }

    public function testFilesUnderIsSortedByByteValueSoEveryMachineAgrees(): void
    {
        // Uppercase sorts before lowercase by byte value; a locale-aware sort would disagree.
        file_put_contents($this->root . '/app/alpha.php', '<?php');
        file_put_contents($this->root . '/app/Beta.php', '<?php');

        $found = $this->repository()->filesUnder('app', 'php');
        $sorted = $found;
        sort($sorted, SORT_STRING);

        self::assertSame($sorted, $found);
        self::assertSame('app/Beta.php', $found[0]);
    }

    #[DataProvider('equivalentPrefixesAndExtensions')]
    public function testPrefixAndExtensionAreAcceptedInEitherForm(string $prefix, string $extension): void
    {
        self::assertContains(
            'app/Services/Thing.php',
            $this->repository()->filesUnder($prefix, $extension)
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function equivalentPrefixesAndExtensions(): iterable
    {
        yield 'trailing slash, bare extension' => ['app/', 'php'];
        yield 'no trailing slash, dotted extension' => ['app', '.php'];
        yield 'leading slash' => ['/app/', 'php'];
        yield 'whole repository' => ['', 'php'];
    }

    public function testFilesUnderSkipsSymlinksRatherThanFollowingThemOut(): void
    {
        symlink($this->outside, $this->root . '/app/elsewhere');
        file_put_contents($this->outside . '/Leaked.php', '<?php');

        $found = $this->repository()->filesUnder('app', 'php');

        self::assertNotContains('app/elsewhere/Leaked.php', $found);
    }

    #[DataProvider('unusablePrefixes')]
    public function testAnUnusablePrefixListsNothing(string $prefix): void
    {
        self::assertSame([], $this->repository()->filesUnder($prefix, 'php'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unusablePrefixes(): iterable
    {
        yield 'missing directory' => ['does/not/exist'];
        yield 'escaping prefix' => ['../outside'];
        yield 'a file, not a directory' => ['composer.json'];
    }

    public function testListingIsRepeatable(): void
    {
        $repository = $this->repository();

        self::assertSame($repository->filesUnder('app', 'php'), $repository->filesUnder('app', 'php'));
    }

    // ---------------------------------------------------------------- helpers

    private function repository(): LocalSourceRepository
    {
        return new LocalSourceRepository($this->root);
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        foreach (array_diff((array) scandir($path), ['.', '..']) as $entry) {
            $this->remove($path . '/' . $entry);
        }

        rmdir($path);
    }
}
