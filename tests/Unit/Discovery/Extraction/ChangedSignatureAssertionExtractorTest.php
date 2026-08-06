<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Discovery\Extraction;

use ContextDiscovery\Discovery\Extraction\ChangedSignatureAssertionExtractor;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedMember;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChangedSignatureAssertionExtractor::class)]
final class ChangedSignatureAssertionExtractorTest extends TestCase
{
    private ChangedSignatureAssertionExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ChangedSignatureAssertionExtractor();
    }

    public function testAnArityChangeIsDetected(): void
    {
        // Experiment 4: reactivate(PlaidItem, array) → reactivate(PlaidItem, string, ?string, ?string).
        $assertions = $this->extract(
            'public function reactivate(PlaidItem $item, array $data): void',
            'public function reactivate(PlaidItem $item, string $token, ?string $id = null): void',
        );

        self::assertCount(1, $assertions);
        self::assertSame(AssertionKind::ChangedSignature, $assertions[0]->kind);
        self::assertSame('reactivate', $assertions[0]->subject);
        self::assertStringContainsString('call sites are not shown by the diff', $assertions[0]->claim);
    }

    public function testAParameterShapeChangeAtTheSameArityIsDetected(): void
    {
        self::assertCount(1, $this->extract(
            'public function reactivate(PlaidItem $item, array $data): void',
            'public function reactivate(PlaidItem $item, string $data): void',
        ));
    }

    #[DataProvider('changesThatAreNotSignatureChanges')]
    public function testSomethingThatIsNotAParameterChangeYieldsNothing(?string $old, ?string $new): void
    {
        self::assertSame([], $this->extract($old, $new));
    }

    /**
     * @return iterable<string, array{string|null, string|null}>
     */
    public static function changesThatAreNotSignatureChanges(): iterable
    {
        yield 'an identical signature — a body-only change' => [
            'public function reactivate(PlaidItem $item, array $data): void',
            'public function reactivate(PlaidItem $item, array $data): void',
        ];

        yield 'reformatting only' => [
            'public function reactivate(PlaidItem $item, array $data): void',
            'public  function reactivate( PlaidItem $item,  array $data ): void',
        ];

        yield 'a return type change is neither arity nor parameter shape' => [
            'public function reactivate(PlaidItem $item): void',
            'public function reactivate(PlaidItem $item): bool',
        ];

        yield 'a visibility change alone' => [
            'public function reactivate(PlaidItem $item): void',
            'protected function reactivate(PlaidItem $item): void',
        ];

        yield 'an added member has nothing to compare against' => [
            null,
            'public function reactivate(PlaidItem $item): void',
        ];

        yield 'a removed member has nothing to compare against' => [
            'public function reactivate(PlaidItem $item): void',
            null,
        ];
    }

    public function testARenameIsTwoMembersAndNeitherIsASignatureChange(): void
    {
        // The parser treats a rename as two members and does not track it; neither side has both
        // halves, so nothing is asserted.
        $file = new ChangedFile('app/Repositories/PlaidItemRepository.php', [], [
            new ChangedMember('oldName', 'public function oldName(int $a): void', null),
            new ChangedMember('newName', null, 'public function newName(int $a): void'),
        ]);

        self::assertSame([], $this->extractor->forRegion($file, $this->regionDeclaring('oldName', 'newName'), ''));
    }

    public function testAMemberIsAttributedToTheRegionWhoseLinesCarryItsDeclaration(): void
    {
        $file = new ChangedFile('app/Repositories/PlaidItemRepository.php', [], [
            new ChangedMember(
                'reactivate',
                'public function reactivate(PlaidItem $item, array $data): void',
                'public function reactivate(PlaidItem $item, string $token): void',
            ),
        ]);

        $declaring = $this->regionDeclaring('reactivate');
        $elsewhere = new ChangedRegion(90, 92, ['        $x = 1;'], []);

        self::assertCount(1, $this->extractor->forRegion($file, $declaring, ''));
        self::assertSame([], $this->extractor->forRegion($file, $elsewhere, ''));
    }

    public function testSeveralChangedMembersEachRaiseTheirOwnAssertion(): void
    {
        $file = new ChangedFile('app/Repositories/PlaidItemRepository.php', [], [
            new ChangedMember('reactivate', 'function reactivate(int $a)', 'function reactivate(int $a, int $b)'),
            new ChangedMember('refresh', 'function refresh(int $a)', 'function refresh(string $a)'),
        ]);

        $region = $this->regionDeclaring('reactivate', 'refresh');

        self::assertSame(
            ['reactivate', 'refresh'],
            array_map(
                static fn (Assertion $assertion): string => $assertion->subject,
                $this->extractor->forRegion($file, $region, '')
            )
        );
    }

    public function testAFileWithNoChangedMembersYieldsNothing(): void
    {
        $file = new ChangedFile('app/Foo.php', [], []);

        self::assertSame([], $this->extractor->forRegion($file, $this->regionDeclaring('anything'), ''));
    }

    /**
     * @return list<Assertion>
     */
    private function extract(?string $old, ?string $new): array
    {
        $file = new ChangedFile('app/Repositories/PlaidItemRepository.php', [], [
            new ChangedMember('reactivate', $old, $new),
        ]);

        return $this->extractor->forRegion($file, $this->regionDeclaring('reactivate'), '');
    }

    private function regionDeclaring(string ...$members): ChangedRegion
    {
        $lines = array_map(
            static fn (string $member): string => sprintf('    public function %s($a): void', $member),
            $members
        );

        return new ChangedRegion(20, 20 + count($lines), $lines, []);
    }
}
