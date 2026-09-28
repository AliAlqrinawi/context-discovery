# M36 · Two end-to-end trials: where the bundle helped a real task, and where it added nothing

> **Classification: two hand-run trials of the bundle on a real reviewing task, reported by the
> author who ran them.** Not a pre-registered measurement; not scored by the instrument; not a
> reviewer experiment in M17–M35's sense. It is the first evidence in the project that the
> bundle helps a real task, and the first that shows where it does not - and both come from the
> same two trials. **Trial 1** (`ec92403`, six structural rules): the diff alone was sufficient;
> the bundle found 2 violations, the diff alone 4 plus one in spirit, with exact line numbers,
> and the bundle's `surrounding-transaction` flag led to a wrong conclusion that reading the
> code corrected. **Trial 2** (the M26 diff, the same rules plus a caller rule): the context
> **was** needed - `GetBranchesAction` is not in the diff and the bundle supplied it,
> `BranchResource::toArray` grounded a third violation, and `call_sites_truncated` made the
> reviewer say the caller list may be incomplete rather than assume it complete.
>
> **The finding:** the bundle's value is scoped to rules about **contracts and callers**, where
> the evidence is outside the diff by definition. For **structural** rules it adds nothing,
> because the violation is in the added line itself. That is what the engine was built for.

## 1 · What this is, and is not

M35 ended with an instrument that showed no resolution: a language-model reviewer that returned
one fixed answer per diff whatever accompanied it. These two trials are a different kind of
evidence - not a measurement of a proxy with a protocol, but the author using the bundle to do a
task and reporting what it did for them. They were run by the author outside the recorded
harness; this write-up records the author's account as given, and does not have transcripts,
cell files or a key to cite. Where a detail was not reported, it is marked so rather than filled
in.

The task in both trials: **check a diff against a written set of rules** and name every
violation with its location.

## 2 · Trial 1 · `ec92403`, six structural rules

**Rules** (the author's, written for the task): repository pattern; controller responsibility;
actions; FormRequest / DTO; `ApiResponse`; six in all, every one a rule about **how the changed
code is structured** - what a controller may do, where a query may live, how a request is
validated.

**Material:** the diff of `ec92403` (D1, *menu PDF upload with permanent URL and QR code with
logo*), once with a bundle and once without. Which of the recorded `ec92403` runs supplied the
bundle was not reported.

**Result, as reported:**

| | violations found |
|---|---|
| diff + bundle | **2** |
| diff alone | **4**, plus one "in spirit", each with exact line numbers |

**Verdict:** the diff alone was sufficient. Every rule asked about a property of the added
lines, and the added lines were in front of the reviewer either way. The bundle did not add the
evidence the rules needed, because the rules needed none from outside the diff.

**One harm, as reported:** the bundle's `surrounding-transaction` flag - the premise M20's D1 key
credits as naming defect (a)'s mechanism almost exactly - **led to a wrong conclusion**, which
reading the code then corrected. The flag is a true sentence about an unverified premise
(ADR-A009); read as a finding rather than as a question, it pointed the wrong way on this task.
Recorded as it happened, without a mechanism claimed beyond that.

## 3 · Trial 2 · the M26 diff, the same rules plus a caller rule

**Rules:** the six above, plus **rule 7 - a change to a public function's behaviour or return
value must be checked against its callers.** The one rule of the seven whose evidence is, by
definition, not in the diff.

**Material:** the M26 diff (`tests/Acceptance/fixtures/golden/m26.diff`: `BranchRepository::
getAll` changing `get()` to `first()`, plus `BranchController` and `Branch` - three files, three
hunks) with its golden bundle (`m26-bundle.v2.json`, 3 assertions, 23 items, 624 tokens). No
backend run carries this diff; it is the engine's golden fixture.

**Result, as reported:**

- **The context was needed.** The bundle supplied `GetBranchesAction` - the caller of
  `getAll()`, not in the diff - as a `changed_return_contract` item, and the reviewer used it.
- **The reviewer separated the real caller from the noise unaided.** The caller search returns
  every `getAll(` under `app/`, and thirteen of them are same-name methods on other
  repositories. The reviewer set them aside as not callers of *this* `getAll` and kept the one
  that was.
- **`BranchResource::toArray` grounded a third violation.** A fetched surface the reviewer
  would not have had from the diff.
- **`call_sites_truncated` changed what the reviewer said.** With the `--max-call-sites` bound
  hit and the diagnostic present, the reviewer stated that the caller list *may be incomplete*
  rather than assuming it complete. The flag did its P10 job: an unverified premise stated, not
  silence.

**On the noise.** At what the author reports as **5% caller precision** - one true caller among
the returned call sites - the noise **did not mislead the reviewer**: it was named as noise and
set aside. Recorded as the author's figure and the author's observation; this write-up did not
recount the call sites.

## 4 · The finding

The two trials differ in one thing that matters: whether the rules asked about the added lines
or about something the added lines *affect*.

| Rule kind | Where the evidence is | What the bundle did |
|---|---|---|
| **Structural** (rules 1–6: pattern, responsibility, validation, response helpers) | **in the added line itself** | nothing; the diff already carried it, and one flag misled |
| **Contract / caller** (rule 7: a changed return or behaviour against its callers) | **outside the diff, by definition** | supplied the caller, the resource surface, and the statement that the list may be incomplete |

**The bundle's value is scoped to rules about contracts and callers.** That is not a surprise
about the engine; it is what the engine was built to do - `ChangedSignature` and
`ChangedReturnContract` (ADR-A023) with `CallerResolver` (ADR-A006) are the moves whose evidence
is never in the diff, and `NamedReference`'s surfaces are the collaborators the diff names but
does not show. A structural rule asks a question the diff answers; the bundle can only repeat
the diff or distract from it, and in Trial 1 it did the second once.

What the two trials add to the record is not the scoping itself, which the architecture
states, but **the first instance of a real task landing on each side of it** - and the first
instance of a reviewer, given noise at 5% precision and a truncation diagnostic, doing the right
thing with both.

## 5 · What this does not show

- **Two trials.** One on each side of the line; nothing about the line's position between them.
- **One reviewer**, who is also the author of the rules, the author of the context keys, and the
  person running the programme. Nothing here is blind, and nothing was pre-registered.
- **One codebase**, the same one every measurement since M17 has used.
- **Rules written by the author** for the trial, not drawn from a project's existing standards.
- No transcript, cell file or key is committed for either trial; the numbers above are the
  author's report of what they found, recorded as reported. A later reader has this write-up's
  word and nothing to recount from.
- Nothing about option B: neither trial's rules turned on an inherited member, and the
  `ec92403` bundle used was not identified by engine, so whether S1 citations were present is not
  recorded.

M35's conclusion stands beside this: the instrument that measured nothing was a language-model
proxy at default sampling; the reviewer here was a person doing a task. The two are different
kinds of evidence, and neither one is the other.

## Related

- [M35](M35-the-citation-in-front-of-a-reviewer.md) - the pre-registered reviewer measurement
  and its instrument's zero variance.
- [M26](M26-changed-return-contract.md), `tests/Acceptance/fixtures/golden/` - the M26 diff
  and bundle Trial 2 used.
- [M20](M20-defect-corpus.md) D1 - `ec92403`'s defect key, and the `surrounding-transaction`
  flag's standing there.
- ADR-A006 (grep, not graph; the caller bound), ADR-A009 (premises are questions, not findings),
  ADR-A023 (`ChangedReturnContract`).
