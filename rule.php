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

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use quizaccess_intentionalskip\local\state_manager;

/**
 * Quiz access rule for intentional per-question skip tracking.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_intentionalskip extends access_rule_base {

    /**
     * Return the rule when intentional skip tracking is enabled for this quiz.
     *
     * @param quiz_settings $quizobj Quiz settings object.
     * @param int $timenow Current timestamp.
     * @param bool $canignoretimelimits Whether the user can ignore time limits.
     * @return self|null
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->intentionalskip_enabled)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Add quiz-level settings.
     *
     * @param mod_quiz_mod_form $quizform Quiz form.
     * @param MoodleQuickForm $mform Moodle quick form.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('html', html_writer::tag('h4',
                get_string('pluginname', 'quizaccess_intentionalskip'), ['class' => 'mt-3']));

        $mform->addElement('selectyesno', 'intentionalskip_enabled',
                get_string('enableintentionalskip', 'quizaccess_intentionalskip'));
        $mform->addHelpButton('intentionalskip_enabled', 'enableintentionalskip', 'quizaccess_intentionalskip');
        $mform->setDefault('intentionalskip_enabled', 0);

        $mform->addElement('selectyesno', 'intentionalskip_showcheckbox',
                get_string('showcheckbox', 'quizaccess_intentionalskip'));
        $mform->setDefault('intentionalskip_showcheckbox', 1);
        $mform->hideIf('intentionalskip_showcheckbox', 'intentionalskip_enabled', 'eq', 0);

        $mform->addElement('selectyesno', 'intentionalskip_warnonnext',
                get_string('warnonnext', 'quizaccess_intentionalskip'));
        $mform->setDefault('intentionalskip_warnonnext', 1);
        $mform->hideIf('intentionalskip_warnonnext', 'intentionalskip_enabled', 'eq', 0);

        $mform->addElement('selectyesno', 'intentionalskip_allowcontinue',
                get_string('allowcontinue', 'quizaccess_intentionalskip'));
        $mform->addHelpButton('intentionalskip_allowcontinue', 'allowcontinue', 'quizaccess_intentionalskip');
        $mform->setDefault('intentionalskip_allowcontinue', 1);
        $mform->hideIf('intentionalskip_allowcontinue', 'intentionalskip_enabled', 'eq', 0);

        $mform->addElement('selectyesno', 'intentionalskip_ajaxsave',
                get_string('ajaxsave', 'quizaccess_intentionalskip'));
        $mform->addHelpButton('intentionalskip_ajaxsave', 'ajaxsave', 'quizaccess_intentionalskip');
        $mform->setDefault('intentionalskip_ajaxsave', 1);
        $mform->hideIf('intentionalskip_ajaxsave', 'intentionalskip_enabled', 'eq', 0);

        $mform->addElement('selectyesno', 'intentionalskip_excludepreviews',
                get_string('excludepreviews', 'quizaccess_intentionalskip'));
        $mform->setDefault('intentionalskip_excludepreviews', 1);
        $mform->hideIf('intentionalskip_excludepreviews', 'intentionalskip_enabled', 'eq', 0);

        $mform->addElement('html', html_writer::empty_tag('hr', ['class' => 'my-3']));
    }

    /**
     * Save quiz-level settings.
     *
     * @param stdClass $quiz Quiz form data.
     */
    public static function save_settings($quiz) {
        global $DB, $USER;

        $enabled = !empty($quiz->intentionalskip_enabled) ? 1 : 0;
        $existing = $DB->get_record('quizaccess_intskip_config', ['quizid' => $quiz->id]);

        if (!$enabled && !$existing) {
            return;
        }

        $now = time();
        $record = $existing ?: (object) [
            'quizid' => $quiz->id,
            'timecreated' => $now,
        ];

        $record->enabled = $enabled;
        $record->showcheckbox = !empty($quiz->intentionalskip_showcheckbox) ? 1 : 0;
        $record->warnonnext = !empty($quiz->intentionalskip_warnonnext) ? 1 : 0;
        $record->allowcontinue = !empty($quiz->intentionalskip_allowcontinue) ? 1 : 0;
        $record->ajaxsave = !empty($quiz->intentionalskip_ajaxsave) ? 1 : 0;
        $record->excludepreviews = !empty($quiz->intentionalskip_excludepreviews) ? 1 : 0;
        $record->timemodified = $now;
        $record->usermodified = $USER->id ?? 0;

        if ($existing) {
            $DB->update_record('quizaccess_intskip_config', $record);
        } else {
            $DB->insert_record('quizaccess_intskip_config', $record);
        }
    }

    /**
     * Delete quiz-level settings when the quiz is deleted.
     *
     * @param stdClass $quiz Quiz record.
     */
    public static function delete_settings($quiz) {
        global $DB;

        $DB->delete_records('quizaccess_intskip_config', ['quizid' => $quiz->id]);
    }

    /**
     * Efficiently load this access rule's settings with the quiz.
     *
     * @param int $quizid Quiz id.
     * @return array
     */
    public static function get_settings_sql($quizid) {
        return [
            'intskip.enabled AS intentionalskip_enabled,
             intskip.showcheckbox AS intentionalskip_showcheckbox,
             intskip.warnonnext AS intentionalskip_warnonnext,
             intskip.allowcontinue AS intentionalskip_allowcontinue,
             intskip.ajaxsave AS intentionalskip_ajaxsave,
             intskip.excludepreviews AS intentionalskip_excludepreviews',
            'LEFT JOIN {quizaccess_intskip_config} intskip ON intskip.quizid = quiz.id',
            [],
        ];
    }

    /**
     * Show a report link to users who can view the intentional skip report.
     *
     * @return string
     */
    public function description() {
        if (!has_capability('quizaccess/intentionalskip:viewreport', $this->quizobj->get_context())) {
            return '';
        }

        $url = new moodle_url('/mod/quiz/accessrule/intentionalskip/report.php', ['cmid' => $this->quizobj->get_cmid()]);
        return html_writer::link($url, get_string('reporttitle', 'quizaccess_intentionalskip'));
    }

    /**
     * Initialise attempt and summary page JavaScript.
     *
     * @param moodle_page $page Page object.
     */
    public function setup_attempt_page($page) {
        if (!has_capability('quizaccess/intentionalskip:use', $this->quizobj->get_context())) {
            return;
        }

        $strings = [
            'answeredandskippedmessage',
            'answeredandskippedtitle',
            'blanknotconfirmed',
            'clearskipandcontinue',
            'continuewithoutmarking',
            'finalunansweredmessage',
            'finalunansweredtitle',
            'goback',
            'markandcontinue',
            'markasskipped',
            'markedasskipped',
            'markedasskippednotsaved',
            'markedasskippedsaved',
            'markedasskippedsaving',
            'nextunansweredmessage',
            'nextunansweredtitle',
            'notmarkedasskipped',
            'refreshrequiredmessage',
            'refreshrequiredtitle',
            'skiptrackingunavailablemessage',
            'skiptrackingunavailabletitle',
            'submitandmarkskipped',
            'submitwithoutmarking',
        ];
        $page->requires->strings_for_js($strings, 'quizaccess_intentionalskip');
        $attemptid = optional_param('attempt', 0, PARAM_INT);
        $readonly = $page->url->get_path(false) === '/mod/quiz/review.php';
        if ($attemptid) {
            $attemptobj = quiz_attempt::create($attemptid);
            $readonly = $readonly || $attemptobj->is_finished() || $attemptobj->get_state() !== quiz_attempt::IN_PROGRESS;
        }
        $page->requires->js_call_amd('quizaccess_intentionalskip/intentionalskip', 'init', [[
            'cmid' => $this->quizobj->get_cmid(),
            'attemptid' => $attemptid,
            'showcheckbox' => !empty($this->quiz->intentionalskip_showcheckbox),
            'warnonnext' => !empty($this->quiz->intentionalskip_warnonnext),
            'allowcontinue' => !empty($this->quiz->intentionalskip_allowcontinue),
            'ajaxsave' => !empty($this->quiz->intentionalskip_ajaxsave),
            'clientversion' => state_manager::CLIENT_VERSION,
            'readonly' => $readonly,
        ]]);
    }
}
