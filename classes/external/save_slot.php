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
use core_external\external_single_structure;
use core_external\external_value;
use quizaccess_intentionalskip\local\state_manager;

/**
 * Save intentional skip state for one slot.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_slot extends external_api {

    /**
     * Request parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'slot' => new external_value(PARAM_INT, 'Question slot'),
            'isskipped' => new external_value(PARAM_BOOL, 'Skip state'),
            'source' => new external_value(PARAM_ALPHANUMEXT, 'Save source'),
            'clientversion' => new external_value(PARAM_INT, 'Client compatibility version', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute request.
     *
     * @param int $attemptid Attempt ID.
     * @param int $cmid Course module ID.
     * @param int $slot Question slot.
     * @param bool $isskipped Skip state.
     * @param string $source Save source.
     * @return array
     */
    public static function execute(
        int $attemptid,
        int $cmid,
        int $slot,
        bool $isskipped,
        string $source,
        int $clientversion = 0
    ): array {
        [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'slot' => $slot,
            'isskipped' => $isskipped,
            'source' => $source,
            'clientversion' => $clientversion,
        ] = self::validate_parameters(self::execute_parameters(), [
            'attemptid' => $attemptid,
            'cmid' => $cmid,
            'slot' => $slot,
            'isskipped' => $isskipped,
            'source' => $source,
            'clientversion' => $clientversion,
        ]);

        state_manager::validate_client_version($clientversion);
        state_manager::validate_manual_source($source);
        [$attemptobj] = state_manager::validate_attempt($attemptid, $cmid);
        $record = state_manager::save_slot_state($attemptobj, $slot, $isskipped, $source);

        return [
            'status' => true,
            'slot' => (int) $record->slot,
            'skipped' => (bool) $record->isskipped,
            'source' => $record->source,
            'timemodified' => (int) $record->timemodified,
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
            'slot' => new external_value(PARAM_INT, 'Question slot'),
            'skipped' => new external_value(PARAM_BOOL, 'Saved skip state'),
            'source' => new external_value(PARAM_ALPHANUMEXT, 'Save source'),
            'timemodified' => new external_value(PARAM_INT, 'Last modified timestamp'),
        ]);
    }
}
