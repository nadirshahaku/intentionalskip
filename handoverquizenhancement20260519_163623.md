# Handover: Moodle Quiz Intentional Skip Enhancement

**File name:** `handoverquizenhancement20260519_163623.md`  
**Prepared on:** Tuesday, 19 May 2026, 04:36:23 PM PKT  
**Project context:** AKU Moodle VLE quiz enhancement  
**Target platform:** Moodle 4.x through Moodle 5.2 and beyond, subject to API compatibility testing  
**Recommended plugin component:** `quizaccess_intentionalskip`  
**Recommended location:** `mod/quiz/accessrule/intentionalskip`

---

## 1. Purpose of This Handover

This file captures the full agreed understanding of the proposed Moodle quiz enhancement, so that if all conversation context is lost, a developer or AI assistant can read this document and continue from the correct design.

The feature is **not** a quiz-level forfeit button. The final requirement is a **per-question intentional skip tracking feature**.

The purpose is to distinguish between:

- a question answered by the learner;
- a question intentionally skipped by the learner;
- a question left blank temporarily because the learner may come back later;
- a question left blank because of timeout, disconnect, browser crash, SEB/proctoring issue, or other unknown reason.

The key problem being solved:

> In standard Moodle, a blank answer does not clearly tell us whether the learner intentionally skipped the question or accidentally left it blank. This plugin records explicit learner intention where possible.

---

## 2. Core Principle

The plugin must **respect Moodle’s normal quiz flow**.

Moodle remains the authority for:

- quiz attempt creation;
- quiz attempt state;
- question attempt state;
- answer saving;
- autosave;
- page navigation;
- time limits;
- grace periods;
- automatic submission;
- manual final submission;
- grading;
- review options;
- quiz preview;
- SEB/proctoring-controlled quiz behavior.

The plugin must not create a parallel quiz system and must not override Moodle’s grading or attempt lifecycle.

The plugin only adds one extra metadata layer:

> Was this unanswered question intentionally marked as skipped by the learner?

Professional design statement:

> The plugin must treat Moodle’s quiz attempt, question attempt, autosave, submission, timing, grading, review, and proctoring behavior as authoritative. The plugin must only record additional skip-intention metadata and must never override Moodle’s core quiz state or grading rules.

---

## 3. Correct Feature Definition

### 3.1 What the Plugin Adds

For each question in a quiz attempt, the learner should be able to mark the question as intentionally skipped.

Example learner-facing control:

```text
☐ Mark this question as intentionally skipped
```

If already marked:

```text
☑ This question is marked as intentionally skipped — saved
```

This control should be displayed near the question controls/answer area, not as part of the question text itself.

### 3.2 What the Plugin Does Not Do

The plugin does **not**:

- award marks;
- reduce marks;
- change Moodle question grading;
- replace Moodle submission;
- replace Moodle autosave;
- force all blank questions to be skipped without learner confirmation;
- infer skipped status from timeout, crash, disconnect, or abandonment;
- require SEB specifically;
- require any external page or external service.

---

## 4. Desired Learner Flow

Moodle’s normal quiz flow remains:

```text
Start attempt
→ Question page
→ Next / Previous
→ Finish attempt
→ Summary of attempt
→ Submit all and finish
→ Attempt submitted
```

The plugin adds skip-awareness inside that flow.

---

## 5. Question Page Behavior

On each question page, every question should show a skip control.

Example:

```text
Question 4

[question content]

[answer area]

☐ Mark this question as intentionally skipped
```

When the learner ticks the checkbox:

```text
☑ This question is marked as intentionally skipped — saving...
```

After successful save:

```text
☑ This question is marked as intentionally skipped — saved
```

If saving fails:

```text
☑ This question is marked as intentionally skipped — not saved. Check your connection.
```

If the learner unticks it:

```text
☐ Mark this question as intentionally skipped
```

If the learner answers a question that was previously marked skipped, the skip mark should be cleared automatically where technically feasible.

Rule:

> An answered question should not remain marked as skipped.

---

## 6. Next Page Behavior

When the learner clicks **Next page**, the plugin checks the current page for unanswered questions.

If all questions are answered or already intentionally skipped, Moodle continues normally.

If one or more questions on the current page are unanswered, show a warning with three choices:

```text
You have unanswered question(s) on this page.

Do you want to mark them as skipped before moving to the next page?

[Go back]
[Mark unanswered as skipped and continue]
[Continue without marking as skipped]
```

### 6.1 Meaning of Each Button

| Button | Meaning |
|---|---|
| **Go back** | Stay on the current page so the learner can answer or change something. |
| **Mark unanswered as skipped and continue** | Record the unanswered questions on this page as intentionally skipped, then continue to the next page. |
| **Continue without marking as skipped** | Move to the next page, leave those questions blank for now, and do not mark them skipped. This supports learners who want to return later. |

This is important because a blank answer during navigation may simply mean:

- the learner wants to return later;
- the learner is unsure;
- the learner temporarily skipped the page;
- the learner did not yet decide to submit it as skipped.

