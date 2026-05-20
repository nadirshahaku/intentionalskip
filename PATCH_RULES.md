# PATCH_RULES.md

## Purpose

This file controls how AI/Codex should make code changes in this repository.

It exists to keep bug-fix patches small, safe, Moodle-compliant, and easy to review. Future AI/Codex sessions must use `FILE_INDEX.md`, `BUG_MAP.md`, `CODE_OBJECT_INDEX.md`, and `FLOW_MAP.md` as the repository source of truth for structure, code objects, runtime flows, and bug routing before deciding what to inspect or change.

## Golden rule

Future AI agents must make the smallest safe patch that fixes the reported issue, and must not refactor unrelated code.

## Required workflow for every bug fix

1. Read `AGENTS.md` if it exists.
2. Read `FILE_INDEX.md`.
3. Read `BUG_MAP.md`.
4. Read `CODE_OBJECT_INDEX.md`.
5. Read `FLOW_MAP.md`.
6. Identify the smallest relevant file set.
7. Inspect only those files first.
8. Make the smallest safe code change.
9. Run syntax/check commands where available.
10. Report changed files, reason, test steps, and risks.

## What AI may do

- Fix incorrect logic in the directly affected flow.
- Add missing validation.
- Fix wrong parameter handling.
- Fix capability or permission checks.
- Fix Moodle API misuse.
- Fix report display or count logic.
- Fix JavaScript event handling.
- Update AMD source and matching build output when needed.
- Add targeted comments only where they clarify non-obvious logic.
- Update documentation only when the code behavior changes.

## What AI must not do without explicit permission

- Do not rewrite the plugin architecture.
- Do not rename the plugin component.
- Do not rename database tables.
- Do not change database schema unless the bug requires it and the user explicitly approves.
- Do not add `db/upgrade.php` unless schema/version changes require it.
- Do not change `version.php` unless preparing an upgrade/release.
- Do not refactor unrelated files.
- Do not change Moodle core files.
- Do not remove existing settings.
- Do not remove capabilities.
- Do not replace Moodle APIs with custom alternatives.
- Do not edit minified AMD build output alone if the source JS exists.
- Do not make cosmetic changes during a bug fix.
- Do not introduce external CDN dependencies.
- Do not add new third-party libraries unless explicitly approved.

## Moodle-specific patch rules

- Use `require_login()` on direct page entry points where Moodle expects authenticated access.
- Keep capability checks explicit and as narrow as possible for the affected action.
- Validate the correct module or quiz context before reading or writing plugin data.
- Use `sesskey()` and external API parameter validation where appropriate for state-changing requests.
- Follow Moodle coding style and existing repository conventions instead of introducing a new style.
- Preserve Moodle privacy API coverage when user-related data is stored, deleted, or described.
- Preserve Moodle event API behavior for auditable skip-state transitions.
- Respect the AMD JavaScript source/build relationship: source first, build artifact second.
- Use language strings instead of hardcoded UI text.
- Avoid direct output without Moodle output APIs where appropriate.
- Use safe database access through Moodle DB APIs.
- Respect quiz attempt, user, slot, and context boundaries at all times.
- Preserve Moodle as the authority for attempt state, answer state, submission, review, and grading.

## JavaScript/AMD patch rules

- Prefer editing `amd/src/*.js`.
- Rebuild/update `amd/build/*.min.js` only from matching source changes.
- Do not manually edit build output unless clearly documented as emergency.
- Keep selectors scoped to quiz attempt content where possible.
- Avoid global event handlers unless necessary.
- Preserve existing AJAX service names unless changing them is required.
- Keep behavior aligned with documented graceful degradation; failed tracking must not block normal quiz progress unless the bug explicitly requires otherwise.

## Database patch rules

- Treat `db/install.xml` as high risk.
- Do not change existing table/field names casually.
- If schema changes are required, explain need first.
- Schema changes require version bump and `db/upgrade.php`.
- Preserve existing data unless explicitly instructed otherwise.
- Avoid destructive migrations.
- Remember that this repository currently has no `db/upgrade.php`; do not treat `db/install.xml` alone as a safe upgrade path for installed sites.

## Security patch rules

- Validate user identity, attempt ownership, quiz/course context.
- Do not trust client-side slot/question/user data.
- Check capabilities before showing reports or privileged data.
- Avoid exposing other users' attempt data.
- Use Moodle parameter validation for external services.
- Do not log sensitive data unnecessarily.
- Tighten validation before loosening permissions when fixing access bugs.

## Reporting/counting patch rules

- Report logic must distinguish Moodle question state from intentional skip state.
- Do not treat all unanswered questions as intentionally skipped.
- Preview attempt inclusion/exclusion must follow the current `includepreviews` request-parameter behavior in `report.php`; there is no built-in report toggle UI documented in the current code.
- Count logic changes must include manual test steps.
- Keep the distinction between current state and history data clear.
- Do not patch labels before confirming whether the underlying classification or storage logic is actually wrong.

## Privacy patch rules

- If storing user-related data, privacy provider must remain accurate.
- If new personal data is added, update privacy docs/provider as needed.
- Do not remove privacy implementation.
- Be explicit when a privacy limitation is an existing implementation gap rather than a regression.

## Documentation update rules

- Documentation files may be updated after behavior changes.
- Do not let docs drift away from code.
- If a code change affects `FILE_INDEX.md`, `BUG_MAP.md`, `CODE_OBJECT_INDEX.md`, or `FLOW_MAP.md`, mention this in the final response.

## Patch report format

Future AI/Codex responses must end with:

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

## Risk levels

- Low: language text, comments, docs, small display-only fix.
- Medium: report logic, JavaScript UI behavior, capability checks.
- High: database writes, external services, quiz attempt state, `install.xml`, versioning.
- Critical: schema changes, data migration, permission model redesign.

## Final instruction to future AI agents

When in doubt, stop after identifying the smallest likely file set and explain the uncertainty. Do not widen the patch or refactor the plugin without explicit user approval.
