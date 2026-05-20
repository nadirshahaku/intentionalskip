# BUG_MAP.md

Purpose: help future AI/Codex sessions fix bugs surgically, starting from `FILE_INDEX.md` and only then touching the smallest relevant source-file set.

Routing assumptions:
- This map is based primarily on `FILE_INDEX.md`.
- Prefer the smallest "Start here" set first.
- Only expand into "Then inspect" if the first set does not explain the bug.
- Avoid reading unrelated files just because they are nearby.
- This plugin has no `db/upgrade.php` at present; absence of that file is itself relevant for upgrade/schema bugs.

## Plugin not installing

**Typical symptoms:**
- Moodle does not detect the plugin correctly.
- Installation aborts during discovery or XMLDB install.
- Component-name or dependency errors appear during install.

**Start here:**
- `version.php`
- `db/install.xml`

**Then inspect only if needed:**
- `db/access.php`
- `db/services.php`
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `report.php`
- `classes/local/state_manager.php`

**Likely root causes:**
- Wrong `$plugin->component`
- Bad `$plugin->requires` or malformed `$plugin->version`
- Invalid XMLDB syntax in `db/install.xml`
- Schema/table names not matching plugin component expectations

**Safe patch rules:**
- Do not change schema names casually; install schema is a cross-file contract.
- Do not touch runtime logic until install metadata/schema is proven correct.
- If schema must change after release, do not patch `install.xml` alone without planning upgrade steps.

**Manual test steps:**
1. Place plugin under `mod/quiz/accessrule/intentionalskip`.
2. Visit Moodle notifications/admin upgrade page.
3. Confirm Moodle detects `quizaccess_intentionalskip`.
4. Complete install.
5. Verify custom tables exist.

**Risk level:** High

## Plugin upgrade/version issue

**Typical symptoms:**
- Moodle does not run upgrade after code changes.
- Version bump is ignored.
- Existing sites fail after schema changes.
- Fresh install works, but upgrade from an earlier version fails.

**Start here:**
- `version.php`
- `db/install.xml`

**Then inspect only if needed:**
- `FILE_INDEX.md`

**Do not inspect unless clearly needed:**
- `rule.php`
- `amd/src/intentionalskip.js`
- `report.php`

**Likely root causes:**
- `$plugin->version` not increased
- Schema changed in `db/install.xml` without corresponding upgrade path
- Missing `db/upgrade.php` for existing-site migrations
- Release/package metadata not matching deployed code

**Safe patch rules:**
- Do not change `version.php` without a deliberate upgrade path.
- Do not assume `install.xml` fixes an already-installed site.
- If the bug affects upgrades on existing sites, treat missing `db/upgrade.php` as the primary design gap.

**Manual test steps:**
1. Test on a fresh install.
2. Test on a site with an older installed plugin version.
3. Bump version intentionally.
4. Run Moodle upgrade.
5. Verify schema/data still match runtime code.

**Risk level:** High

## Quiz setting not appearing

**Typical symptoms:**
- Intentional skip settings are missing from quiz settings form.
- Only some plugin settings appear.
- Enable/disable checkbox is absent.

**Start here:**
- `rule.php`
- `lang/en/quizaccess_intentionalskip.php`

**Then inspect only if needed:**
- `version.php`

**Do not inspect unless clearly needed:**
- `classes/external/save_slot.php`
- `classes/local/state_manager.php`
- `amd/src/intentionalskip.js`

**Likely root causes:**
- Settings form hook not adding fields
- Wrong string keys causing render failures
- Plugin class not being loaded as a quiz access rule
- Settings conditionally hidden due to bad enablement logic

**Safe patch rules:**
- Keep fixes inside settings-form integration first.
- Do not change state-saving code for a form-rendering issue.
- Preserve existing setting names if data may already exist in DB.

**Manual test steps:**
1. Open quiz settings as a teacher/admin.
2. Find the plugin settings section.
3. Confirm all expected fields appear.
4. Check help icons/strings render correctly.

