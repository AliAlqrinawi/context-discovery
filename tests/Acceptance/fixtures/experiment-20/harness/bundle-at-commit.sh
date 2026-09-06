#!/bin/sh
# M20 · generate a bundle against the commit's OWN tree, not the repository's HEAD.
#
# The M19 harness defect: run-corpus.php passed --repo=<the working tree at HEAD> for every commit
# while --diff was the historical diff, so source resolution used whatever the files look like today.
# For commits whose files have since moved, the tool read the wrong tree or none at all — 6 of M18's
# 46 commits carry an `unreadable path` diagnostic because of it, and it compromised M19's K1.
#
# The correction: check the commit out into a detached worktree and point --repo at that.
# Production behaviour is untouched; only where the harness points the tool changes.
#
#   bundle-at-commit.sh <corpus-repo> <sha> <cli-root> <out-prefix> [budget]

set -e
REPO="$1"; SHA="$2"; CLI="$3"; OUT="$4"; BUDGET="${5:-8000}"
WT="$(mktemp -d)/tree"

git -C "$REPO" worktree add --detach --quiet "$WT" "$SHA"
trap 'git -C "$REPO" worktree remove --force "$WT" >/dev/null 2>&1 || true' EXIT

# vendor/ is not committed, so the installed dependencies live only in the main checkout.
# Composer's generated map is location metadata (ADR-A014) and the tool never executes it, so the
# historical tree borrows the one on disk. Recorded as a caveat rather than hidden: a commit whose
# dependency set differed from today's would resolve framework classes against today's vendor.
if [ -d "$REPO/vendor" ] && [ ! -e "$WT/vendor" ]; then ln -s "$REPO/vendor" "$WT/vendor"; fi

git -C "$REPO" diff "$SHA^" "$SHA" > "$OUT.diff"

php "$CLI/bin/context-discover" --diff="$OUT.diff" --repo="$WT" --budget="$BUDGET" --format=json     > "$OUT.bundle.json" 2> "$OUT.stderr"
php "$CLI/bin/context-discover" --diff="$OUT.diff" --repo="$WT" --budget="$BUDGET" --format=markdown > "$OUT.bundle.md"   2> /dev/null
