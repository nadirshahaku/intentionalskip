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

namespace quizaccess_intentionalskip\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use quizaccess_intentionalskip\local\state_manager;

/**
 * Mark multiple unanswered slots as intentionally skipped.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_slots extends external_api {

    /**
     * Request parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'slots' => new external_multiple_structure(new external_value(PARAM_INT, 'Question slot')),
            'source' => new external_value(PARAM_ALPHANUMEXT, 'Save source'),
            'markall' => new external_value(PARAM_BOOL, 'Mark all unanswered slots', VALUE_DEFAULT, false),
            'clientversion' => new external_value(PARAM_INT, 'Client compatibility version', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute request.
     *
     * @param int $attemptid Attempt ID.
     * @param int $cmid Course module ID.
     * @param array $slots Question slots.
     * @param string $source Save source.
     * @param bool $markall Whether to mark all unanswered slots.
     * @return array
     */
    public static function execute(
        int $attemptid,
        int $cmid,
        array $slots,
        string $source,
        bool $markall = false,
        int $clientversion = 0
    ): array {
        [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'slots' => $slots,
            'source' => $source,
            'markall' => $markall,
            'clientversion' => $clientversion,
        ] = self::validate_parameters(self::execute_parameters(), [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'slots' => $slots,
            'source' => $source,
            'markall' => $markall,
            'clientversion' => $clientversion,
        ]);

        state_manager::validate_client_version($clientversion);
        if (!in_array($source, [state_manager::SOURCE_NEXT_CONFIRMATION, state_manager::SOURCE_FINAL_CONFIRMATION], true)) {
            throw new invalid_parameter_exception('Invalid intentional skip source.');
        }

        [$attemptobj] = state_manager::validate_attempt($attemptid, $cmid);
        if ($markall) {
            $saved = state_manager::mark_all_unanswered_slots($attemptobj, $source);
        } else {
            $saved = state_manager::mark_unanswered_slots($attemptobj, $slots, $source);
        }

        return [
            'status' => true,
            'markedslots' => $saved,
        ];
    }

    /**
     * Response shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Whether save succeeded'),
            'markedslots' => new external_multiple_structure(new external_value(PARAM_INT, 'Marked slot')),
        ]);
    }
}
