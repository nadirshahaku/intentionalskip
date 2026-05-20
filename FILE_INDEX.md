# FILE_INDEX.md

## Repository summary

This repository is a Moodle quiz access rule subplugin with component name `quizaccess_intentionalskip`. It appears intended to live under `mod/quiz/accessrule/intentionalskip` and adds optional per-question intentional skip tracking to Moodle quiz attempts. The plugin stores quiz-level settings, current skip state, and audit history in custom database tables; exposes Moodle AJAX external functions for reading and saving skip state; loads an AMD JavaScript module. The AMD/module behavior covers quiz attempt-page, summary/final-submit, and review-related contexts. Exact invocation depends on Moodle core quiz access-rule hook/page behavior; triggers Moodle events for skip-state changes; provides a teacher-facing report page; declares module-context capabilities; and implements a privacy metadata/delete provider with export currently unimplemented. Moodle remains the authority for quiz attempts, answers, timing, submission, grading, and review.

## Folder overview

| Folder/File | Purpose | Risk if modified |
|---|---|---|
| `FILE_INDEX.md` | AI-readable repository map and bug navigation guide. | Low |
| `version.php` | Moodle plugin metadata for component, required Moodle version, release, and maturity. | Medium |
| `rule.php` | Main quiz access rule class, settings form integration, settings persistence, report link, and AMD bootstrap. | High |
| `report.php` | Teacher report page showing per-attempt question skip status. | High |
| `newfile20may26812pm.txt` | Small note text file created on 20 May 2026. | Low |
| `handoverquizenhancement20260519_163623.md` | Long design handover describing intended intentional-skip behavior and edge cases. | Low |
| `graceful_degradation_20260520.md` | Documentation of fail-open behavior when JavaScript or AJAX fails. | Low |
| `lang/en/` | English Moodle strings for UI, capabilities, events, errors, reports, and privacy metadata. | Medium |
| `db/` | Moodle install schema, capability declarations, and AJAX external service registration. | High |
| `classes/local/` | Core server-side state service for validation, reconciliation, database writes, history, and events. | High |
| `classes/external/` | Moodle external API classes called by AMD JavaScript through AJAX. | High |
| `classes/event/` | Moodle event classes for audit/logging of skip-state changes. | Medium |
| `classes/privacy/` | Moodle privacy API metadata and deletion implementation. | Medium |
| `amd/src/` | Source AMD JavaScript for learner UI, dialogs, checkbox behavior, summary enhancement, and AJAX calls. | High |
| `amd/build/` | Minified generated AMD JavaScript build output. | Medium |

## Detailed file index

### FILE_INDEX.md

**Purpose:**  
AI-readable repository map created to help future AI/Codex sessions navigate this plugin without scanning every file first.

**Main responsibilities:**  
- Summarize the plugin's purpose, folders, and important files.
- Record factual file-by-file responsibilities, risks, connections, and common bug areas.
- Provide a navigation guide for common bug categories.

**Important Moodle concepts used:**  
- Documents Moodle concepts used elsewhere in the repository, but does not execute Moodle code.

**Connected files:**  
- All repository files listed in this index.

**Common bugs related to this file:**  
- Stale guidance after source files change.
- Missing entries after new files are added.
- Incorrect risk guidance if future implementation changes are not reflected here.

**Inspect this file when:**  
- Starting a new AI/Codex debugging session.
- Deciding which plugin file to inspect first.
- Adding a new source file or changing a file's responsibility.

**Do not inspect this file when:**  
- Runtime behavior must be verified from executable code.
- Moodle API signatures or database schema need authoritative confirmation.

**Risk level:** Low

**Notes for AI agents:**  
Treat this as a navigation aid, not the source of truth. Verify behavior in source files before editing code.

### version.php

**Purpose:**  
Declares Moodle plugin metadata for `quizaccess_intentionalskip`.

**Main responsibilities:**  
- Set `$plugin->component` to `quizaccess_intentionalskip`.
- Set plugin version `2026051906`.
- Require Moodle version `2024100700`.
- Declare alpha maturity and release `0.1.0`.

**Important Moodle concepts used:**  
- Moodle plugin component naming.
- Moodle plugin versioning and upgrade detection.
- `MOODLE_INTERNAL` access guard.

**Connected files:**  
- `db/install.xml` for install-time schema that Moodle applies based on plugin metadata.
- `db/access.php` and `db/services.php` as plugin metadata files Moodle discovers.
- `lang/en/quizaccess_intentionalskip.php` because the component name must match language file naming.

**Common bugs related to this file:**  
- Plugin not installing or upgrading because `version` is stale or malformed.
- Component mismatch causing language strings, services, or classes not to resolve.
- Incompatible Moodle versions if `requires` is too high or too low.

**Inspect this file when:**  
- Moodle does not recognize the plugin.
- Upgrade/install steps are not running.
- Component-name or release metadata appears wrong.

**Do not inspect this file when:**  
- Debugging checkbox rendering, AJAX saves, report rows, permissions, or privacy deletes.
- Looking for business logic for skip state.

**Risk level:** Medium

**Notes for AI agents:**  
Do not change this file unless the task is specifically about plugin version metadata, Moodle compatibility, release packaging, or upgrade triggering.

### rule.php

**Purpose:**  
Main Moodle quiz access rule class that enables intentional skip tracking per quiz and bootstraps AMD behavior on quiz pages where Moodle invokes the access-rule hook.

**Main responsibilities:**  
- Implement `quizaccess_intentionalskip` extending `mod_quiz\local\access_rule_base`.
- Return the rule only when `intentionalskip_enabled` is set for the quiz.
- Add per-quiz settings fields for enablement, checkbox visibility, Next warning, continue behavior, AJAX saving, and preview exclusion.
- Save and delete quiz-level settings in `quizaccess_intskip_config`.
- Provide SQL join fragments so quiz settings load from custom config.
- Show a report link to users with `quizaccess/intentionalskip:viewreport`.
- Load JavaScript strings and call AMD module `quizaccess_intentionalskip/intentionalskip`.
- Pass `cmid`, `attemptid`, display/save options, `state_manager::CLIENT_VERSION`, and readonly state to JavaScript.

