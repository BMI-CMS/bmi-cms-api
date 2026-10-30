---
name: find-similar-implementation
description: Use when a new feature or a problem in this project has no clear approach, uses an unfamiliar package or API, or a fix has already failed twice. Stop guessing (no brute force), ask the user for permission, then look for similar implementations on GitHub and adapt them.
---

# Find a similar implementation instead of brute force

When a new feature or problem has no clear solution, do not brute force it. Brute force includes:

- trying edit after edit to see what sticks;
- changing config or code at random;
- running many variations of a command or test hoping one passes.

Look for how others have solved it instead, but **ask the user first**.

## When this applies

- You don't know how to implement the feature or fix the problem.
- The feature uses a package, API or Laravel feature you are not sure about.
- Two attempts at a fix have already failed.

## Steps

1. **Stop** before trying another guess.
2. **Check this project first.** Look for an existing pattern in the codebase and in `vendor/` (for example `vendor/laravel/framework`). This needs no permission. If it answers the question, use it and skip the rest.
3. **Ask the user first** with AskUserQuestion, before any GitHub search. Say:
   - what is unclear, in one or two sentences;
   - what you would search for (the exact search terms);
   - the options: search GitHub, the user gives direction, or stop.
   Do not search until the user agrees.
4. **Search GitHub** (only after approval):
   - WebSearch with `site:github.com`, for example `site:github.com laravel 12 sanctum sliding token expiration`.
   - WebFetch to read a file, using its `raw.githubusercontent.com` URL.
   - The `gh` CLI is not installed on this machine; `gh search code` can be used only if it is installed later.
5. **Choose good references:**
   - prefer the official Laravel repositories and docs, then well-maintained, popular repositories;
   - check that it matches this project's versions (Laravel 12, PHP 8.2+, MySQL / PostgreSQL);
   - compare at least two implementations when you can.
6. **Show the user what you found** before implementing: the links, the approach, and how it would fit this project. Wait for approval.
7. **Implement** using the project's own conventions (`CLAUDE.md`, and the `laravel-artisan-make` skill for new files).

## Rules

- Search terms must not contain secrets, credentials, customer data, internal hostnames or business-confidential names. Describe the technical problem in general terms.
- GitHub code is a reference, not something to copy as is. Rewrite it to fit the project, and check its license before reusing any significant part.
- Do not run scripts or install packages from a repository you found. A new Composer package needs the user's approval.
- Code you find does not establish a business rule. Business rules come only from the user.
- In the end-of-task summary, list the GitHub links you used.
