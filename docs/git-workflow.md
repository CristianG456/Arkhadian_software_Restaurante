# Git workflow (GitFlow)

Two permanent branches:

- **`main`** — stable/production only. Never developed on directly, never
  committed to directly. Only receives merges from `release/*` and
  `hotfix/*`.
- **`develop`** — integration branch. Finished features land here.

## Starting a task

```powershell
git switch develop
git fetch origin --prune
git pull --ff-only origin develop
git switch -c feature/nombre-de-la-tarea
```

Never branch off a `develop` you haven't just fetched — you may be
missing migrations, seeders, or code the other developer already pushed.

## While working

```powershell
git status
git add <archivos específicos>
git commit -m "tipo: descripción clara en español"
```

Small, coherent commits. No `cambios`, `fix`, `final2`. No secrets, no
generated builds (`dist/`, `target/`, `.exe`, `.msi`, `vendor/`,
`node_modules/`).

## Before pushing — every time, no exceptions

```powershell
git status
git fetch origin --prune
```

Check whether `origin/develop` (or whatever you branched from) moved
while you were working. If it did:

- **Unpublished feature branch (only you have it):** rebasing onto the
  updated base is fine.
- **Already-pushed/shared branch:** don't rewrite it — merge
  `origin/develop` into it instead.
- **Never** `git push --force` without explicit, one-off authorization.
  It is not a normal step in this workflow.

If migrations landed on `develop` since you branched, read them — see
`docs/database.md` "Two developers, two new migrations at once".
Re-run tests/builds after integrating anything.

```powershell
git push -u origin feature/nombre-de-la-tarea
```

## Integration

```
feature/*  →  Pull Request  →  develop
```

No direct pushes of features to `develop`. Open the PR (`gh pr create`
if the CLI is authenticated) and let it get reviewed/merged — don't
merge your own PR unless explicitly told to.

## Release

```
develop  →  release/x.y.z  →  main  +  develop
```

`release/*` branches off `develop` once it has enough finished work for
a version. Only fixes, version bump, docs, and packaging happen on a
release branch — no new features. When ready, merge into **both** `main`
and `develop` so the release's fixes aren't lost on `develop`.

## Hotfix

```
main  →  hotfix/x.y.z  →  main  +  develop
```

For urgent production fixes only, branched from `main`. Merges back into
both `main` and `develop` when done. Not a substitute for normal feature
development.

## Handling conflicts

Read every conflicted hunk — never resolve with a blanket "ours" or
"theirs" for a whole file. Keep both developers' valid work; if two
changes are genuinely incompatible, reconcile them deliberately rather
than picking one side. If files you don't recognize show up after
syncing, check `git log`/`git diff` on them before assuming they're
disposable — don't delete another developer's work.