**Important Moodle concepts used:**  
- Quiz access rule subplugin class.
- `mod_quiz\quiz_settings` and `mod_quiz\quiz_attempt`.
- Moodle form API (`MoodleQuickForm`, `mod_quiz_mod_form`).
- Moodle DB API.
- Moodle capability checks.
- Moodle page requirements and AMD module initialization.
- Moodle language string loading for JavaScript.
- Quiz attempt state and review-page readonly detection.

**Connected files:**  
- `classes/local/state_manager.php` for `CLIENT_VERSION`.
- `amd/src/intentionalskip.js` and `amd/build/intentionalskip.min.js` for the UI module initialized here.
- `db/install.xml` for `quizaccess_intskip_config`.
- `db/access.php` for `use` and `viewreport` capabilities.
- `report.php` for the report link target.
- `lang/en/quizaccess_intentionalskip.php` for every setting, help, dialog, and status string loaded here.

**Common bugs related to this file:**  
- Checkbox UI not loading because AMD initialization is not called or receives wrong config.
- Settings not saved or loaded because custom config fields or SQL aliases mismatch.
- Report link missing because capability or description logic fails.
- JavaScript stale-client errors if `CLIENT_VERSION` coordination is wrong.
- Feature appearing on review/finished attempts when it should be readonly.
- Preview settings not aligned with report behavior.

**Inspect this file when:**  
- The plugin does not appear in quiz settings.
- Enabling/disabling skip tracking has no effect.
- The AMD module does not initialize on quiz pages.
- Report link visibility is wrong.
- Per-quiz options are not respected by the UI.

**Do not inspect this file when:**  
- A specific AJAX endpoint validates or saves incorrectly.
- Database tables are missing on install.
- Minified JavaScript differs from source.
- Privacy deletion/export behavior is the issue.

**Risk level:** High

**Notes for AI agents:**  
This is a central integration point. Changes can affect plugin activation, settings persistence, JavaScript loading, and report access.

### report.php

**Purpose:**  
Displays the intentional skip report for a quiz course module.

**Main responsibilities:**  
- Read `cmid` and optional `includepreviews`.
- Load course module, course, quiz, and module context.
- Require login and `quizaccess/intentionalskip:viewreport`.
- Configure Moodle page URL, context, heading, navbar, and title.
- Load all quiz attempts and all `quizaccess_intskip_state` rows for the quiz.
- Build an HTML table with user, attempt number, Moodle attempt status, question number, Moodle question status, skip status, save source, saved time, and preview flag.
- Exclude preview attempts unless `includepreviews` is truthy.
- Use `state_manager::slot_is_answered()` to classify answered versus skipped versus blank.

**Important Moodle concepts used:**  
- Moodle page setup and output API.
- `required_param` and `optional_param`.
- `get_coursemodule_from_id`, `get_course`, context loading, `require_login`, `require_capability`.
- `mod_quiz\quiz_attempt`.
- Moodle quiz attempt status and question status APIs.
- `html_table`, `html_writer`, `fullname`, `userdate`, escaping through `s()`.

**Connected files:**  
- `rule.php` links here from the access rule description.
- `classes/local/state_manager.php` supplies answered-state logic.
- `db/install.xml` defines `quizaccess_intskip_state`.
- `db/access.php` declares `quizaccess/intentionalskip:viewreport`.
- `lang/en/quizaccess_intentionalskip.php` supplies report labels and status strings.

**Common bugs related to this file:**  
- Report counts/statuses wrong because answered-state reconciliation differs from state storage.
- Preview attempts included or excluded unexpectedly.
- Missing rows if attempt loading or slot filtering excludes non-real questions incorrectly.
- Performance issues on quizzes with many attempts because all attempts and state records are loaded at once.
- Permission errors or overexposure if capability checks are changed incorrectly.

**Inspect this file when:**  
- Teachers see wrong report values.
- Skipped/answered/blank classifications look wrong in the report.
- Preview data appears in real reports.
- Report page access is denied or too permissive.
- Report table columns or language strings are wrong.

**Do not inspect this file when:**  
- The learner checkbox does not appear.
- AJAX saves are failing before data reaches the database.
- Install schema or capabilities are not created.

**Risk level:** High

**Notes for AI agents:**  
This file reads state but does not create it. For source-of-truth save behavior, inspect `classes/local/state_manager.php` and external service files first.

### newfile20may26812pm.txt

**Purpose:**  
Plain text note stating that this file was created on 20 May 2026 at 8:12pm.

**Main responsibilities:**  
- No runtime responsibility.
- Acts only as a small repository artifact/note.

**Important Moodle concepts used:**  
- None.

**Connected files:**  
- None found in code.

**Common bugs related to this file:**  
- None for plugin runtime behavior.
- Could confuse future agents if assumed to be required plugin documentation.

**Inspect this file when:**  
- Auditing miscellaneous repository artifacts.
- Checking whether every repository file has been indexed.

**Do not inspect this file when:**  
- Debugging Moodle behavior, install, UI, reports, AJAX, permissions, events, or privacy.

**Risk level:** Low

**Notes for AI agents:**  
This file is not connected to the Moodle plugin implementation based on current contents.

### handoverquizenhancement20260519_163623.md

**Purpose:**  
Long design handover documenting the intended Moodle quiz intentional-skip enhancement.

