# CODE_OBJECT_INDEX.md

## Repository summary

This repository is a Moodle quiz access rule subplugin with component name `quizaccess_intentionalskip`. It appears to add per-question "intentional skip" tracking during quiz attempts, expose AJAX endpoints for reading and saving skip state, record audit history, show a teacher report, and provide privacy metadata/deletion support with export currently unimplemented.

## PHP classes

### `quizaccess_intentionalskip`

- Namespace: none
- File path: `rule.php`
- Extends / implements: extends `mod_quiz\local\access_rule_base`
- Main purpose: main quiz access rule class; provides quiz settings, persists plugin config, exposes a report link, and bootstraps the learner AMD module.
- Important Moodle APIs used: quiz access rule API, `mod_quiz_mod_form`, `MoodleQuickForm`, DB API, capability API, page requirements AMD bootstrap, language strings, `mod_quiz\quiz_attempt`.
- Connected files: `db/install.xml`, `db/access.php`, `report.php`, `amd/src/intentionalskip.js`, `amd/build/intentionalskip.min.js`, `classes/local/state_manager.php`, `lang/en/quizaccess_intentionalskip.php`
- Risk if modified: High

Methods:

- `make`
  - Visibility: `public static`
  - Parameters: `quiz_settings $quizobj, $timenow, $canignoretimelimits`
  - Return type: none declared, effectively `self|null`
  - Purpose: only enables this access rule when quiz field `intentionalskip_enabled` is truthy.
  - Important side effects: none
  - Database reads/writes: none directly
  - Events triggered: none
  - Capabilities/security checks: none

- `add_settings_form_fields`
  - Visibility: `public static`
  - Parameters: `mod_quiz_mod_form $quizform, MoodleQuickForm $mform`
  - Return type: none declared
  - Purpose: adds quiz settings fields `intentionalskip_enabled`, `intentionalskip_showcheckbox`, `intentionalskip_warnonnext`, `intentionalskip_allowcontinue`, `intentionalskip_ajaxsave`, `intentionalskip_excludepreviews`.
  - Important side effects: changes quiz settings form display; uses `hideIf` rules tied to enablement
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none visible in this method

- `save_settings`
  - Visibility: `public static`
  - Parameters: `$quiz`
  - Return type: none declared
  - Purpose: inserts or updates row in `quizaccess_intskip_config` for one quiz.
  - Important side effects: writes `enabled`, UI/config flags, timestamps, `usermodified`
  - Database reads/writes: reads `quizaccess_intskip_config`; writes via `update_record` or `insert_record`
  - Events triggered: none
  - Capabilities/security checks: none explicit; depends on Moodle quiz settings workflow

- `delete_settings`
  - Visibility: `public static`
  - Parameters: `$quiz`
  - Return type: none declared
  - Purpose: removes config row when the quiz is deleted.
  - Important side effects: deletes config for `quizid`
  - Database reads/writes: deletes from `quizaccess_intskip_config`
  - Events triggered: none
  - Capabilities/security checks: none explicit

- `get_settings_sql`
  - Visibility: `public static`
  - Parameters: `$quizid`
  - Return type: none declared, effectively array of select/join/params fragments
  - Purpose: provides SQL fragments so plugin quiz settings load with quiz records.
  - Important side effects: none
  - Database reads/writes: none directly; defines left join on `quizaccess_intskip_config`
  - Events triggered: none
  - Capabilities/security checks: none

- `description`
  - Visibility: `public`
  - Parameters: none
  - Return type: none declared, effectively `string`
  - Purpose: returns report link HTML for users who can view the report.
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: checks `quizaccess/intentionalskip:viewreport`

- `setup_attempt_page`
  - Visibility: `public`
  - Parameters: `$page`
  - Return type: none declared
  - Purpose: registers JS strings and calls AMD module `quizaccess_intentionalskip/intentionalskip.init(...)`.
  - Important side effects: The AMD/module behavior covers quiz attempt-page, summary/final-submit, and review-related contexts. Exact invocation depends on Moodle core quiz access-rule hook/page behavior; computes readonly mode from URL and attempt state
  - Database reads/writes: none directly
  - Events triggered: none
  - Capabilities/security checks: checks `quizaccess/intentionalskip:use`

### `quizaccess_intentionalskip\local\state_manager`

- Namespace: `quizaccess_intentionalskip\local`
- File path: `classes/local/state_manager.php`
- Extends / implements: none
- Main purpose: central state validation, state reads, state writes, history writes, answered reconciliation, and event triggering.
- Important Moodle APIs used: `mod_quiz\quiz_attempt`, `question_state`, DB API, sesskey/login/context validation, capability API, external API context validation, Moodle exceptions.
- Connected files: `classes/external/get_state.php`, `classes/external/save_slot.php`, `classes/external/mark_slots.php`, `classes/event/*`, `report.php`, `rule.php`, `db/install.xml`
- Risk if modified: High

Constants:

- `CLIENT_VERSION = 2026052004`
- `SOURCE_MANUAL_CHECKBOX = 'manual_checkbox'`
- `SOURCE_MANUAL_UNCHECK = 'manual_uncheck'`
- `SOURCE_NEXT_CONFIRMATION = 'next_page_confirmation'`
- `SOURCE_FINAL_CONFIRMATION = 'final_submit_confirmation'`
- `SOURCE_CLEARED_BY_ANSWER = 'auto_cleared_after_answer'`

Methods:

- `validate_client_version`
  - Visibility: `public static`
  - Parameters: `int $clientversion`
  - Return type: `void`
  - Purpose: rejects stale browser clients if JS `clientversion` does not match server constant.
  - Important side effects: throws `moodle_exception('errorstaleclient')`
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: compatibility gate for AJAX callers

- `validate_manual_source`
  - Visibility: `public static`
  - Parameters: `string $source`
  - Return type: `void`
  - Purpose: restricts one-slot save sources to manual check, manual uncheck, or auto-clear-by-answer.
  - Important side effects: throws `moodle_exception('errorinvalidsource')`
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: input validation

- `validate_attempt`
  - Visibility: `public static`
  - Parameters: `int $attemptid, int $cmid, bool $requireopen = true, bool $allowreview = false`
  - Return type: `array`
  - Purpose: validates sesskey, attempt ownership/context, quiz enablement, optional review access, and optional open/in-progress requirement.
  - Important side effects: requires sesskey and login; throws on invalid attempt, mismatched cmid, unauthorized access, disabled plugin, or closed attempt
  - Database reads/writes: indirect reads through `quiz_attempt::create`
  - Events triggered: none
  - Capabilities/security checks: `mod/quiz:attempt`, `quizaccess/intentionalskip:use`, `quizaccess/intentionalskip:viewreport`

