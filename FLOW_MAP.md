# FLOW_MAP.md

Purpose: trace the actual execution paths in `quizaccess_intentionalskip` from Moodle UI entry points through PHP, AJAX services, persistence, reporting, privacy, and build artifacts.

Scope notes:
- This map is based primarily on `CODE_OBJECT_INDEX.md`, with runtime details verified in `rule.php`, `report.php`, `classes/local/state_manager.php`, `classes/external/*.php`, `classes/privacy/provider.php`, `db/*.php`, `db/install.xml`, `lang/en/quizaccess_intentionalskip.php`, and `amd/src/intentionalskip.js`.
- If behavior is not implemented or is unclear from code, it is stated explicitly.
- Moodle core does a large part of the surrounding work. This file only maps what is visible from this plugin repository.

## Plugin installation flow

**Trigger:**  
An admin places the plugin in `mod/quiz/accessrule/intentionalskip` and visits Moodle's plugin discovery / upgrade page on a site where the plugin is not yet installed.

**Files involved in order:**  
1. `version.php`
2. `db/install.xml`
3. `db/access.php`
4. `db/services.php`
5. `lang/en/quizaccess_intentionalskip.php`

**Code objects involved:**  
- No custom install class or hook is implemented in this repository.
- Moodle discovers plugin metadata from `version.php`.
- Moodle discovers schema from `db/install.xml`.
- Moodle discovers capabilities from `db/access.php`.
- Moodle discovers AJAX function registration from `db/services.php`.

**Step-by-step sequence:**  
1. Moodle scans the plugin folder and reads `version.php` for component name `quizaccess_intentionalskip`, version `2026051906`, and required Moodle version.
2. On fresh install, Moodle creates plugin tables from `db/install.xml`.
3. Moodle registers plugin capabilities from `db/access.php`.
4. Moodle registers AJAX external function definitions from `db/services.php`.
5. Moodle can then resolve the plugin's language strings from `lang/en/quizaccess_intentionalskip.php`.
6. No plugin-defined install-time PHP callback runs in this repository.

**Data involved:**  
- Plugin component name
- Plugin version
- Required Moodle version
- Table definitions
- Capability definitions
- External function registration metadata

**Database tables:**  
- `quizaccess_intskip_config`: created on install
- `quizaccess_intskip_state`: created on install
- `quizaccess_intskip_history`: created on install

**Capabilities/security checks:**  
- No runtime capability check inside the plugin install files
- Moodle core installation permissions apply

**Events/logging:**  
- No plugin event class is triggered during install
- Install logging is handled by Moodle core, not visible here

**Common failure points:**  
- Wrong plugin path or component name mismatch
- Invalid XMLDB schema in `db/install.xml`
- Version / requires metadata preventing discovery
- Table definitions changed without matching upgrade path for existing sites

**Best starting point for debugging:**  
- `version.php`
- `db/install.xml`

**Usually do not touch:**  
- `amd/src/intentionalskip.js`
- `report.php`
- `classes/local/state_manager.php`

## Plugin upgrade/version flow

**Trigger:**  
An existing Moodle site already has this plugin installed and the code version changes.

**Files involved in order:**  
1. `version.php`
2. `db/install.xml`
3. `FILE_INDEX.md`
4. `BUG_MAP.md`

**Code objects involved:**  
- No `db/upgrade.php` exists in this repository.
- Moodle uses `$plugin->version` from `version.php` to decide whether an upgrade is needed.

**Step-by-step sequence:**  
1. Moodle compares the installed plugin version with `version.php`.
2. If the code version is higher, Moodle attempts plugin upgrade handling.
3. This repository does not provide `db/upgrade.php`, so there is no plugin-defined migration path for installed sites.
4. `db/install.xml` remains the fresh-install schema reference, not a safe upgrade mechanism by itself.
5. Any schema drift between runtime code and already-installed databases must therefore be treated as a missing-upgrade-path issue.

**Data involved:**  
- Installed plugin version
- New plugin version
- Existing table shape on upgraded sites
- Fresh-install schema from `db/install.xml`

**Database tables:**  
- Potentially all plugin tables, depending on the change
- This flow does not itself read or write plugin tables in repository code

**Capabilities/security checks:**  
- No plugin-defined capability checks in the upgrade metadata files
- Moodle admin upgrade permissions apply

**Events/logging:**  
- No plugin event class is triggered by version comparison
- Upgrade logging is handled by Moodle core, not visible here

**Common failure points:**  
- `$plugin->version` not increased
- Schema changed in `db/install.xml` without a migration path
- Existing sites break because `db/upgrade.php` is absent

**Best starting point for debugging:**  
- `version.php`
- Confirm that no `db/upgrade.php` exists

**Usually do not touch:**  
- `amd/src/intentionalskip.js`
- `report.php`
- Event classes

## Quiz settings configuration flow

**Trigger:**  
A teacher or manager opens the quiz settings form and saves settings for a quiz.

**Files involved in order:**  
1. `rule.php`
2. `db/install.xml`
3. `rule.php`

**Code objects involved:**  
- `quizaccess_intentionalskip::add_settings_form_fields()`
- `quizaccess_intentionalskip::save_settings()`
- `quizaccess_intentionalskip::get_settings_sql()`
- `quizaccess_intentionalskip::make()`

