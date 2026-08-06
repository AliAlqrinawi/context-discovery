<?php

declare(strict_types=1);

namespace ContextDiscovery\Discovery\Extraction;

use ContextDiscovery\Discovery\Lever\PremiseCatalogue;
use ContextDiscovery\Domain\Assertion\Assertion;
use ContextDiscovery\Domain\Assertion\AssertionKind;
use ContextDiscovery\Domain\Diff\ChangedFile;
use ContextDiscovery\Domain\Diff\ChangedRegion;
use ContextDiscovery\Ports\MemberSlicer;

/**
 * Emits `UnverifiablePremise` from the closed catalogue — a premise if and only if its literal
 * trigger is present (ADR-A009).
 *
 * The four premises here are the evidence-earned ones. The other three in the catalogue are P10
 * failure premises raised at *resolution* time, not extraction, and never come from a diff.
 *
 * | Premise | Trigger | Earned by |
 * |---|---|---|
 * | `surrounding-transaction` | ≥ 2 persistence-write calls in the region **and** the enclosing member opens no transaction | Exp 1 |
 * | `atomic-lock-store` | the region names `Cache::lock(` | Exp 3 |
 * | `schema-index-support` | the region names `lockForUpdate(` or `sharedLock(` | Exp 3 |
 * | `data-state-after-behaviour-change` | a **removed** line is a `use <Trait>;` inside a class body | Exp 3 |
 *
 * **There is no inference path.** Every test below is a literal token test over the changed region
 * and the changed file's own text — the two things R1 already loads. Nothing is scored, weighted or
 * judged (P6, X4), and a region matching no trigger yields nothing, which is what keeps Experiment
 * 2's bundle almost empty.
 *
 * Triggers are read from **added** lines, except the trait-removal trigger which is by definition
 * about a removed one. Added lines are what the change asserts.
 *
 * ---
 *
 * **AA9 — architectural assumption, not evidence.** The list of calls that count as a
 * "persistence write" (`WRITE_METHODS`) is not enumerated by any experiment. Experiment 1 earned
 * the premise from one reconciliation path; this list generalises it. **Experiment 4 is the
 * declared precision guard: its key asks for no premise, so if the trigger fires there the list is
 * wrong** — and that goes back to the research repository rather than being tuned here (ADR-A003).
 * The list is kept to canonical Eloquent and query-builder writes, and a bare function call never
 * counts: a write is always reached through `->` or `::`.
 *
 * **AA10 — architectural assumption, not evidence.** Experiment 3 earned the trait-removal trigger
 * for `SoftDeletes` specifically. It is widened here to any trait removal inside a class body, as
 * ADR-A009 states. Narrowing back to that single trait is the correction if a fixture shows a
 * false positive.
 */
final class UnverifiablePremiseAssertionExtractor implements RegionAssertionExtractor
{
    /**
     * AA9. See the class docblock: assumed, not measured, and guarded by Experiment 4.
     */
    private const WRITE_METHODS = [
        'save',
        'update',
        'updateOrCreate',
        'create',
        'firstOrCreate',
        'insert',
        'insertGetId',
        'upsert',
        'delete',
        'forceDelete',
        'restore',
        'increment',
        'decrement',
    ];

    /**
     * The two forms ADR-A009 names for a member opening its own transaction.
     */
    private const TRANSACTION_OPENERS = ['transaction', 'beginTransaction'];

    public function __construct(private readonly MemberSlicer $slicer)
    {
    }

    /**
     * @return list<Assertion>
     */
    public function forRegion(ChangedFile $file, ChangedRegion $region, string $fileText): array
    {
        $added = $this->tokenize(implode("\n", $region->addedLines));

        $premises = [];

        if ($this->opensNoTransactionAround($region, $fileText) && $this->persistenceWrites($added) >= 2) {
            $premises[PremiseCatalogue::SurroundingTransaction->value] =
                'the region performs several persistence writes; whether a transaction wraps them '
                . 'is decided by the caller, which the diff does not show';
        }

        if ($this->namesStaticCall($added, 'Cache', 'lock')) {
            $premises[PremiseCatalogue::AtomicLockStore->value] =
                'the region takes a cache lock; whether the deployed store makes it atomic is not '
                . 'a fact any file settles';
        }

        if ($this->namesCall($added, 'lockForUpdate') || $this->namesCall($added, 'sharedLock')) {
            $premises[PremiseCatalogue::SchemaIndexSupport->value] =
                'the region takes a row lock; whether the schema indexes the locked predicate is '
                . 'not shown by the diff';
        }

        if ($this->removesTraitUse($region)) {
            $premises[PremiseCatalogue::DataStateAfterBehaviourChange->value] =
                'the change removes a trait from a class body; how pre-existing rows behave after '
                . 'it depends on production data state';
        }

        $assertions = [];

        foreach ($premises as $premise => $claim) {
            $assertions[] = new Assertion(
                AssertionKind::UnverifiablePremise,
                $premise,
                $file->path,
                $region,
                $claim,
            );
        }

        return $assertions;
    }

