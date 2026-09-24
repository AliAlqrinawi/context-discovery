<?php

declare(strict_types=1);

namespace ContextDiscovery\Domain\Source;

/**
 * Where an inherited member is declared, found by walking `extends` and `use <Trait>` through
 * project files to a fixed point (ADR-A010 D2, verify-only; ADR-A028).
 *
 * Every field is a fact the walk read: the declaring type and its file and line, whether it was
 * reached as a trait or as a parent, the class that applies the trait (null when the member is
 * a parent's own), and the parents walked that did **not** declare it - the clause that corrects
 * "it is on the parent". Nothing here is inferred, and no body is carried (ADR-A010 §4).
 */
final class AncestorDeclaration
{
    /**
     * @param list<string> $walkedParents fully-qualified, in walk order, each opened and found not to declare the member
     */
    public function __construct(
        public readonly string $callingClass,
        public readonly string $member,
        public readonly string $declaringClass,
        public readonly string $path,
        public readonly int $line,
        public readonly bool $viaTrait,
        public readonly ?string $appliedBy,
        public readonly array $walkedParents,
    ) {
    }
}