**Step-by-step sequence:**  
1. Moodle loads the access rule plugin and calls `add_settings_form_fields()` to add plugin settings to the quiz form.
2. The form shows `intentionalskip_enabled`, `intentionalskip_showcheckbox`, `intentionalskip_warnonnext`, `intentionalskip_allowcontinue`, `intentionalskip_ajaxsave`, and `intentionalskip_excludepreviews`.
3. `hideIf` rules keep the subordinate settings hidden until enablement is on.
4. When the quiz is saved, Moodle calls `save_settings()`.
5. `save_settings()` reads any existing `quizaccess_intskip_config` row for the quiz and inserts or updates it.
6. Later, `get_settings_sql()` provides SQL fragments so the saved config is loaded with the quiz record.
7. `make()` only returns the access rule when `intentionalskip_enabled` is truthy on the loaded quiz object.

**Data involved:**  
- Quiz id
- User id of the editor
- Enable flag
- `showcheckbox`
- `warnonnext`
- `allowcontinue`
- `ajaxsave`
- `excludepreviews`
- `timecreated`
- `timemodified`

**Database tables:**  
- `quizaccess_intskip_config`: read, inserted, updated, deleted

**Capabilities/security checks:**  
- No plugin-specific capability check is visible inside `add_settings_form_fields()` or `save_settings()`
- Moodle's normal quiz-edit permissions govern who can reach this flow

**Events/logging:**  
- No plugin event class is triggered when quiz settings are saved

**Common failure points:**  
- Settings render but do not persist because `quizaccess_intskip_config` row handling is wrong
- Loaded quiz aliases do not match the saved DB field names
- Admin expects `excludepreviews` to affect reporting automatically, but `report.php` does not read that config directly

**Best starting point for debugging:**  
- `rule.php`
- `quizaccess_intskip_config` table shape in `db/install.xml`

**Usually do not touch:**  
- `classes/external/*.php`
- `amd/src/intentionalskip.js`
- `report.php` unless the bug is only report-side interpretation

## Quiz attempt page load flow

**Trigger:**  
A user opens a quiz page for a quiz where intentional skip tracking is enabled and Moodle invokes `setup_attempt_page()`. The AMD/module behavior covers quiz attempt-page, summary/final-submit, and review-related contexts. Exact invocation depends on Moodle core quiz access-rule hook/page behavior.

**Files involved in order:**  
1. `rule.php`
2. `classes/local/state_manager.php`
3. `lang/en/quizaccess_intentionalskip.php`
4. `amd/build/intentionalskip.min.js`

**Code objects involved:**  
- `quizaccess_intentionalskip::make()`
- `quizaccess_intentionalskip::setup_attempt_page()`
- `state_manager::CLIENT_VERSION`

**Step-by-step sequence:**  
1. Moodle loads the quiz settings and access rules.
2. `make()` checks `intentionalskip_enabled`; if it is empty, the plugin does nothing for this quiz.
3. If enabled, Moodle instantiates `quizaccess_intentionalskip`.
4. `setup_attempt_page()` runs during page setup.
5. `setup_attempt_page()` checks `quizaccess/intentionalskip:use`; if missing, the JavaScript UI is not bootstrapped.
6. It registers a specific subset of strings for JavaScript with `strings_for_js()`.
7. It reads `attempt` from request params, computes `readonly` from the URL path and attempt state, and passes config to `js_call_amd()`.
8. Moodle serves module `quizaccess_intentionalskip/intentionalskip`, which resolves to the built AMD artifact under `amd/build/`.

**Data involved:**  
- Course module id
- Attempt id
- `showcheckbox`
- `warnonnext`
- `allowcontinue`
- `ajaxsave`
- `clientversion`
- `readonly`

**Database tables:**  
- No direct table read/write in `setup_attempt_page()`
- Quiz config has already been loaded onto the quiz object earlier via `get_settings_sql()`

**Capabilities/security checks:**  
- `quizaccess/intentionalskip:use` in `setup_attempt_page()`
- Review / finished / non-in-progress attempts are forced into `readonly` mode on the client

**Events/logging:**  
- No plugin event is triggered on page load

**Common failure points:**  
- Rule not instantiated because `intentionalskip_enabled` did not load
- Capability check prevents bootstrap
- JS string registration missing a key used in the client
- `readonly` becomes true unexpectedly because the attempt is finished or not `IN_PROGRESS`

**Best starting point for debugging:**  
- `quizaccess_intentionalskip::make()`
- `quizaccess_intentionalskip::setup_attempt_page()`

**Usually do not touch:**  
- `classes/local/state_manager.php` unless the bug is actually in AJAX validation
- `report.php`
- Privacy provider

## JavaScript initialization flow

**Trigger:**  
`rule.php` calls `quizaccess_intentionalskip/intentionalskip.init(...)` on a page where Moodle invokes `setup_attempt_page()`. The AMD/module behavior covers quiz attempt-page, summary/final-submit, and review-related contexts. Exact invocation depends on Moodle core quiz access-rule hook/page behavior.

**Files involved in order:**  
1. `rule.php`
2. `amd/build/intentionalskip.min.js`
3. `amd/src/intentionalskip.js`
4. `classes/external/get_state.php`
5. `classes/local/state_manager.php`

**Code objects involved:**  
- AMD export `init()`
- `loadState()`
- `injectStyle()`
- `injectControls()`
- `enhanceSummary()`
- `registerEvents()`
- `get_state::execute()`
- `state_manager::validate_client_version()`
- `state_manager::validate_attempt()`
- `state_manager::get_attempt_state()`

