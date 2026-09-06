<?php

declare(strict_types=1);

/**
 * M15 · boundary simulation — an experiment artifact, not production code.
 *
 * Asks what each candidate origin boundary WOULD produce, without changing anything. Nothing here
 * is wired into the CLI, and no production file is edited to run it.
 *
 *   php tests/Acceptance/fixtures/experiment-15/boundary-simulation.php <repo-root> <diff> <cli-root>
 *
 * The origin categories are read from the repository's OWN composer.json — `autoload.psr-4` is
 * production, `autoload-dev.psr-4` is dev, anything else is unmapped — rather than from a
 * hand-written list of directory names. That is the strongest form the discriminator could take,
 * so if it cannot be justified here it cannot be justified in a weaker form either.
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
$resolver = new NamedReferenceResolver($locator, $source, $slicer, new LaravelFrameworkKnowledge());

$diff = (new UnifiedDiffParser())->parse((string) file_get_contents($diffPath));
$assertions = (new AssertionExtractor([
    new NamedReferenceAssertionExtractor($slicer),
]))->extract($diff, $source);

// ---------------------------------------------------------------- origin, from composer.json

$manifest = json_decode((string) $source->text('composer.json'), true);

$dirs = static function (?array $section): array {
    $out = [];

    foreach (($section['psr-4'] ?? []) as $paths) {
        foreach ((array) $paths as $path) {
            $out[] = rtrim($path, '/') . '/';
        }
    }

    return $out;
};

$productionDirs = $dirs($manifest['autoload'] ?? null);
$devDirs        = $dirs($manifest['autoload-dev'] ?? null);

$originOf = static function (string $path) use ($productionDirs, $devDirs): string {
    foreach ($devDirs as $dir) {
        if (str_starts_with($path, $dir)) {
            return 'dev';
        }
    }

    foreach ($productionDirs as $dir) {
        if (str_starts_with($path, $dir)) {
            return 'production';
        }
    }

    return 'unmapped';
};

// ---------------------------------------------------------------- what the pipeline would do

$records = [];

foreach ($assertions as $assertion) {
    if ($assertion->kind !== AssertionKind::NamedReference) {
        continue;
    }

    [$class] = NamedReferenceResolver::split($assertion->subject);

    $slices  = $resolver->resolve($assertion);
    $settled = $slices === [] && $resolver->lookupRan($assertion);
    $surface = ($slices === [] && !$settled) ? $resolver->unresolvedMemberSurface($assertion) : [];

    $records[] = [
        'subject' => $assertion->subject,
        'class'   => $class,
        'origin'  => $originOf($assertion->originPath),
        'path'    => $assertion->originPath,
        'settled' => $settled,
        'slices'  => $slices,
        'surface' => $surface,
    ];
}

// ---------------------------------------------------------------- the boundaries

/**
 * @param callable(array): array{flag:bool, slices:list<object>, surface:list<object>} $rule
 */
$run = static function (string $label, callable $rule) use ($records, $diff, $estimate): array {
    $flags = 0;
    $fetched = 0;
    $tokens = 0;
    $surfacedClasses = [];
    $bareClasses = [];
    $detail = [];

    foreach ($records as $r) {
        if (str_contains($r['subject'], '::') === false) {
            $bareClasses[$r['class']] = true;
        }
    }

    foreach ($records as $r) {
        $decision = $rule($r);

        if ($decision['flag']) {
            $flags++;
            $tokens += 20; // every flag statement in this fixture estimates at 20
            $detail[] = 'flag ' . $r['subject'] . ' @' . $r['path'];
        }

        foreach ($decision['slices'] as $slice) {
            $fetched++;
            $tokens += $estimate->of($slice->text);
            $detail[] = 'slice ' . $slice->path . '::' . $slice->member;
        }

        if ($decision['surface'] !== [] && !isset($surfacedClasses[$r['class']]) && !isset($bareClasses[$r['class']])) {
            $surfacedClasses[$r['class']] = true;

            foreach ($decision['surface'] as $slice) {
                if ($diff->showsEntirely($slice->path, $slice->firstLine, $slice->lastLine)) {
                    continue; // ADR-A019, unchanged by every boundary
                }

                $fetched++;
                $tokens += $estimate->of($slice->text);
                $detail[] = 'surface ' . $slice->path . '::' . $slice->member;
            }
        }
    }

    return ['label' => $label, 'flags' => $flags, 'fetched' => $fetched, 'tokens' => $tokens, 'detail' => $detail];
};

$boundaries = [
    // B0 · current behaviour.
    'B0 current' => static fn (array $r): array => [
        'flag'    => $r['slices'] === [] && !$r['settled'],
        'slices'  => $r['slices'],
        'surface' => $r['surface'],
    ],

    // B1 · ignore every dev-origin reference entirely.
    'B1 drop dev' => static fn (array $r): array => $r['origin'] === 'dev'
        ? ['flag' => false, 'slices' => [], 'surface' => []]
        : ['flag' => $r['slices'] === [] && !$r['settled'], 'slices' => $r['slices'], 'surface' => $r['surface']],

    // B2 · suppress only the SURFACE for a dev-origin reference; keep flags and member slices.
    'B2 no dev surface' => static fn (array $r): array => [
        'flag'    => $r['slices'] === [] && !$r['settled'],
        'slices'  => $r['slices'],
        'surface' => $r['origin'] === 'dev' ? [] : $r['surface'],
    ],

    // B3 · suppress flags AND surfaces for dev origin, but keep resolved member slices.
    'B3 no dev flag/surface' => static fn (array $r): array => [
        'flag'    => $r['origin'] === 'dev' ? false : ($r['slices'] === [] && !$r['settled']),
        'slices'  => $r['slices'],
        'surface' => $r['origin'] === 'dev' ? [] : $r['surface'],
    ],

    // B4 · three-way: production keeps everything, dev loses the surface, unmapped keeps
    //      everything (the tool has no declaration either way, so it must not guess).
    'B4 three-way' => static fn (array $r): array => [
        'flag'    => $r['slices'] === [] && !$r['settled'],
        'slices'  => $r['slices'],
        'surface' => $r['origin'] === 'dev' ? [] : $r['surface'],
    ],
];

printf("%-24s %6s %8s %8s\n", 'boundary', 'flags', 'fetched', 'tokens');

$results = [];

foreach ($boundaries as $label => $rule) {
    $results[$label] = $run($label, $rule);
    printf("%-24s %6d %8d %8d\n", $label, $results[$label]['flags'], $results[$label]['fetched'], $results[$label]['tokens']);
}

echo "\n--- what each boundary changes against B0\n";

$base = $results['B0 current']['detail'];
sort($base);

foreach ($results as $label => $result) {
    if ($label === 'B0 current') {
        continue;
    }

    $detail = $result['detail'];
    sort($detail);

    $lost = array_values(array_diff($base, $detail));
    $gained = array_values(array_diff($detail, $base));

    printf("\n%s\n", $label);

    foreach ($lost as $line) {
        printf("  - %s\n", $line);
    }

    foreach ($gained as $line) {
        printf("  + %s\n", $line);
    }
}

echo "\n--- origin census\n";

$census = [];

foreach ($records as $r) {
    $census[$r['origin']] = ($census[$r['origin']] ?? 0) + 1;
}

ksort($census);

foreach ($census as $origin => $count) {
    printf("  %-12s %d references\n", $origin, $count);
}