**Main responsibilities:**  
- Explain the core product requirement: per-question intentional skip tracking, not a quiz-level forfeit button.
- State that Moodle remains authoritative for attempts, answers, autosave, timing, submission, grading, review, preview, and proctoring.
- Define desired learner flow for question pages, Next navigation, previous navigation, summary page, final submission, timeout, grace period, preview, reporting, logging, capabilities, settings, UI text, testing, deployment, and documentation.
- Capture non-negotiable design decisions and edge cases.

**Important Moodle concepts used:**  
- Moodle quiz attempt lifecycle.
- Moodle autosave, time limits, grace periods, automatic submission, manual submission, preview attempts, SEB/proctoring compatibility.
- Moodle quiz access rule subplugin architecture.
- Moodle AMD JavaScript, external AJAX functions, events, DB API, capabilities, language strings, and reports.

**Connected files:**  
- `rule.php`, `amd/src/intentionalskip.js`, `classes/local/state_manager.php`, `report.php`, and `db/*` implement parts of this design.
- `graceful_degradation_20260520.md` documents one implementation principle from this handover in more focused form.
- `lang/en/quizaccess_intentionalskip.php` implements many learner/report/admin strings described here.

**Common bugs related to this file:**  
- No runtime bugs, because it is documentation.
- Future implementation may drift from documented intended behavior.
- Some suggested strings or optional features may not match current code exactly.

**Inspect this file when:**  
- Clarifying intended behavior before changing plugin logic.
- Deciding how timeout, final submission, preview, or proctoring edge cases should work.
- Writing tests or acceptance criteria.
- Checking whether a requested behavior was part of the original design.

**Do not inspect this file when:**  
- You need the exact current implemented code path.
- Debugging a syntax error, class autoloading issue, or database schema mismatch.

**Risk level:** Low

**Notes for AI agents:**  
Use this as product intent, not executable truth. If it conflicts with source code, report the mismatch before editing.

### graceful_degradation_20260520.md

**Purpose:**  
Documents fail-open behavior when intentional skip tracking cannot save or initialize.

**Main responsibilities:**  
- State that skip tracking is optional metadata and must not block Moodle quiz progress or submission.
- Document that only server-saved skip states are valid.
- Explain behavior when AJAX works, initial AJAX load fails, manual checkbox save fails, Next-page marking fails, final-submit save fails, JavaScript fails completely, or timeout auto-submit occurs.
- Clarify that failed tracking should not infer skipped status from blank answers.

**Important Moodle concepts used:**  
- Moodle AJAX external functions.
- Moodle quiz progress and final submission.
- Moodle timeout and auto-submit behavior.
- Conservative report interpretation.

**Connected files:**  
- `amd/src/intentionalskip.js` implements fail-open dialog and save-failure handling.
- `classes/external/get_state.php`, `classes/external/save_slot.php`, and `classes/external/mark_slots.php` are the AJAX functions described.
- `report.php` reflects the conservative report states.
- `classes/local/state_manager.php` enforces server-side validity.

**Common bugs related to this file:**  
- No runtime bugs, because it is documentation.
- Runtime behavior may drift from the documented graceful-degradation principle.
- Developers may mistakenly use it as proof that fallback form saving exists; the document explicitly says hidden form fallback is not implemented.

**Inspect this file when:**  
- Debugging behavior after AJAX failures.
- Deciding whether a failed save should block navigation or submission.
- Clarifying report meaning for unsaved local checkbox changes.

**Do not inspect this file when:**  
- Looking for exact JavaScript selectors or external API parameter shapes.
- Fixing database schema, capability declarations, or event mappings.

**Risk level:** Low

**Notes for AI agents:**  
This file is especially useful for avoiding over-eager fixes that infer intention from blank answers.

### lang/en/quizaccess_intentionalskip.php

**Purpose:**  
English language strings for the plugin.

**Main responsibilities:**  
- Provide strings for plugin name, quiz settings, help text, learner dialogs, checkbox/status UI, error messages, report headings/statuses, events, capabilities, and privacy metadata.
- Supply strings loaded into JavaScript by `rule.php`.
- Provide privacy metadata descriptions used by `classes/privacy/provider.php`.

**Important Moodle concepts used:**  
- Moodle string API.
- JavaScript language strings via `strings_for_js`.
- Capability display strings.
- Event display names.
- Privacy metadata language keys.
- `MOODLE_INTERNAL` access guard.

**Connected files:**  
- `rule.php` references many settings and JavaScript strings.
- `amd/src/intentionalskip.js` reads strings by key at runtime.
- `report.php` uses report and status strings.
- `classes/event/*.php` use event strings.
- `classes/privacy/provider.php` references privacy metadata strings.
- `db/access.php` capability keys are described here.

**Common bugs related to this file:**  
- Missing string exceptions in Moodle UI or JavaScript.
- Misleading learner/admin/report text.
- String key mismatch between PHP, JavaScript, events, privacy provider, and language file.
- Capability labels not appearing correctly.

**Inspect this file when:**  
- UI text is wrong, missing, or untranslated.
- Moodle reports missing string identifiers.
- JavaScript dialogs or statuses show raw keys.
- Capability or event labels need correction.
- Privacy metadata text is incomplete.

**Do not inspect this file when:**  
- Business logic, saving, validation, schema, or permission checks are wrong.
- Minified JavaScript needs rebuilding.

**Risk level:** Medium

**Notes for AI agents:**  
Changing strings is lower risk than changing logic, but string keys are cross-file contracts. Check all usages before renaming any key.

### db/access.php

**Purpose:**  
Declares Moodle capabilities for the plugin.

**Main responsibilities:**  
- Define `quizaccess/intentionalskip:use` for students and teaching/admin roles.
- Define `quizaccess/intentionalskip:viewreport` for manager, editing teacher, and teacher.
- Define `quizaccess/intentionalskip:exportreport` for manager, editing teacher, and teacher.
- Define `quizaccess/intentionalskip:manage` for manager and editing teacher.
- Scope all capabilities to `CONTEXT_MODULE`.