**Step-by-step sequence:**  
1. `init()` merges defaults with the PHP-supplied config object.
2. If `attemptid` or `cmid` is missing, `init()` returns immediately.
3. `injectStyle()` adds inline CSS once per page.
4. `loadState()` calls AJAX service `quizaccess_intentionalskip_get_state`.
5. The PHP endpoint validates parameters, client version, and attempt access, then returns per-slot state.
6. The JS stores the returned data in `stateBySlot`.
7. `injectControls()` uses the state to add UI to question blocks.
8. `enhanceSummary()` updates summary-row text for unanswered slots.
9. `registerEvents()` adds capturing click handlers for Next navigation and final submission.

**Data involved:**  
- Attempt id
- Course module id
- Client version
- Slot list
- Per-slot `answered`
- Per-slot `skipped`
- Per-slot `source`
- Per-slot `timemodified`
- `readonly`

**Database tables:**  
- `quizaccess_intskip_state`: read indirectly through `state_manager::get_attempt_state()`

**Capabilities/security checks:**  
- `state_manager::validate_client_version()`
- Service registration in `db/services.php` requires `quizaccess/intentionalskip:use`
- `state_manager::validate_attempt(..., false, true)` inside `get_state`
- `require_sesskey()`
- `require_login()`
- `quizaccess/intentionalskip:use` for owners
- `quizaccess/intentionalskip:viewreport` for the non-owner review branch inside `validate_attempt()`

**Events/logging:**  
- No plugin event is triggered by initialization alone

**Common failure points:**  
- AJAX `get_state` fails and the entire UI stops after `Notification.exception()`
- Stale client version throws `errorstaleclient`
- Slots not found because the quiz page markup differs from what JS expects

**Best starting point for debugging:**  
- `amd/src/intentionalskip.js` `init()` and `loadState()`
- `classes/external/get_state.php`

**Usually do not touch:**  
- `report.php`
- Event classes

## Skip checkbox/control display flow

**Trigger:**  
The initial AJAX state load succeeds and `injectControls()` runs.

**Files involved in order:**  
1. `amd/src/intentionalskip.js`
2. `lang/en/quizaccess_intentionalskip.php`
3. `rule.php`

**Code objects involved:**  
- `injectControls()`
- `getQuestionElement()`
- `getQuestionInputs()`
- `setStatus()`
- `setChecked()`

**Step-by-step sequence:**  
1. `injectControls()` exits immediately if `config.showcheckbox` is false.
2. For each returned slot state, it finds the corresponding question DOM node using `getQuestionElement()`.
3. If the question cannot be found, or a plugin control already exists, it skips that slot.
4. If `config.readonly` is true, it appends text only: `Marked as skipped` or `Not marked as intentionally skipped`.
5. Otherwise it renders a wrapper, a checkbox, a label using string `markasskipped`, and a status span.
6. It sets the initial checkbox state from `slotState.skipped`.
7. It wires change listeners on the checkbox and on each question input.

**Data involved:**  
- Slot id
- `slotState.skipped`
- `config.showcheckbox`
- `config.readonly`
- Local DOM inputs belonging to the slot

**Database tables:**  
- None directly in this display step
- It depends on previously loaded server state

**Capabilities/security checks:**  
- No new security check in `injectControls()`
- This flow only exists if earlier PHP capability checks allowed JS bootstrap

**Events/logging:**  
- No plugin event is triggered by rendering the control

**Common failure points:**  
- `.que` selector or slot-to-element matching fails on current theme / question markup
- `showcheckbox` is disabled at quiz config level
- Review pages show text-only state because `readonly` is true

**Best starting point for debugging:**  
- `getQuestionElement()`
- `injectControls()`

**Usually do not touch:**  
- `classes/external/*.php`
- `report.php`
- Privacy provider

## Existing skip state restore/load flow

**Trigger:**  
The page initializes and calls `loadState()` to restore previously saved skip data.

**Files involved in order:**  
1. `amd/src/intentionalskip.js`
2. `db/services.php`
3. `classes/external/get_state.php`
4. `classes/local/state_manager.php`
5. `db/install.xml`

**Code objects involved:**  
- `loadState()`
- Service `quizaccess_intentionalskip_get_state`
- `get_state::execute()`
- `state_manager::validate_client_version()`
- `state_manager::validate_attempt()`
- `state_manager::get_attempt_state()`
- `state_manager::slot_is_answered()`

**Step-by-step sequence:**  
1. JS calls `quizaccess_intentionalskip_get_state` with `attemptid`, `cmid`, and `clientversion`.
2. `db/services.php` maps the service name to class `quizaccess_intentionalskip\external\get_state`.
3. `get_state::execute()` validates request parameters.
4. It rejects stale clients via `validate_client_version()`.
5. It validates the attempt with `validate_attempt($attemptid, $cmid, false, true)`; the runtime code includes a non-owner review branch, while service reachability still depends on the external-service `quizaccess/intentionalskip:use` requirement and role configuration.
6. `get_attempt_state()` reads all `quizaccess_intskip_state` rows for the attempt.
7. For each real slot in the attempt, it calculates `answered` from Moodle question state and only reports `skipped=true` when the slot is not answered and the saved row has `isskipped=1`.
8. JS stores the returned slot-state array in `stateBySlot`.

**Data involved:**  
- Attempt id
- Course module id
- Client version
- Slot id
- Saved `isskipped`
- Saved `source`
- Saved `timemodified`
- Derived `answered`
- Derived `status`

**Database tables:**  
- `quizaccess_intskip_state`: read

**Capabilities/security checks:**  
- Service capability registration: `quizaccess/intentionalskip:use`
- `require_sesskey()`
- `require_login()`
- `validate_context()`
- Owner path: `mod/quiz:attempt` for non-preview owners, plus `quizaccess/intentionalskip:use`
- Non-owner review path: `is_review_allowed()` plus `quizaccess/intentionalskip:viewreport`

