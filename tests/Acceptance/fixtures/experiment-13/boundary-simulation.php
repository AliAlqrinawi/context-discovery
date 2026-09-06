<?php

declare(strict_types=1);

/**
 * M13 · boundary simulation — an experiment artifact, not production code.
 *
 * It reads the real components and asks what each candidate boundary WOULD produce, without
 * changing any of them. Nothing here is wired into the CLI.
 *
 *   php tests/Acceptance/fixtures/experiment-13/boundary-simulation.php <repo-root> <diff> <cli-root>
 */

require $argv[3] . '/vendor/autoload.php';

use ContextDiscovery\Adapters\Autoload\ComposerPsr4ClassLocator;
use ContextDiscovery\Adapters\Filesystem\LocalSourceRepository;
use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\NamedReferenceAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Domain\Assertion\AssertionKind;

[$script, $repoRoot, $diffPath, $cliRoot] = $argv;

$source   = new LocalSourceRepository($repoRoot);
$slicer   = new TokenizerMemberSlicer();
$locator  = new ComposerPsr4ClassLocator($source);
$estimate = new TokenEstimate();

$diff = (new UnifiedDiffParser())->parse((string) file_get_contents($diffPath));
$assertions = (new AssertionExtractor([
    new OwnFileAssertionExtractor($slicer),
    new NamedReferenceAssertionExtractor($slicer),
]))->extract($diff, $source);

$memberSubjects = [];
$bareSubjects   = [];
foreach ($assertions as $a) {
    if ($a->kind !== AssertionKind::NamedReference) {
        continue;
    }
    [$class, $member] = NamedReferenceResolver::split($a->subject);
    if ($member === null) {
        $bareSubjects[$class] = true;
    } else {
        $memberSubjects[$class][] = $member;
    }
}

$memberResolves = static function (string $class, string $member) use ($locator, $source, $slicer): bool {
    $path = $locator->pathFor($class);
    $text = $path === null ? null : $source->text($path);

    return $text !== null && $slicer->member($text, $member) !== null;
};

$isProject = static function (string $class) use ($locator): bool {
    $p = $locator->pathFor($class);

    return $p !== null && $locator->isProjectSource($p);
};

$boundaries = [
    'B0 - today' => [],
    'B1 - frozen form 3, unconditional' => array_keys($memberSubjects),
    'B2 - B1, project source only' => array_values(array_filter(array_keys($memberSubjects), $isProject)),
    'B3 - only when the member does not resolve' => array_values(array_filter(
        array_keys($memberSubjects),
        static function (string $class) use ($memberSubjects, $memberResolves): bool {
            foreach ($memberSubjects[$class] as $m) {
                if (!$memberResolves($class, $m)) {
                    return true;
                }
            }

            return false;
        }
    )),
    'B4 - placeable AND project AND member unresolved' => array_values(array_filter(
        array_keys($memberSubjects),
        static function (string $class) use ($memberSubjects, $memberResolves, $isProject): bool {
            if (!$isProject($class)) {
                return false;                    // unplaceable, or a dependency's — ADR-A012/A017
            }

            foreach ($memberSubjects[$class] as $m) {
                if (!$memberResolves($class, $m)) {
                    return true;                 // the member is not in the class's own file
                }
            }

            return false;                        // every member resolved — the member slice IS the answer
        }
    )),
];

printf("%-44s %-7s %-9s %-8s %s\n", 'BOUNDARY', 'added', 'slices', 'tokens', 'what each added class becomes');
foreach ($boundaries as $label => $classes) {
    $classes = array_values(array_diff($classes, array_keys($bareSubjects)));
    $slices = 0;
    $tokens = 0;
    $named  = [];
    foreach ($classes as $class) {
        $short = substr($class, strrpos($class, '\\') + 1);
        $path = $locator->pathFor($class);
        if ($path === null) {
            $named[] = $short . ' EXTRA-FLAG';
            continue;
        }
        if (!$locator->isProjectSource($path)) {
            $named[] = $short . ' settled(no item)';
            continue;
        }
        $text = (string) $source->text($path);
        $n = 0;
        foreach ($slicer->memberNames($text) as $name) {
            $slice = $slicer->member($text, $name);
            if ($slice !== null) {
                $n++;
                $tokens += $estimate->of($slice->text);
            }
        }
        $slices += $n;
        $named[] = sprintf('%s(+%d)', $short, $n);
    }
    printf("%-44s %-7d %-9d %-8d %s\n", $label, count($classes), $slices, $tokens, implode(' ', $named) ?: '-');
}