**Important Moodle concepts used:**  
- Moodle capability API.
- Context level `CONTEXT_MODULE`.
- Archetype role defaults.
- Capability type `read`/`write`.
- `MOODLE_INTERNAL` access guard.

**Connected files:**  
- `rule.php` checks `use` and `viewreport`.
- `report.php` requires `viewreport`.
- `db/services.php` declares `use` for AJAX functions.
- `classes/local/state_manager.php` requires `use` for attempt owners and `viewreport` for review/report access.
- `lang/en/quizaccess_intentionalskip.php` defines capability names.

**Common bugs related to this file:**  
- Students cannot use the feature because `use` is missing or denied.
- Teachers cannot view reports because `viewreport` is missing or role defaults are wrong.
- Capabilities not appearing due to string key mismatch.
- Security regression if learner roles are granted report or manage capabilities.

**Inspect this file when:**  
- Access is denied unexpectedly.
- A role can do too much or too little.
- Moodle capability UI does not show expected capabilities.
- External AJAX services fail capability checks.

**Do not inspect this file when:**  
- The code checks the wrong capability at runtime.
- Database tables or service functions are missing.
- UI selectors or AJAX payloads are wrong.

**Risk level:** High

**Notes for AI agents:**  
Capability changes can affect privacy and assessment security. Confirm intended role behavior before editing.

### db/install.xml

**Purpose:**  
Defines the plugin's database schema for fresh Moodle installs.

**Main responsibilities:**  
- Create `quizaccess_intskip_config` for per-quiz settings.
- Create `quizaccess_intskip_state` for current per-attempt, per-slot skip state.
- Create `quizaccess_intskip_history` for audit history of state changes.
- Define primary keys, foreign keys, unique indexes, and secondary indexes.
- Store identifiers for course, course module, quiz, attempt, question attempt, slot, user, preview state, source, timestamps, and saver.

**Important Moodle concepts used:**  
- Moodle XMLDB install schema.
- Foreign keys to `quiz`, `user`, `course`, `course_modules`, and `quiz_attempts`.
- Unique index on `attemptid, slot` for current state.
- Moodle install-time database creation.

**Connected files:**  
- `rule.php` reads/writes `quizaccess_intskip_config`.
- `classes/local/state_manager.php` reads/writes `quizaccess_intskip_state` and `quizaccess_intskip_history`.
- `report.php` reads `quizaccess_intskip_state`.
- `classes/privacy/provider.php` declares and deletes `quizaccess_intskip_state` and `quizaccess_intskip_history`.
- `classes/event/skip_state_base.php` maps object IDs to `quizaccess_intskip_state`.

**Common bugs related to this file:**  
- Fresh install fails due to invalid XMLDB syntax.
- Runtime DB errors because fields expected by PHP are missing or named differently.
- Duplicate state save errors if `attemptid, slot` uniqueness conflicts with write logic.
- Report or privacy deletes fail due to schema mismatch.
- Upgrade bugs if this schema changes without matching upgrade steps.

**Inspect this file when:**  
- Installing the plugin fails.
- Database table or field missing errors occur.
- Schema does not match save/report/privacy code.
- Unique index or foreign key behavior seems wrong.

**Do not inspect this file when:**  
- Existing installed sites need schema migration behavior and no `db/upgrade.php` exists yet.
- UI strings, JavaScript selectors, or report display text are the issue.

**Risk level:** High

**Notes for AI agents:**  
Changing install schema after release usually requires upgrade steps. This repository currently has no `db/upgrade.php`.

### db/services.php

**Purpose:**  
Registers Moodle external AJAX functions for intentional skip tracking.

**Main responsibilities:**  
- Define `quizaccess_intentionalskip_get_state` as an AJAX read service.
- Define `quizaccess_intentionalskip_save_slot` as an AJAX write service.
- Define `quizaccess_intentionalskip_mark_slots` as an AJAX write service.
- Map each service to its external class and `execute` method.
- Declare required capability `quizaccess/intentionalskip:use`.

**Important Moodle concepts used:**  
- Moodle external function registration.
- AJAX-enabled web services.
- Capability metadata for external functions.
- `MOODLE_INTERNAL` access guard.

**Connected files:**  
- `classes/external/get_state.php`.
- `classes/external/save_slot.php`.
- `classes/external/mark_slots.php`.
- `amd/src/intentionalskip.js` calls these method names.
- `db/access.php` declares the `use` capability.

**Common bugs related to this file:**  
- JavaScript AJAX calls fail because method names do not match.
- Service not callable via AJAX if `ajax` is false or missing.
- Wrong class name prevents Moodle from locating the external function.
- Capability declaration prevents valid users from calling the endpoint.

**Inspect this file when:**  
- Browser console shows unknown external function or AJAX service errors.
- Method names in JavaScript and PHP appear out of sync.
- AJAX calls are blocked before reaching external class logic.

**Do not inspect this file when:**  
- Endpoint logic is reached but validation/save behavior is wrong.
- UI controls do not inject before AJAX is attempted.
- Database writes fail inside `state_manager`.

**Risk level:** High

**Notes for AI agents:**  
Service names are a strict contract with the AMD module. Rename only with coordinated JavaScript changes and Moodle cache rebuild awareness.

### classes/local/state_manager.php

**Purpose:**  
Core server-side service for intentional skip state validation, classification, persistence, history, and event triggering.

**Main responsibilities:**  
- Define the JavaScript/server contract version `CLIENT_VERSION`.
- Define source constants for manual checkbox, manual uncheck, Next-page confirmation, final-submit confirmation, and auto-cleared-by-answer.
- Reject stale clients through `validate_client_version()`.
- Validate manual save source values.
- Validate quiz attempt, cmid, login, context, ownership/review access, capabilities, plugin enablement, and open attempt state.
- Return per-slot state for all real questions in an attempt.
- Save one slot state while clearing skip if the slot is already answered.
- Mark selected unanswered slots or all unanswered slots as skipped.
- Determine answered state using Moodle `question_state`.
- Write current state to `quizaccess_intskip_state`.
- Write audit records to `quizaccess_intskip_history`.
- Trigger the appropriate Moodle event for state transitions.