- `get_attempt_state`
  - Visibility: `public static`
  - Parameters: `quiz_attempt $attemptobj`
  - Return type: `array`
  - Purpose: returns per-real-slot state payload with `slot`, `answered`, `skipped`, `source`, `timemodified`, `status`.
  - Important side effects: none
  - Database reads/writes: reads `quizaccess_intskip_state`
  - Events triggered: none
  - Capabilities/security checks: none internally; relies on validated caller

- `save_slot_state`
  - Visibility: `public static`
  - Parameters: `quiz_attempt $attemptobj, int $slot, bool $isskipped, string $source`
  - Return type: `stdClass`
  - Purpose: saves one slot's skip state, auto-clearing if Moodle already considers it answered.
  - Important side effects: may rewrite requested `isskipped=true` to `false` and source to `auto_cleared_after_answer`
  - Database reads/writes: indirect via `write_state`
  - Events triggered: indirect via `write_state`
  - Capabilities/security checks: validates slot exists and is a real question

- `mark_unanswered_slots`
  - Visibility: `public static`
  - Parameters: `quiz_attempt $attemptobj, array $slots, string $source`
  - Return type: `array`
  - Purpose: bulk-mark selected unanswered slots as skipped.
  - Important side effects: clears any answered slot encountered; skips already-skipped slots; returns only newly marked slots
  - Database reads/writes: reads current state via `slot_is_currently_skipped`; writes via `write_state`
  - Events triggered: via `write_state`
  - Capabilities/security checks: filters invalid/non-real slots

- `mark_all_unanswered_slots`
  - Visibility: `public static`
  - Parameters: `quiz_attempt $attemptobj, string $source`
  - Return type: `array`
  - Purpose: bulk-mark every currently unanswered real question in the attempt.
  - Important side effects: auto-clears stale skip flags on answered slots before bulk mark
  - Database reads/writes: reads/writes state via helper methods
  - Events triggered: via `write_state`
  - Capabilities/security checks: none beyond validated caller

- `slot_is_answered`
  - Visibility: `public static`
  - Parameters: `quiz_attempt $attemptobj, int $slot`
  - Return type: `bool`
  - Purpose: treats any question state other than `question_state::$todo` and `question_state::$invalid` as answered.
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `slot_exists`
  - Visibility: `private static`
  - Parameters: `quiz_attempt $attemptobj, int $slot`
  - Return type: `bool`
  - Purpose: checks if a slot belongs to the attempt.
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `slot_is_currently_skipped`
  - Visibility: `private static`
  - Parameters: `quiz_attempt $attemptobj, int $slot`
  - Return type: `bool`
  - Purpose: tests whether saved current state has `isskipped = 1` for one slot.
  - Important side effects: none
  - Database reads/writes: reads `quizaccess_intskip_state`
  - Events triggered: none
  - Capabilities/security checks: none

- `write_state`
  - Visibility: `private static`
  - Parameters: `quiz_attempt $attemptobj, int $slot, bool $isskipped, string $source`
  - Return type: `stdClass`
  - Purpose: inserts or updates current state row, then writes history and triggers event.
  - Important side effects: sanitizes source, stores `questionattemptid`, preview flag, `lastsavedby`; always writes history row even when state may already exist
  - Database reads/writes: reads `quizaccess_intskip_state`; writes `quizaccess_intskip_state`
  - Events triggered: calls `trigger_event`
  - Capabilities/security checks: throws `coding_exception` if source is empty after cleaning

- `write_history`
  - Visibility: `private static`
  - Parameters: `stdClass $state, int $oldstate, int $newstate, string $source, int $now`
  - Return type: `void`
  - Purpose: inserts audit row with action name derived from source/state transition.
  - Important side effects: writes `action` values `marked`, `unmarked`, `final_submit_marked`, `page_marked`, or `cleared_by_answer`
  - Database reads/writes: writes `quizaccess_intskip_history`
  - Events triggered: none
  - Capabilities/security checks: none