**Events/logging:**  
- No plugin event is triggered by state load

**Common failure points:**  
- Saved state exists in the table, but returned `skipped` is false because Moodle now considers the slot answered
- Review / teacher read path fails on capability mismatch
- Client version mismatch causes `errorstaleclient`

**Best starting point for debugging:**  
- `classes/external/get_state.php`
- `state_manager::get_attempt_state()`

**Usually do not touch:**  
- `report.php`
- `amd/build/intentionalskip.min.js` unless checking build drift

## Single question skip save flow

**Trigger:**  
A learner manually checks or unchecks one skip checkbox on a question page while `ajaxsave` is enabled.

**Files involved in order:**  
1. `amd/src/intentionalskip.js`
2. `db/services.php`
3. `classes/external/save_slot.php`
4. `classes/local/state_manager.php`
5. `classes/event/question_marked_skipped.php`
6. `classes/event/question_unmarked_skipped.php`

**Code objects involved:**  
- Checkbox `change` listener inside `injectControls()`
- `saveSlot()`
- Service `quizaccess_intentionalskip_save_slot`
- `save_slot::execute()`
- `state_manager::validate_manual_source()`
- `state_manager::validate_attempt()`
- `state_manager::save_slot_state()`
- `state_manager::write_state()`
- `state_manager::write_history()`
- `state_manager::trigger_event()`

**Step-by-step sequence:**  
1. The checkbox listener picks source `manual_checkbox` or `manual_uncheck`.
2. If the user tries to check a slot that already has a local answer, JS immediately unchecks it, clears the status text, and only shows the answered/skipped dialog instead of saving.
3. If `config.ajaxsave` is true, JS calls `saveSlot()`.
4. `save_slot::execute()` validates parameters, client version, manual source, and attempt access.
5. `save_slot_state()` verifies the slot exists and is a real question.
6. If the request is trying to save `isskipped=true` for a slot Moodle already considers answered, server code rewrites it to `isskipped=false` and source `auto_cleared_after_answer`.
7. `write_state()` inserts or updates the current state row in `quizaccess_intskip_state`.
8. `write_history()` always appends a history row in `quizaccess_intskip_history`.
9. `trigger_event()` fires `question_marked_skipped` or `question_unmarked_skipped` when the transition is a normal manual state change.
10. JS updates `stateBySlot`, checkbox state, and status text from the server response.

**Data involved:**  
- Attempt id
- Course module id
- Slot id
- Requested `isskipped`
- `source`
- Client version
- User id
- `questionattemptid`
- `timemodified`
- `lastsavedby`
- Preview flag

**Database tables:**  
- `quizaccess_intskip_state`: read, insert, update
- `quizaccess_intskip_history`: insert

**Capabilities/security checks:**  
- `quizaccess/intentionalskip:use`
- `mod/quiz:attempt` for non-preview owners
- `require_sesskey()`
- `require_login()`
- `validate_context()`
- Plugin-enabled check inside `validate_attempt()`
- Open attempt / `IN_PROGRESS` checks inside `validate_attempt()`

**Events/logging:**  
- `quizaccess_intentionalskip\event\question_marked_skipped`
- `quizaccess_intentionalskip\event\question_unmarked_skipped`
- Server history row is always written even if the current-state row already exists

**Common failure points:**  
- `ajaxsave` disabled: UI changes locally but no server save occurs
- Client uses a stale `clientversion`
- Slot exists visually but not as a real question slot server-side
- Server rewrites a requested skip to clear-by-answer because Moodle sees an answer

**Best starting point for debugging:**  
- `saveSlot()` in `amd/src/intentionalskip.js`
- `classes/external/save_slot.php`
- `state_manager::save_slot_state()`

**Usually do not touch:**  
- `report.php`
- Privacy provider

## Multiple/page-level skip save flow

**Trigger:**  
The learner chooses a bulk-marking option from the Next-page unanswered dialog or the final-submit unanswered dialog.

**Files involved in order:**  
1. `amd/src/intentionalskip.js`
2. `db/services.php`
3. `classes/external/mark_slots.php`
4. `classes/local/state_manager.php`
5. `classes/event/page_unanswered_marked_skipped.php`
6. `classes/event/final_unanswered_marked_skipped.php`

**Code objects involved:**  
- `handleNavigation()`
- `currentPageUnansweredSlots()`
- `markSlots()`
- `handleFinalSubmit()`
- `continueFinalSubmit()`
- Service `quizaccess_intentionalskip_mark_slots`
- `mark_slots::execute()`
- `state_manager::mark_unanswered_slots()`
- `state_manager::mark_all_unanswered_slots()`
- `state_manager::write_state()`
- `state_manager::write_history()`
- `state_manager::trigger_event()`

**Step-by-step sequence:**  
1. For Next navigation, `handleNavigation()` intercepts the click when the button is Next, `warnonnext` is true, and the current page has unanswered slots.
2. The dialog offers Go back, Mark and continue, and optionally Continue without marking.
3. If the learner chooses Mark and continue, JS calls `markSlots(unanswered, 'next_page_confirmation', false)`.
4. For final submission, `handleFinalSubmit()` eventually calls `continueFinalSubmit()`.
5. `continueFinalSubmit()` reloads server state, collects unanswered slots, and if any exist shows a final dialog.
6. If the learner confirms final submission with marking, JS calls `markSlots([], 'final_submit_confirmation', true)`.
7. `mark_slots::execute()` validates params, client version, attempt access, and restricts sources to `next_page_confirmation` or `final_submit_confirmation`.
8. When `markall` is false, `mark_unanswered_slots()` marks only the provided unanswered slots.
9. When `markall` is true, `mark_all_unanswered_slots()` scans all real slots in the attempt, auto-clears any stale skip marks on answered slots, and delegates unanswered slots to `mark_unanswered_slots()`.
10. Each saved slot writes current state, history, and a matching event.

