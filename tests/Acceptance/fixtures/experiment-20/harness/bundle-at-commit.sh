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
#
# The corpus repository of M18–M22 and M26 is abouelsid-backend: 47 commits, no merges, root
# `4eb427c`, measured at HEAD `450d91f`.
#   remote    https://github.com/AliAlqrinawi/abouelsid-backend.git
#   measured  /Users/alialqrinqwi/Projects laravel/nzrh/abouelsid/abouelsid-backend
# The local path is one machine's and is recorded because no milestone wrote it down: it had to be
# recovered by searching the disk for commit `ec92403`. It needs `composer install` run in it —
# vendor/ is not committed, and the copy below has nothing to copy without it.

set -e
REPO="$1"; SHA="$2"; CLI="$3"; OUT="$4"; BUDGET="${5:-8000}"
WT="$(mktemp -d)/tree"

git -C "$REPO" worktree add --detach --quiet "$WT" "$SHA"
trap 'git -C "$REPO" worktree remove --force "$WT" >/dev/null 2>&1 || true' EXIT

# vendor/ is not committed, so the installed dependencies live only in the main checkout.
# Composer's generated map is location metadata (ADR-A014) and the tool never executes it, so the
# historical tree borrows the one on disk. Recorded as a caveat rather than hidden: a commit whose
# dependency set differed from today's would resolve framework classes against today's vendor.
#
# It is a COPY, never a symlink. `LocalSourceRepository` resolves every read through realpath() and
# refuses one that lands outside --repo, so a symlinked vendor/ is refused whole: the tool behaves
# exactly as if no dependency were installed, and says so nowhere. This line was `ln -s` until
# 2026-09-21, and a run through it is byte-identical — bundle and stderr — to a run with no vendor/
# at all. `worktree remove --force` on exit takes the copy with it.
if [ -d "$REPO/vendor" ] && [ ! -e "$WT/vendor" ]; then cp -R "$REPO/vendor" "$WT/vendor"; fi

git -C "$REPO" diff "$SHA^" "$SHA" > "$OUT.diff"

php "$CLI/bin/context-discover" --diff="$OUT.diff" --repo="$WT" --budget="$BUDGET" --format=json     > "$OUT.bundle.json" 2> "$OUT.stderr"
php "$CLI/bin/context-discover" --diff="$OUT.diff" --repo="$WT" --budget="$BUDGET" --format=markdown > "$OUT.bundle.md"   2> /dev/null