- `trigger_event`
  - Visibility: `private static`
  - Parameters: `quiz_attempt $attemptobj, stdClass $state, int $oldstate, int $newstate, string $source`
  - Return type: `void`
  - Purpose: maps saved transitions to concrete Moodle event classes.
  - Important side effects: triggers one of five event classes; suppresses event when old/new state are identical except special-source handling
  - Database reads/writes: none
  - Events triggered: `skip_state_cleared_by_answer`, `final_unanswered_marked_skipped`, `page_unanswered_marked_skipped`, `question_marked_skipped`, `question_unmarked_skipped`
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\external\get_state`

- Namespace: `quizaccess_intentionalskip\external`
- File path: `classes/external/get_state.php`
- Extends / implements: extends `core_external\external_api`
- Main purpose: AJAX read endpoint returning per-slot skip/answered state for an attempt.
- Important Moodle APIs used: external function parameter/return structures, parameter validation
- Connected files: `db/services.php`, `amd/src/intentionalskip.js`, `classes/local/state_manager.php`
- Risk if modified: High

Methods:

- `execute_parameters`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `external_function_parameters`
  - Purpose: declares `attemptid`, `cmid`, `clientversion`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: parameter validation only

- `execute`
  - Visibility: `public static`
  - Parameters: `int $attemptid, int $cmid, int $clientversion = 0`
  - Return type: `array`
  - Purpose: validates params, validates client version, validates attempt in read/review mode, returns slot states.
  - Important side effects: requires valid sesskey through `state_manager::validate_attempt`
  - Database reads/writes: indirect read through `state_manager::get_attempt_state`
  - Events triggered: none
  - Capabilities/security checks: service registration requires `quizaccess/intentionalskip:use`; runtime code also calls `validate_attempt(..., false, true)`, which contains a separate non-owner review branch guarded by review/report checks

- `execute_returns`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `external_single_structure`
  - Purpose: declares response schema for slot state list.
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: response validation contract

### `quizaccess_intentionalskip\external\save_slot`

- Namespace: `quizaccess_intentionalskip\external`
- File path: `classes/external/save_slot.php`
- Extends / implements: extends `core_external\external_api`
- Main purpose: AJAX write endpoint for one-slot skip toggles and auto-clear requests.
- Important Moodle APIs used: external API parameter/return structures, parameter validation
- Connected files: `db/services.php`, `amd/src/intentionalskip.js`, `classes/local/state_manager.php`
- Risk if modified: High

Methods:

- `execute_parameters`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `external_function_parameters`
  - Purpose: declares `attemptid`, `cmid`, `slot`, `isskipped`, `source`, `clientversion`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: parameter validation only

- `execute`
  - Visibility: `public static`
  - Parameters: `int $attemptid, int $cmid, int $slot, bool $isskipped, string $source, int $clientversion = 0`
  - Return type: `array`
  - Purpose: validates params/source/version/attempt and persists one slot state.
  - Important side effects: writes state/history; may auto-clear skip if slot is already answered
  - Database reads/writes: indirect write via `state_manager::save_slot_state`
  - Events triggered: indirect via `state_manager`
  - Capabilities/security checks: `state_manager::validate_attempt` with open-attempt requirement; `validate_manual_source`

- `execute_returns`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `external_single_structure`
  - Purpose: declares response with `status`, `slot`, `skipped`, `source`, `timemodified`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: response validation contract

### `quizaccess_intentionalskip\external\mark_slots`

- Namespace: `quizaccess_intentionalskip\external`
- File path: `classes/external/mark_slots.php`
- Extends / implements: extends `core_external\external_api`
- Main purpose: AJAX write endpoint for bulk marking unanswered slots skipped from Next-page or final-submit flows.
- Important Moodle APIs used: external API parameter/return structures, `invalid_parameter_exception`
- Connected files: `db/services.php`, `amd/src/intentionalskip.js`, `classes/local/state_manager.php`
- Risk if modified: High

Methods:

- `execute_parameters`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `external_function_parameters`
  - Purpose: declares `attemptid`, `cmid`, `slots[]`, `source`, `markall`, `clientversion`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: parameter validation only

- `execute`
  - Visibility: `public static`
  - Parameters: `int $attemptid, int $cmid, array $slots, string $source, bool $markall = false, int $clientversion = 0`
  - Return type: `array`
  - Purpose: validates params/version/attempt, restricts sources to next-page or final-submit confirmation, then bulk-writes states.
  - Important side effects: may mark all unanswered slots if `markall` is true
  - Database reads/writes: indirect writes via `state_manager::mark_all_unanswered_slots` or `mark_unanswered_slots`
  - Events triggered: indirect via `state_manager`
  - Capabilities/security checks: `validate_attempt`; source whitelist with `invalid_parameter_exception`

- `execute_returns`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `external_single_structure`
  - Purpose: declares response with `status` and `markedslots[]`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: response validation contract

### `quizaccess_intentionalskip\event\skip_state_base`

- Namespace: `quizaccess_intentionalskip\event`
- File path: `classes/event/skip_state_base.php`
- Extends / implements: extends `core\event\base`
- Main purpose: shared event payload builder and metadata for all intentional-skip state-change events.
- Important Moodle APIs used: Events API, restore mapping API, `moodle_url`
- Connected files: `classes/event/question_marked_skipped.php`, `question_unmarked_skipped.php`, `page_unanswered_marked_skipped.php`, `final_unanswered_marked_skipped.php`, `skip_state_cleared_by_answer.php`, `classes/local/state_manager.php`
- Risk if modified: Medium

Methods:

- `create_from_state`
  - Visibility: `public static`
  - Parameters: `quiz_attempt $attemptobj, stdClass $state`
  - Return type: `self`
  - Purpose: creates event instance from one saved state row.
  - Important side effects: fills `other` with `cmid`, `quizid`, `attemptid`, `questionattemptid`, `slot`, `isskipped`, `source`, `ispreview`
  - Database reads/writes: none
  - Events triggered: creates event instance only
  - Capabilities/security checks: none

- `init`
  - Visibility: `protected`
  - Parameters: none
  - Return type: none declared
  - Purpose: sets `objecttable = quizaccess_intskip_state`, `crud = 'u'`, `edulevel = LEVEL_PARTICIPATING`
  - Important side effects: event metadata initialization
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `get_description`
  - Visibility: `public`
  - Parameters: none
  - Return type: none declared, effectively `string`
  - Purpose: describes actor, attempt id, slot, and source.
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `get_url`
  - Visibility: `public`
  - Parameters: none
  - Return type: none declared, effectively `moodle_url`
  - Purpose: links event to `/mod/quiz/review.php?attempt=...`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `get_objectid_mapping`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `array`
  - Purpose: restore mapping for event `objectid`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `get_other_mapping`
  - Visibility: `public static`
  - Parameters: none
  - Return type: `array`
  - Purpose: restore mapping for `cmid`, `quizid`, `attemptid`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\event\question_marked_skipped`

- Namespace: `quizaccess_intentionalskip\event`
- File path: `classes/event/question_marked_skipped.php`
- Extends / implements: extends `skip_state_base`
- Main purpose: event class for manual mark-skipped transition.
- Important Moodle APIs used: Events API, string API
- Connected files: `classes/local/state_manager.php`, `lang/en/quizaccess_intentionalskip.php`
- Risk if modified: Medium

Methods:

- `get_name`
  - Visibility: `public static`
  - Parameters: none
  - Return type: none declared
  - Purpose: returns string `eventquestionmarkedskipped`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\event\question_unmarked_skipped`

- Namespace: `quizaccess_intentionalskip\event`
- File path: `classes/event/question_unmarked_skipped.php`
- Extends / implements: extends `skip_state_base`
- Main purpose: event class for manual unmark-skipped transition.
- Important Moodle APIs used: Events API, string API
- Connected files: `classes/local/state_manager.php`, `lang/en/quizaccess_intentionalskip.php`
- Risk if modified: Medium

Methods:

- `get_name`
  - Visibility: `public static`
  - Parameters: none
  - Return type: none declared
  - Purpose: returns string `eventquestionunmarkedskipped`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\event\page_unanswered_marked_skipped`

- Namespace: `quizaccess_intentionalskip\event`
- File path: `classes/event/page_unanswered_marked_skipped.php`
- Extends / implements: extends `skip_state_base`
- Main purpose: event class for Next-page bulk marking.
- Important Moodle APIs used: Events API, string API
- Connected files: `classes/local/state_manager.php`, `classes/external/mark_slots.php`, `amd/src/intentionalskip.js`, `lang/en/quizaccess_intentionalskip.php`
- Risk if modified: Medium

Methods:

- `get_name`
  - Visibility: `public static`
  - Parameters: none
  - Return type: none declared
  - Purpose: returns string `eventpageunansweredmarkedskipped`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\event\final_unanswered_marked_skipped`

- Namespace: `quizaccess_intentionalskip\event`
- File path: `classes/event/final_unanswered_marked_skipped.php`
- Extends / implements: extends `skip_state_base`
- Main purpose: event class for final-submit bulk marking.
- Important Moodle APIs used: Events API, string API
- Connected files: `classes/local/state_manager.php`, `classes/external/mark_slots.php`, `amd/src/intentionalskip.js`, `lang/en/quizaccess_intentionalskip.php`
- Risk if modified: Medium

Methods:

- `get_name`
  - Visibility: `public static`
  - Parameters: none
  - Return type: none declared
  - Purpose: returns string `eventfinalunansweredmarkedskipped`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\event\skip_state_cleared_by_answer`