**Risk level:** Medium

## Quiz setting not saving

**Typical symptoms:**
- Settings appear in the form but revert after save.
- Enablement changes have no effect after editing the quiz.
- Report/link/UI behavior does not match saved quiz settings.

**Start here:**
- `rule.php`
- `db/install.xml`

**Then inspect only if needed:**
- `report.php`
- `amd/src/intentionalskip.js`

**Do not inspect unless clearly needed:**
- `classes/external/get_state.php`
- `classes/external/save_slot.php`
- `classes/privacy/provider.php`

**Likely root causes:**
- Custom config fields not saved into `quizaccess_intskip_config`
- SQL aliases/load logic not matching saved fields
- Config row deletion/recreation logic dropping values
- Field names in form/save/load paths not aligned

**Safe patch rules:**
- Keep changes limited to quiz config persistence.
- Do not alter attempt-state tables for a quiz-settings bug.
- Avoid renaming config keys on an installed site unless migration is planned.

**Manual test steps:**
1. Enable the plugin on a quiz.
2. Change one or more plugin settings.
3. Save quiz settings.
4. Reopen settings page.
5. Confirm values persist and affect runtime behavior.

**Risk level:** High

## JavaScript not loading

**Typical symptoms:**
- No intentional skip UI appears on attempt pages.
- Browser console shows missing AMD module or JS init error.
- Strings expected by JS are unavailable.

**Start here:**
- `rule.php`
- `amd/build/intentionalskip.min.js`
- `amd/src/intentionalskip.js`

**Then inspect only if needed:**
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `classes/external/save_slot.php`
- `db/install.xml`
- `report.php`

**Likely root causes:**
- `rule.php` not calling the AMD initializer
- Wrong module name in PHP vs AMD
- Built file stale or missing relative to `amd/src`
- Missing JS string registration

**Safe patch rules:**
- Do not edit only the minified file for logic changes.
- If source JS changes, rebuild/check `amd/build/intentionalskip.min.js`.
- Keep fixes focused on bootstrap/module-name/string-loading first.

**Manual test steps:**
1. Open a quiz attempt page with browser dev tools.
2. Confirm the AMD module loads without console errors.
3. Verify checkboxes/status UI appear when enabled.
4. Refresh with Moodle caches purged if needed.

**Risk level:** High

## Skip checkbox/control not appearing

**Typical symptoms:**
- JS loads, but no per-question skip checkbox appears.
- Controls appear for some question types/pages but not others.
- Summary/review enhancements may also be missing.

**Start here:**
- `amd/src/intentionalskip.js`
- `rule.php`

**Then inspect only if needed:**
- `classes/external/get_state.php`
- `classes/local/state_manager.php`
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `report.php`
- `classes/privacy/provider.php`
- `db/access.php`

**Likely root causes:**
- DOM selectors for question containers fail on the current Moodle theme/version
- Current-page slot detection from `form#responseform` is wrong
- UI intentionally disabled by quiz config/read-only mode
- Initial state load failure short-circuits rendering

**Safe patch rules:**
- Keep fixes selector-focused; do not rewrite save/report logic first.
- Preserve readonly/review protections.
- Test on multiple question types before widening selectors.

**Manual test steps:**
1. Start a quiz attempt with the plugin enabled.
2. Visit a page containing real question slots.
3. Confirm a checkbox/control appears per question.
4. Repeat on another question type/page if available.

**Risk level:** High

## Skip checkbox/control appears in wrong place

**Typical symptoms:**
- Checkbox renders outside the question block.
- Control overlaps native Moodle UI.
- Skip status/checkbox attaches to the wrong question.

**Start here:**
- `amd/src/intentionalskip.js`

**Then inspect only if needed:**
- `rule.php`
- `amd/build/intentionalskip.min.js`

**Do not inspect unless clearly needed:**
- `classes/external/save_slot.php`
- `report.php`
- `db/access.php`