    /**
     * Whether the member enclosing the region opens no transaction of its own.
     *
     * A region outside any member yields false: the enclosing member cannot be inspected, so the
     * second half of the trigger cannot be established and the premise does not fire. Not firing
     * is the conservative direction — Experiment 4 is the guard.
     */
    private function opensNoTransactionAround(ChangedRegion $region, string $fileText): bool
    {
        $member = null;

        for ($line = $region->firstLine; $line <= $region->lastLine; $line++) {
            $member = $this->slicer->enclosingMemberName($fileText, $line);

            if ($member !== null) {
                break;
            }
        }

        if ($member === null) {
            return false;
        }

        $slice = $this->slicer->member($fileText, $member);

        if ($slice === null) {
            return false;
        }

        $tokens = $this->tokenize($slice->text);

        foreach (self::TRANSACTION_OPENERS as $opener) {
            if ($this->namesStaticCall($tokens, 'DB', $opener)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calls that write to persistence, reached through `->` or `::`. A bare function of the same
     * name is not a write.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function persistenceWrites(array $tokens): int
    {
        $writes = 0;
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_STRING || !in_array($tokens[$i]['text'], self::WRITE_METHODS, true)) {
                continue;
            }

            $before = $this->previousSignificant($tokens, $i - 1);
            $after = $this->nextSignificant($tokens, $i + 1);

            $reachedThroughAnObject = $before !== null
                && ($tokens[$before]['id'] === T_OBJECT_OPERATOR || $tokens[$before]['id'] === T_DOUBLE_COLON);

            $isCall = $after !== null && $tokens[$after]['id'] === null && $tokens[$after]['text'] === '(';

            if ($reachedThroughAnObject && $isCall) {
                $writes++;
            }
        }

        return $writes;
    }

    /**
     * `Name::member(` — the form both `Cache::lock(` and `DB::transaction(` take.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function namesStaticCall(array $tokens, string $class, string $member): bool
    {
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_STRING || $tokens[$i]['text'] !== $class) {
                continue;
            }

            $operator = $this->nextSignificant($tokens, $i + 1);
            $name = $operator === null ? null : $this->nextSignificant($tokens, $operator + 1);
            $open = $name === null ? null : $this->nextSignificant($tokens, $name + 1);

            if (
                $operator !== null && $tokens[$operator]['id'] === T_DOUBLE_COLON
                && $name !== null && $tokens[$name]['id'] === T_STRING && $tokens[$name]['text'] === $member
                && $open !== null && $tokens[$open]['id'] === null && $tokens[$open]['text'] === '('
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * `name(` anywhere — the form `lockForUpdate(` and `sharedLock(` take.
     *
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function namesCall(array $tokens, string $name): bool
    {
        $total = count($tokens);

        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]['id'] !== T_STRING || $tokens[$i]['text'] !== $name) {
                continue;
            }

            $open = $this->nextSignificant($tokens, $i + 1);

            if ($open !== null && $tokens[$open]['id'] === null && $tokens[$open]['text'] === '(') {
                return true;
            }
        }

        return false;
    }

    /**
     * A removed `use <Trait>;` inside a class body.
     *
     * Two literal discriminators separate it from a removed import, and both must hold: the line is
     * **indented**, because a class body is, and a top-level import never is; and the name is
     * **unqualified**, which is the form a class body uses once the trait is imported at the top.
     * A removed `use App\Models\Thing;` satisfies neither.
     */
    private function removesTraitUse(ChangedRegion $region): bool
    {
        foreach ($region->removedLines as $line) {
            if (preg_match('/^\s+use\s+[A-Za-z_]\w*(\s*,\s*[A-Za-z_]\w*)*\s*;\s*$/', $line) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{id:int|null,text:string}>
     */
    private function tokenize(string $code): array
    {
        $tokens = [];

        foreach (@token_get_all('<?php ' . $code) as $token) {
            $tokens[] = is_array($token)
                ? ['id' => $token[0], 'text' => $token[1]]
                : ['id' => null, 'text' => $token];
        }

        return $tokens;
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function nextSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from, $total = count($tokens); $i < $total; $i++) {
            if (!$this->isSkippable($tokens[$i]['id'])) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param list<array{id:int|null,text:string}> $tokens
     */
    private function previousSignificant(array $tokens, int $from): ?int
    {
        for ($i = $from; $i >= 0; $i--) {
            if (!$this->isSkippable($tokens[$i]['id'])) {
                return $i;
            }
        }

        return null;
    }

    private function isSkippable(?int $id): bool
    {
        return $id !== null && in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], true);
    }
}