- Namespace: `quizaccess_intentionalskip\event`
- File path: `classes/event/skip_state_cleared_by_answer.php`
- Extends / implements: extends `skip_state_base`
- Main purpose: event class for skip removal caused by answered-state reconciliation.
- Important Moodle APIs used: Events API, string API
- Connected files: `classes/local/state_manager.php`, `amd/src/intentionalskip.js`, `lang/en/quizaccess_intentionalskip.php`
- Risk if modified: Medium

Methods:

- `get_name`
  - Visibility: `public static`
  - Parameters: none
  - Return type: none declared
  - Purpose: returns string `eventskipstateclearedbyanswer`
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

### `quizaccess_intentionalskip\privacy\provider`

- Namespace: `quizaccess_intentionalskip\privacy`
- File path: `classes/privacy/provider.php`
- Extends / implements: implements `core_privacy\local\metadata\provider`, `core_privacy\local\request\plugin\provider`
- Main purpose: declares stored user data, discovers contexts, and deletes plugin data for privacy requests.
- Important Moodle APIs used: Privacy metadata API, privacy contextlist API, approved contextlist API, DB API
- Connected files: `db/install.xml`, `lang/en/quizaccess_intentionalskip.php`, `classes/local/state_manager.php`
- Risk if modified: Medium

Methods:

- `get_metadata`
  - Visibility: `public static`
  - Parameters: `collection $collection`
  - Return type: `collection`
  - Purpose: declares selected user-data fields stored in `quizaccess_intskip_state` and `quizaccess_intskip_history`.
  - Important side effects: metadata coverage is partial rather than a column-for-column schema listing
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `get_contexts_for_userid`
  - Visibility: `public static`
  - Parameters: `int $userid`
  - Return type: `contextlist`
  - Purpose: finds module contexts where the user has rows in `quizaccess_intskip_state`.
  - Important side effects: does not search history-only records
  - Database reads/writes: reads via SQL join on `context`, `course_modules`, `quizaccess_intskip_state`
  - Events triggered: none
  - Capabilities/security checks: none

- `export_user_data`
  - Visibility: `public static`
  - Parameters: `approved_contextlist $contextlist`
  - Return type: none declared
  - Purpose: placeholder only; no export logic implemented.
  - Important side effects: none
  - Database reads/writes: none
  - Events triggered: none
  - Capabilities/security checks: none

- `delete_data_for_all_users_in_context`
  - Visibility: `public static`
  - Parameters: `\context $context`
  - Return type: none declared
  - Purpose: deletes plugin state/history rows for one module context.
  - Important side effects: returns early for non-module contexts
  - Database reads/writes: deletes from `quizaccess_intskip_history` and `quizaccess_intskip_state`
  - Events triggered: none
  - Capabilities/security checks: contextlevel check only

- `delete_data_for_user`
  - Visibility: `public static`
  - Parameters: `approved_contextlist $contextlist`
  - Return type: none declared
  - Purpose: deletes one user's plugin state/history within approved module contexts.
  - Important side effects: returns early if component does not match
  - Database reads/writes: deletes from `quizaccess_intskip_history` and `quizaccess_intskip_state`
  - Events triggered: none
  - Capabilities/security checks: component and contextlevel checks

## Non-class PHP functions

No standalone user-defined PHP functions were found in this repository. Runtime PHP outside classes exists in `report.php`, but it is script-level page controller code, not reusable functions.

## Moodle quiz access rule entry points

- `rule.php` -> `quizaccess_intentionalskip::make()`
  - Access-rule factory; enables the rule only when quiz setting `intentionalskip_enabled` is loaded onto the quiz.

- `rule.php` -> `quizaccess_intentionalskip::add_settings_form_fields()`
  - Quiz settings form hook; defines plugin configuration fields.

- `rule.php` -> `quizaccess_intentionalskip::save_settings()`
  - Quiz settings persistence hook; stores config in `quizaccess_intskip_config`.

- `rule.php` -> `quizaccess_intentionalskip::delete_settings()`
  - Quiz deletion hook; removes plugin config row.

- `rule.php` -> `quizaccess_intentionalskip::get_settings_sql()`
  - Quiz settings load hook; injects select/join fragments so plugin settings are attached to quiz objects.

- `rule.php` -> `quizaccess_intentionalskip::description()`
  - Access rule description/report-link integration point; outputs teacher-visible report link.

- `rule.php` -> `quizaccess_intentionalskip::setup_attempt_page()`
  - Registers JS strings and initializes the AMD module. The AMD/module behavior covers quiz attempt-page, summary/final-submit, and review-related contexts. Exact invocation depends on Moodle core quiz access-rule hook/page behavior.

- `report.php`
  - Teacher report page for per-attempt, per-question skip state.

## External / AJAX web service methods

Registered in `db/services.php`:

- `quizaccess_intentionalskip_get_state`
  - Class/method called: `quizaccess_intentionalskip\external\get_state::execute`
  - AJAX availability: `true`
  - Parameters: `attemptid`, `cmid`, optional `clientversion`
  - Return values: `slots[]` containing `slot`, `answered`, `skipped`, `source`, `timemodified`, `status`
  - Required capabilities, if visible: service declares `quizaccess/intentionalskip:use`; runtime validation may also allow review/report-style reads through `validate_attempt(..., false, true)` where applicable; non-owner review reads therefore require both the service capability gate and the runtime validation path
  - Data validation performed: `validate_parameters`, client version check, sesskey/login/context validation, cmid match, plugin enabled check
  - Files involved: `db/services.php`, `classes/external/get_state.php`, `classes/local/state_manager.php`, `amd/src/intentionalskip.js`
  - Common failure points: stale client version, sesskey failure, invalid/mismatched attempt, plugin disabled, review not allowed

- `quizaccess_intentionalskip_save_slot`
  - Class/method called: `quizaccess_intentionalskip\external\save_slot::execute`
  - AJAX availability: `true`
  - Parameters: `attemptid`, `cmid`, `slot`, `isskipped`, `source`, optional `clientversion`
  - Return values: `status`, `slot`, `skipped`, `source`, `timemodified`
  - Required capabilities, if visible: service declares `quizaccess/intentionalskip:use`; runtime requires attempt owner or preview user path plus plugin use capability
  - Data validation performed: parameter validation, client version check, manual-source whitelist, sesskey/login/context validation, open/in-progress attempt check, slot existence/real-question check
  - Files involved: `db/services.php`, `classes/external/save_slot.php`, `classes/local/state_manager.php`, `amd/src/intentionalskip.js`
  - Common failure points: source not in manual whitelist, answered slot being auto-cleared instead of saved skipped, closed attempt, invalid slot, stale client

