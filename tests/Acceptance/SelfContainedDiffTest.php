<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Acceptance;

use ContextDiscovery\Tests\Support\ReadsBundles;
use PHPUnit\Framework\TestCase;

/**
 * A self-contained diff must produce an almost-empty bundle.
 *
 * Trace logging added to a method that already imports everything it names touches nothing outside
 * the file. The temptation is to fetch the model "just in case" — the code reads `$account->id`, so
 * why not show the model? Because that is speculation, and the research is explicit that it *"is a
 * precision failure, not caution"*. Nothing may be fetched that the diff does not name as a
 * cross-file dependency, and no premise fires on a change that writes nothing and locks nothing.
 *
 * The one item this shape does produce is the facade flag: `Log::debug` cannot be placed in a
 * repository with no framework on disk, and an unresolved reference is stated rather than assumed
 * (P10). It is a flag, not a fetch. "Almost empty" means exactly that.
 */
final class SelfContainedDiffTest extends TestCase
{
    use ReadsBundles;

    private string $repository = '';

    protected function setUp(): void
    {
        $this->repository = sys_get_temp_dir() . '/self-contained-' . bin2hex(random_bytes(6));

        mkdir($this->repository . '/app/Services/Plaid', 0o777, true);
        mkdir($this->repository . '/app/Models', 0o777, true);
        file_put_contents($this->repository . '/composer.json', '{"autoload":{"psr-4":{"App\\\\":"app/"}}}');

        file_put_contents($this->repository . '/app/Models/PlaidAccount.php', <<<'PHP'
            <?php

            namespace App\Models;

            class PlaidAccount
            {
                protected $fillable = ['official_name', 'mask'];

                public function forItem(int $itemId): static
                {
                    return new static();
                }
            }
            PHP);

        // The post-image: the logging lines are already in the file (03-interfaces.md §1).
        file_put_contents($this->repository . '/app/Services/Plaid/PlaidAccountService.php', <<<'PHP'
            <?php

            namespace App\Services\Plaid;

            use App\Models\PlaidAccount;
            use Illuminate\Support\Facades\Log;

            class PlaidAccountService
            {
                public function syncFromResponse(PlaidAccount $account, array $response): void
                {
                    Log::debug('sync start', ['account' => $account->id, 'keys' => array_keys($response)]);

                    $account->fill($response);

                    Log::debug('sync end', ['account' => $account->id]);
                }
            }
            PHP);

        file_put_contents($this->repository . '/change.diff', <<<'DIFF'
            diff --git a/app/Services/Plaid/PlaidAccountService.php b/app/Services/Plaid/PlaidAccountService.php
            --- a/app/Services/Plaid/PlaidAccountService.php
            +++ b/app/Services/Plaid/PlaidAccountService.php
            @@ -10,5 +10,9 @@ class PlaidAccountService
                 public function syncFromResponse(PlaidAccount $account, array $response): void
                 {
            +        Log::debug('sync start', ['account' => $account->id, 'keys' => array_keys($response)]);
            +
                     $account->fill($response);
            +
            +        Log::debug('sync end', ['account' => $account->id]);
                 }
             }
            DIFF . "\n");
    }

    protected function tearDown(): void
    {
        if ($this->repository !== '' && is_dir($this->repository)) {
            exec('rm -rf ' . escapeshellarg($this->repository));
        }
    }

    public function testTheModelIsNotPulledJustInCase(): void
    {
        $bundle = $this->bundle();

        foreach ($bundle['assertions'] as $assertion) {
            self::assertStringNotContainsString(
                'PlaidAccount',
                $assertion['subject'],
                'the model the code touches is not fetched speculatively — that is a precision failure, not caution'
            );
        }

        foreach ($bundle['items'] as $item) {
            self::assertStringNotContainsString('app/Models/', $item['provenance']['path']);
        }
    }

    public function testNoPremiseFiresOnTraceLogging(): void
    {
        self::assertNotContains(
            'unverifiable_premise',
            array_column($this->bundle()['assertions'], 'kind'),
            'nothing is written, locked or migrated, so no catalogue trigger is present'
        );
    }

    public function testNothingIsFetchedAtAll(): void
    {
        // "Almost empty. At most nothing." The only item is a flag for a facade this repository
        // cannot place; there is no fetched slice of any kind.
        $fetched = array_filter($this->bundle()['items'], static fn (array $i): bool => $i['lever'] === 'fetched');

        self::assertSame([], array_values($fetched));
    }

    /**
     * @return array<string, mixed>
     */
    private function bundle(): array
    {
        $root = dirname(__DIR__, 2);

        $process = proc_open(
            [PHP_BINARY, $root . '/bin/context-discover', '--diff', $this->repository . '/change.diff',
             '--repo', $this->repository, '--budget', '8000', '--format', 'json'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root
        );

        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $bundle = json_decode($stdout, true);

        self::assertIsArray($bundle, $stderr);

        return $bundle;
    }
}
