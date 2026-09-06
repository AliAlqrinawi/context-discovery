# Experiment 09 · OQ6 — can the facts in hand classify an unresolved reference?

Twelve rows, each differing from its neighbours in exactly one fact that is available at the moment
resolution gives up. Key written before the run.

The three rows that decide it — `Package::create`, `Package::activatte`,
`Package::totallyUnknownThing` — are **identical in all seven available facts** and have three
different correct answers. That is the proof, and it is why nothing changed.

Contrast `Log::info`: `Log.php` declares `class Log extends Facade` *and* carries
`@method static void info(...)`. A facade publishes a member-level fact; Eloquent does not.

`diff.patch` adds one method to an existing file, so D1/D2 stay out of the measurement. The two
framework files under `vendor/` are copied from the M1 fixture, never fabricated; `Illuminate\` is
declared in `composer.json` (the variant-B device) so the facade control works without a full install.

Result: **12 of 12 rows correct** — the current behaviour already matches the key everywhere, with
`Package::create` taking the safe FLAG.

See [`docs/research/M9-oq6-classification.md`](../../../../docs/research/M9-oq6-classification.md)
and [ADR-A016](../../../../../context-discovery-architecture/architecture/decisions/ADR-A016-oq6-the-available-facts-cannot-classify.md).