- `quizaccess_intentionalskip_mark_slots`
  - Class/method called: `quizaccess_intentionalskip\external\mark_slots::execute`
  - AJAX availability: `true`
  - Parameters: `attemptid`, `cmid`, `slots[]`, `source`, optional `markall`, optional `clientversion`
  - Return values: `status`, `markedslots[]`
  - Required capabilities, if visible: service declares `quizaccess/intentionalskip:use`; runtime requires validated open attempt
  - Data validation performed: parameter validation, client version check, source restricted to `next_page_confirmation` or `final_submit_confirmation`, sesskey/login/context validation, open/in-progress attempt check
  - Files involved: `db/services.php`, `classes/external/mark_slots.php`, `classes/local/state_manager.php`, `amd/src/intentionalskip.js`
  - Common failure points: invalid source, stale client, closed attempt, `markall` path marking based on live Moodle answered state rather than client-side state

## AMD JavaScript functions

### Module `quizaccess_intentionalskip/intentionalskip`

- Source file: `amd/src/intentionalskip.js`
- Build file: `amd/build/intentionalskip.min.js`
- Matching build/minified file under `amd/build`: yes
- Whether it appears generated: yes; it is a minified AMD wrapper matching the source module logic
- Public exports: `init`

Internal functions:

- `getString(key)` -> reads Moodle strings using `M.util.get_string`
- `call(methodname, args)` -> wraps `core/ajax` service call
- `isStaleClientError(error)` -> checks `error.errorcode === 'errorstaleclient'`
- `showRefreshRequired()` -> modal forcing user back on stale-client state
- `getSlotsFromForm()` -> parses `form#responseform input[name="slots"]`
- `getQuestionElement(slot)` -> maps slot to `.que` DOM block using slot-aware selectors
- `getQuestionInputs(slot)` -> collects active question inputs excluding plugin controls, hidden/button fields, `slots`, `sesskey`
- `slotHasLocalAnswer(slot)` -> local heuristic for whether the slot currently has an answer in DOM
- `currentPageUnansweredSlots()` -> unanswered slots on current page
- `currentPageAnsweredSkippedSlots()` -> checked-skip slots that also look locally answered
- `setStatus(slot, text, statusClass)` -> updates status text span
- `setChecked(slot, checked)` -> updates checkbox UI
- `saveSlot(slot, isskipped, source)` -> calls `quizaccess_intentionalskip_save_slot`
- `markSlots(slots, source, markall)` -> calls `quizaccess_intentionalskip_mark_slots`
- `loadState()` -> calls `quizaccess_intentionalskip_get_state`
- `injectStyle()` -> injects inline CSS for controls/dialogs
- `showChoiceDialog(title, message, buttons)` -> renders modal dialog
- `addSubmitIntentAndSubmit(form, buttonName)` -> appends hidden submit-intent field and submits form
- `confirmSkipTrackingUnavailable(submitLabel)` -> fallback dialog when AJAX fails
- `confirmAnsweredSkipped()` -> dialog for answered+skipped conflict
- `clearAnsweredSkippedSlots(slots)` -> auto-clears skip flags on answered slots
- `handleNavigation(event)` -> intercepts Next-page submit flow
- `continueFinalSubmit(button)` -> handles final submit after state refresh and unanswered check
- `handleFinalSubmit(event)` -> intercepts final submission button
- `injectControls(slots)` -> inserts controls/read-only labels into question blocks
- `enhanceSummary(slots)` -> rewrites summary rows for blank/skipped unanswered slots
- `registerEvents()` -> adds document click listeners for navigation/final-submit handlers
- `init(initialConfig)` -> entry point invoked from PHP

Event listeners:

- `document.addEventListener('click', handleNavigation, true)`
- `document.addEventListener('click', handleFinalSubmit, true)`
- Per-dialog button `click`
- Per-slot checkbox `change`
- Per-question input `change` for auto-clear-by-answer behavior

DOM selectors used:

- `form#responseform input[name="slots"]`
- `.que`
- ``[name*=":${slot}_"], [id*=":${slot}_"], [for*=":${slot}_"]``
- `input, textarea, select`
- `.quizaccess-intentionalskip-control`
- ``[data-intentionalskip-checkbox="${slot}"]``
- ``[data-intentionalskip-status="${slot}"]``
- `input[type="submit"], button[type="submit"]`
- `form#responseform`
- `.path-mod-quiz .btn-finishattempt button`
- `.quizsummary${slotState.slot}`
- `#quizaccess-intentionalskip-style`

AJAX calls made:

- `quizaccess_intentionalskip_get_state`
  - Data sent to PHP: `attemptid`, `cmid`, `clientversion`
  - Data received from PHP: `slots[]` with `slot`, `answered`, `skipped`, `source`, `timemodified`, `status`
  - Connected PHP file: `classes/external/get_state.php`

- `quizaccess_intentionalskip_save_slot`
  - Data sent to PHP: `attemptid`, `cmid`, `slot`, `isskipped`, `source`, `clientversion`
  - Data received from PHP: `status`, `slot`, `skipped`, `source`, `timemodified`
  - Connected PHP file: `classes/external/save_slot.php`

- `quizaccess_intentionalskip_mark_slots`
  - Data sent to PHP: `attemptid`, `cmid`, `slots`, `source`, `markall`, `clientversion`
  - Data received from PHP: `status`, `markedslots`
  - Connected PHP file: `classes/external/mark_slots.php`

Moodle JS APIs used:

- `core/ajax`
- `core/notification`
- `M.util.get_string`

## Database schema

### Table `quizaccess_intskip_config`

- Defined in: `db/install.xml`
- Purpose: quiz-level plugin settings
- Fields:
  - `id`: primary key
  - `quizid`: related quiz id
  - `enabled`: plugin enabled flag for quiz
  - `showcheckbox`: whether checkbox UI should be shown
  - `warnonnext`: whether Next-page warning is active
  - `allowcontinue`: whether continuing without marking is allowed
  - `ajaxsave`: whether checkbox changes save immediately by AJAX
  - `excludepreviews`: stored quiz setting intended for preview-report filtering; current `report.php` preview filtering uses request param `includepreviews` instead
  - `timecreated`: creation timestamp
  - `timemodified`: last modification timestamp
  - `usermodified`: last editor user id
- Keys/indexes:
  - primary key on `id`
  - foreign-unique key on `quizid -> quiz.id`
  - foreign key on `usermodified -> user.id`