**Likely root causes:**
- Wrong DOM traversal from slot to question container
- Theme/version markup assumptions no longer hold
- Inline CSS injection positioning the control incorrectly
- Question-slot mapping off by one or bound to the wrong node

**Safe patch rules:**
- Patch selectors and placement logic before touching persistence.
- Avoid broad CSS changes that could affect unrelated quiz controls.
- Re-test on attempt, summary, and review pages after any placement fix.

**Manual test steps:**
1. Open a multi-question quiz page.
2. Verify each control appears inside the correct question area.
3. Check mobile/narrow width if practical.
4. Confirm summary/review pages are still sane.

**Risk level:** Medium

## Skip state not saving

**Typical symptoms:**
- User checks intentionally skipped, but report/state does not update.
- Checkbox briefly changes, then reverts.
- Network call returns an error for a single-slot save.

**Start here:**
- `amd/src/intentionalskip.js`
- `db/services.php`
- `classes/external/save_slot.php`
- `classes/local/state_manager.php`

**Then inspect only if needed:**
- `db/access.php`
- `db/install.xml`

**Do not inspect unless clearly needed:**
- `report.php`
- `classes/privacy/provider.php`
- `classes/event/question_marked_skipped.php`

**Likely root causes:**
- Wrong AJAX service name or payload
- External API parameter mismatch
- Rejected save source such as `manual_checkbox` or `manual_uncheck`
- Capability/ownership/attempt-open validation failure
- DB write logic not persisting `attemptid + slot` correctly

**Safe patch rules:**
- Do not change schema unless the write path proves it is necessary.
- Do not edit minified JS alone.
- Keep fixes constrained to the one-slot save flow before touching bulk mark flows.

**Manual test steps:**
1. Start a quiz attempt.
2. Mark one unanswered question as intentionally skipped.
3. Refresh the page.
4. Confirm the checkbox/state remains.
5. Check the report for the saved state.

**Risk level:** High

## Skip state saving but later disappearing

**Typical symptoms:**
- Skip state is initially saved, then disappears after answering, refresh, navigation, or review.
- Report shows skipped at first, later blank/answered.
- State clears without the user manually unchecking.

**Start here:**
- `classes/local/state_manager.php`
- `amd/src/intentionalskip.js`

**Then inspect only if needed:**
- `classes/external/save_slot.php`
- `classes/external/get_state.php`
- `report.php`
- `classes/event/skip_state_cleared_by_answer.php`

**Do not inspect unless clearly needed:**
- `db/access.php`
- `classes/privacy/provider.php`
- `lang/en/quizaccess_intentionalskip.php`

**Likely root causes:**
- Answered-state reconciliation clears skip state when Moodle sees an answer
- JS auto-clear after answer fires too aggressively
- State reload path reclassifies answered/skipped differently than save path
- Attempt/review readonly handling suppresses or hides previously saved state

**Safe patch rules:**
- Respect the documented rule that Moodle answer state is authoritative.
- Do not infer intentional skip from blank answers as a shortcut fix.
- Keep fixes aligned between JS local behavior and `state_manager::slot_is_answered()`.

**Manual test steps:**
1. Mark a question skipped.
2. Refresh and confirm it persists.
3. Answer the question and observe whether skip clears as intended.
4. Navigate away and back.
5. Check the report and review page for consistency.

**Risk level:** High

## AJAX/external service error

**Typical symptoms:**
- Browser network/console shows external function failures.
- Initial load, manual save, or bulk mark calls return exceptions.
- Feature degrades to warning-only behavior.

**Start here:**
- `db/services.php`
- `classes/external/get_state.php`
- `classes/external/save_slot.php`
- `classes/external/mark_slots.php`
- `classes/local/state_manager.php`

**Then inspect only if needed:**
- `amd/src/intentionalskip.js`
- `db/access.php`

**Do not inspect unless clearly needed:**
- `report.php`
- `classes/privacy/provider.php`
- `amd/build/intentionalskip.min.js`