Therefore, during page navigation, blank must not automatically mean skipped.

---

## 7. Previous Page Behavior

When the learner clicks **Previous page**, the plugin should not aggressively force skip decisions.

Recommended behavior:

- Save any explicit checkbox changes already made.
- Allow the learner to move backward normally.
- Do not force a skipped/not-skipped decision merely because the learner is navigating backward.

If the implementation applies the same warning as Next, it must still include the three choices, including **Continue without marking as skipped**.

---

## 8. Finish Attempt Behavior

When the learner clicks **Finish attempt**, Moodle takes the learner to the attempt summary page.

The plugin may optionally warn if there are unanswered questions, but the most important decision point is still **Submit all and finish**.

Optional warning on Finish attempt:

```text
Some questions are still unanswered.

You may return to the attempt and answer them, or continue to the attempt summary.
Unanswered questions are not finally submitted yet.

[Continue to summary]
[Go back]
```

This warning is optional. The final submission warning is mandatory.

---

## 9. Summary of Attempt Page Behavior

The Moodle attempt summary page should remain Moodle’s normal summary page, but with skip-aware status wording if technically feasible.

Example statuses:

```text
Question 1: Answer saved
Question 2: Not yet answered — will be marked as skipped if submitted now
Question 3: Marked as skipped
Question 4: Answer saved
```

Recommended status logic:

| Question state | Learner-facing summary text |
|---|---|
| Answer saved | Answer saved |
| Explicitly marked skipped | Marked as skipped |
| Blank and not marked skipped | Not yet answered — will be marked as skipped if submitted now |
| Attempt closed by timeout/system | Use Moodle attempt state; do not imply learner confirmed skip unless a saved skip mark exists. |

The goal is to make it very clear before final submission that remaining blanks will be treated as skipped only if the learner chooses to submit.

---

## 10. Final Submit All and Finish Behavior

This is the most important rule.

When the learner clicks **Submit all and finish**, the plugin checks the whole attempt.

If there are no unanswered questions, Moodle submits normally.

If there are unanswered questions, show this warning:

```text
You still have unanswered question(s).

If you continue, all unanswered question(s) will be recorded as intentionally skipped.

If you want to answer or change any question, click Go back before submitting.

[Go back]
[Submit and mark unanswered as skipped]
```

At final submission there should be **no** option to “continue without marking as skipped,” because the attempt is being submitted.

Final rule:

> At final manual submission, remaining unanswered questions are marked as skipped only after the learner is warned and chooses to continue.

---

## 11. Saving Strategy

The saving strategy is crucial because of internet disconnections, browser crashes, SEB/proctoring interruptions, and Moodle timeout behavior.

The agreed best design is to save skip state in two ways:

1. **Immediately by AJAX when the checkbox is changed.**
2. **Again during normal quiz navigation as a fallback.**

### 11.1 Immediate AJAX Save

When the learner ticks or unticks the skip checkbox, the plugin should immediately send a same-site Moodle AJAX request.

Data to save:

- course id;
- course module id;
- quiz id;
- attempt id;
- question attempt id if available;
- slot number;
- user id;
- skip state: yes/no;
- source/action;
- timestamp;
- preview flag if relevant.

Example sources:

```text
manual_checkbox_checked
manual_checkbox_unchecked
```

After a successful save, the UI should clearly show:

```text
saved
```

If save fails, it should clearly show:

```text
not saved / connection issue
```

### 11.2 Navigation Save Fallback

When the learner clicks:

- Next;
- Previous;
- Finish attempt;
- Submit all and finish;

The current page’s skip states should also be submitted/saved as a fallback.

This protects against cases where AJAX failed but the normal form request succeeds.

---

## 12. Relationship with Moodle Autosave

Moodle autosave saves normal quiz responses. The plugin should not assume that Moodle autosave automatically saves custom skip metadata unless explicitly integrated.

Professional rule:

> Moodle autosave remains responsible for Moodle question responses. The plugin maintains its own skip-state saving mechanism and must not rely only on Moodle autosave.

Recommended design:

| Save mechanism | Purpose |
|---|---|
| Immediate AJAX save on checkbox change | Main skip-state save. |
| Navigation/form save | Backup skip-state save. |
| Moodle quiz autosave | Normal Moodle answer saving. |
| Final submit confirmation | Records remaining unanswered questions as skipped only after explicit learner confirmation. |

### 12.1 If Moodle Autosave Is Enabled

Moodle autosave may save answers while the learner is on the page. Our plugin should still save skip state independently.

Possible cases:

| Scenario | Expected result |
|---|---|
| Learner ticks skip and AJAX succeeds before Next | Skip mark is saved even if learner never clicks Next. |
| Learner ticks skip, AJAX fails, Moodle autosave saves answers | Answer data may save, but skip mark is not guaranteed. UI must show skip not saved. |
| Learner ticks skip, AJAX succeeds, Moodle autosave later runs | No conflict. Skip mark remains saved. |
| Learner answers a previously skipped question | Plugin should clear skip mark where possible. |