**Data involved:**  
- Attempt id
- Course module id
- Slot list for the current page
- `markall` flag
- `next_page_confirmation` source
- `final_submit_confirmation` source
- Client version
- User id

**Database tables:**  
- `quizaccess_intskip_state`: read, insert, update
- `quizaccess_intskip_history`: insert

**Capabilities/security checks:**  
- `quizaccess/intentionalskip:use`
- `require_sesskey()`
- `require_login()`
- `validate_context()`
- Plugin-enabled check
- Attempt-open and `IN_PROGRESS` checks
- Source whitelist in `mark_slots::execute()`

**Events/logging:**  
- `quizaccess_intentionalskip\event\page_unanswered_marked_skipped`
- `quizaccess_intentionalskip\event\final_unanswered_marked_skipped`
- `skip_state_cleared_by_answer` may also fire during bulk flows when answered slots are reconciled

**Common failure points:**  
- Current-page slot detection comes from `form#responseform input[name="slots"]`; if that input changes, bulk page marking breaks
- Final submit uses `markall=true`, so it does not rely on current-page slot extraction
- The learner may continue without marking if JS falls back to graceful degradation

**Best starting point for debugging:**  
- `handleNavigation()`
- `continueFinalSubmit()`
- `classes/external/mark_slots.php`
- `state_manager::mark_unanswered_slots()`

**Usually do not touch:**  
- `report.php`
- `version.php`

## Skip state clear-by-answer flow

**Trigger:**  
A previously skipped slot becomes answered locally or server reconciliation decides the slot is answered and cannot remain skipped.

**Files involved in order:**  
1. `amd/src/intentionalskip.js`
2. `classes/external/save_slot.php`
3. `classes/external/mark_slots.php`
4. `classes/local/state_manager.php`
5. `classes/event/skip_state_cleared_by_answer.php`

**Code objects involved:**  
- Input `change` listeners from `injectControls()`
- `slotHasLocalAnswer()`
- `saveSlot()`
- `state_manager::save_slot_state()`
- `state_manager::mark_unanswered_slots()`
- `state_manager::mark_all_unanswered_slots()`
- `state_manager::slot_is_answered()`
- `state_manager::trigger_event()`

**Step-by-step sequence:**  
1. JS adds `change` listeners to each question input on the page.
2. If a checkbox is currently checked and the slot now has a local answer, JS calls `saveSlot(slot, false, 'auto_cleared_after_answer')`.
3. On the server, `save_slot::execute()` allows `auto_cleared_after_answer` as a valid manual-source request.
4. `save_slot_state()` also forcibly rewrites any attempted `isskipped=true` on an answered slot into `isskipped=false` with source `auto_cleared_after_answer`.
5. During bulk flows, `mark_unanswered_slots()` and `mark_all_unanswered_slots()` clear answered slots instead of marking them skipped.
6. `write_state()` persists the non-skipped state.
7. `write_history()` records action `cleared_by_answer`.
8. `trigger_event()` fires `skip_state_cleared_by_answer` only when old and new state differ.

**Data involved:**  
- Attempt id
- Slot id
- Local answer presence
- Moodle question state
- `auto_cleared_after_answer` source
- Old skip state
- New skip state

**Database tables:**  
- `quizaccess_intskip_state`: read, insert, update
- `quizaccess_intskip_history`: insert

**Capabilities/security checks:**  
- Same as the save flows that invoke it
- No separate capability for clear-by-answer

**Events/logging:**  
- `quizaccess_intentionalskip\event\skip_state_cleared_by_answer`
- History action `cleared_by_answer`

**Common failure points:**  
- Local DOM answer detection and server `slot_is_answered()` may disagree temporarily
- Clear-by-answer can create or update a state row with `isskipped=0`; this is intentional server logging behavior
- If old and new states are already both zero, history is still written but the event is not triggered

**Best starting point for debugging:**  
- `slotHasLocalAnswer()` in JS
- `state_manager::slot_is_answered()`
- `state_manager::trigger_event()`

**Usually do not touch:**  
- `report.php`
- Capability declarations

## Report page access flow

**Trigger:**  
A teacher or manager clicks the report link shown by the plugin, or opens `report.php?cmid=...` directly.

**Files involved in order:**  
1. `rule.php`
2. `db/access.php`
3. `report.php`
4. `lang/en/quizaccess_intentionalskip.php`

**Code objects involved:**  
- `quizaccess_intentionalskip::description()`
- Capability `quizaccess/intentionalskip:viewreport`

**Step-by-step sequence:**  
1. On eligible quiz pages, `description()` checks `quizaccess/intentionalskip:viewreport`.
2. If allowed, it returns an HTML link to `/mod/quiz/accessrule/intentionalskip/report.php?cmid=...`.
3. `report.php` reads required param `cmid`.
4. It resolves the course module, course, quiz, and module context.
5. It calls `require_login()` and `require_capability('quizaccess/intentionalskip:viewreport', $context)`.
6. It then sets the page URL, context, CM, title, heading, and navbar.

