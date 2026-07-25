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

namespace local_profilephoto\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_profilephoto.
 *
 * The photo itself is never plugin-owned data: it is stored entirely
 * through \core\user::update_picture() under the core 'user' component
 * (see classes/local/image/updater.php), already covered by core_user's
 * own privacy provider. What THIS provider covers is the plugin's own
 * bookkeeping introduced for sessions/queues/export in this entrega:
 *
 *  - local_profilephoto_session: who ran a session, and what filter they used.
 *  - local_profilephoto_session_user: which students were queued, by whom,
 *    and their capture status.
 *  - local_profilephoto_log: the audit trail (operator, target, action,
 *    result, IP - never image data, tokens or passwords).
 *
 * All of it lives at CONTEXT_SYSTEM (the plugin has no per-course storage
 * in this entrega), which is why get_contexts_for_userid() and
 * get_users_in_context() only ever deal with the system context.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    core_userlist_provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_profilephoto_session', [
            'operatorid' => 'privacy:metadata:session:operatorid',
            'filtertype' => 'privacy:metadata:session:filtertype',
            'filterdata' => 'privacy:metadata:session:filterdata',
            'timecreated' => 'privacy:metadata:session:timecreated',
        ], 'privacy:metadata:session');

        $collection->add_database_table('local_profilephoto_session_user', [
            'userid' => 'privacy:metadata:session_user:userid',
            'capturedby' => 'privacy:metadata:session_user:capturedby',
            'status' => 'privacy:metadata:session_user:status',
            'timecaptured' => 'privacy:metadata:session_user:timecaptured',
        ], 'privacy:metadata:session_user');

        $collection->add_database_table('local_profilephoto_log', [
            'operatorid' => 'privacy:metadata:log:operatorid',
            'targetuserid' => 'privacy:metadata:log:targetuserid',
            'action' => 'privacy:metadata:log:action',
            'ipaddress' => 'privacy:metadata:log:ipaddress',
            'timecreated' => 'privacy:metadata:log:timecreated',
        ], 'privacy:metadata:log');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:corefiles');

        return $collection;
    }

    /**
     * Contexts holding data for a user, as operator, queue entry, or log subject.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT :contextid AS contextid
                 WHERE EXISTS (SELECT 1 FROM {local_profilephoto_session_user} su WHERE su.userid = :userid1)
                    OR EXISTS (SELECT 1 FROM {local_profilephoto_session} s WHERE s.operatorid = :userid2)
                    OR EXISTS (SELECT 1 FROM {local_profilephoto_log} l
                               WHERE l.operatorid = :userid3 OR l.targetuserid = :userid4)";

        $contextlist->add_from_sql($sql, [
            'contextid' => context_system::instance()->id,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
            'userid4' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Every user with data in the given (system) context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_system) {
            return;
        }

        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_profilephoto_session_user}', []);
        $userlist->add_from_sql('operatorid', 'SELECT operatorid FROM {local_profilephoto_session}', []);
        $userlist->add_from_sql('operatorid', 'SELECT operatorid FROM {local_profilephoto_log}', []);
        $userlist->add_from_sql(
            'targetuserid',
            'SELECT targetuserid FROM {local_profilephoto_log} WHERE targetuserid IS NOT NULL',
            []
        );
    }

    /**
     * Export a user's data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (count($contextlist) === 0) {
            return;
        }

        $context = context_system::instance();
        $userid = $contextlist->get_user()->id;

        $queueentries = $DB->get_records('local_profilephoto_session_user', ['userid' => $userid]);
        if ($queueentries) {
            $data = array_map(static function(stdClass $row): stdClass {
                return (object) [
                    'sessionid' => $row->sessionid,
                    'status' => $row->status,
                    'capturedby' => $row->capturedby,
                    'timecaptured' => $row->timecaptured ? userdate($row->timecaptured) : null,
                ];
            }, array_values($queueentries));
            writer::with_context($context)->export_data(
                [get_string('privacy:path:queueentries', 'local_profilephoto')],
                (object) ['entries' => $data]
            );
        }

        $sessions = $DB->get_records('local_profilephoto_session', ['operatorid' => $userid]);
        if ($sessions) {
            $data = array_map(static function(stdClass $row): stdClass {
                return (object) [
                    'filtertype' => $row->filtertype,
                    'status' => $row->status,
                    'timecreated' => userdate($row->timecreated),
                ];
            }, array_values($sessions));
            writer::with_context($context)->export_data(
                [get_string('privacy:path:sessions', 'local_profilephoto')],
                (object) ['sessions' => $data]
            );
        }

        $logs = $DB->get_records_select(
            'local_profilephoto_log',
            'operatorid = :uid OR targetuserid = :uid2',
            ['uid' => $userid, 'uid2' => $userid]
        );
        if ($logs) {
            $data = array_map(static function(stdClass $row) use ($userid): stdClass {
                return (object) [
                    'role' => ((int) $row->operatorid === $userid) ? 'operator' : 'target',
                    'action' => $row->action,
                    'result' => $row->result,
                    'timecreated' => userdate($row->timecreated),
                ];
            }, array_values($logs));
            writer::with_context($context)->export_data(
                [get_string('privacy:path:logs', 'local_profilephoto')],
                (object) ['entries' => $data]
            );
        }
    }

    /**
     * Delete ALL plugin data within a context (used for full-site erasure).
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        if (!$context instanceof context_system) {
            return;
        }

        global $DB;
        $DB->delete_records('local_profilephoto_log');
        $DB->delete_records('local_profilephoto_session_user');
        $DB->delete_records('local_profilephoto_session');
    }

    /**
     * Delete one user's data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (count($contextlist) === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        $DB->delete_records('local_profilephoto_session_user', ['userid' => $userid]);

        $ownsessionids = $DB->get_fieldset_select('local_profilephoto_session', 'id', 'operatorid = :uid', ['uid' => $userid]);
        if ($ownsessionids) {
            [$insql, $params] = $DB->get_in_or_equal($ownsessionids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_profilephoto_session_user', "sessionid {$insql}", $params);
            $DB->delete_records_select('local_profilephoto_log', "sessionid {$insql}", $params);
            $DB->delete_records('local_profilephoto_session', ['operatorid' => $userid]);
        }

        $DB->delete_records_select(
            'local_profilephoto_log',
            'operatorid = :uid OR targetuserid = :uid2',
            ['uid' => $userid, 'uid2' => $userid]
        );
    }

    /**
     * Delete data for a set of approved users within a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_system) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('local_profilephoto_session_user', "userid {$insql}", $params);
        $DB->delete_records_select('local_profilephoto_log', "operatorid {$insql}", $params);
        $DB->delete_records_select('local_profilephoto_log', "targetuserid {$insql}", $params);

        $sessionids = $DB->get_fieldset_select('local_profilephoto_session', 'id', "operatorid {$insql}", $params);
        if ($sessionids) {
            [$sessioninsql, $sessionparams] = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_profilephoto_session_user', "sessionid {$sessioninsql}", $sessionparams);
            $DB->delete_records_select('local_profilephoto_log', "sessionid {$sessioninsql}", $sessionparams);
        }
        $DB->delete_records_select('local_profilephoto_session', "operatorid {$insql}", $params);
    }
}
