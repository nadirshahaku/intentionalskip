# CHECKS.md

## Purpose

This file defines the verification checks future AI/Codex sessions must run after making patches to this Moodle plugin. It covers syntax checks, Moodle-aware checks, JavaScript/AMD checks, and manual quiz/report/privacy/security test steps so patches are not reported as complete without verification.

## General rule

Every future code patch must include verification. If a check cannot be run in the current environment, the AI/Codex response must say so honestly under `Not tested`.

## Minimum checks after every patch

- Run `git diff --check`.
- Run `git status --short`.
- Run `php -l` for every modified PHP file.
- Confirm no unrelated files were modified.
- Confirm documentation was updated only if behavior changed.

## PHP syntax checks

For each changed PHP file, run:

```bash
php -l path/to/file.php
```

If multiple PHP files changed, run `php -l` for each one. Do not assume one syntax check covers the whole plugin.

## Moodle coding style checks

If the relevant tools are available in the environment, run them. Do not assume they are installed.

- Moodle Code Checker / `phpcs` with Moodle standard, if available.
- PHP Mess Detector / `phpmd`, if available.
- Moodle plugin validation tools, if available.

If these tools are not installed, the final patch report must say:

`Not run: tool not installed or not available in this environment.`

## JavaScript / AMD checks

- If `amd/src/*.js` changes, verify matching `amd/build/*.min.js` is updated or explicitly explain why not.
- Check browser console on the quiz attempt page.
- Check the browser Network tab for AJAX failures.
- Confirm event handlers are not duplicated after page navigation or refresh.
- Confirm selectors are scoped and do not affect unrelated Moodle pages.

Possible command if this repository or surrounding Moodle setup has the tooling available:

- `grunt amd`
- `npm install` only if appropriate and explicitly approved

Do not assume Node, npm, or grunt are installed.

## Database/schema checks

- If `db/install.xml` changes, this is high risk.
- Schema changes require `version.php` bump and `db/upgrade.php`.
- This repository currently has no `db/upgrade.php`; if schema changes are introduced, the upgrade path must be handled explicitly.
- Confirm the install and upgrade path manually.
- Confirm existing data is preserved.
- Confirm new or changed fields are reflected in the privacy provider if user data is affected.

## External service/AJAX checks

Manual checks:

- Confirm the service is registered in `db/services.php`.
- Confirm request parameters match the PHP external function definitions.
- Confirm the return structure matches JavaScript expectations.
- Confirm AJAX calls work for a real enrolled user.
- Confirm AJAX calls fail safely for unauthorized users.
- Confirm a user cannot save state for another user's attempt.
- Confirm slot, attempt, and context validation works.

For this plugin, pay particular attention to the service contracts between `db/services.php`, `classes/external/get_state.php`, `classes/external/save_slot.php`, `classes/external/mark_slots.php`, and `amd/src/intentionalskip.js`.

## Capability/security checks

Manual checks:

- Confirm the report page requires login.
- Confirm the report page checks the correct capability.
- Confirm a student cannot view other users' intentional skip reports.
- Confirm a teacher or manager with the correct role can view reports if intended.
- Confirm AJAX write actions validate user, session, and attempt ownership.
- Confirm preview attempts are included or excluded according to current `includepreviews` request-parameter logic; do not assume a built-in report toggle UI exists.

## Quiz attempt UI checks

Manual test steps:

1. Enable the plugin setting in a quiz.
2. Start a quiz attempt as a student or test user.
3. Confirm the skip control appears only where intended.
4. Mark an unanswered question as intentionally skipped.
5. Refresh the page.
6. Confirm the state is restored.
7. Answer the question.
8. Confirm the skip state is cleared or handled according to intended logic.
9. Move between quiz pages.
10. Confirm the skip state remains correct.

Also verify that normal Moodle quiz progress still works if skip tracking fails, because this plugin is documented to degrade gracefully rather than block quiz progress.

## Report checks

Manual test steps:

1. Open the intentional skip report as an authorized user.
2. Confirm a skipped question appears.
3. Confirm an answered question is not wrongly counted as intentionally skipped.
4. Confirm an unanswered but not marked question is not wrongly counted as intentionally skipped.
5. Confirm preview attempt inclusion or exclusion follows the current `includepreviews` request-parameter behavior.
6. Confirm user, attempt, question, slot, Moodle state, skip state, source, and saved time display correctly where applicable.

If a counting bug is patched, compare the report against at least one known sample attempt with expected slot-by-slot outcomes.

## Privacy checks

- If stored personal data or user attempt data changes, check `classes/privacy/provider.php`.
- Confirm the provider declares the relevant table and user data.
- Confirm export and delete behavior remains accurate.
- If the privacy provider is incomplete or placeholder, report this clearly.

This repository currently has an incomplete privacy export implementation, so future patch reports must describe that honestly if privacy behavior is touched or reviewed.

## Event/logging checks

- If skip state events are changed, confirm events are triggered only at the correct moments.
- Confirm event data does not expose unnecessary sensitive information.
- Confirm logs are meaningful for audit and debugging.

For this plugin, compare event behavior against `classes/local/state_manager.php` and `classes/event/*` so manual, next-page, final-submit, and clear-by-answer flows remain distinct.

## Language string checks

- New UI text must use `lang/en/quizaccess_intentionalskip.php`.
- Do not introduce new hardcoded user-facing strings unless unavoidable.
- Confirm string identifiers match code references.

If JavaScript strings change, also confirm the PHP bootstrap still registers the needed keys for JS.

## Documentation consistency checks

- If behavior changes, consider whether `FILE_INDEX.md`, `BUG_MAP.md`, `CODE_OBJECT_INDEX.md`, `FLOW_MAP.md`, or `PATCH_RULES.md` need updates.
- Do not update docs unnecessarily.
- If docs are outdated after a patch, mention this in the final response.

## Suggested final report format

Future AI/Codex responses should include:

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

## Bug-specific check matrix

| Change type | Required checks |
|---|---|
| PHP-only logic fix | `php -l`, targeted manual test |
| Report/count fix | `php -l`, report manual checks, sample attempt comparison |
| JavaScript UI fix | JS source/build check, browser console, quiz attempt UI checks |
| AJAX/external service fix | `php -l`, Network tab, authorized/unauthorized user test |
| Capability fix | role-based manual test |
| Database schema fix | install/upgrade test, version bump, `db/upgrade.php`, privacy review |
| Privacy fix | privacy export/delete review |
| Language string fix | UI display check |
| Documentation-only change | spelling/link check, no source modification |