**Likely root causes:**
- Wrong external class/method registration
- Capability mismatch on service definitions
- Parameter or return-structure validation failures
- Client-version mismatch
- Attempt-state/ownership validation rejecting otherwise valid requests

**Safe patch rules:**
- Keep service names stable unless JS is updated in lockstep.
- Fix API contract mismatches at the contract boundary first.
- Preserve fail-open quiz navigation behavior when save/load fails.

**Manual test steps:**
1. Load the attempt page and inspect the initial state AJAX call.
2. Toggle one checkbox and inspect the save call.
3. Trigger Next/final-submit mark flow and inspect the bulk call.
4. Confirm returned payload matches UI expectations.

**Risk level:** High

## Permission/capability issue

**Typical symptoms:**
- Students cannot use the feature.
- Teachers cannot view the report.
- AJAX works for admins but fails for normal learners/teachers.
- A role can access more than it should.

**Start here:**
- `db/access.php`
- `rule.php`
- `report.php`
- `classes/local/state_manager.php`
- `db/services.php`

**Then inspect only if needed:**
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `db/install.xml`
- `classes/privacy/provider.php`

**Likely root causes:**
- Wrong capability assigned to the wrong action
- `quizaccess/intentionalskip:use` not granted where needed
- `viewreport` check too strict or missing
- Service registration requiring a capability that runtime users lack

**Safe patch rules:**
- Treat capability changes as assessment-security changes.
- Do not grant learner access to reporting/admin paths as a convenience fix.
- Keep module-context assumptions intact unless there is strong evidence otherwise.

**Manual test steps:**
1. Test as a student on an active attempt.
2. Test as a teacher on the report page.
3. Test with a non-editing role if relevant.
4. Verify both UI visibility and server-side access.

**Risk level:** High

## Report page not accessible

**Typical symptoms:**
- Report link does not appear.
- Direct access to `report.php` gives require-login/capability errors.
- Page loads for admins only when teachers should also have access.

**Start here:**
- `rule.php`
- `report.php`
- `db/access.php`

**Then inspect only if needed:**
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `classes/external/save_slot.php`
- `amd/src/intentionalskip.js`
- `db/install.xml`

**Likely root causes:**
- Report link gated by wrong capability or quiz enablement logic
- `report.php` capability check not matching intended roles
- Broken `cmid`/URL construction
- Missing string causing link/heading confusion rather than true access failure

**Safe patch rules:**
- Do not weaken access checks just to make the page visible.
- Keep link-generation fixes separate from report-data fixes.
- Confirm whether the problem is "link missing" or "direct access denied."

**Manual test steps:**
1. Open a quiz as a teacher.
2. Check whether the report link appears.
3. Access `report.php?cmid=...` directly.
4. Repeat as a student to verify denial still holds.

**Risk level:** Medium

## Report page shows wrong skipped/answered status

**Typical symptoms:**
- Questions marked skipped show as blank or answered.
- Answered questions remain marked skipped.
- Report disagrees with attempt/review reality.

**Start here:**
- `report.php`
- `classes/local/state_manager.php`

**Then inspect only if needed:**
- `db/install.xml`
- `lang/en/quizaccess_intentionalskip.php`
- `classes/external/get_state.php`

**Do not inspect unless clearly needed:**
- `amd/build/intentionalskip.min.js`
- `db/access.php`
- `classes/privacy/provider.php`

**Likely root causes:**
- `slot_is_answered()` classification not aligned with report display logic
- Stored skip state and current Moodle question state diverge
- Non-real questions or slot filtering included/excluded incorrectly
- Status string mapping wrong even when raw data is right

**Safe patch rules:**
- Treat server-side classification as the source of truth.
- Do not patch report labels until you confirm the underlying classification is correct.
- Keep report formatting changes separate from state persistence changes.

**Manual test steps:**
1. Create attempts with blank, skipped, and answered questions.
2. Open the report.
3. Compare each slot with the actual attempt state.
4. Repeat after refresh/review.