**Data involved:**  
- Course module id
- Course id
- Quiz id
- Current user id

**Database tables:**  
- Core Moodle tables are read indirectly by `get_coursemodule_from_id()`, `get_course()`, and quiz lookup
- No plugin table is read until the report data-loading stage

**Capabilities/security checks:**  
- `quizaccess/intentionalskip:viewreport`
- `require_login()`

**Events/logging:**  
- No plugin event is triggered by opening the report page

**Common failure points:**  
- Link missing because `description()` capability check fails
- Direct URL access denied because the user lacks `viewreport`
- Confusion between `:use` and `:viewreport`; the report path uses `viewreport`

**Best starting point for debugging:**  
- `quizaccess_intentionalskip::description()`
- `report.php`
- `db/access.php`

**Usually do not touch:**  
- `amd/src/intentionalskip.js`
- `classes/external/*.php`

## Report data loading flow

**Trigger:**  
`report.php` has passed access checks and begins building the table contents.

**Files involved in order:**  
1. `report.php`
2. `db/install.xml`
3. `classes/local/state_manager.php`

**Code objects involved:**  
- `report.php` main script
- `state_manager::slot_is_answered()`
- `mod_quiz\quiz_attempt::create()`

**Step-by-step sequence:**  
1. `report.php` loads all `quiz_attempts` rows for the quiz.
2. It loads all `quizaccess_intskip_state` rows for the quiz.
3. It groups plugin state rows by `attemptid` and `slot`.
4. It creates an `html_table` with fixed report columns.
5. For each quiz attempt, it creates a `quiz_attempt` object.
6. Unless URL param `includepreviews` is truthy, preview attempts are skipped.
7. It loads the attempt owner with `core_user::get_user()`.
8. It iterates each real slot and looks up any corresponding plugin state row.
9. It calculates the display values and appends a row to the HTML table.

**Data involved:**  
- Course module id
- Quiz id
- Attempt ids
- Attempt number
- User id
- User fullname
- Slot id
- Question number
- Plugin state row
- Preview flag

**Database tables:**  
- Core `quiz_attempts`: read
- Core `quiz`: read
- Core `course`: read
- Core `course_modules`: read
- Core `user`: read
- `quizaccess_intskip_state`: read
- `quizaccess_intskip_history`: not read by the report

**Capabilities/security checks:**  
- `quizaccess/intentionalskip:viewreport` already enforced before data loading

**Events/logging:**  
- No plugin event is triggered by report data loading

**Common failure points:**  
- Performance issues because all attempts and all plugin state rows for the quiz are loaded at once
- Preview inclusion is controlled by request param `includepreviews`, not by quiz config `excludepreviews`
- Missing rows usually mean the state was never saved, not that the report inserts data

**Best starting point for debugging:**  
- `report.php`
- Raw contents of `quizaccess_intskip_state`

**Usually do not touch:**  
- `amd/src/intentionalskip.js` unless the save path itself is suspected
- Privacy provider

## Report skipped/answered/status calculation flow

**Trigger:**  
`report.php` is preparing each per-slot row for display.

**Files involved in order:**  
1. `report.php`
2. `classes/local/state_manager.php`
3. `lang/en/quizaccess_intentionalskip.php`

**Code objects involved:**  
- `state_manager::slot_is_answered()`
- `quiz_attempt::get_question_status()`
- `quiz_attempt_state()`

**Step-by-step sequence:**  
1. For each real slot in an attempt, `report.php` retrieves any saved plugin state row for that `attemptid + slot`.
2. It calls `state_manager::slot_is_answered()` to decide whether Moodle considers the slot answered.
3. It sets `$skipped` only when the slot is not answered and the saved row exists with `isskipped=1`.
4. If `$skipped` is true, the report shows string `skipped`.
5. Otherwise, if `$answered` is true, the report shows string `answered`.
6. Otherwise it shows string `blanknotconfirmed`.
7. It also shows the raw saved `source`, saved time, preview flag, Moodle attempt status, and Moodle question status.

**Data involved:**  
- Attempt status
- Slot id
- Moodle question state
- Plugin `isskipped`
- Plugin `source`
- Plugin `timemodified`
- Preview flag

**Database tables:**  
- `quizaccess_intskip_state`: read

**Capabilities/security checks:**  
- No new checks at this row-calculation stage

**Events/logging:**  
- No event is triggered

**Common failure points:**  
- A saved skip row exists, but the report still shows Answered because `slot_is_answered()` overrides skip display
- `blanknotconfirmed` means there is no active skip flag and Moodle does not consider the slot answered; it does not infer intentional skip
- Teachers may expect history-based reporting, but `report.php` only reads current state, not `quizaccess_intskip_history`

**Best starting point for debugging:**  
- `state_manager::slot_is_answered()`
- The `$skipped` calculation in `report.php`

**Usually do not touch:**  
- Event classes
- `db/services.php`

## Capability/permission flow

**Trigger:**  
Any runtime path needs to decide whether a user may use the learner UI, call AJAX services, or open the report.

**Files involved in order:**  
1. `db/access.php`
2. `db/services.php`
3. `rule.php`
4. `classes/local/state_manager.php`
5. `report.php`

**Code objects involved:**  
- Capability `quizaccess/intentionalskip:use`
- Capability `quizaccess/intentionalskip:viewreport`
- Capability `quizaccess/intentionalskip:exportreport`
- Capability `quizaccess/intentionalskip:manage`
- `quizaccess_intentionalskip::description()`
- `quizaccess_intentionalskip::setup_attempt_page()`
- `state_manager::validate_attempt()`