**Important Moodle concepts used:**  
- `mod_quiz\quiz_attempt`.
- Moodle session key validation through `require_sesskey()`.
- Moodle login and context validation.
- Moodle external context validation.
- Moodle capabilities and quiz attempt ownership.
- Moodle question states `question_state::$todo` and `question_state::$invalid`.
- Moodle DB API.
- Moodle Events API.
- Moodle exception classes.

**Connected files:**  
- `rule.php` uses `CLIENT_VERSION`.
- `classes/external/get_state.php`, `save_slot.php`, and `mark_slots.php` delegate validation and state work here.
- `report.php` uses `slot_is_answered()`.
- `db/install.xml` defines the tables written here.
- `classes/event/*.php` are triggered here.
- `lang/en/quizaccess_intentionalskip.php` supplies error/event strings.

**Common bugs related to this file:**  
- Save requests fail due to sesskey, capability, ownership, attempt-state, or client-version validation.
- Answered questions incorrectly remain skipped or skipped state gets cleared unexpectedly.
- Slots are ignored because `slot_exists()` or `is_real_question()` logic excludes them.
- History rows or events are missing or duplicated.
- Final-submit or Next-page source handling does not match JavaScript behavior.
- Report classification mismatches because `slot_is_answered()` is reused in reports.

**Inspect this file when:**  
- Data reaches the server but skip state is wrong.
- AJAX save returns Moodle exceptions.
- Answered/skipped reconciliation is wrong.
- Events or history rows are missing.
- Permission, ownership, stale client, or closed attempt behavior is under investigation.
- Report status likely disagrees with Moodle question state.

**Do not inspect this file when:**  
- JavaScript controls are not injected at all.
- Moodle cannot find external function names.
- Language strings are missing.
- Install schema itself is malformed.

**Risk level:** High

**Notes for AI agents:**  
This is the highest-value file for server-side behavior. Small changes can affect AJAX saves, reports, audit history, permissions, and event logs.

### classes/external/get_state.php

**Purpose:**  
Moodle external API endpoint for reading intentional skip state for a quiz attempt.

**Main responsibilities:**  
- Define request parameters: `attemptid`, `cmid`, and optional `clientversion`.
- Validate parameters with Moodle external API helpers.
- Validate client version.
- Validate attempt with `requireopen=false` and `allowreview=true`.
- Return the slot state array from `state_manager::get_attempt_state()`.
- Define return shape for slot, answered, skipped, source, timemodified, and status.

**Important Moodle concepts used:**  
- Moodle `core_external\external_api`.
- External function parameter and return structure definitions.
- External parameter validation.
- AJAX read endpoint behavior.

**Connected files:**  
- `db/services.php` registers this class as `quizaccess_intentionalskip_get_state`.
- `amd/src/intentionalskip.js` calls this service on initialization and before final submit.
- `classes/local/state_manager.php` performs validation and state calculation.

**Common bugs related to this file:**  
- AJAX initialization fails due to parameter mismatch.
- Review/report access to state fails because validation flags are wrong.
- Return shape mismatch causes JavaScript errors.
- Stale client exception when JavaScript and PHP versions differ.

**Inspect this file when:**  
- Initial state load fails.
- Summary or checkbox state is not populated.
- JavaScript receives malformed slot data.
- Review-page readonly state loading does not work.

**Do not inspect this file when:**  
- Saving a checkbox or marking multiple slots fails after state loaded successfully.
- Database writes or event triggering are wrong.
- UI selector placement is wrong.

**Risk level:** High

**Notes for AI agents:**  
The endpoint is intentionally thin. Most behavioral fixes belong in `state_manager.php` unless the API contract itself is wrong.

### classes/external/save_slot.php

**Purpose:**  
Moodle external API endpoint for saving one slot's intentional skip state.

**Main responsibilities:**  
- Define request parameters: `attemptid`, `cmid`, `slot`, `isskipped`, `source`, and optional `clientversion`.
- Validate parameters with Moodle external API helpers.
- Validate client version and manual source.
- Validate attempt as open and editable.
- Save the slot through `state_manager::save_slot_state()`.
- Return status, slot, saved skipped flag, source, and timemodified.

**Important Moodle concepts used:**  
- Moodle `core_external\external_api`.
- External parameter and return structure definitions.
- AJAX write endpoint behavior.
- Moodle PARAM types for input/output validation.

**Connected files:**  
- `db/services.php` registers this class as `quizaccess_intentionalskip_save_slot`.
- `amd/src/intentionalskip.js` calls this for checkbox changes and auto-clear after answer.
- `classes/local/state_manager.php` validates the attempt and writes state/history.
- `lang/en/quizaccess_intentionalskip.php` supplies errors and status strings.

**Common bugs related to this file:**  
- Manual checkbox save fails due to source validation.
- JavaScript payload does not match expected parameters.
- Return shape mismatch causes UI status update failures.
- Save rejected as stale client or closed attempt.

**Inspect this file when:**  
- A single checkbox change fails in AJAX.
- `manual_checkbox`, `manual_uncheck`, or `auto_cleared_after_answer` behavior is failing.
- Browser network payload differs from endpoint parameters.

**Do not inspect this file when:**  
- Bulk Next/final submission marking is the only failing path.
- UI cannot find question elements or inject controls.
- Report display logic is wrong after data is saved.

**Risk level:** High

**Notes for AI agents:**  
This file controls the public API contract for one-slot saves. Most persistence details are in `state_manager.php`.

### classes/external/mark_slots.php

**Purpose:**  
Moodle external API endpoint for marking multiple unanswered slots as intentionally skipped.

