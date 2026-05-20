<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for intentional skip tracking.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['ajaxsave'] = 'Save skip changes immediately';
$string['ajaxsave_help'] = 'When enabled, skip checkbox changes are saved immediately by a same-site AJAX request.';
$string['allowcontinue'] = 'Allow continuing without marking unanswered questions skipped';
$string['allowcontinue_help'] = 'When enabled, students can move to the next page while leaving unanswered questions unmarked so they can return later.';
$string['answered'] = 'Answered';
$string['blanknotconfirmed'] = 'Blank / not confirmed skipped';
$string['cachedef_settings'] = 'Intentional skip quiz settings';
$string['clearedbyanswer'] = 'Cleared because the question is answered';
$string['continuewithoutmarking'] = 'Continue without marking as skipped';
$string['enableintentionalskip'] = 'Enable intentional skip tracking';
$string['enableintentionalskip_help'] = 'Adds per-question controls so students can explicitly mark unanswered questions as intentionally skipped.';
$string['errorattemptclosed'] = 'This attempt is no longer open.';
$string['errorinvalidattempt'] = 'Invalid quiz attempt.';
$string['errorinvalidslot'] = 'Invalid question slot.';
$string['errorinvalidsource'] = 'Invalid skip tracking source.';
$string['errornotenabled'] = 'Intentional skip tracking is not enabled for this quiz.';
$string['errorstaleclient'] = 'This quiz page must be refreshed before skip tracking can continue.';
$string['eventfinalunansweredmarkedskipped'] = 'Final unanswered questions marked skipped';
$string['eventpageunansweredmarkedskipped'] = 'Page unanswered questions marked skipped';
$string['eventquestionmarkedskipped'] = 'Question marked skipped';
$string['eventquestionunmarkedskipped'] = 'Question unmarked skipped';
$string['eventskipstateclearedbyanswer'] = 'Skip state cleared by answer';
$string['excludepreviews'] = 'Exclude preview attempts from reports';
$string['finalunansweredmessage'] = 'You still have unanswered question(s). If you continue, all unanswered question(s) will be recorded as intentionally skipped. If you want to answer or change any question, click Go back before submitting.';
$string['finalunansweredtitle'] = 'Unanswered questions';
$string['goback'] = 'Go back';
$string['intentionalskip:exportreport'] = 'Export intentional skip reports';
$string['intentionalskip:manage'] = 'Manage intentional skip tracking';
$string['intentionalskip:use'] = 'Use intentional skip tracking during quiz attempts';
$string['intentionalskip:viewreport'] = 'View intentional skip reports';
$string['markasskipped'] = 'Mark this question as intentionally skipped';
$string['markandcontinue'] = 'Mark unanswered as skipped and continue';
$string['markedasskipped'] = 'Marked as skipped';
$string['markedasskippednotsaved'] = 'Not saved - check your connection';
$string['markedasskippedsaved'] = 'Saved';
$string['markedasskippedsaving'] = 'Saving...';
$string['notmarkedasskipped'] = 'Not marked as intentionally skipped';
$string['nextunansweredmessage'] = 'You have unanswered question(s) on this page. Do you want to mark them as skipped before moving to the next page?';
$string['nextunansweredtitle'] = 'Unanswered questions on this page';
$string['pluginname'] = 'Intentional skip tracking';
$string['privacy:metadata:quizaccess_intskip_history'] = 'Audit history of intentional skip state changes.';
$string['privacy:metadata:quizaccess_intskip_history:attemptid'] = 'The quiz attempt id.';
$string['privacy:metadata:quizaccess_intskip_history:slot'] = 'The question slot.';
$string['privacy:metadata:quizaccess_intskip_history:source'] = 'How the skip state changed.';
$string['privacy:metadata:quizaccess_intskip_history:userid'] = 'The user who made the quiz attempt.';
$string['privacy:metadata:quizaccess_intskip_state'] = 'Current intentional skip state for quiz attempt slots.';
$string['privacy:metadata:quizaccess_intskip_state:attemptid'] = 'The quiz attempt id.';
$string['privacy:metadata:quizaccess_intskip_state:isskipped'] = 'Whether the slot is currently marked as intentionally skipped.';
$string['privacy:metadata:quizaccess_intskip_state:slot'] = 'The question slot.';
$string['privacy:metadata:quizaccess_intskip_state:source'] = 'How the current skip state was last saved.';
$string['privacy:metadata:quizaccess_intskip_state:userid'] = 'The user who made the quiz attempt.';
$string['privacy:metadata:quizaccess_intskip_state:timemodified'] = 'When the skip state was last saved.';
$string['reportattempt'] = 'Attempt';
$string['reportattemptstatus'] = 'Attempt status';
$string['reportmoodlestatus'] = 'Moodle response status';
$string['reportpreview'] = 'Preview?';
$string['reportquestion'] = 'Question';
$string['reportquiz'] = 'Quiz';
$string['reportsavedtime'] = 'Skip saved time';
$string['reportskipstatus'] = 'Skip status';
$string['reportsource'] = 'Skip source';
$string['reporttitle'] = 'Intentional skip report';
$string['reportuser'] = 'User';
$string['refreshrequiredmessage'] = 'This quiz page was loaded before the latest skip-tracking update. Refresh the page before changing skip status.';
$string['refreshrequiredtitle'] = 'Refresh required';
$string['showcheckbox'] = 'Show skip checkbox on question pages';
$string['skipped'] = 'Skipped';
$string['skiptrackingunavailablemessage'] = 'Intentional skip tracking could not save right now. You may continue, but unanswered questions will not be recorded as intentionally skipped unless they were already saved.';
$string['skiptrackingunavailabletitle'] = 'Skip tracking unavailable';
$string['submitwithoutmarking'] = 'Submit without marking skipped';
$string['submitandmarkskipped'] = 'Submit and mark unanswered as skipped';
$string['answeredandskippedmessage'] = 'One or more questions have both an answer and an intentional skip mark. A question with an answer cannot also be submitted as intentionally skipped. You may go back to review them, or continue and clear the skip mark from answered questions.';
$string['answeredandskippedtitle'] = 'Answered questions marked skipped';
$string['clearskipandcontinue'] = 'Clear skip mark and continue';
$string['warnonnext'] = 'Warn on Next when this page has unanswered questions';
