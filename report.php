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
 * Intentional skip report.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\quiz_attempt;
use quizaccess_intentionalskip\local\state_manager;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$includepreviews = optional_param('includepreviews', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('quizaccess/intentionalskip:viewreport', $context);

$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/intentionalskip/report.php', ['cmid' => $cmid]));
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_title(get_string('reporttitle', 'quizaccess_intentionalskip'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(format_string($quiz->name), new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]));
$PAGE->navbar->add(get_string('reporttitle', 'quizaccess_intentionalskip'));

$attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id], 'userid, attempt, id');
$states = $DB->get_records('quizaccess_intskip_state', ['quizid' => $quiz->id]);
$statesbyattemptslot = [];
foreach ($states as $state) {
    $statesbyattemptslot[(int) $state->attemptid][(int) $state->slot] = $state;
}

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = [
    get_string('reportuser', 'quizaccess_intentionalskip'),
    get_string('reportattempt', 'quizaccess_intentionalskip'),
    get_string('reportattemptstatus', 'quizaccess_intentionalskip'),
    get_string('reportquestion', 'quizaccess_intentionalskip'),
    get_string('reportmoodlestatus', 'quizaccess_intentionalskip'),
    get_string('reportskipstatus', 'quizaccess_intentionalskip'),
    get_string('reportsource', 'quizaccess_intentionalskip'),
    get_string('reportsavedtime', 'quizaccess_intentionalskip'),
    get_string('reportpreview', 'quizaccess_intentionalskip'),
];
$table->data = [];

foreach ($attempts as $attempt) {
    $attemptobj = quiz_attempt::create((int) $attempt->id);
    if (!$includepreviews && $attemptobj->is_preview()) {
        continue;
    }

    $user = core_user::get_user($attemptobj->get_userid(), '*', MUST_EXIST);
    foreach ($attemptobj->get_slots() as $slot) {
        if (!$attemptobj->is_real_question($slot)) {
            continue;
        }

        $record = $statesbyattemptslot[$attemptobj->get_attemptid()][$slot] ?? null;
        $answered = state_manager::slot_is_answered($attemptobj, $slot);
        $skipped = !$answered && $record && !empty($record->isskipped);
        $table->data[] = [
            fullname($user),
            (int) $attemptobj->get_attempt_number(),
            quiz_attempt_state($quiz, $attemptobj->get_attempt()),
            s($attemptobj->get_question_number($slot)),
            $attemptobj->get_question_status($slot, false),
            $skipped ? get_string('skipped', 'quizaccess_intentionalskip')
                : ($answered ? get_string('answered', 'quizaccess_intentionalskip')
                    : get_string('blanknotconfirmed', 'quizaccess_intentionalskip')),
            $record ? s($record->source) : '',
            $record ? userdate((int) $record->timemodified) : '',
            $attemptobj->is_preview() ? get_string('yes') : get_string('no'),
        ];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reporttitle', 'quizaccess_intentionalskip'));
echo html_writer::table($table);
echo $OUTPUT->footer();