**Main responsibilities:**  
- Define request parameters: `attemptid`, `cmid`, `slots`, `source`, optional `markall`, and optional `clientversion`.
- Validate parameters with Moodle external API helpers.
- Validate client version.
- Accept only `next_page_confirmation` and `final_submit_confirmation` sources.
- Validate attempt as open and editable.
- Call `mark_all_unanswered_slots()` when `markall` is true.
- Otherwise call `mark_unanswered_slots()` for provided slots.
- Return status and the list of marked slots.

**Important Moodle concepts used:**  
- Moodle `core_external\external_api`.
- External multiple-structure parameters and returns.
- AJAX write endpoint behavior.
- Moodle `invalid_parameter_exception`.

**Connected files:**  
- `db/services.php` registers this class as `quizaccess_intentionalskip_mark_slots`.
- `amd/src/intentionalskip.js` calls this from Next-page and final-submit confirmation dialogs.
- `classes/local/state_manager.php` performs slot filtering, answered reconciliation, writes, history, and events.

**Common bugs related to this file:**  
- Next-page or final-submit marking fails because source strings mismatch.
- `markall` behavior marks too much, too little, or nothing.
- Return list does not match UI expectations.
- Stale client or closed-attempt failures during final submission flow.

**Inspect this file when:**  
- "Mark unanswered as skipped and continue" fails.
- "Submit and mark unanswered as skipped" fails.
- Bulk marking accepts/denies the wrong source.
- `markall` payload behavior is suspect.

**Do not inspect this file when:**  
- Single checkbox saves are failing.
- Initial state load fails.
- The report page misclassifies saved records.

**Risk level:** High

**Notes for AI agents:**  
This is the gateway for navigation and final-submission skip marking. Any change here affects high-stakes quiz submission flow.

### classes/event/skip_state_base.php

**Purpose:**  
Abstract base Moodle event class for all intentional skip state-change events.

**Main responsibilities:**  
- Create event instances from a quiz attempt object and skip state record.
- Set event context, course id, object id, related user id, and `other` metadata.
- Initialize object table, CRUD type, and educational level.
- Provide a generic event description.
- Link events to the Moodle quiz review URL for the attempt.
- Define restore mapping for object id and other ids.

**Important Moodle concepts used:**  
- Moodle Events API.
- `core\event\base`.
- Moodle restore mapping.
- `mod_quiz\quiz_attempt`.
- Moodle URLs.

**Connected files:**  
- `classes/event/question_marked_skipped.php`.
- `classes/event/question_unmarked_skipped.php`.
- `classes/event/page_unanswered_marked_skipped.php`.
- `classes/event/final_unanswered_marked_skipped.php`.
- `classes/event/skip_state_cleared_by_answer.php`.
- `classes/local/state_manager.php` triggers concrete events.
- `db/install.xml` defines `quizaccess_intskip_state`.

**Common bugs related to this file:**  
- Event logs have wrong object table, context, user, or metadata.
- Restore mapping issues during backup/restore.
- Event URL points to wrong attempt or inaccessible page.
- Generic description omits information needed for audit.

**Inspect this file when:**  
- Moodle logs for skip events are wrong.
- Backup/restore mapping warnings mention this plugin's events.
- All event variants share the same metadata bug.

**Do not inspect this file when:**  
- Only one event's display name is wrong.
- State is not being saved before event triggering.
- Report reads the wrong rows.

**Risk level:** Medium

**Notes for AI agents:**  
Concrete event classes are mostly names; shared event payload behavior lives here.

### classes/event/question_marked_skipped.php

**Purpose:**  
Concrete Moodle event for a manually marked skipped question.

**Main responsibilities:**  
- Extend `skip_state_base`.
- Return the localized event name `eventquestionmarkedskipped`.

**Important Moodle concepts used:**  
- Moodle Events API.
- Moodle language strings.

**Connected files:**  
- `classes/event/skip_state_base.php`.
- `classes/local/state_manager.php` triggers this event when state changes to skipped outside bulk confirmation special cases.
- `lang/en/quizaccess_intentionalskip.php` defines the event name.

**Common bugs related to this file:**  
- Event name missing or wrong.
- Event class not autoloading due to namespace/path mismatch.

**Inspect this file when:**  
- The "question marked skipped" event label is missing or wrong.
- Moodle reports this specific event class cannot be found.

**Do not inspect this file when:**  
- Event payload, URL, restore mapping, or database state is wrong.
- Bulk page/final events are the issue.

**Risk level:** Medium

**Notes for AI agents:**  
This file is intentionally minimal. Shared event behavior is in `skip_state_base.php`.

### classes/event/question_unmarked_skipped.php

**Purpose:**  
Concrete Moodle event for a manually unmarked skipped question.

**Main responsibilities:**  
- Extend `skip_state_base`.
- Return the localized event name `eventquestionunmarkedskipped`.

**Important Moodle concepts used:**  
- Moodle Events API.
- Moodle language strings.

**Connected files:**  
- `classes/event/skip_state_base.php`.
- `classes/local/state_manager.php` triggers this event when state changes from skipped to unskipped outside auto-clear special handling.
- `lang/en/quizaccess_intentionalskip.php` defines the event name.

**Common bugs related to this file:**  
- Event name missing or wrong.
- Event class not autoloading due to namespace/path mismatch.

**Inspect this file when:**  
- The "question unmarked skipped" event label is missing or wrong.
- Moodle reports this specific event class cannot be found.

**Do not inspect this file when:**  
- Auto-clear-by-answer behavior is wrong.
- Event payload, URL, restore mapping, or database state is wrong.

**Risk level:** Medium

**Notes for AI agents:**  
Most logic deciding whether this event fires is in `state_manager::trigger_event()`.

### classes/event/page_unanswered_marked_skipped.php