**Risk level:** High

## Report count mismatch

**Typical symptoms:**
- Number of skipped rows is too high or too low.
- Per-attempt totals do not match what teachers expect.
- Counts drift when preview attempts exist.

**Start here:**
- `report.php`
- `classes/local/state_manager.php`
- `db/install.xml`

**Then inspect only if needed:**
- `classes/external/mark_slots.php`
- `classes/external/save_slot.php`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `lang/en/quizaccess_intentionalskip.php`
- `classes/privacy/provider.php`

**Likely root causes:**
- Current-state table and report logic disagree about one row per `attemptid, slot`
- State cleared/rewritten unexpectedly
- Preview filtering or slot filtering distorts totals
- Bulk mark/save logic writes fewer or more rows than expected

**Safe patch rules:**
- Verify whether the mismatch is in storage, filtering, or display before editing.
- Avoid schema changes for a counting bug unless uniqueness logic is demonstrably broken.
- Preserve the distinction between current state and history rows.

**Manual test steps:**
1. Create several attempts with known skipped counts.
2. Include one attempt with answers later clearing skips.
3. Compare expected totals with report totals.
4. Check raw row count assumptions through Moodle behavior, not guesswork.

**Risk level:** High

## Preview attempts included/excluded incorrectly

**Typical symptoms:**
- Preview attempts appear in the report unexpectedly.
- Real attempts are hidden when preview filtering is active.
- Quiz setting about preview exclusion does not seem to match report behavior.

**Start here:**
- `report.php`
- `rule.php`
- `classes/local/state_manager.php`

**Then inspect only if needed:**
- `db/install.xml`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `classes/privacy/provider.php`
- `classes/event/skip_state_base.php`

**Likely root causes:**
- `includepreviews` handling in `report.php` is wrong
- Preview-state persistence in saved rows is wrong
- Quiz-level preview exclusion setting and report filtering are not aligned

**Safe patch rules:**
- Keep preview logic separate from general report classification logic.
- Do not change learner-facing save flows unless preview-state data itself is wrong.
- Confirm whether the bug is report filtering or state capture.

**Manual test steps:**
1. Create at least one preview attempt and one real attempt.
2. Save skip states in both.
3. Open report with default filtering.
4. Note that there is currently no built-in report toggle UI documented in the code; test preview inclusion by using the `includepreviews` request parameter/value.
5. Confirm only intended rows appear.

**Risk level:** Medium

## User/attempt/question data displayed incorrectly

**Typical symptoms:**
- Wrong user name, attempt number, slot, question number, or question status shown.
- Rows appear shifted or tied to the wrong attempt.
- Save-source or timestamp columns look wrong.

**Start here:**
- `report.php`
- `db/install.xml`

**Then inspect only if needed:**
- `classes/local/state_manager.php`
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `classes/privacy/provider.php`
- `db/access.php`

**Likely root causes:**
- Wrong joins/lookup assumptions when building report rows
- Misread fields from `quizaccess_intskip_state`
- Slot/question numbering mismatch
- Display labels correct for one field but bound to another

**Safe patch rules:**
- Fix data binding before changing labels.
- Preserve escaping and Moodle helper usage for names/dates/statuses.
- Keep row-building changes isolated to the report.

**Manual test steps:**
1. Create multiple attempts by different users.
2. Save different skip states by slot.
3. Open the report and verify each row field against the real attempt.

**Risk level:** Medium

## Language string missing

**Typical symptoms:**
- Moodle shows `[[missingstring]]` style output or raw string keys.
- JS dialog/status text is missing.
- Capability/event/privacy labels are not human-readable.

**Start here:**
- `lang/en/quizaccess_intentionalskip.php`

**Then inspect only if needed:**
- `rule.php`
- `report.php`
- `amd/src/intentionalskip.js`
- `classes/event/question_marked_skipped.php`
- `classes/event/question_unmarked_skipped.php`
- `classes/event/page_unanswered_marked_skipped.php`
- `classes/event/final_unanswered_marked_skipped.php`
- `classes/event/skip_state_cleared_by_answer.php`
- `classes/privacy/provider.php`

