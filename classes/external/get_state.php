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
use quizaccess_intentionalskip\local\state_manager;

/**
 * Get intentional skip state for an attempt.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_state extends external_api {

    /**
     * Request parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'clientversion' => new external_value(PARAM_INT, 'Client compatibility version', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute request.
     *
     * @param int $attemptid Attempt ID.
     * @param int $cmid Course module ID.
     * @return array
     */
    public static function execute(int $attemptid, int $cmid, int $clientversion = 0): array {
        [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'clientversion' => $clientversion,
        ] = self::validate_parameters(self::execute_parameters(), [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'clientversion' => $clientversion,
        ]);

        state_manager::validate_client_version($clientversion);
        [$attemptobj] = state_manager::validate_attempt($attemptid, $cmid, false, true);

        return [
            'slots' => state_manager::get_attempt_state($attemptobj),
        ];
    }

    /**
     * Response shape.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'slots' => new external_multiple_structure(new external_single_structure([
                'slot' => new external_value(PARAM_INT, 'Question slot'),
                'answered' => new external_value(PARAM_BOOL, 'Whether Moodle considers this slot answered'),
                'skipped' => new external_value(PARAM_BOOL, 'Whether this slot is intentionally skipped'),
                'source' => new external_value(PARAM_ALPHANUMEXT, 'Skip source'),
                'timemodified' => new external_value(PARAM_INT, 'Last modified timestamp'),
                'status' => new external_value(PARAM_ALPHA, 'Status key'),
            ])),
        ]);
    }
}