### 12.2 Do Not Depend Only on Autosave

If the plugin relies only on Moodle autosave, current-page skip marks may be lost if:

- time expires before the autosave interval;
- internet disconnects before autosave;
- browser crashes;
- proctoring tool closes the browser;
- SEB/proctoring environment interrupts the attempt.

Therefore immediate AJAX save is preferred.

---

## 13. Internet Disconnect, Browser Error, Crash, and Timeout Rules

A skip mark is valid only when it has successfully reached the Moodle server.

Professional rule:

> A skipped question means the learner’s skip intention reached the Moodle server and was recorded.

It must not mean:

> The learner may have clicked something locally before losing internet.

### 13.1 Case: Learner Ticks Skip and Clicks Next Successfully

Expected result:

```text
Skip mark is saved.
```

If the attempt later closes or is auto-submitted, the saved skip mark remains attached to that attempt.

### 13.2 Case: Learner Ticks Skip but Internet Disconnects Before Save

Expected result:

```text
Skip mark is not guaranteed and should not be reported as skipped unless the server received it.
```

### 13.3 Case: Learner Ticks Skip, AJAX Save Succeeds, Then Browser Crashes

Expected result:

```text
Skip mark remains saved because it reached the server before the crash.
```

### 13.4 Case: Learner Ticks Skip but AJAX Fails and Next Is Never Clicked

Expected result:

```text
Skip mark is not saved. Report should not show it as intentionally skipped.
```

### 13.5 Case: Learner Answers a Skipped Question

Expected result:

```text
Skip mark should be cleared.
```

Where JavaScript detection is unreliable for some question types, the server should reconcile state during navigation/final submission:

> If the question has a real saved response, the skip flag should be removed or ignored.

---

## 14. Moodle Time Limit Behavior

The plugin must respect Moodle’s quiz setting for what happens when time expires.

Moodle has three relevant behaviors:

1. Open attempts are submitted automatically.
2. There is a grace period when open attempts can be submitted, but no more questions answered.
3. Attempts must be submitted before time expires, or they are not counted.

The plugin must follow these behaviors and must not override them.

---

## 15. Time Expiry Option 1: Open Attempts Are Submitted Automatically

This is Moodle’s common/default behavior.

When time expires, Moodle submits the open attempt automatically.

Plugin rule:

> When Moodle auto-submits, preserve already-saved skip marks, but do not create new skip marks merely because questions are blank.

### 15.1 If Skip Was Saved Before Timeout

Example:

```text
Learner ticks Skip
→ AJAX save succeeds or Next is clicked successfully
→ time expires
→ Moodle auto-submits attempt
→ saved skip mark remains attached to submitted attempt
```

Final report:

```text
Question X: Skipped — saved before timeout
```

### 15.2 If Skip Was Ticked Locally but Not Saved

Example:

```text
Learner ticks Skip
→ internet disconnects before AJAX/Next save
→ time expires
→ Moodle auto-submits
```

Final report:

```text
Question X: Blank / not confirmed skipped
```

### 15.3 If the Learner Is on the Current Page When Time Expires

If skip checkboxes on the current page were saved by AJAX before timeout, they should be included.

If they were only changed locally in the browser and never reached the server, they should not be included.

Best implementation target:

> The skip checkbox must save immediately when clicked, so current-page skip state is already on the server before timeout.

If technically possible, current visible page skip states may also be included in the final automatic submit request, but the plugin must still not mark unrelated blank questions as skipped simply because timeout happened.

---

## 16. Time Expiry Option 2: Grace Period, No More Questions Answered

In this Moodle mode, after time expires, learners may submit during the grace period but cannot continue answering questions.

Plugin rule:

> During grace period, the plugin should not allow new skip decisions, because marking a question as skipped is an attempt action similar to changing the response state.

Expected behavior:

| State before timeout | During grace period / final result |
|---|---|
| Answer saved | Preserved. |
| Skip mark saved | Preserved. |
| Blank with no saved skip | Remains blank / not confirmed skipped. |
| Learner tries to newly mark skipped after time expires | Should not be allowed. |

Submitting during grace period should submit only the state saved before expiry.

---

## 17. Time Expiry Option 3: Attempts Must Be Submitted Before Time Expires or They Are Not Counted

In this Moodle mode, attempts not submitted before expiry are not counted.

Plugin rule:

> The plugin may record saved skip marks, but it must not make an uncounted Moodle attempt count.

Expected behavior:

| Item | Result |
|---|---|
| Skip mark saved before timeout | Exists in plugin data. |
| Attempt not submitted before timeout | Follows Moodle status: not counted. |
| Plugin report | May show skip records as part of an unsubmitted/not-counted attempt. |
| Grade/report authority | Moodle attempt state remains authoritative. |

Report wording should make this clear:

```text
Attempt status: Not submitted / not counted
Question X: Skip mark was saved before timeout
```

But it must not present the attempt as a normal completed/submitted graded attempt.

