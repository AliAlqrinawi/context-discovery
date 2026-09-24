<?php

declare(strict_types=1);

namespace ContextDiscovery\Tests\Unit\Domain\Bundle;

use ContextDiscovery\Domain\Bundle\ContractVersion;
use PHPUnit\Framework\TestCase;

/**
 * `policy_version` is a declared constant, and this is what stops it going stale.
 *
 * A bundle carries the value so two scored runs can be compared knowing whether the *rules*
 * changed underneath them. A constant nobody is forced to bump would answer that question wrongly
 * and silently, which is worse than not answering it — so the classes that decide lever, band and
 * premise are hashed here, and the hash is recorded beside the constant.
 *
 * Comments are stripped before hashing: rewriting a docblock changes no decision, and a guard that
 * fires on prose would be trained away within a week.
 *
 * **When this fails**, both values move together in the same commit: bump `ContractVersion::POLICY`
 * and paste the hash the failure prints. That is the whole ceremony, and it is deliberate — it is
 * the discipline ADR-A024 adopted after `KIND_ORDER` sat stale in three places at once.
 */
final class PolicyVersionGuardTest extends TestCase
{
    /**
     * The classes whose content `policy_version` claims to describe.
     */
    private const GOVERNED = [
        'src/Discovery/Lever/LeverPolicy.php',
        'src/Assembly/ItemPriority.php',
        'src/Discovery/Lever/PremiseCatalogue.php',
    ];

    private const RECORDED_HASH = '30921b9b9e02';

    public function testThePolicyVersionMatchesTheRulesItDescribes(): void
    {
        self::assertSame(
            self::RECORDED_HASH,
            $this->hashOfGovernedRules(),
            sprintf(
                "The lever, band or premise rules changed while policy_version stayed \"%s\".\n\n"
                . "Bump ContractVersion::POLICY and set RECORDED_HASH to the actual value above, in\n"
                . "the same commit as the rule change. A bundle's policy_version has to mean\n"
                . "something for a scored comparison to be worth anything (ADR-A024).",
                ContractVersion::POLICY
            )
        );
    }

    public function testTheGuardActuallyCoversEveryGoverningClass(): void
    {
        // A guard that hashes a file that has been moved or renamed silently stops guarding.
        foreach (self::GOVERNED as $path) {
            self::assertFileExists(dirname(__DIR__, 4) . '/' . $path, $path . ' is no longer where the guard looks');
        }
    }

    private function hashOfGovernedRules(): string
    {
        $root = dirname(__DIR__, 4);
        $parts = [];

        foreach (self::GOVERNED as $path) {
            $parts[] = $this->withoutCommentsOrBlankLines((string) file_get_contents($root . '/' . $path));
        }

        return substr(sha1(implode("\n", $parts)), 0, 12);
    }

    private function withoutCommentsOrBlankLines(string $source): string
    {
        $kept = [];

        foreach (token_get_all($source) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true)) {
                continue;
            }

            $kept[] = is_array($token) ? $token[1] : $token;
        }

        return implode(' ', $kept);
    }
}
