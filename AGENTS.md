# AGENTS.md

## Repository identity

This repository is a Moodle plugin repository.

- Plugin type: Moodle quiz access rule subplugin.
- Plugin component name: `quizaccess_intentionalskip`.
- Expected plugin path: `mod/quiz/accessrule/intentionalskip`.
- Main purpose: add optional per-question intentional skip tracking to Moodle quiz attempts, including quiz-level settings, AJAX-backed skip state saving/loading, reporting, audit history, and related privacy/event support.
- Important warning: Moodle rules, APIs, data ownership boundaries, and plugin architecture must be respected at all times. Moodle remains authoritative for quiz attempts, answers, timing, submission, grading, and review behavior.

## Required reading order

Read this file first. Before making any code changes, future AI/Codex agents must then read these repository guide files in this exact order:

1. `FILE_INDEX.md`
2. `BUG_MAP.md`
3. `CODE_OBJECT_INDEX.md`
4. `FLOW_MAP.md`
5. `PATCH_RULES.md`
6. `CHECKS.md`

Roles of these files:

- `FILE_INDEX.md` = what each file does.
- `BUG_MAP.md` = where to start for each bug type.
- `CODE_OBJECT_INDEX.md` = classes, methods, services, DB tables, events, settings.
- `FLOW_MAP.md` = cross-file execution flows.
- `PATCH_RULES.md` = what changes are allowed or prohibited.
- `CHECKS.md` = how to verify changes.

Use those files as the source of truth for repository navigation, bug routing, object lookup, safe patch scope, and verification expectations.

## Default behavior

Do not scan or rewrite the whole repository by default.

For normal bug fixes:

1. Read the required guide files.
2. Identify the smallest relevant file set using `BUG_MAP.md` and `FLOW_MAP.md`.
3. Inspect only those files first.
4. Make the smallest safe patch.
5. Run relevant checks from `CHECKS.md`.
6. Report changed files, verification, risks, and not-tested items.

Default patching posture:

- Start narrow.
- Expand file inspection only when the first file set does not explain the bug.
- Preserve existing behavior outside the reported issue.
- Treat install/upgrade files, DB schema, permissions, external services, and attempt-state logic as high risk.

## Absolute rules

- Do not modify Moodle core.
- Do not rename the plugin component.
- Do not rename database tables.
- Do not change database schema without explicit user approval.
- Do not add `db/upgrade.php` unless schema/version changes require it.
- Do not change `version.php` unless preparing a release/upgrade or explicitly asked.
- Do not refactor unrelated code.
- Do not make cosmetic changes during bug fixes.
- Do not remove existing settings or capabilities without explicit approval.
- Do not introduce external CDN dependencies.
- Do not edit minified AMD JavaScript alone when source JS exists.
- Do not claim tests/checks were run unless actually run.

## Moodle-specific rules

- Respect Moodle coding style.
- Use Moodle APIs where appropriate.
- Use `require_login()` for protected pages.
- Check capabilities before exposing reports or privileged data.
- Validate quiz/course/context/attempt ownership.
- Do not trust client-side user/attempt/slot data.
- Use language strings for user-facing text.
- Keep privacy API accurate if stored user data changes.
- Keep event/logging behavior meaningful and safe.
- Preserve Moodle as the authority for attempt state, answer state, submission, review, and grading.

## JavaScript/AMD rules

- Prefer editing `amd/src/*.js`.
- Update or rebuild matching `amd/build/*.min.js` when source changes require it.
- Keep selectors scoped to quiz attempt UI.
- Avoid duplicate event handlers.
- Preserve existing AJAX service names unless a change is required and justified.
- Keep graceful degradation intact unless the bug explicitly requires a behavior change.

## Database rules

- Treat `db/install.xml` as high risk.
- Schema changes require explicit approval, a version bump, and `db/upgrade.php`.
- Preserve existing data.
- Review the privacy provider if user-related stored data changes.
- Do not treat `db/install.xml` alone as a safe upgrade path for already-installed sites.

## Bug fixing workflow

1. Understand the reported bug.
2. Find the matching category in `BUG_MAP.md`.
3. Read the relevant flow in `FLOW_MAP.md`.
4. Use `CODE_OBJECT_INDEX.md` to identify actual classes, methods, services, and tables.
5. Inspect the smallest relevant source files.
6. Patch only the cause.
7. Avoid unrelated cleanup.
8. Run `CHECKS.md` checks.
9. Provide the final patch report.

## Documentation update workflow

If a code change changes behavior, future AI agents should consider whether these docs need updating:

- `FILE_INDEX.md`
- `BUG_MAP.md`
- `CODE_OBJECT_INDEX.md`
- `FLOW_MAP.md`
- `PATCH_RULES.md`
- `CHECKS.md`

Documentation should not be changed unnecessarily. Update docs only when behavior, routing guidance, object mapping, or verification expectations have truly changed.

## Required final response format

Future AI/Codex responses after a patch must include:

## Patch summary
- Files changed:
- Reason:
- Behavior changed:

## Verification
- Commands run:
- Manual tests completed:
- Not tested:
- Environment limitations:

## Risk notes
- Risk level:
- Possible side effects:
- Rollback suggestion:

## If uncertain

When uncertain, stop after identifying the smallest likely file set and explain the uncertainty. Do not widen the patch, refactor the plugin, or modify high-risk files without explicit user approval.