---

## 18. What Must Not Be Marked as Skipped

The following must **not** automatically create new skipped status:

- internet disconnect;
- browser crash;
- SEB crash;
- proctoring tool interruption;
- timeout auto-submit;
- grace-period submit without previous learner confirmation;
- blank answer alone;
- learner clicking “Continue without marking as skipped”;
- abandoned attempt.

Only these actions should create skip marks:

1. Learner manually ticks the skip checkbox and the save succeeds.
2. Learner chooses **Mark unanswered as skipped and continue** on page navigation.
3. Learner chooses **Submit and mark unanswered as skipped** at final manual submission.

---

## 19. Preview Quiz Behavior

The feature should appear in Moodle’s **Preview quiz** mode because teachers/admins need to test the learner experience.

In preview, the teacher/admin should see:

- skip checkbox;
- AJAX save behavior;
- Next-page warning;
- Summary page skip-aware statuses;
- final submission warning;
- timeout behavior if tested.

However, preview data must not pollute real learner reports.

Recommended behavior:

| Area | Preview behavior |
|---|---|
| UI controls | Show normally. |
| Skip checkbox | Works normally. |
| Navigation warnings | Show normally. |
| Final submission warning | Shows normally. |
| Custom skip records | Either save with `ispreview = 1` or use Moodle attempt metadata to exclude. |
| Student reports | Exclude preview attempts. |
| Audit/debug logs | May include preview events, clearly marked as preview. |

---

## 20. Logging and Events

The plugin should include two kinds of tracking:

1. **Operational data** in custom plugin tables, used for reports.
2. **Moodle events/logs** for audit trail.

The custom database records are the authoritative source for skip reporting.

Moodle event logs are useful for audit/history but should not be the only reporting source.

---

## 21. Suggested Moodle Events

Suggested event classes:

```text
\quizaccess_intentionalskip\event\question_marked_skipped
\quizaccess_intentionalskip\event\question_unmarked_skipped
\quizaccess_intentionalskip\event\page_unanswered_marked_skipped
\quizaccess_intentionalskip\event\final_unanswered_marked_skipped
\quizaccess_intentionalskip\event\skip_state_cleared_by_answer
```

Optional event:

```text
\quizaccess_intentionalskip\event\navigation_without_marking_skipped
```

This optional event may be useful for audit, but it could generate too many logs. It should be considered carefully.

Each event should include enough context:

- course id;
- course module id;
- quiz id;
- attempt id;
- question attempt id;
- slot;
- user id;
- preview flag;
- source/action;
- timestamp.

Example source/action values:

```text
manual_checkbox
manual_uncheck
next_page_confirmation
final_submit_confirmation
auto_cleared_after_answer
```

---

## 22. Custom Database Design

Exact schema should be refined during development, but the plugin should maintain an operational table for skip state.

Suggested table:

```text
quizaccess_intskip_state
```

Suggested fields:

| Field | Purpose |
|---|---|
| id | Primary key. |
| courseid | Course id. |
| cmid | Course module id. |
| quizid | Quiz id. |
| attemptid | Quiz attempt id. |
| questionattemptid | Question attempt id if available. |
| slot | Question slot in the attempt. |
| userid | Learner user id. |
| isskipped | 1 = skipped, 0 = not skipped/cleared. |
| source | How skip state was set/cleared. |
| ispreview | 1 for preview/test attempts, 0 for real learner attempts. |
| timecreated | First created timestamp. |
| timemodified | Last modified timestamp. |
| lastsavedby | User id responsible for last change. |

Suggested history/audit table, if needed:

```text
quizaccess_intskip_history
```

This can record every state change, while the state table stores the current/latest status.

Suggested history fields:

| Field | Purpose |
|---|---|
| id | Primary key. |
| stateid | Link to state record if applicable. |
| courseid | Course id. |
| cmid | Course module id. |
| quizid | Quiz id. |
| attemptid | Attempt id. |
| questionattemptid | Question attempt id. |
| slot | Question slot. |
| userid | Learner. |
| action | marked, unmarked, final_submit_marked, cleared_by_answer, etc. |
| oldstate | Previous state. |
| newstate | New state. |
| source | manual checkbox, next confirmation, final confirmation, etc. |
| ispreview | Preview flag. |
| timecreated | Timestamp. |

---

## 23. Reporting Requirements

The plugin should provide teacher/admin reporting that distinguishes final question states.

Recommended report columns:

| Column | Description |
|---|---|
| Course | Course name/id. |
| Quiz | Quiz name/id. |
| User | Learner identity. |
| Attempt | Attempt number/id. |
| Attempt status | Moodle attempt status. |
| Question slot | Slot number. |
| Question name/text summary | For identification. |
| Moodle response status | Answered / blank / etc. |
| Skip status | Skipped / not skipped. |
| Skip source | Manual checkbox / next-page confirmation / final submit confirmation. |
| Skip saved time | Timestamp. |
| Preview? | Yes/No. |

Example report rows:

| User | Attempt | Question | Moodle response status | Skip status | Source | Time |
|---|---:|---|---|---|---|---|
| Student A | 1 | Q2 | Blank | Skipped | Next page confirmation | 10:32 |
| Student A | 1 | Q5 | Blank | Not skipped | — | — |
| Student A | 1 | Q7 | Blank | Skipped | Final submit confirmation | 10:55 |

Reports should not rely only on Moodle logs, because logs are event-by-event and less suitable for final-state reporting.

---

## 24. Attempt Interpretation Rules

Suggested final state categories:

| Category | Meaning |
|---|---|
| Answered | Moodle has a saved answer/response. |
| Skipped manually | Learner explicitly ticked skip and it saved. |
| Skipped during navigation | Learner chose to mark unanswered questions skipped before moving page. |
| Skipped at final submit | Learner confirmed final submission with blanks marked skipped. |
| Blank / not confirmed skipped | No answer and no saved skip mark. |
| Auto-submitted / unknown blank | Attempt closed by timeout/system; blank questions without saved skip marks remain unknown. |
| Preview/test | Teacher/admin preview; excluded from real learner reporting. |

---

## 25. “Not Reached” Future Enhancement

“Not reached” means a question/page was never actually viewed by the learner before the attempt ended.

Example:

```text
Quiz has 20 questions.
Learner answers page 1.
Internet disconnects.
Attempt auto-submits at timeout.
Learner never opened pages 3, 4, or 5.
```

This is different from a question that was displayed but left blank.

Version 1 does not need to implement “not reached” unless required.

Future implementation would require tracking:

- which pages/questions were actually loaded;
- when each page/question was displayed;
- whether the user reached the question before timeout.

For version 1, keep categories simpler:

```text
Answered
Skipped
Not answered / not confirmed skipped
Auto-submitted / unknown blank
```

---

## 26. SEB and Other Proctoring/Lockdown Tools

The plugin must not be SEB-specific.

Correct design target:

> Moodle-controlled quiz attempt flow.

Better wording:

> The plugin must respect Moodle’s standard quiz attempt flow, including SEB or any other proctoring/lockdown mechanism that operates through Moodle’s normal quiz pages.

This means the plugin should:

- stay inside the Moodle quiz attempt page;
- use the same Moodle session;
- use Moodle-safe forms/AJAX/web service calls;
- avoid external URLs;
- avoid new tabs;
- avoid popups as a dependency;
- avoid separate forms outside the quiz attempt;
- avoid browser behavior that lockdown tools may block;
- respect Moodle’s attempt state and timing.

Expected compatibility:

- Should work with SEB if all actions stay inside Moodle’s quiz attempt page.
- Should work with other proctoring/lockdown tools that allow Moodle’s normal quiz pages and same-site JavaScript/AJAX.
- Must be tested with each proctoring tool used by AKU.

Important caveat:

> No plugin can guarantee compatibility with every future proctoring tool. Some tools may block custom JavaScript, AJAX, injected UI, or modified navigation. The plugin should follow Moodle standards to maximize compatibility, then validate through testing.

---

## 27. Security Requirements

The plugin must follow Moodle secure coding practices.

Key requirements:

- No direct access to PHP scripts where not appropriate.
- Use `require_login()` and Moodle context checks.
- Verify the user owns the attempt or has proper capability.
- Use Moodle sesskey protection for state-changing requests.
- Validate attempt id, quiz id, cmid, slot, and user id server-side.
- Never trust client-side values alone.
- Use Moodle database API, not raw unsafe SQL.
- Use parameterized queries through Moodle DB API.
- Escape all output using Moodle output APIs.
- Use Moodle string API for all UI text.
- Use capabilities for report access.
- Do not expose other users’ attempt data to learners.
- Do not allow skip changes after the attempt is finished/closed.
- Do not allow skip changes during grace period if Moodle prevents further answering.
- Do not allow skip changes if the attempt is not active.
- Do not allow skip changes for preview records to appear in real learner reports.

---

## 28. Capability Design

Suggested capabilities:

```text
quizaccess/intentionalskip:use
quizaccess/intentionalskip:viewreport
quizaccess/intentionalskip:exportreport
quizaccess/intentionalskip:manage
```

Suggested meaning:

| Capability | Purpose |
|---|---|
| `use` | Allows learner to use skip feature during attempts. Usually allowed for students. |
| `viewreport` | Allows teacher/admin to view skip reports. |
| `exportreport` | Allows teacher/admin to export skip reports. |
| `manage` | Allows admin/manager to configure plugin settings. |

Capabilities should be reviewed against Moodle plugin type norms.

---

## 29. Configuration Requirements

The plugin should be configurable per quiz if technically supported by quiz access rule settings.

Suggested settings:

| Setting | Description |
|---|---|
| Enable intentional skip tracking | Turns feature on/off for a quiz. |
| Show skip checkbox on question page | Controls checkbox visibility. |
| Warn on Next if page has unanswered questions | Enables/disables Next warning. |
| Allow continue without marking skipped | Should be enabled to support returning later. |
| Warn on final submission | Should be mandatory when enabled. |
| Save skip immediately by AJAX | Should be enabled and strongly recommended. |
| Exclude preview attempts from reports | Should be enabled by default. |
| Report visibility | Teacher/manager/admin as per capability. |

Default recommended behavior:

- Plugin disabled by default globally or per quiz until enabled.
- When enabled for a quiz, final submission warning should be mandatory.
- AJAX save should be enabled.
- Preview records excluded from real reports.

---

## 30. UI/UX Requirements

The UI must be simple, clear, and not intrusive.

### 30.1 Checkbox Text

Recommended:

```text
☐ Mark this question as intentionally skipped
```

When selected:

```text
☑ This question is marked as intentionally skipped — saved
```

### 30.2 Save Status

Show clear micro-status:

```text
saving...
saved
not saved — check connection
```

### 30.3 Next Page Warning

Use three choices:

```text
You have unanswered question(s) on this page.

Do you want to mark them as skipped before moving to the next page?

[Go back]
[Mark unanswered as skipped and continue]
[Continue without marking as skipped]
```

### 30.4 Final Submit Warning

Use two choices:

```text
You still have unanswered question(s).

If you continue, all unanswered question(s) will be recorded as intentionally skipped.

If you want to answer or change any question, click Go back before submitting.

[Go back]
[Submit and mark unanswered as skipped]
```

### 30.5 Summary Page Status

Example:

```text
Question 1: Answer saved
Question 2: Not yet answered — will be marked as skipped if submitted now
Question 3: Marked as skipped
Question 4: Answer saved
```

---

## 31. Internationalization and Language Strings

All UI text must be placed in Moodle language files, e.g.:

```text
mod/quiz/accessrule/intentionalskip/lang/en/quizaccess_intentionalskip.php
```

Do not hard-code user-facing strings in PHP or JavaScript.

Suggested string keys:

```text
pluginname
markasskipped
markedasskippedsaved
markedasskippedsaving
markedasskippednotsaved
nextunansweredtitle
nextunansweredmessage
goback
markandscontinue
continuewithoutmarking
finalunansweredtitle
finalunansweredmessage
submitandmarkskipped
statusanswered
statusnotansweredwillbeskipped
statusmarkedskipped
reporttitle
```

---

## 32. Moodle Version Compatibility

Target: Moodle 4.x through Moodle 5.2 and beyond.

Development should follow current Moodle coding standards and avoid deprecated APIs.

Compatibility requirements:

- Use official Moodle plugin structure.
- Use supported quiz access rule APIs.
- Avoid modifying Moodle core.
- Avoid theme-specific hacks.
- Avoid depending on fragile HTML selectors where possible.
- Prefer Moodle AMD JavaScript modules where appropriate.
- Use Moodle output/renderers/templates where appropriate.
- Use Moodle DB API and upgrade steps.
- Test across Moodle 4.x, 4.5 LTS/current AKU version, 5.x, and 5.2 when available.

Future-proofing statement:

> The plugin should be built as a normal Moodle quiz access rule subplugin using public Moodle APIs wherever possible. Any unavoidable DOM integration must be isolated, documented, and covered by regression tests because Moodle quiz UI markup may change between versions.

---

## 33. Recommended Plugin Type

Recommended component:

```text
quizaccess_intentionalskip
```

Recommended path:

```text
mod/quiz/accessrule/intentionalskip
```

Why:

- The feature belongs inside the quiz attempt flow.
- It should be enabled/disabled per quiz.
- It interacts with attempt access/navigation/submission behavior.
- It avoids Moodle core modifications.
- It is more suitable than adding a separate activity or block.

However, developers must confirm the exact best hook points during implementation against current Moodle 4.x/5.x quiz access rule APIs.

---

## 34. Integration Points to Investigate During Development

The developer must verify exact Moodle APIs/hooks for:

- adding UI to the attempt page;
- checking attempt state;
- detecting current page/slots;
- detecting whether a question has a saved response;
- intercepting or enhancing Next page behavior;
- enhancing Summary of attempt page;
- enhancing final Submit all and finish confirmation;
- handling timeout/autosubmit cases;
- preview attempt detection;
- AJAX endpoint creation;
- event logging;
- report page integration.

Do not assume a hacky solution is acceptable. If the quiz access rule plugin cannot safely inject all needed UI, consider a hybrid Moodle-standard approach, such as:

- quiz access rule plugin for settings/rules;
- AMD JavaScript for attempt-page UI;
- external functions/AJAX endpoint for saving skip state;
- report page under plugin namespace;
- Moodle events for logging.

---

## 35. Data Reconciliation Rules

Server-side reconciliation is necessary because browser-side state can be unreliable.

Recommended rules:

1. If question has a valid saved answer, skip flag should be cleared or ignored.
2. If skip flag exists and answer is blank, report as skipped.
3. If no skip flag and answer is blank, report as blank/not confirmed skipped.
4. If attempt was auto-submitted by timeout, preserve saved skip flags only.
5. If attempt is preview, exclude from real learner reports.
6. If attempt is not counted by Moodle, plugin report must show that Moodle attempt status.
7. If skip flag was saved after attempt was closed, reject it.
8. If skip flag was submitted by a user who does not own the attempt and lacks capability, reject it.

---

## 36. Edge Cases

### 36.1 Learner Marks Skip Then Answers Later

Expected:

- Skip mark is removed.
- Report shows answered, not skipped.

### 36.2 Learner Marks Skip Then Unticks

Expected:

- Skip mark is removed.
- Report shows blank/not skipped if no answer is saved.

### 36.3 Learner Leaves Blank and Continues Without Marking

Expected:

- No skip mark.
- Question remains blank/not confirmed skipped until final submission confirmation.

### 36.4 Learner Leaves Blank Until Final Submit

Expected:

- Final warning shown.
- If learner chooses Go back, nothing is submitted.
- If learner confirms, all remaining unanswered questions are marked skipped and then Moodle submits normally.

### 36.5 Time Expires Before Final Submit Confirmation

Expected:

- Moodle time-expiry behavior applies.
- Do not create new skip marks from remaining blank questions.
- Preserve only already-saved skip marks.

### 36.6 Grace Period Starts

Expected:

- New skip decisions should not be allowed if no more answers can be changed.
- Preserve prior saved skip marks.

### 36.7 Attempt Not Counted

Expected:

- Plugin must not override Moodle.
- Reports must show Moodle attempt status clearly.

### 36.8 Preview Attempt

Expected:

- Feature visible for testing.
- Data excluded from real student reports.

### 36.9 SEB/Proctoring Tool Blocks AJAX

Expected:

- UI should show not saved / connection issue.
- Navigation fallback may still save if same-site form submit is allowed.
- Compatibility must be tested with each lockdown/proctoring tool.

---

## 37. Testing Plan

### 37.1 Functional Tests

Test:

- skip checkbox appears when plugin enabled;
- skip checkbox does not appear when plugin disabled;
- manual checkbox save;
- manual checkbox uncheck;
- answer after skip clears skip;
- Next warning with unanswered questions;
- Go back option;
- Mark unanswered as skipped and continue option;
- Continue without marking as skipped option;
- Summary page statuses;
- final submission warning;
- Submit and mark unanswered as skipped;
- Go back from final warning;
- report output;
- export output if implemented.

### 37.2 Timeout Tests

Test all three Moodle time-expiry settings:

1. Open attempts submitted automatically.
2. Grace period, no more questions answered.
3. Attempts must be submitted before time expires or not counted.

For each, test:

- saved skip before timeout;
- local unsaved skip before timeout;
- blank with no skip;
- answer saved before timeout;
- current page skip saved by AJAX before timeout;
- current page skip not saved before timeout.

### 37.3 Autosave Tests

Test with Moodle autosave:

- enabled;
- disabled if possible/configurable;
- short autosave interval;
- network interruption during autosave;
- AJAX success before autosave;
- AJAX failure but navigation success;
- autosave success but skip AJAX failure.

### 37.4 Preview Tests

Test teacher/admin preview:

- UI appears;
- warnings work;
- skip records are marked preview or excluded;
- real learner reports do not include preview data.

### 37.5 Proctoring Tests

Test with:

- SEB;
- any AKU-used proctoring/lockdown tool;
- normal browser;
- mobile browser if quizzes allow mobile use;
- restricted network conditions.

### 37.6 Security Tests

Test:

- learner cannot update another learner’s attempt;
- learner cannot update closed attempt;
- learner cannot update attempt after time expiry where not allowed;
- sesskey required;
- invalid attempt id rejected;
- invalid slot rejected;
- preview data excluded;
- report access controlled by capability.

### 37.7 Upgrade Tests

Test install/upgrade/uninstall:

- fresh install;
- upgrade from v1.0 to v1.1;
- database table creation;
- database field addition;
- language strings;
- cache purge;
- uninstall cleanup policy.

---

## 38. Code Quality and Moodle Best Practices

The plugin should follow:

- Moodle coding style;
- Moodle security guidelines;
- Moodle plugin contribution expectations;
- PHP strictness and compatibility with supported Moodle PHP versions;
- Moodle namespace/autoloading conventions;
- Moodle AMD JavaScript practices;
- Moodle Mustache/templates where appropriate;
- Moodle Events API;
- Moodle DB API;
- Moodle capabilities/access control.

Recommended validation tools:

- Moodle Code Checker / PHP_CodeSniffer with Moodle rules;
- PHPStan where compatible;
- PHPUnit tests where practical;
- Behat tests for quiz flow where practical;
- Moodle plugin validation before any public release.

---

## 39. Suggested Development Phases

### Phase 1: Technical Feasibility Spike

Goal:

- Confirm exact Moodle quiz hooks.
- Confirm UI injection method.
- Confirm AJAX save endpoint.
- Confirm attempt/slot/questionattempt mapping.
- Confirm preview detection.
- Confirm timeout behavior.