**Purpose:**  
Concrete Moodle event for unanswered questions marked skipped from the Next-page confirmation flow.

**Main responsibilities:**  
- Extend `skip_state_base`.
- Return the localized event name `eventpageunansweredmarkedskipped`.

**Important Moodle concepts used:**  
- Moodle Events API.
- Moodle language strings.

**Connected files:**  
- `classes/event/skip_state_base.php`.
- `classes/local/state_manager.php` triggers this for `SOURCE_NEXT_CONFIRMATION`.
- `classes/external/mark_slots.php` accepts the matching source.
- `amd/src/intentionalskip.js` sends `next_page_confirmation`.
- `lang/en/quizaccess_intentionalskip.php` defines the event name.

**Common bugs related to this file:**  
- Event name missing or wrong.
- Next-page event not firing because source strings or trigger logic changed elsewhere.

**Inspect this file when:**  
- The Next-page bulk mark event label is missing or wrong.
- Moodle reports this specific event class cannot be found.

**Do not inspect this file when:**  
- Next-page marking does not save data.
- Dialog behavior or JavaScript source strings are wrong.
- Shared event payload is wrong.

**Risk level:** Medium

**Notes for AI agents:**  
This file only names the event. Source validation and triggering are elsewhere.

### classes/event/final_unanswered_marked_skipped.php

**Purpose:**  
Concrete Moodle event for unanswered questions marked skipped during final submission confirmation.

**Main responsibilities:**  
- Extend `skip_state_base`.
- Return the localized event name `eventfinalunansweredmarkedskipped`.

**Important Moodle concepts used:**  
- Moodle Events API.
- Moodle language strings.

**Connected files:**  
- `classes/event/skip_state_base.php`.
- `classes/local/state_manager.php` triggers this for `SOURCE_FINAL_CONFIRMATION`.
- `classes/external/mark_slots.php` accepts the matching source.
- `amd/src/intentionalskip.js` sends `final_submit_confirmation`.
- `lang/en/quizaccess_intentionalskip.php` defines the event name.

**Common bugs related to this file:**  
- Event name missing or wrong.
- Final-submit event not firing because source strings or trigger logic changed elsewhere.

**Inspect this file when:**  
- The final-submit bulk mark event label is missing or wrong.
- Moodle reports this specific event class cannot be found.

**Do not inspect this file when:**  
- Final-submit marking does not save data.
- The final confirmation dialog is wrong.
- Shared event payload is wrong.

**Risk level:** Medium

**Notes for AI agents:**  
This event represents server-saved final confirmation, not Moodle final submission itself.

### classes/event/skip_state_cleared_by_answer.php

**Purpose:**  
Concrete Moodle event for clearing a skip state because Moodle considers the question answered.

**Main responsibilities:**  
- Extend `skip_state_base`.
- Return the localized event name `eventskipstateclearedbyanswer`.

**Important Moodle concepts used:**  
- Moodle Events API.
- Moodle language strings.

**Connected files:**  
- `classes/event/skip_state_base.php`.
- `classes/local/state_manager.php` triggers this for `SOURCE_CLEARED_BY_ANSWER`.
- `amd/src/intentionalskip.js` sends `auto_cleared_after_answer` for client-side clear attempts.
- `lang/en/quizaccess_intentionalskip.php` defines the event name.

**Common bugs related to this file:**  
- Event name missing or wrong.
- Auto-clear event not firing because source strings or answered reconciliation changed elsewhere.

**Inspect this file when:**  
- The clear-by-answer event label is missing or wrong.
- Moodle reports this specific event class cannot be found.

**Do not inspect this file when:**  
- The code fails to detect answered questions.
- Skip state is not actually cleared in the database.
- Shared event payload is wrong.

**Risk level:** Medium

**Notes for AI agents:**  
Most clear-by-answer behavior is in `state_manager.php` and `amd/src/intentionalskip.js`.

### classes/privacy/provider.php

**Purpose:**  
Implements Moodle privacy API metadata and deletion hooks for intentional skip data.

**Main responsibilities:**  
- Declare selected stored user data fields for `quizaccess_intskip_state`.
- Declare selected stored user data fields for `quizaccess_intskip_history`.
- Return contexts containing data for a user by joining Moodle contexts, course modules, and skip state rows.
- Leave `export_user_data()` as an empty placeholder comment.
- Delete all plugin user data for a module context.
- Delete one user's plugin data for approved module contexts.

**Important Moodle concepts used:**  
- Moodle Privacy API metadata provider.
- Moodle Privacy API plugin provider.
- Context list SQL.
- Approved context lists.
- Module contexts and `CONTEXT_MODULE`.
- Moodle DB API.

**Connected files:**  
- `db/install.xml` defines the state and history tables declared here.
- `lang/en/quizaccess_intentionalskip.php` defines privacy metadata strings.
- `classes/local/state_manager.php` writes the state and history data this provider describes/deletes.

**Common bugs related to this file:**  
- Privacy registry missing fields or tables.
- User data not exported because `export_user_data()` is intentionally not implemented beyond a placeholder.
- Context discovery misses history-only records because SQL joins only `quizaccess_intskip_state`.
- Deletion removes too much or too little data if `cmid` or context mapping is wrong.

**Inspect this file when:**  
- Moodle privacy checks fail.
- Data deletion requests do not remove skip records.
- Privacy metadata is incomplete.
- Export behavior is requested or reported missing.

**Do not inspect this file when:**  
- Runtime skip saves fail.
- Report status is wrong for visible data.
- Capabilities deny report or AJAX access.

**Risk level:** Medium

**Notes for AI agents:**  
Export support is currently only a placeholder, and the metadata declaration covers selected rather than every stored column. Be explicit about both limitations if privacy bugs are reported.

### amd/src/intentionalskip.js

**Purpose:**  
Source AMD JavaScript module for learner-facing intentional skip UI and AJAX interactions.