- Relationships to Moodle core tables if clear:
  - `quizid -> quiz.id`
  - `usermodified -> user.id`
- PHP files that read/write this table:
  - `rule.php` reads, inserts, updates, deletes, and joins it

### Table `quizaccess_intskip_state`

- Defined in: `db/install.xml`
- Purpose: current saved skip state per `attemptid + slot`
- Fields:
  - `id`: primary key
  - `courseid`: course containing the quiz
  - `cmid`: course module id
  - `quizid`: quiz id
  - `attemptid`: quiz attempt id
  - `questionattemptid`: question attempt database id
  - `slot`: quiz slot number
  - `userid`: attempt owner
  - `isskipped`: current skip flag
  - `source`: last save source string
  - `ispreview`: preview-attempt flag
  - `timecreated`: row creation timestamp
  - `timemodified`: last saved timestamp
  - `lastsavedby`: user id who last saved
- Keys/indexes:
  - primary key on `id`
  - foreign keys on `courseid`, `cmid`, `quizid`, `attemptid`, `userid`, `lastsavedby`
  - unique index `attemptslot` on `(attemptid, slot)`
  - non-unique index `quizuser` on `(quizid, userid)`
- Relationships to Moodle core tables if clear:
  - `courseid -> course.id`
  - `cmid -> course_modules.id`
  - `quizid -> quiz.id`
  - `attemptid -> quiz_attempts.id`
  - `userid -> user.id`
  - `lastsavedby -> user.id`
- PHP files that read/write this table:
  - `classes/local/state_manager.php` reads, inserts, updates, existence-checks
  - `classes/privacy/provider.php` metadata lookup and delete operations
  - `report.php` report read
  - `classes/event/skip_state_base.php` uses it as event object table

### Table `quizaccess_intskip_history`

- Defined in: `db/install.xml`
- Purpose: audit history for state transitions
- Fields:
  - `id`: primary key
  - `stateid`: current-state row id at time of write
  - `courseid`: course id
  - `cmid`: course module id
  - `quizid`: quiz id
  - `attemptid`: quiz attempt id
  - `questionattemptid`: question attempt id
  - `slot`: slot number
  - `userid`: attempt owner
  - `action`: audit action string
  - `oldstate`: prior `isskipped` value
  - `newstate`: new `isskipped` value
  - `source`: transition source string
  - `ispreview`: preview flag
  - `timecreated`: audit timestamp
- Keys/indexes:
  - primary key on `id`
  - foreign keys on `courseid`, `cmid`, `quizid`, `attemptid`, `userid`
  - index `attemptslot` on `(attemptid, slot)`
  - index `stateid` on `stateid`
- Relationships to Moodle core tables if clear:
  - `courseid -> course.id`
  - `cmid -> course_modules.id`
  - `quizid -> quiz.id`
  - `attemptid -> quiz_attempts.id`
  - `userid -> user.id`
  - `stateid` likely points to plugin table `quizaccess_intskip_state.id`, but no foreign key is declared in XML
- PHP files that read/write this table:
  - `classes/local/state_manager.php` inserts rows
  - `classes/privacy/provider.php` metadata and delete operations

## Capabilities

Defined in `db/access.php`:

- `quizaccess/intentionalskip:use`
  - Risk bitmask: none declared
  - Context level: `CONTEXT_MODULE`
  - Default roles/permissions: manager `CAP_ALLOW`, editingteacher `CAP_ALLOW`, teacher `CAP_ALLOW`, student `CAP_ALLOW`
  - Files that check or rely on this capability: `rule.php`, `classes/local/state_manager.php`, `db/services.php`

- `quizaccess/intentionalskip:viewreport`
  - Risk bitmask: none declared
  - Context level: `CONTEXT_MODULE`
  - Default roles/permissions: manager `CAP_ALLOW`, editingteacher `CAP_ALLOW`, teacher `CAP_ALLOW`
  - Files that check or rely on this capability: `rule.php`, `report.php`, `classes/local/state_manager.php`

- `quizaccess/intentionalskip:exportreport`
  - Risk bitmask: none declared
  - Context level: `CONTEXT_MODULE`
  - Default roles/permissions: manager `CAP_ALLOW`, editingteacher `CAP_ALLOW`, teacher `CAP_ALLOW`
  - Files that check or rely on this capability: no runtime checks found in this repository

- `quizaccess/intentionalskip:manage`
  - Risk bitmask: none declared
  - Context level: `CONTEXT_MODULE`
  - Default roles/permissions: manager `CAP_ALLOW`, editingteacher `CAP_ALLOW`
  - Files that check or rely on this capability: no runtime checks found in this repository

## Events

### `quizaccess_intentionalskip\event\question_marked_skipped`

- File path: `classes/event/question_marked_skipped.php`
- Purpose: logs a question being marked skipped
- Extends class: `quizaccess_intentionalskip\event\skip_state_base`
- Data expected in event: `objectid` of `quizaccess_intskip_state`; `other` contains `cmid`, `quizid`, `attemptid`, `questionattemptid`, `slot`, `isskipped`, `source`, `ispreview`
- When it appears to be triggered: in `state_manager::trigger_event()` when `newstate` becomes true and source is not handled as page/final special case
- Files likely triggering it: `classes/local/state_manager.php`

### `quizaccess_intentionalskip\event\question_unmarked_skipped`

- File path: `classes/event/question_unmarked_skipped.php`
- Purpose: logs a question being unmarked skipped
- Extends class: `quizaccess_intentionalskip\event\skip_state_base`
- Data expected in event: same base payload as above
- When it appears to be triggered: in `state_manager::trigger_event()` when state changes from skipped to not skipped and source is not a special case
- Files likely triggering it: `classes/local/state_manager.php`

### `quizaccess_intentionalskip\event\page_unanswered_marked_skipped`

- File path: `classes/event/page_unanswered_marked_skipped.php`
- Purpose: logs Next-page bulk marking of unanswered questions
- Extends class: `quizaccess_intentionalskip\event\skip_state_base`
- Data expected in event: same base payload as above
- When it appears to be triggered: in `state_manager::trigger_event()` for source `next_page_confirmation` when `newstate` is true
- Files likely triggering it: `classes/local/state_manager.php`, indirectly `classes/external/mark_slots.php`

### `quizaccess_intentionalskip\event\final_unanswered_marked_skipped`

- File path: `classes/event/final_unanswered_marked_skipped.php`
- Purpose: logs final-submit bulk marking of unanswered questions
- Extends class: `quizaccess_intentionalskip\event\skip_state_base`
- Data expected in event: same base payload as above
- When it appears to be triggered: in `state_manager::trigger_event()` for source `final_submit_confirmation` when `newstate` is true
- Files likely triggering it: `classes/local/state_manager.php`, indirectly `classes/external/mark_slots.php`