**Do not inspect unless clearly needed:**
- `db/install.xml`
- `classes/local/state_manager.php`
- `db/services.php`

**Likely root causes:**
- Missing key in language file
- Key renamed in code but not in strings
- JS string not registered in `rule.php`
- Event/capability/privacy string references out of sync

**Safe patch rules:**
- Prefer adding/fixing keys over renaming existing ones.
- If a JS key is added, confirm `strings_for_js` registration path still covers it.
- Do not touch business logic for a pure string issue.

**Manual test steps:**
1. Visit the page where the missing text appears.
2. Note the raw/missing key.
3. Trace the referencing file.
4. Confirm rendered text appears correctly after fix.

**Risk level:** Low

## Moodle privacy API issue

**Typical symptoms:**
- Privacy registry check fails.
- Delete-context/delete-user requests do not remove data correctly.
- Export expectations are unmet.

**Start here:**
- `classes/privacy/provider.php`
- `db/install.xml`
- `lang/en/quizaccess_intentionalskip.php`

**Then inspect only if needed:**
- `classes/local/state_manager.php`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `report.php`
- `db/services.php`

**Likely root causes:**
- Metadata entries do not match actual tables/fields
- Context discovery misses history-only records
- Delete logic maps module context incorrectly
- `export_user_data()` is still only a placeholder

**Safe patch rules:**
- Be explicit if the bug is really "export not implemented yet."
- Keep privacy fixes isolated from runtime save/report logic.
- Do not change table names to satisfy privacy metadata.

**Manual test steps:**
1. Run Moodle privacy checks for the plugin.
2. Create user skip data.
3. Request context discovery and deletion.
4. Confirm state/history rows are removed as intended.
5. Verify whether export is expected or currently placeholder-only.

**Risk level:** Medium

## Event/logging issue

**Typical symptoms:**
- Expected skip events do not appear in logs.
- Wrong event names appear.
- Event metadata, URL, user, or object ID is wrong.

**Start here:**
- `classes/local/state_manager.php`
- `classes/event/skip_state_base.php`

**Then inspect only if needed:**
- `classes/event/question_marked_skipped.php`
- `classes/event/question_unmarked_skipped.php`
- `classes/event/page_unanswered_marked_skipped.php`
- `classes/event/final_unanswered_marked_skipped.php`
- `classes/event/skip_state_cleared_by_answer.php`
- `lang/en/quizaccess_intentionalskip.php`

**Do not inspect unless clearly needed:**
- `amd/src/intentionalskip.js`
- `db/access.php`
- `classes/privacy/provider.php`

**Likely root causes:**
- State change saved but event not triggered
- Wrong source-to-event mapping in `state_manager`
- Shared event payload metadata wrong in `skip_state_base`
- Event name string missing in language file

**Safe patch rules:**
- Fix save-path bugs before blaming event classes.
- Touch concrete event files only for naming/autoload issues.
- Keep logging fixes consistent with actual persisted transitions.

**Manual test steps:**
1. Manually mark a question skipped.
2. Unmark it.
3. Trigger Next-page bulk mark.
4. Trigger final-submit bulk mark if safe.
5. Verify expected events and metadata in Moodle logs.

**Risk level:** Medium

## AMD build/minified JavaScript mismatch

**Typical symptoms:**
- Source JS looks correct, but Moodle behavior still reflects older code.
- Production mode differs from local expectations.
- Bug exists only when the built file is served.

**Start here:**
- `amd/src/intentionalskip.js`
- `amd/build/intentionalskip.min.js`

**Then inspect only if needed:**
- `rule.php`

**Do not inspect unless clearly needed:**
- `classes/external/save_slot.php`
- `report.php`
- `db/install.xml`

**Likely root causes:**
- Source changed but built file not regenerated
- Build artifact committed from different source revision
- PHP loads the expected module name, but built output does not match it

