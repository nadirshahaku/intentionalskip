# Intentional Skip Graceful Degradation Logic

**Date:** 20 May 2026  
**Plugin:** `quizaccess_intentionalskip`  
**Location:** `mod/quiz/accessrule/intentionalskip`

## Purpose

This note documents the graceful-degradation logic added for intentional skip tracking.

The design decision is:

> Intentional skip tracking is optional metadata. If AJAX or JavaScript is unavailable, blocked, or failing, Moodle quiz progress and submission must not be blocked. The plugin must not guess skipped status from blank answers.

## Core Rule

Only skip states successfully saved on the Moodle server are valid.

If a learner locally ticks a skip checkbox but the save does not reach the server, that question must not be reported as intentionally skipped.

## AJAX Works

When Moodle AJAX works normally:

1. The plugin loads saved skip state using `quizaccess_intentionalskip_get_state`.
2. The plugin injects skip controls into the quiz page.
3. Manual checkbox changes are saved using `quizaccess_intentionalskip_save_slot`.
4. Next-page confirmation saves using `quizaccess_intentionalskip_mark_slots`.
5. Final submit confirmation saves all unanswered questions using `quizaccess_intentionalskip_mark_slots`.

In this case, skip tracking works as intended.

## Initial AJAX State Load Fails

If the first AJAX state load fails:

1. Skip controls are not injected.
2. Custom skip warnings are not attached.
3. Moodle quiz pages continue to work normally.
4. No new intentional skip records are created by the plugin.

This means the plugin degrades to unavailable instead of interfering with the quiz.

## Manual Checkbox Save Fails

If a learner ticks or unticks a skip checkbox and the AJAX save fails:

1. The UI shows `Not saved - check your connection`.
2. The local checkbox state is not treated as authoritative.
3. No server-side skip record is created or updated.
4. Reports must not show that question as intentionally skipped unless a previous successful server save exists.

## Next Page Marking Fails

When the learner chooses:

```text
Mark unanswered as skipped and continue
```

the plugin first tries to save the skip state by AJAX.

If that AJAX save fails:

1. The plugin shows a warning that skip tracking could not save.
2. The learner can choose to go back or continue.
3. If the learner continues, Moodle proceeds to the next page normally.
4. The unanswered questions are not recorded as intentionally skipped.

The important behavior is:

> Failed skip saving must not trap the learner on the page.

## Final Submit Save Fails

When the learner chooses:

```text
Submit and mark unanswered as skipped
```

the plugin tries to save final unanswered skip records by AJAX before submitting the Moodle attempt.

If that AJAX save fails:

1. The plugin shows a warning that skip tracking could not save.
2. The learner can choose to go back or submit without marking skipped.
3. If the learner submits, Moodle final submission proceeds normally.
4. Remaining unanswered questions are not newly recorded as intentionally skipped.

The important behavior is:

> Failed skip tracking must not block final quiz submission.

## JavaScript Fails Completely

If JavaScript is unavailable or blocked:

1. The plugin UI does not run.
2. Skip controls are not shown.
3. Custom skip warnings are not shown.
4. Moodle quiz behavior continues normally.
5. No new intentional skip records are created.

The server cannot reliably know that JavaScript failed, because the plugin code never ran in the browser.

## Timeout and Auto-Submit

If Moodle auto-submits the quiz because of timeout:

1. Moodle remains authoritative.
2. Already-saved skip records remain valid.
3. Unsaved local checkbox state is ignored.
4. Blank answers are not newly inferred as skipped.

## Reporting Meaning

Reports must interpret data conservatively:

| Report state | Meaning |
|---|---|
| Skipped | A skip record successfully reached the Moodle server. |
| Blank / not confirmed skipped | No answer and no saved skip record. |

Reports do not currently show `skip tracking failed`, because that can only be known when the server receives reliable evidence of a failure.

If JavaScript fails completely, the server receives no such evidence.

## Deliberately Not Implemented

The current graceful-degradation design does not add:

- hidden form fallback fields;
- a wrapper around Moodle `processattempt.php`;
- quiz attempt event observers to reconstruct skip intent;
- server-side guessing from blank answers;
- report claims that skip tracking failed without server-side evidence.

## Final Principle

It is safer to lose optional skip-tracking metadata than to block a learner, alter Moodle quiz flow, or infer learner intention from blank answers.