### `quizaccess_intentionalskip\event\skip_state_cleared_by_answer`

- File path: `classes/event/skip_state_cleared_by_answer.php`
- Purpose: logs clearing skip state because Moodle considers the question answered
- Extends class: `quizaccess_intentionalskip\event\skip_state_base`
- Data expected in event: same base payload as above
- When it appears to be triggered: in `state_manager::trigger_event()` for source `auto_cleared_after_answer` when old/new state differ
- Files likely triggering it: `classes/local/state_manager.php`, indirectly `classes/external/save_slot.php` and `classes/external/mark_slots.php`

## Privacy API

From `classes/privacy/provider.php`:

- Provider interfaces implemented:
  - `core_privacy\local\metadata\provider`
  - `core_privacy\local\request\plugin\provider`
- User data exported:
  - None currently; `export_user_data()` is a placeholder with no export implementation
- Tables involved:
  - `quizaccess_intskip_state`
  - `quizaccess_intskip_history`
- Delete behavior, if implemented:
  - `delete_data_for_all_users_in_context()` deletes all rows in both tables for one module context
  - `delete_data_for_user()` deletes only matching `userid` rows in both tables for approved module contexts
- Gaps or unclear areas:
  - No actual export logic
  - Metadata lists selected columns only, not every stored column
  - Context discovery SQL only looks at `quizaccess_intskip_state`, so history-only data would be unclear from code for context discovery

## Language strings

Defined in `lang/en/quizaccess_intentionalskip.php`.

Important identifiers and likely use:

- `pluginname`
  - Used by `rule.php` heading for settings section

- Settings/admin strings:
  - `enableintentionalskip`, `enableintentionalskip_help`
  - `showcheckbox`
  - `warnonnext`
  - `allowcontinue`, `allowcontinue_help`
  - `ajaxsave`, `ajaxsave_help`
  - `excludepreviews`
  - Used by `rule.php`

- Learner UI / JS strings:
  - `answeredandskippedmessage`, `answeredandskippedtitle`
  - `blanknotconfirmed`
  - `clearskipandcontinue`
  - `continuewithoutmarking`
  - `finalunansweredmessage`, `finalunansweredtitle`
  - `goback`
  - `markandcontinue`
  - `markasskipped`
  - `markedasskipped`, `markedasskippednotsaved`, `markedasskippedsaved`, `markedasskippedsaving`
  - `nextunansweredmessage`, `nextunansweredtitle`
  - `notmarkedasskipped`
  - `refreshrequiredmessage`, `refreshrequiredtitle`
  - `skiptrackingunavailablemessage`, `skiptrackingunavailabletitle`
  - `submitandmarkskipped`, `submitwithoutmarking`
  - Used by `rule.php` string registration and `amd/src/intentionalskip.js`

- Report strings:
  - `reporttitle`, `reportuser`, `reportattempt`, `reportattemptstatus`, `reportquestion`, `reportmoodlestatus`, `reportskipstatus`, `reportsource`, `reportsavedtime`, `reportpreview`, `reportquiz`
  - Used by `report.php` and `rule.php`

- Status/error strings:
  - `answered`, `skipped`, `clearedbyanswer`
  - `errorattemptclosed`, `errorinvalidattempt`, `errorinvalidslot`, `errorinvalidsource`, `errornotenabled`, `errorstaleclient`
  - Used by `report.php`, `classes/local/state_manager.php`, external endpoints, JS

- Capability strings:
  - `intentionalskip:use`
  - `intentionalskip:viewreport`
  - `intentionalskip:exportreport`
  - `intentionalskip:manage`
  - Used by Moodle capability UI

- Event strings:
  - `eventfinalunansweredmarkedskipped`
  - `eventpageunansweredmarkedskipped`
  - `eventquestionmarkedskipped`
  - `eventquestionunmarkedskipped`
  - `eventskipstateclearedbyanswer`
  - Used by event classes

- Privacy strings:
  - `privacy:metadata:quizaccess_intskip_state` and field keys
  - `privacy:metadata:quizaccess_intskip_history` and field keys
  - Used by `classes/privacy/provider.php`

Missing/unused-looking strings if obvious:

- `cachedef_settings` appears unused in this repository
- `reportquiz` appears unused in this repository
- `clearedbyanswer` appears unused in this repository

## Quiz settings/config fields

- `intentionalskip_enabled`
  - Label/string key: `enableintentionalskip`
  - Default value if visible: `0`
  - File where defined: `rule.php`
  - File where used: `rule.php`, `classes/local/state_manager.php`
  - Purpose: master per-quiz enable switch

- `intentionalskip_showcheckbox`
  - Label/string key: `showcheckbox`
  - Default value if visible: `1`
  - File where defined: `rule.php`
  - File where used: `rule.php`, passed into JS config
  - Purpose: whether learner checkbox UI is shown

- `intentionalskip_warnonnext`
  - Label/string key: `warnonnext`
  - Default value if visible: `1`
  - File where defined: `rule.php`
  - File where used: `rule.php`, passed into JS config
  - Purpose: whether unanswered current-page warning appears on Next

- `intentionalskip_allowcontinue`
  - Label/string key: `allowcontinue`
  - Default value if visible: `1`
  - File where defined: `rule.php`
  - File where used: `rule.php`, passed into JS config
  - Purpose: whether learner can continue without marking unanswered questions skipped

- `intentionalskip_ajaxsave`
  - Label/string key: `ajaxsave`
  - Default value if visible: `1`
  - File where defined: `rule.php`
  - File where used: `rule.php`, passed into JS config, read by `amd/src/intentionalskip.js`
  - Purpose: whether checkbox changes save immediately via AJAX

- `intentionalskip_excludepreviews`
  - Label/string key: `excludepreviews`
  - Default value if visible: `1`
  - File where defined: `rule.php`
  - File where used: `rule.php`
  - Purpose: stored quiz setting intended for preview-report filtering; current `report.php` preview filtering is controlled by request param `includepreviews`, not by this stored setting

Back-end storage fields in `quizaccess_intskip_config` corresponding to these settings:

- `enabled`
- `showcheckbox`
- `warnonnext`
- `allowcontinue`
- `ajaxsave`
- `excludepreviews`

## Cross-reference table