Deliverable:

- Small prototype on dev Moodle only.

### Phase 2: Core Plugin Skeleton

Goal:

- Create plugin structure.
- Add version.php.
- Add lang strings.
- Add db/install.xml.
- Add capabilities.
- Add settings.
- Add basic attempt-page UI.

### Phase 3: Save/State Engine

Goal:

- Implement AJAX save.
- Implement navigation fallback save.
- Implement state reconciliation.
- Implement server-side validation.

### Phase 4: Learner Flow

Goal:

- Add checkbox UI.
- Add save status UI.
- Add Next warning.
- Add final submission warning.
- Add summary page status enhancements if safely possible.

### Phase 5: Reports and Logs

Goal:

- Add report page.
- Add export if needed.
- Add Moodle events.
- Exclude preview data.

### Phase 6: Timeout, Autosave, Preview, Proctoring Testing

Goal:

- Test all edge cases.
- Test SEB/proctoring compatibility.
- Fix conflicts.

### Phase 7: Production Readiness

Goal:

- Code checker.
- Security review.
- Performance review.
- Accessibility review.
- Documentation.
- Deployment plan.

---

## 40. Deployment Considerations

Before live deployment:

- Test on dev/staging Moodle matching AKU version.
- Test with realistic quiz settings.
- Test with SEB/proctoring if used.
- Test with time limits.
- Test with autosave enabled.
- Test with multiple question types.
- Train teachers/admins on report interpretation.
- Explain that “skipped” means saved learner intention, not merely blank.
- Confirm backup/rollback plan.

Production rollout suggestion:

1. Install plugin on test environment.
2. Enable for one pilot quiz only.
3. Run teacher preview.
4. Run student test users.
5. Run SEB/proctoring test if applicable.
6. Review logs/reports.
7. Fix issues.
8. Enable for selected real quiz.
9. Monitor.
10. Expand gradually.

---

## 41. Documentation for Teachers/Admins

Teacher-facing explanation should say:

> This quiz uses intentional skip tracking. Students may mark a question as skipped. If they leave questions unanswered and submit the attempt, they will be warned that unanswered questions will be recorded as skipped if they continue. A skipped question means the student explicitly confirmed or saved an intention to skip. A blank question without saved skip status may indicate timeout, technical issue, or incomplete attempt.

Important report interpretation:

| Report status | Meaning |
|---|---|
| Answered | Student answered the question. |
| Skipped | Student explicitly marked/confirmed skipped and the state was saved. |
| Blank / not confirmed skipped | No answer and no saved skip intention. |
| Auto-submitted / unknown | Moodle submitted due to time/system behavior; no new skip inference. |

---

## 42. Documentation for Learners

Learner-facing explanation should be simple:

> If you do not want to answer a question, you can mark it as intentionally skipped. If you are not sure and want to return later, you can leave it blank and continue without marking it skipped. When you finally submit the quiz, any unanswered questions will be marked as skipped if you choose to continue.

---

## 43. Non-Negotiable Design Decisions Already Agreed

1. This is **not** a forfeit button.
2. This is a **per-question intentional skip** feature.
3. Blank answer does not automatically mean skipped during navigation.
4. Next-page warning must have three options:
   - Go back;
   - Mark unanswered as skipped and continue;
   - Continue without marking as skipped.
5. Final submission warning must have two options:
   - Go back;
   - Submit and mark unanswered as skipped.
6. Skip state must reflect visually in the checkbox/status.
7. Skip state should save immediately by AJAX and again through navigation fallback.
8. Moodle autosave is not enough by itself.
9. Timeout auto-submit must preserve saved skip marks only.
10. Timeout auto-submit must not infer new skip marks from blank questions.
11. Grace period must not allow new skip decisions if Moodle disallows new answers.
12. Moodle attempt status remains authoritative.
13. Preview should show the feature but not pollute real reports.
14. Plugin must not be SEB-specific.
15. Plugin should work with SEB/proctoring tools by staying inside Moodle’s standard quiz flow.
16. Plugin must follow Moodle best practices and avoid core modifications.

---

## 44. Summary

The final desired plugin is a Moodle quiz enhancement that records intentional per-question skipping while preserving Moodle’s standard quiz behavior.

The core workflow:

```text
Question page:
Learner may manually mark question as skipped.

Next page:
If blanks exist, learner chooses:
- Go back
- Mark unanswered as skipped and continue
- Continue without marking as skipped

Summary page:
Learner sees answered, skipped, and not-yet-answered statuses.

Submit all and finish:
Learner is warned that all remaining unanswered questions will be recorded as skipped if they continue.

Timeout/system submission:
Only already-saved skip marks are preserved; no new skip marks are inferred.
```

Final architectural principle:

> The plugin must respect Moodle’s quiz flow and only add skip-intention metadata. Moodle remains the authority for attempts, responses, timing, submission, grading, preview, and proctoring behavior.

