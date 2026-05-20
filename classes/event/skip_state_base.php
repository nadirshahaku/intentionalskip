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

namespace quizaccess_intentionalskip\event;

use core\event\base;
use mod_quiz\quiz_attempt;
use moodle_url;
use stdClass;

/**
 * Base event for intentional skip state changes.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class skip_state_base extends base {

    /**
     * Create an event from a state record.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param stdClass $state State record.
     * @return static
     */
    public static function create_from_state(quiz_attempt $attemptobj, stdClass $state): self {
        return static::create([
            'context' => $attemptobj->get_context(),
            'courseid' => $attemptobj->get_courseid(),
            'objectid' => $state->id,
            'relateduserid' => $attemptobj->get_userid(),
            'other' => [
                'cmid' => $attemptobj->get_cmid(),
                'quizid' => $attemptobj->get_quizid(),
                'attemptid' => $attemptobj->get_attemptid(),
                'questionattemptid' => $state->questionattemptid,
                'slot' => $state->slot,
                'isskipped' => $state->isskipped,
                'source' => $state->source,
                'ispreview' => $state->ispreview,
            ],
        ]);
    }

    /**
     * Initialise event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'quizaccess_intskip_state';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Event description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->relateduserid}' changed intentional skip state for quiz attempt " .
            "with id '{$this->other['attemptid']}', slot '{$this->other['slot']}', source '{$this->other['source']}'.";
    }

    /**
     * URL for the related attempt.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/mod/quiz/review.php', ['attempt' => $this->other['attemptid']]);
    }

    /**
     * Restore mapping for object id.
     *
     * @return array
     */
    public static function get_objectid_mapping(): array {
        return ['db' => 'quizaccess_intskip_state', 'restore' => 'quizaccess_intskip_state'];
    }

    /**
     * Restore mapping for other ids.
     *
     * @return array
     */
    public static function get_other_mapping(): array {
        return [
            'cmid' => ['db' => 'course_modules', 'restore' => 'course_modules'],
            'quizid' => ['db' => 'quiz', 'restore' => 'quiz'],
            'attemptid' => ['db' => 'quiz_attempts', 'restore' => 'quiz_attempt'],
        ];
    }
}