**Step-by-step sequence:**  
1. `db/access.php` declares four module-context capabilities.
2. `db/services.php` registers all AJAX services with capability `quizaccess/intentionalskip:use`.
3. `setup_attempt_page()` requires `:use` before bootstrapping the learner UI.
4. `description()` requires `:viewreport` before showing the report link.
5. `report.php` requires `:viewreport` for direct access.
6. `validate_attempt()` enforces session, login, context validation, quiz enablement, and access mode.
7. For attempt owners, `validate_attempt()` requires `mod/quiz:attempt` for non-preview users and then `quizaccess/intentionalskip:use`.
8. For non-owner reads, `validate_attempt()` requires `allowreview`, `is_review_allowed()`, and `quizaccess/intentionalskip:viewreport`.
9. `:exportreport` and `:manage` are declared but no runtime checks in this repository use them.

**Data involved:**  
- User id
- Attempt owner id
- Course module id
- Context id
- Attempt review/open state
- Quiz enabled flag

**Database tables:**  
- None directly in the capability declaration file
- Indirect core reads occur during attempt and context validation

**Capabilities/security checks:**  
- `quizaccess/intentionalskip:use`
- `quizaccess/intentionalskip:viewreport`
- `mod/quiz:attempt`
- `require_sesskey()`
- `require_login()`
- `validate_context()`
- `is_review_allowed()`

**Events/logging:**  
- No plugin event is triggered by permission checks alone

**Common failure points:**  
- Review-read expectations can be wrong because `get_state` combines service-level `:use` metadata with a deeper non-owner review branch in `validate_attempt(..., false, true)`
- `:exportreport` and `:manage` look important but are currently unused in runtime code
- Preview attempts skip `mod/quiz:attempt` for owners

**Best starting point for debugging:**  
- `db/access.php`
- `state_manager::validate_attempt()`
- `db/services.php`

**Usually do not touch:**  
- `amd/src/intentionalskip.js` unless the issue is only UI hiding after valid access

## Moodle event/logging flow

**Trigger:**  
Any server-side save path reaches `state_manager::write_state()`.

**Files involved in order:**  
1. `classes/local/state_manager.php`
2. `classes/event/skip_state_base.php`
3. `classes/event/question_marked_skipped.php`
4. `classes/event/question_unmarked_skipped.php`
5. `classes/event/page_unanswered_marked_skipped.php`
6. `classes/event/final_unanswered_marked_skipped.php`
7. `classes/event/skip_state_cleared_by_answer.php`
8. `lang/en/quizaccess_intentionalskip.php`

**Code objects involved:**  
- `state_manager::write_state()`
- `state_manager::write_history()`
- `state_manager::trigger_event()`
- `skip_state_base::create_from_state()`
- Concrete event classes listed above

**Step-by-step sequence:**  
1. A save path calls `write_state()` with slot, state, and source.
2. `write_state()` inserts or updates the current-state row.
3. `write_history()` appends an audit row to `quizaccess_intskip_history` with an action derived from source and state transition.
4. `trigger_event()` decides which Moodle event class to use.
5. `SOURCE_CLEARED_BY_ANSWER` triggers `skip_state_cleared_by_answer` only if old and new states differ.
6. `SOURCE_FINAL_CONFIRMATION` with `newstate=true` triggers `final_unanswered_marked_skipped`.
7. `SOURCE_NEXT_CONFIRMATION` with `newstate=true` triggers `page_unanswered_marked_skipped`.
8. Otherwise, if old and new states differ, normal manual transitions trigger `question_marked_skipped` or `question_unmarked_skipped`.
9. `skip_state_base::create_from_state()` packages context, course id, object id, related user id, and `other` metadata including `cmid`, `quizid`, `attemptid`, `questionattemptid`, `slot`, `isskipped`, `source`, `ispreview`.
10. Event names come from language strings in `lang/en/quizaccess_intentionalskip.php`.

**Data involved:**  
- State row id
- Attempt id
- Question attempt id
- Slot id
- Old state
- New state
- Source
- Preview flag
- Related user id

**Database tables:**  
- `quizaccess_intskip_state`: inserted/updated and used as event `objecttable`
- `quizaccess_intskip_history`: inserted

**Capabilities/security checks:**  
- No event-specific capability check
- Events depend on the validated save path that reached `write_state()`

**Events/logging:**  
- `question_marked_skipped`
- `question_unmarked_skipped`
- `page_unanswered_marked_skipped`
- `final_unanswered_marked_skipped`
- `skip_state_cleared_by_answer`
- Review URL for events is `/mod/quiz/review.php?attempt=...`

**Common failure points:**  
- Developers check Moodle logs but forget that the history table is written separately and more consistently
- No event fires when old and new states are identical, except that history can still be written
- Final/page events depend on source strings, not just on the fact that many rows were written

**Best starting point for debugging:**  
- `state_manager::trigger_event()`
- `state_manager::write_history()`
- `skip_state_base::create_from_state()`

**Usually do not touch:**  
- `report.php`
- `version.php`

## Privacy API export/delete flow

**Trigger:**  
Moodle privacy tooling asks the plugin what user data it stores, which contexts contain it, or asks the plugin to export/delete that data.

**Files involved in order:**  
1. `classes/privacy/provider.php`
2. `db/install.xml`
3. `lang/en/quizaccess_intentionalskip.php`

**Code objects involved:**  
- `provider::get_metadata()`
- `provider::get_contexts_for_userid()`
- `provider::export_user_data()`
- `provider::delete_data_for_all_users_in_context()`
- `provider::delete_data_for_user()`

