<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace quizaccess_intentionalskip\event;

/**
 * Event for final unanswered questions marked skipped.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class final_unanswered_marked_skipped extends skip_state_base {

    /**
     * Event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventfinalunansweredmarkedskipped', 'quizaccess_intentionalskip');
    }
}