**Safe patch rules:**
- Never patch only the minified file for logic fixes.
- Treat `amd/build` as generated output.
- After JS source changes, ensure the build artifact corresponds to that source.

**Manual test steps:**
1. Compare intended behavior in source vs served behavior.
2. Check whether the built file reflects the latest source logic.
3. Purge Moodle caches if needed.
4. Retest in the browser.

**Risk level:** Medium

## Moodle coding standards issue

**Typical symptoms:**
- CI/linting/manual review flags Moodle coding-style violations.
- Guards, naming, escaping, or API usage violate Moodle conventions.
- Code review asks for Moodle-standard compliance fixes.

**Start here:**
- `FILE_INDEX.md`
- The smallest file directly implicated by the warning

**Then inspect only if needed:**
- `rule.php`
- `report.php`
- `classes/local/state_manager.php`
- `db/access.php`
- `db/services.php`
- `lang/en/quizaccess_intentionalskip.php`
- `amd/src/intentionalskip.js`

**Do not inspect unless clearly needed:**
- Unrelated event classes
- `classes/privacy/provider.php`
- `db/install.xml`

**Likely root causes:**
- Missing/incorrect `MOODLE_INTERNAL` guard
- Wrong capability/string/component naming
- Escaping/output API misuse in `report.php`
- External API signatures or PARAM usage not following Moodle expectations
- JS or PHP style drift after manual edits

**Safe patch rules:**
- Fix only the file and rule being violated; avoid opportunistic refactors.
- Preserve behavior while correcting standards issues.
- If a standards fix would alter security or data flow, treat it as a behavior change and test it.

**Manual test steps:**
1. Reproduce the coding-standard finding.
2. Apply the narrowest fix.
3. Re-run the same check or manual review.
4. Smoke-test the affected runtime path.

**Risk level:** Low

## Security issue

**Typical symptoms:**
- Unauthorized users can view or modify skip data.
- Sensitive report data is exposed.
- Input validation, ownership, or context checks appear bypassable.
- Audit review flags assessment-integrity risks.

**Start here:**
- `classes/local/state_manager.php`
- `db/access.php`
- `db/services.php`
- `report.php`
- `rule.php`

**Then inspect only if needed:**
- `classes/external/get_state.php`
- `classes/external/save_slot.php`
- `classes/external/mark_slots.php`
- `classes/privacy/provider.php`

**Do not inspect unless clearly needed:**
- `amd/build/intentionalskip.min.js`
- `lang/en/quizaccess_intentionalskip.php`
- `newfile20may26812pm.txt`

**Likely root causes:**
- Missing or wrong capability check
- Attempt ownership/review validation too weak
- Context/module/quiz mismatch not enforced
- Unsafe report access or over-broad role defaults
- External endpoints trusting client data too much

**Safe patch rules:**
- Default to tightening validation, not loosening it.
- Do not grant extra capabilities as a shortcut to make features "work."
- Preserve Moodle as the authority for attempts, answers, submission, and review.
- Re-test both learner and teacher flows after any security fix.

**Manual test steps:**
1. Attempt the action as an authorized user.
2. Attempt the same action as an unauthorized user/role.
3. Test direct access to AJAX/report URLs where relevant.
4. Verify legitimate quiz flows still work after the fix.

**Risk level:** High

## Quick routing reminders

- Install/upgrade first stop: `version.php`, `db/install.xml`
- UI/bootstrap first stop: `rule.php`, `amd/src/intentionalskip.js`
- AJAX/save first stop: `db/services.php`, `classes/external/*.php`, `classes/local/state_manager.php`
- Report first stop: `report.php`, `classes/local/state_manager.php`
- Permissions/security first stop: `db/access.php`, `state_manager.php`, `report.php`
- Privacy first stop: `classes/privacy/provider.php`
- Build mismatch first stop: `amd/src/intentionalskip.js`, `amd/build/intentionalskip.min.js`