| Code object / setting / service | Defined in | Used by | Purpose | Risk |
|---|---|---|---|---|
| `quizaccess_intentionalskip` class | `rule.php` | Moodle quiz access rule loader | Main plugin integration class | High |
| `make()` | `rule.php` | Moodle access-rule factory path | Enables rule when quiz setting is on | High |
| `add_settings_form_fields()` | `rule.php` | Moodle quiz settings form | Adds plugin quiz settings | Medium |
| `save_settings()` | `rule.php` | Moodle quiz save flow | Persists quiz config row | High |
| `delete_settings()` | `rule.php` | Moodle quiz delete flow | Deletes quiz config row | Medium |
| `get_settings_sql()` | `rule.php` | Moodle quiz settings loading | Loads plugin config with quiz | High |
| `description()` | `rule.php` | Moodle access-rule description area | Adds report link for teachers | Medium |
| `setup_attempt_page()` | `rule.php` | Quiz pages where Moodle invokes the access-rule hook | Boots AMD module and JS strings; AMD behavior covers quiz attempt-page, summary/final-submit, and review-related contexts, with exact invocation depending on Moodle core quiz access-rule hook/page behavior | High |
| `state_manager` class | `classes/local/state_manager.php` | External services, report, rule | Central state/validation/history/event service | High |
| `CLIENT_VERSION` | `classes/local/state_manager.php` | `rule.php`, `amd/src/intentionalskip.js`, external services | AJAX contract/version gate | High |
| `validate_attempt()` | `classes/local/state_manager.php` | All external services | Validates sesskey, ownership, capability, open attempt, plugin enabled | High |
| `get_attempt_state()` | `classes/local/state_manager.php` | `classes/external/get_state.php` | Returns per-slot server state | High |
| `save_slot_state()` | `classes/local/state_manager.php` | `classes/external/save_slot.php` | Saves one slot state | High |
| `mark_unanswered_slots()` | `classes/local/state_manager.php` | `classes/external/mark_slots.php` | Bulk-mark selected unanswered slots | High |
| `mark_all_unanswered_slots()` | `classes/local/state_manager.php` | `classes/external/mark_slots.php` | Bulk-mark all unanswered slots | High |
| `slot_is_answered()` | `classes/local/state_manager.php` | `report.php`, `state_manager` internals | Defines answered vs blank logic | High |
| `write_state()` | `classes/local/state_manager.php` | `state_manager` internals | Inserts/updates current state row | High |
| `write_history()` | `classes/local/state_manager.php` | `state_manager` internals | Appends audit history row | Medium |
| `trigger_event()` | `classes/local/state_manager.php` | `state_manager` internals | Fires matching Moodle event | Medium |
| `get_state` external class | `classes/external/get_state.php` | `db/services.php`, JS `loadState()` | AJAX read endpoint | High |
| `save_slot` external class | `classes/external/save_slot.php` | `db/services.php`, JS `saveSlot()` | AJAX one-slot write endpoint | High |
| `mark_slots` external class | `classes/external/mark_slots.php` | `db/services.php`, JS `markSlots()` | AJAX bulk write endpoint | High |
| `skip_state_base` event base | `classes/event/skip_state_base.php` | All concrete event classes | Shared event payload/metadata | Medium |
| `question_marked_skipped` event | `classes/event/question_marked_skipped.php` | `state_manager::trigger_event()` | Logs manual mark-skipped | Medium |
| `question_unmarked_skipped` event | `classes/event/question_unmarked_skipped.php` | `state_manager::trigger_event()` | Logs manual unmark-skipped | Medium |
| `page_unanswered_marked_skipped` event | `classes/event/page_unanswered_marked_skipped.php` | `state_manager::trigger_event()` | Logs Next-page bulk mark | Medium |
| `final_unanswered_marked_skipped` event | `classes/event/final_unanswered_marked_skipped.php` | `state_manager::trigger_event()` | Logs final-submit bulk mark | Medium |
| `skip_state_cleared_by_answer` event | `classes/event/skip_state_cleared_by_answer.php` | `state_manager::trigger_event()` | Logs auto-clear-by-answer | Medium |
| `provider` privacy class | `classes/privacy/provider.php` | Moodle privacy subsystem | Metadata and deletion support; export currently unimplemented | Medium |
| `report.php` | `report.php` | Direct page request, report link | Teacher report page | High |
| `quizaccess_intentionalskip_get_state` service | `db/services.php` | JS, Moodle AJAX router | Reads attempt slot states | High |
| `quizaccess_intentionalskip_save_slot` service | `db/services.php` | JS, Moodle AJAX router | Saves one slot state | High |
| `quizaccess_intentionalskip_mark_slots` service | `db/services.php` | JS, Moodle AJAX router | Saves multiple slot states | High |
| `quizaccess/intentionalskip:use` | `db/access.php` | `rule.php`, `state_manager`, services | Learner/teacher use of tracking feature | High |
| `quizaccess/intentionalskip:viewreport` | `db/access.php` | `rule.php`, `report.php`, `state_manager` | View report and some review reads | High |
| `quizaccess/intentionalskip:exportreport` | `db/access.php` | no runtime use found | Reserved export permission | Low |
| `quizaccess/intentionalskip:manage` | `db/access.php` | no runtime use found | Reserved management permission | Low |
| `quizaccess_intskip_config` table | `db/install.xml` | `rule.php` | Stores quiz-level plugin settings | High |
| `quizaccess_intskip_state` table | `db/install.xml` | `state_manager.php`, `report.php`, privacy provider, event base | Stores current per-slot state | High |
| `quizaccess_intskip_history` table | `db/install.xml` | `state_manager.php`, privacy provider | Stores audit history | Medium |
| AMD module `quizaccess_intentionalskip/intentionalskip` | `amd/src/intentionalskip.js` | `rule.php` | Learner UI and AJAX orchestration | High |
| `init()` export | `amd/src/intentionalskip.js` | `rule.php` | Module entry point | High |
| `intentionalskip_enabled` | `rule.php` / `quizaccess_intskip_config.enabled` | `rule.php`, `state_manager.php` | Master plugin enable flag | High |
| `intentionalskip_showcheckbox` | `rule.php` / `quizaccess_intskip_config.showcheckbox` | `rule.php`, JS | Controls checkbox rendering | Medium |
| `intentionalskip_warnonnext` | `rule.php` / `quizaccess_intskip_config.warnonnext` | `rule.php`, JS | Controls Next-page warning | Medium |
| `intentionalskip_allowcontinue` | `rule.php` / `quizaccess_intskip_config.allowcontinue` | `rule.php`, JS | Allows continue without marking | Medium |
| `intentionalskip_ajaxsave` | `rule.php` / `quizaccess_intskip_config.ajaxsave` | `rule.php`, JS | Enables immediate AJAX save | Medium |
| `intentionalskip_excludepreviews` | `rule.php` / `quizaccess_intskip_config.excludepreviews` | `rule.php` | Stored preview-report filtering setting; current `report.php` filtering uses request param `includepreviews` instead | Medium |