**Step-by-step sequence:**  
1. `get_metadata()` declares selected plugin data fields stored in `quizaccess_intskip_state` and `quizaccess_intskip_history`.
2. `get_contexts_for_userid()` finds module contexts by joining core `context`, `course_modules`, and `quizaccess_intskip_state`.
3. `export_user_data()` currently does not export anything; it contains only a placeholder comment.
4. `delete_data_for_all_users_in_context()` deletes both plugin tables by `cmid` when the context is a module context.
5. `delete_data_for_user()` deletes both tables by `cmid + userid` for approved module contexts when the component matches `quizaccess_intentionalskip`.

**Data involved:**  
- User id
- Context id
- Context level
- Course module id
- Attempt id
- Slot id
- Skip source

**Database tables:**  
- `quizaccess_intskip_state`: declared, searched for contexts, deleted
- `quizaccess_intskip_history`: declared, deleted

**Capabilities/security checks:**  
- No plugin capability checks are visible in the privacy provider
- Moodle privacy subsystem determines when these methods are called

**Events/logging:**  
- No plugin event is triggered by privacy export/delete operations

**Common failure points:**  
- Export requests appear "broken" because `export_user_data()` is intentionally not implemented
- Metadata coverage is partial because `get_metadata()` lists selected fields rather than every stored column
- Context discovery only joins `quizaccess_intskip_state`; history-only data would not be found from this SQL
- Deletion is scoped by `cmid`, so context mapping mistakes would delete too much or too little

**Best starting point for debugging:**  
- `classes/privacy/provider.php`

**Usually do not touch:**  
- `amd/src/intentionalskip.js`
- `report.php`

## Language string usage flow

**Trigger:**  
PHP or JavaScript needs human-readable UI text, report labels, capability names, event names, or privacy metadata text.

**Files involved in order:**  
1. `lang/en/quizaccess_intentionalskip.php`
2. `rule.php`
3. `report.php`
4. `classes/event/*.php`
5. `classes/privacy/provider.php`
6. `amd/src/intentionalskip.js`

**Code objects involved:**  
- `get_string()`
- `strings_for_js()`
- JS helper `getString()`
- Event class `get_name()` implementations via language keys
- Privacy metadata keys referenced in `provider::get_metadata()`

**Step-by-step sequence:**  
1. PHP files call `get_string(..., 'quizaccess_intentionalskip')` for settings labels, report headings, statuses, capability labels, and event names.
2. `rule.php` explicitly registers a subset of language keys for JavaScript with `strings_for_js()`.
3. `amd/src/intentionalskip.js` calls `M.util.get_string()` through helper `getString()`.
4. `report.php` uses language keys for column headings and skip/answer status text.
5. Event classes resolve their display names from language keys such as `eventquestionmarkedskipped`.
6. The privacy provider uses `privacy:metadata:*` keys for metadata descriptions.

**Data involved:**  
- String keys
- Component name `quizaccess_intentionalskip`

**Database tables:**  
- None

**Capabilities/security checks:**  
- None in the string lookup itself

**Events/logging:**  
- Event display names depend on language strings

**Common failure points:**  
- A key exists in JS but was not included in `strings_for_js()`
- A PHP file calls a key that is missing from the language file
- Capability or event label changes break only UI text, not runtime logic

**Best starting point for debugging:**  
- `lang/en/quizaccess_intentionalskip.php`
- Then the specific caller file

**Usually do not touch:**  
- Database schema
- `classes/local/state_manager.php` for a pure string bug

## AMD JavaScript source-to-build flow

**Trigger:**  
A developer changes the learner UI logic, or a debugging session needs to confirm whether the served AMD module matches the source.

**Files involved in order:**  
1. `amd/src/intentionalskip.js`
2. `amd/build/intentionalskip.min.js`
3. `rule.php`

**Code objects involved:**  
- AMD module `quizaccess_intentionalskip/intentionalskip`
- AMD export `init()`

**Step-by-step sequence:**  
1. The human-maintained source of the learner UI is `amd/src/intentionalskip.js`.
2. Moodle serves AMD modules from the built artifact in `amd/build/`, here `amd/build/intentionalskip.min.js`.
3. `rule.php` loads module name `quizaccess_intentionalskip/intentionalskip`, which resolves to the built artifact, not directly to `amd/src/`.
4. The build artifact is a minified version of the same module and should mirror source behavior.
5. The repository documentation confirms that source changes should be reflected in the build artifact, but no build command or build script is included in this repository.
6. If source and built file diverge, Moodle can behave differently from what `amd/src/intentionalskip.js` appears to say.

**Data involved:**  
- AMD module name
- JS config object from `rule.php`
- Built module contents

**Database tables:**  
- None

**Capabilities/security checks:**  
- None in the source-to-build step itself
- Runtime security still happens in PHP services after the module runs

**Events/logging:**  
- No plugin event is triggered by the build flow

**Common failure points:**  
- `amd/src/intentionalskip.js` edited but `amd/build/intentionalskip.min.js` left stale
- A production-like Moodle environment uses the built file, masking the intended source change
- Developers patch only the minified file, creating future drift
- Exact build procedure is unclear from this repository because no build helper or script is present here

**Best starting point for debugging:**  
- Compare `amd/src/intentionalskip.js` and `amd/build/intentionalskip.min.js`
- Confirm what `rule.php` is loading

**Usually do not touch:**  
- `amd/build/intentionalskip.min.js` as a primary source of truth
- Server-side PHP files for a pure build-drift issue