**Main responsibilities:**  
- Load strings through Moodle's global `M.util.get_string`.
- Call Moodle AJAX services for get state, save one slot, and mark multiple slots.
- Detect stale-client errors.
- Read current page slots from `form#responseform input[name="slots"]`.
- Locate question DOM elements and question inputs.
- Determine local answered state from visible form inputs.
- Inject checkbox controls and save-status spans into question elements.
- Save checkbox changes immediately when AJAX saving is enabled.
- Auto-clear a skip checkbox when a question receives an answer.
- Intercept Next-page navigation to warn about unanswered current-page slots.
- Offer Go back, mark-and-continue, and continue-without-marking choices.
- Intercept final submission button flow to mark unanswered slots after confirmation.
- Warn when answered questions are also marked skipped and offer clearing.
- Enhance Moodle summary rows with skip-aware statuses.
- Inject inline CSS for plugin controls and dialogs.
- Degrade gracefully when AJAX fails by allowing Moodle quiz navigation/submission to continue after warnings.

**Important Moodle concepts used:**  
- Moodle AMD module pattern.
- Moodle `core/ajax` and `core/notification`.
- Moodle JavaScript string loading.
- Moodle quiz attempt form DOM (`form#responseform`, slots input, Next/Previous controls, finish attempt button).
- Moodle AJAX external function names.

**Connected files:**  
- `rule.php` loads this AMD module and supplies configuration.
- `amd/build/intentionalskip.min.js` is the generated/minified build output.
- `classes/external/get_state.php`, `save_slot.php`, and `mark_slots.php` are called from this file.
- `db/services.php` registers the method names used here.
- `classes/local/state_manager.php` defines source constants and client version expected by calls.
- `lang/en/quizaccess_intentionalskip.php` supplies all UI strings.
- `graceful_degradation_20260520.md` documents the intended fail-open behavior implemented here.

**Common bugs related to this file:**  
- Checkboxes not appearing because question selectors fail on a Moodle theme/version/question type.
- Local answer detection false positives or false negatives for complex question types.
- Next-page or final-submit interception misses Moodle buttons after markup changes.
- AJAX payloads mismatch external API parameters.
- User can navigate or submit with unexpected local/server state after save failure.
- Summary page statuses not updating because row selectors differ.
- Build output gets stale if this source changes but `amd/build/intentionalskip.min.js` is not rebuilt.

**Inspect this file when:**  
- UI/checkbox behavior is wrong.
- Dialog choices or navigation interception are wrong.
- Browser console shows JavaScript errors.
- AJAX calls are not sent or have wrong payloads.
- Summary page status display is wrong.
- Graceful-degradation user flow behaves incorrectly.

**Do not inspect this file when:**  
- Server receives correct AJAX calls but rejects them.
- Database rows, history rows, or event logs are wrong.
- Capabilities or install schema are missing.

**Risk level:** High

**Notes for AI agents:**  
This file is fragile because it depends on Moodle quiz page DOM. If edited, rebuild/check the minified AMD output and test on quiz attempt, summary, review, Next, Previous, and final-submit pages.

### amd/build/intentionalskip.min.js

**Purpose:**  
Generated/minified AMD JavaScript build output for Moodle to serve.

**Main responsibilities:**  
- Provide the built version of `quizaccess_intentionalskip/intentionalskip`.
- Mirror the behavior from `amd/src/intentionalskip.js` in minified form.

**Important Moodle concepts used:**  
- Moodle AMD build output.
- Moodle JavaScript module naming.
- Moodle `core/ajax` and `core/notification`.

**Connected files:**  
- `amd/src/intentionalskip.js` is the human-readable source.
- `rule.php` loads the module name that this built file defines.
- `classes/external/*.php` and `db/services.php` are indirectly connected through AJAX method names inside this build.

**Common bugs related to this file:**  
- Moodle serves stale JavaScript because build output was not regenerated after source changes.
- Minification/build error creates behavior different from source.
- Debugging minified code directly wastes time compared with inspecting source.

**Inspect this file when:**  
- Verifying whether build output matches source.
- Moodle production mode serves behavior different from `amd/src/intentionalskip.js`.
- Checking generated/build artifacts for release packaging.

**Do not inspect this file when:**  
- Making logical JavaScript changes.
- Reading UI behavior for the first time.
- Debugging server-side validation or DB writes.

**Risk level:** Medium

**Notes for AI agents:**  
Treat as generated/build output. Prefer editing `amd/src/intentionalskip.js`, then rebuild the AMD artifact using the repository/Moodle build process.

## AI navigation guide

- For UI/checkbox bugs, start with these files: `amd/src/intentionalskip.js`, `rule.php`, `lang/en/quizaccess_intentionalskip.php`, then `amd/build/intentionalskip.min.js` only to check build staleness.
- For save/AJAX bugs, start with these files: `amd/src/intentionalskip.js`, `db/services.php`, `classes/external/save_slot.php`, `classes/external/mark_slots.php`, `classes/external/get_state.php`, `classes/local/state_manager.php`.
- For report/count bugs, start with these files: `report.php`, `classes/local/state_manager.php`, `db/install.xml`, `lang/en/quizaccess_intentionalskip.php`.
- For permission bugs, start with these files: `db/access.php`, `rule.php`, `report.php`, `classes/local/state_manager.php`, `db/services.php`.
- For install/upgrade bugs, start with these files: `version.php`, `db/install.xml`, `db/access.php`, `db/services.php`; note that this repository currently has no `db/upgrade.php`.
- For privacy/export bugs, start with these files: `classes/privacy/provider.php`, `db/install.xml`, `lang/en/quizaccess_intentionalskip.php`; note that `export_user_data()` is currently a placeholder.
- For language/string bugs, start with these files: `lang/en/quizaccess_intentionalskip.php`, then whichever PHP or JavaScript file references the missing key.
