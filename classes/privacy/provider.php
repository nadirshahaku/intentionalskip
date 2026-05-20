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

namespace quizaccess_intentionalskip\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for intentional skip tracking.
 *
 * @package    quizaccess_intentionalskip
 * @copyright  2026 Aga Khan University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider, plugin_provider {

    /**
     * Metadata about stored user data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('quizaccess_intskip_state', [
            'userid' => 'privacy:metadata:quizaccess_intskip_state:userid',
            'attemptid' => 'privacy:metadata:quizaccess_intskip_state:attemptid',
            'slot' => 'privacy:metadata:quizaccess_intskip_state:slot',
            'isskipped' => 'privacy:metadata:quizaccess_intskip_state:isskipped',
            'source' => 'privacy:metadata:quizaccess_intskip_state:source',
            'timemodified' => 'privacy:metadata:quizaccess_intskip_state:timemodified',
        ], 'privacy:metadata:quizaccess_intskip_state');

        $collection->add_database_table('quizaccess_intskip_history', [
            'userid' => 'privacy:metadata:quizaccess_intskip_history:userid',
            'attemptid' => 'privacy:metadata:quizaccess_intskip_history:attemptid',
            'slot' => 'privacy:metadata:quizaccess_intskip_history:slot',
            'source' => 'privacy:metadata:quizaccess_intskip_history:source',
        ], 'privacy:metadata:quizaccess_intskip_history');

        return $collection;
    }

    /**
     * Contexts containing user information.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {quizaccess_intskip_state} s ON s.cmid = cm.id
                 WHERE s.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextmodule' => CONTEXT_MODULE, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Export user data.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {
        // Export support can be expanded with the report export story.
    }

    /**
     * Delete all user data for a context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $DB->delete_records('quizaccess_intskip_history', ['cmid' => $context->instanceid]);
        $DB->delete_records('quizaccess_intskip_state', ['cmid' => $context->instanceid]);
    }

    /**
     * Delete user data for approved contexts.
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
        global $DB;

        if ($contextlist->get_component() !== 'quizaccess_intentionalskip') {
            return;
        }

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $DB->delete_records('quizaccess_intskip_history', [
                'cmid' => $context->instanceid,
                'userid' => $contextlist->get_user()->id,
            ]);
            $DB->delete_records('quizaccess_intskip_state', [
                'cmid' => $context->instanceid,
                'userid' => $contextlist->get_user()->id,
            ]);
        }
    }
}
