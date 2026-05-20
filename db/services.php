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
 * External service definitions for intentional skip tracking.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'quizaccess_intentionalskip_get_state' => [
        'classname' => 'quizaccess_intentionalskip\external\get_state',
        'methodname' => 'execute',
        'description' => 'Get intentional skip state for a quiz attempt.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'quizaccess/intentionalskip:use',
    ],
    'quizaccess_intentionalskip_save_slot' => [
        'classname' => 'quizaccess_intentionalskip\external\save_slot',
        'methodname' => 'execute',
        'description' => 'Save intentional skip state for one quiz attempt slot.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'quizaccess/intentionalskip:use',
    ],
    'quizaccess_intentionalskip_mark_slots' => [
        'classname' => 'quizaccess_intentionalskip\external\mark_slots',
        'methodname' => 'execute',
        'description' => 'Mark multiple unanswered quiz attempt slots as intentionally skipped.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'quizaccess/intentionalskip:use',
    ],
];
