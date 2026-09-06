<?php

declare(strict_types=1);

namespace ContextDiscovery\Cli;

use ContextDiscovery\Adapters\Autoload\ComposerPsr4ClassLocator;
use ContextDiscovery\Adapters\Filesystem\LocalSourceRepository;
use ContextDiscovery\Adapters\Php\TokenizerMemberSlicer;
use ContextDiscovery\Adapters\Search\ScopedGrepCallSiteSearch;
use ContextDiscovery\Adapters\Serialization\JsonBundleWriter;
use ContextDiscovery\Adapters\Serialization\MarkdownBundleWriter;
use ContextDiscovery\Assembly\BudgetEnforcer;
use ContextDiscovery\Assembly\BundleAssembler;
use ContextDiscovery\Assembly\ItemPriority;
use ContextDiscovery\Assembly\TokenEstimate;
use ContextDiscovery\Discovery\Extraction\AssertionExtractor;
use ContextDiscovery\Discovery\Extraction\ChangedSignatureAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\NamedReferenceAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\OwnFileAssertionExtractor;
use ContextDiscovery\Discovery\Extraction\UnverifiablePremiseAssertionExtractor;
use ContextDiscovery\Discovery\Flagging\AssumptionWriter;
use ContextDiscovery\Discovery\Framework\LaravelFrameworkKnowledge;
use ContextDiscovery\Discovery\Lever\LeverPolicy;
use ContextDiscovery\Discovery\Parsing\UnifiedDiffParser;
use ContextDiscovery\Discovery\Resolution\CallerResolver;
use ContextDiscovery\Discovery\Resolution\NamedReferenceResolver;
use ContextDiscovery\Discovery\Resolution\OwnFileResolver;
use ContextDiscovery\Pipeline\DiscoverContext;
use ContextDiscovery\Ports\BundleWriter;
use RuntimeException;

/**
 * The single place a concrete adapter is named.
 *
 * Plain constructor wiring: no container, no service locator, no auto-discovery, no runtime
 * reflection. What runs is what is written here, so adding an adapter changes exactly one file
 * and the whole object graph is readable in one screen (ADR-A002).
 *
 * The extractors and resolvers are a closed set assembled here in a fixed order — there is no
 * registry and no configuration seam for an unproven move to enter through (ADR-A003).
 */
final class Wiring
{
    /**
     * @param resource $stdout
     * @param resource $stderr
     *
     * @throws RuntimeException when the repository root is not readable; the caller exits `2`.
     */
    public function discoverCommand(Options $options, mixed $stdout, mixed $stderr): DiscoverCommand
    {
        $source = new LocalSourceRepository($options->repositoryRoot);
        $slicer = new TokenizerMemberSlicer();
        $locator = new ComposerPsr4ClassLocator($source);

        $context = new DiscoverContext(
            new UnifiedDiffParser(),
            $source,
            new AssertionExtractor([
                new OwnFileAssertionExtractor($slicer),
                new NamedReferenceAssertionExtractor($slicer),
                new ChangedSignatureAssertionExtractor(),
                new UnverifiablePremiseAssertionExtractor($slicer),
                // Four extractors, one per assertion kind the diff can raise. The set is closed:
                // adding one needs an experiment, a requirement entry, an ADR and a line here —
                // in that order (ADR-A003). There is no registry and no discovery.
            ]),
            new LeverPolicy(),
            $locator,
            new OwnFileResolver($source, $slicer),
            // The one place a framework is chosen. Every other class in Discovery names the
            // `FrameworkKnowledge` interface and no framework symbol; another framework changes
            // this line and nothing else.
            new NamedReferenceResolver($locator, $source, $slicer, new LaravelFrameworkKnowledge()),
            new CallerResolver(
                new ScopedGrepCallSiteSearch($source),
                $source,
                $options->callerScope,
                $options->maxCallSites,
            ),
            new AssumptionWriter(),
            new BundleAssembler(new TokenEstimate()),
            new BudgetEnforcer(new ItemPriority()),
        );

        return new DiscoverCommand($context, $this->writerFor($options->format), $stdout, $stderr);
    }

    private function writerFor(string $format): BundleWriter
    {
        return $format === Options::FORMAT_MARKDOWN
            ? new MarkdownBundleWriter()
            : new JsonBundleWriter();
    }
}
