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

namespace quizaccess_intentionalskip\local;

use coding_exception;
use context_module;
use mod_quiz\quiz_attempt;
use moodle_exception;
use question_state;
use stdClass;

/**
 * Intentional skip state service.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_manager {

    /** @var int Client/server AJAX contract version. */
    public const CLIENT_VERSION = 2026052004;

    /** @var string Manual checkbox source. */
    public const SOURCE_MANUAL_CHECKBOX = 'manual_checkbox';

    /** @var string Manual uncheck source. */
    public const SOURCE_MANUAL_UNCHECK = 'manual_uncheck';

    /** @var string Next-page confirmation source. */
    public const SOURCE_NEXT_CONFIRMATION = 'next_page_confirmation';

    /** @var string Final submit confirmation source. */
    public const SOURCE_FINAL_CONFIRMATION = 'final_submit_confirmation';

    /** @var string Automatic answer reconciliation source. */
    public const SOURCE_CLEARED_BY_ANSWER = 'auto_cleared_after_answer';

    /**
     * Validate that an AJAX request was made by compatible client code.
     *
     * @param int $clientversion Client/server contract version sent by JavaScript.
     */
    public static function validate_client_version(int $clientversion): void {
        if ($clientversion !== self::CLIENT_VERSION) {
            throw new moodle_exception('errorstaleclient', 'quizaccess_intentionalskip');
        }
    }

    /**
     * Validate the source for a manual slot save request.
     *
     * @param string $source Source string.
     */
    public static function validate_manual_source(string $source): void {
        if (!in_array($source, [
                self::SOURCE_MANUAL_CHECKBOX,
                self::SOURCE_MANUAL_UNCHECK,
                self::SOURCE_CLEARED_BY_ANSWER,
            ], true)) {
            throw new moodle_exception('errorinvalidsource', 'quizaccess_intentionalskip');
        }
    }

    /**
     * Validate an attempt and return useful context.
     *
     * @param int $attemptid Attempt id.
     * @param int $cmid Course module id.
     * @param bool $requireopen Whether attempt must accept changes.
     * @param bool $allowreview Whether non-owner users with review/report access may read the attempt.
     * @return array{0: quiz_attempt, 1: context_module}
     */
    public static function validate_attempt(int $attemptid, int $cmid, bool $requireopen = true,
            bool $allowreview = false): array {
        global $USER;

        require_sesskey();

        $attemptobj = quiz_attempt::create($attemptid);
        if ($cmid && (int) $attemptobj->get_cmid() !== $cmid) {
            throw new moodle_exception('errorinvalidattempt', 'quizaccess_intentionalskip');
        }

        require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
        $context = $attemptobj->get_context();
        \core_external\external_api::validate_context($context);

        $ownattempt = (int) $attemptobj->get_userid() === (int) $USER->id;
        if (!$ownattempt) {
            if (!$allowreview || !$attemptobj->is_review_allowed()) {
                throw new moodle_exception('notyourattempt', 'quiz', $attemptobj->view_url());
            }
            require_capability('quizaccess/intentionalskip:viewreport', $context);
        } else {
            if (!$attemptobj->is_preview_user()) {
                $attemptobj->require_capability('mod/quiz:attempt');
            }
            require_capability('quizaccess/intentionalskip:use', $context);
        }

        if (empty($attemptobj->get_quiz()->intentionalskip_enabled)) {
            throw new moodle_exception('errornotenabled', 'quizaccess_intentionalskip');
        }

        if ($requireopen && $attemptobj->is_finished()) {
            throw new moodle_exception('errorattemptclosed', 'quizaccess_intentionalskip');
        }

        if ($requireopen && $attemptobj->get_state() !== quiz_attempt::IN_PROGRESS) {
            throw new moodle_exception('errorattemptclosed', 'quizaccess_intentionalskip');
        }

        return [$attemptobj, $context];
    }

    /**
     * Get skip state for all real questions in an attempt.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @return array
     */
    public static function get_attempt_state(quiz_attempt $attemptobj): array {
        global $DB;

        $records = $DB->get_records('quizaccess_intskip_state', ['attemptid' => $attemptobj->get_attemptid()], '', '*');
        $byslot = [];
        foreach ($records as $record) {
            $byslot[(int) $record->slot] = $record;
        }

        $slots = [];
        foreach ($attemptobj->get_slots() as $slot) {
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            $answered = self::slot_is_answered($attemptobj, $slot);
            $record = $byslot[$slot] ?? null;
            $skipped = !$answered && $record && !empty($record->isskipped);
            $slots[] = [
                'slot' => $slot,
                'answered' => $answered,
                'skipped' => $skipped,
                'source' => $skipped ? $record->source : '',
                'timemodified' => $skipped ? (int) $record->timemodified : 0,
                'status' => $answered ? 'answered' : ($skipped ? 'skipped' : 'blank'),
            ];
        }

        return $slots;
    }

    /**
     * Save one slot state.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param int $slot Slot number.
     * @param bool $isskipped New skip state.
     * @param string $source Source.
     * @return stdClass Saved state record.
     */
    public static function save_slot_state(quiz_attempt $attemptobj, int $slot, bool $isskipped, string $source): stdClass {
        if (!self::slot_exists($attemptobj, $slot) || !$attemptobj->is_real_question($slot)) {
            throw new moodle_exception('errorinvalidslot', 'quizaccess_intentionalskip');
        }

        if ($isskipped && self::slot_is_answered($attemptobj, $slot)) {
            $isskipped = false;
            $source = self::SOURCE_CLEARED_BY_ANSWER;
        }

        return self::write_state($attemptobj, $slot, $isskipped, $source);
    }

    /**
     * Mark multiple unanswered slots as skipped.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param int[] $slots Requested slots.
     * @param string $source Source.
     * @return array
     */
    public static function mark_unanswered_slots(quiz_attempt $attemptobj, array $slots, string $source): array {
        $saved = [];
        foreach (array_unique(array_map('intval', $slots)) as $slot) {
            if (!self::slot_exists($attemptobj, $slot) || !$attemptobj->is_real_question($slot)) {
                continue;
            }

            if (self::slot_is_answered($attemptobj, $slot)) {
                self::write_state($attemptobj, $slot, false, self::SOURCE_CLEARED_BY_ANSWER);
                continue;
            }

            if (self::slot_is_currently_skipped($attemptobj, $slot)) {
                continue;
            }

            $record = self::write_state($attemptobj, $slot, true, $source);
            $saved[] = (int) $record->slot;
        }

        return $saved;
    }

    /**
     * Mark every currently unanswered real question in the attempt.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param string $source Source.
     * @return array
     */
    public static function mark_all_unanswered_slots(quiz_attempt $attemptobj, string $source): array {
        $slots = [];
        foreach ($attemptobj->get_slots() as $slot) {
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            if (self::slot_is_answered($attemptobj, $slot)) {
                if (self::slot_is_currently_skipped($attemptobj, (int) $slot)) {
                    self::write_state($attemptobj, (int) $slot, false, self::SOURCE_CLEARED_BY_ANSWER);
                }
                continue;
            }

            if (!self::slot_is_answered($attemptobj, $slot)) {
                $slots[] = $slot;
            }
        }

        return self::mark_unanswered_slots($attemptobj, $slots, $source);
    }

    /**
     * Whether Moodle currently considers the slot answered.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param int $slot Slot number.
     * @return bool
     */
    public static function slot_is_answered(quiz_attempt $attemptobj, int $slot): bool {
        $state = $attemptobj->get_question_state($slot);

        return $state != question_state::$todo && $state != question_state::$invalid;
    }

    /**
     * Whether the slot belongs to the quiz attempt.
     *
     * Moodle may return slot numbers from page layout data as strings, while
     * external function parameters are validated as integers.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param int $slot Slot number.
     * @return bool
     */
    private static function slot_exists(quiz_attempt $attemptobj, int $slot): bool {
        return in_array($slot, array_map('intval', $attemptobj->get_slots()), true);
    }

    /**
     * Whether the slot already has a saved skip flag.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param int $slot Slot number.
     * @return bool
     */
    private static function slot_is_currently_skipped(quiz_attempt $attemptobj, int $slot): bool {
        global $DB;

        return $DB->record_exists('quizaccess_intskip_state', [
            'attemptid' => $attemptobj->get_attemptid(),
            'slot' => $slot,
            'isskipped' => 1,
        ]);
    }

    /**
     * Write state and history.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param int $slot Slot number.
     * @param bool $isskipped New skip state.
     * @param string $source Source.
     * @return stdClass
     */
    private static function write_state(quiz_attempt $attemptobj, int $slot, bool $isskipped, string $source): stdClass {
        global $DB, $USER;

        $source = clean_param($source, PARAM_ALPHANUMEXT);
        if ($source === '') {
            throw new coding_exception('Intentional skip source is required.');
        }

        $existing = $DB->get_record('quizaccess_intskip_state', [
            'attemptid' => $attemptobj->get_attemptid(),
            'slot' => $slot,
        ]);
        $oldstate = $existing ? (int) $existing->isskipped : 0;
        $newstate = $isskipped ? 1 : 0;
        $now = time();

        $qa = $attemptobj->get_question_attempt($slot);
        $record = $existing ?: (object) [
            'courseid' => $attemptobj->get_courseid(),
            'cmid' => $attemptobj->get_cmid(),
            'quizid' => $attemptobj->get_quizid(),
            'attemptid' => $attemptobj->get_attemptid(),
            'slot' => $slot,
            'userid' => $attemptobj->get_userid(),
            'timecreated' => $now,
        ];

        $record->questionattemptid = $qa->get_database_id();
        $record->isskipped = $newstate;
        $record->source = $source;
        $record->ispreview = $attemptobj->is_preview() ? 1 : 0;
        $record->timemodified = $now;
        $record->lastsavedby = $USER->id ?? 0;

        if ($existing) {
            $DB->update_record('quizaccess_intskip_state', $record);
        } else {
            $record->id = $DB->insert_record('quizaccess_intskip_state', $record);
        }

        self::write_history($record, $oldstate, $newstate, $source, $now);
        self::trigger_event($attemptobj, $record, $oldstate, $newstate, $source);

        return $record;
    }

    /**
     * Insert an audit history row.
     *
     * @param stdClass $state State record.
     * @param int $oldstate Old state.
     * @param int $newstate New state.
     * @param string $source Source.
     * @param int $now Timestamp.
     */
    private static function write_history(stdClass $state, int $oldstate, int $newstate, string $source, int $now): void {
        global $DB;

        $action = $newstate ? 'marked' : 'unmarked';
        if ($source === self::SOURCE_FINAL_CONFIRMATION) {
            $action = 'final_submit_marked';
        } else if ($source === self::SOURCE_NEXT_CONFIRMATION) {
            $action = 'page_marked';
        } else if ($source === self::SOURCE_CLEARED_BY_ANSWER) {
            $action = 'cleared_by_answer';
        }

        $history = (object) [
            'stateid' => $state->id,
            'courseid' => $state->courseid,
            'cmid' => $state->cmid,
            'quizid' => $state->quizid,
            'attemptid' => $state->attemptid,
            'questionattemptid' => $state->questionattemptid,
            'slot' => $state->slot,
            'userid' => $state->userid,
            'action' => $action,
            'oldstate' => $oldstate,
            'newstate' => $newstate,
            'source' => $source,
            'ispreview' => $state->ispreview,
            'timecreated' => $now,
        ];
        $DB->insert_record('quizaccess_intskip_history', $history);
    }

    /**
     * Trigger an event for the state transition.
     *
     * @param quiz_attempt $attemptobj Attempt object.
     * @param stdClass $state State record.
     * @param int $oldstate Old state.
     * @param int $newstate New state.
     * @param string $source Source.
     */
    private static function trigger_event(
        quiz_attempt $attemptobj,
        stdClass $state,
        int $oldstate,
        int $newstate,
        string $source
    ): void {
        if ($source === self::SOURCE_CLEARED_BY_ANSWER && $oldstate !== $newstate) {
            \quizaccess_intentionalskip\event\skip_state_cleared_by_answer::create_from_state($attemptobj, $state)->trigger();
            return;
        }

        if ($source === self::SOURCE_FINAL_CONFIRMATION && $newstate) {
            \quizaccess_intentionalskip\event\final_unanswered_marked_skipped::create_from_state($attemptobj, $state)->trigger();
            return;
        }

        if ($source === self::SOURCE_NEXT_CONFIRMATION && $newstate) {
            \quizaccess_intentionalskip\event\page_unanswered_marked_skipped::create_from_state($attemptobj, $state)->trigger();
            return;
        }

        if ($oldstate === $newstate) {
            return;
        }

        if ($newstate) {
            \quizaccess_intentionalskip\event\question_marked_skipped::create_from_state($attemptobj, $state)->trigger();
        } else {
            \quizaccess_intentionalskip\event\question_unmarked_skipped::create_from_state($attemptobj, $state)->trigger();
        }
    }
}
