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

namespace local_profilephoto\local\session;

use coding_exception;
use context_system;
use local_profilephoto\local\access\scope;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Photography sessions and their queue of students (encargo section 12).
 *
 * A session is built once from a filter (course or cohort) and then walked
 * through: each local_profilephoto_session_user row tracks one student's
 * capture status. Sessions belong to the operator who opened them and can
 * be resumed (encargo: "la sesión debe poder reanudarse si se cierra
 * accidentalmente el navegador").
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string[] Valid filter types. */
    const FILTER_TYPES = ['course', 'cohort'];

    /** @var string[] Valid ordering strategies. */
    const ORDER_TYPES = ['lastname', 'firstname', 'email', 'idnumber', 'username'];

    /** @var string[] Valid queue item statuses. */
    const STATUSES = ['pending', 'captured', 'skipped', 'absent', 'error'];

    /**
     * Create a new session and build its queue from the given filter.
     *
     * @param int $operatorid
     * @param string $filtertype 'course' or 'cohort'.
     * @param array $filterdata e.g. ['courseid' => 5] or ['cohortid' => 3].
     * @param string $ordertype one of self::ORDER_TYPES.
     * @return int the new session id.
     * @throws moodle_exception if the filter is invalid or out of the operator's scope.
     */
    public static function create_session(int $operatorid, string $filtertype, array $filterdata, string $ordertype): int {
        global $DB;

        if (!in_array($filtertype, self::FILTER_TYPES, true)) {
            throw new coding_exception('Invalid filtertype: ' . $filtertype);
        }
        if (!in_array($ordertype, self::ORDER_TYPES, true)) {
            $ordertype = 'lastname';
        }

        $userids = self::resolve_candidates($operatorid, $filtertype, $filterdata);

        $sessionid = $DB->insert_record('local_profilephoto_session', (object) [
            'operatorid' => $operatorid,
            'name' => null,
            'contextid' => context_system::instance()->id,
            'filtertype' => $filtertype,
            'filterdata' => json_encode($filterdata),
            'ordertype' => $ordertype,
            'status' => 'active',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $ordered = self::sort_userids($userids, $ordertype);

        $sortorder = 0;
        foreach ($ordered as $userid) {
            $DB->insert_record('local_profilephoto_session_user', (object) [
                'sessionid' => $sessionid,
                'userid' => $userid,
                'sortorder' => $sortorder++,
                'status' => 'pending',
                'attempts' => 0,
                'timemodified' => time(),
            ]);
        }

        return (int) $sessionid;
    }

    /**
     * Resolve the set of candidate user ids for a filter, respecting scope.
     *
     * @param int $operatorid
     * @param string $filtertype
     * @param array $filterdata
     * @return int[]
     * @throws moodle_exception
     */
    private static function resolve_candidates(int $operatorid, string $filtertype, array $filterdata): array {
        global $DB;

        if ($filtertype === 'course') {
            $courseid = (int) ($filterdata['courseid'] ?? 0);
            if (!$courseid || !scope::can_use_course($operatorid, $courseid)) {
                throw new moodle_exception('error_outofscope', 'local_profilephoto');
            }
            $context = \context_course::instance($courseid, MUST_EXIST);
            $enrolled = get_enrolled_users($context, '', 0, 'u.id, u.deleted, u.suspended');
            return self::filter_visible($enrolled, $operatorid);
        }

        // Cohort.
        $cohortid = (int) ($filterdata['cohortid'] ?? 0);
        if (!$cohortid) {
            throw new moodle_exception('error_outofscope', 'local_profilephoto');
        }
        $members = $DB->get_records('cohort_members', ['cohortid' => $cohortid], '', 'userid');
        if (empty($members)) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal(array_keys($members), SQL_PARAMS_NAMED);
        $users = $DB->get_records_select('user', "id {$insql}", $params, '', 'id, deleted, suspended');

        if (scope::has_unrestricted_scope($operatorid)) {
            return self::filter_visible($users, $operatorid);
        }

        // Cohorts can span multiple courses, so each member needs an
        // individual scope check (unlike the course filter, where scope is
        // established once for the whole course).
        $filtered = [];
        foreach ($users as $user) {
            if (scope::can_operate_on_user($operatorid, $user)) {
                $filtered[] = (int) $user->id;
            }
        }
        return $filtered;
    }

    /**
     * Drop deleted users, and suspended users unless the operator may see them.
     *
     * @param array $users
     * @param int $operatorid
     * @return int[]
     */
    private static function filter_visible(array $users, int $operatorid): array {
        $showsuspended = scope::can_view_suspended($operatorid);
        $ids = [];
        foreach ($users as $user) {
            if (!empty($user->deleted)) {
                continue;
            }
            if (!empty($user->suspended) && !$showsuspended) {
                continue;
            }
            $ids[] = (int) $user->id;
        }
        return $ids;
    }

    /**
     * Sort candidate user ids per the requested ordering strategy.
     *
     * @param int[] $userids
     * @param string $ordertype
     * @return int[]
     */
    private static function sort_userids(array $userids, string $ordertype): array {
        global $DB;

        if (empty($userids)) {
            return [];
        }

        $fieldmap = [
            'lastname' => 'lastname, firstname',
            'firstname' => 'firstname, lastname',
            'email' => 'email',
            'idnumber' => 'idnumber',
            'username' => 'username',
        ];
        $orderby = $fieldmap[$ordertype] ?? $fieldmap['lastname'];

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $rows = $DB->get_records_select('user', "id {$insql}", $params, $orderby, 'id');
        return array_map('intval', array_keys($rows));
    }

    /**
     * Load a session record.
     *
     * @param int $sessionid
     * @return stdClass
     */
    public static function get_session(int $sessionid): stdClass {
        global $DB;
        return $DB->get_record('local_profilephoto_session', ['id' => $sessionid], '*', MUST_EXIST);
    }

    /**
     * Ensure the operator owns this session (or can manage any session).
     *
     * @param stdClass $session
     * @param int $operatorid
     * @throws moodle_exception
     */
    public static function require_owner(stdClass $session, int $operatorid): void {
        if ((int) $session->operatorid === $operatorid) {
            return;
        }
        if (has_capability('local/profilephoto:managesessions', context_system::instance(), $operatorid)) {
            return;
        }
        throw new moodle_exception('error_outofscope', 'local_profilephoto');
    }

    /**
     * The operator's most recent still-active session, if any (for resume).
     *
     * @param int $operatorid
     * @return stdClass|null
     */
    public static function get_active_session(int $operatorid): ?stdClass {
        global $DB;
        $records = $DB->get_records('local_profilephoto_session', [
            'operatorid' => $operatorid,
            'status' => 'active',
        ], 'timemodified DESC', '*', 0, 1);
        return $records ? reset($records) : null;
    }

    /**
     * Progress counts for a session.
     *
     * @param int $sessionid
     * @return array [status => count] plus 'total'.
     */
    public static function get_progress(int $sessionid): array {
        global $DB;

        $counts = array_fill_keys(self::STATUSES, 0);
        $rows = $DB->get_records_sql(
            'SELECT status, COUNT(*) AS c FROM {local_profilephoto_session_user} WHERE sessionid = :sessionid GROUP BY status',
            ['sessionid' => $sessionid]
        );
        foreach ($rows as $row) {
            $counts[$row->status] = (int) $row->c;
        }
        $counts['total'] = array_sum($counts);

        return $counts;
    }

    /**
     * The next pending queue entry, in sort order.
     *
     * @param int $sessionid
     * @return stdClass|null {id, sessionid, userid, sortorder}
     */
    public static function get_next_pending(int $sessionid): ?stdClass {
        global $DB;
        $records = $DB->get_records('local_profilephoto_session_user', [
            'sessionid' => $sessionid,
            'status' => 'pending',
        ], 'sortorder ASC', '*', 0, 1);
        return $records ? reset($records) : null;
    }

    /**
     * The full queue for a session, in sort order.
     *
     * @param int $sessionid
     * @return array of stdClass session_user rows.
     */
    public static function get_queue(int $sessionid): array {
        global $DB;
        return array_values($DB->get_records('local_profilephoto_session_user', ['sessionid' => $sessionid], 'sortorder ASC'));
    }

    /**
     * Update a queue entry's status (skip, mark absent, back to pending...).
     *
     * @param int $sessionid
     * @param int $userid
     * @param string $status one of self::STATUSES.
     * @param string|null $error
     */
    public static function set_status(int $sessionid, int $userid, string $status, ?string $error = null): void {
        global $DB;

        if (!in_array($status, self::STATUSES, true)) {
            throw new coding_exception('Invalid queue status: ' . $status);
        }

        $item = $DB->get_record('local_profilephoto_session_user', ['sessionid' => $sessionid, 'userid' => $userid], '*', MUST_EXIST);
        $item->status = $status;
        $item->lasterror = $error;
        $item->timemodified = time();
        $DB->update_record('local_profilephoto_session_user', $item);

        self::maybe_complete($sessionid);
    }

    /**
     * Mark a queue entry as successfully captured.
     *
     * @param int $sessionid
     * @param int $userid
     * @param int $capturedby
     */
    public static function mark_captured(int $sessionid, int $userid, int $capturedby): void {
        global $DB;

        $item = $DB->get_record('local_profilephoto_session_user', ['sessionid' => $sessionid, 'userid' => $userid], '*', MUST_EXIST);
        $item->status = 'captured';
        $item->attempts = $item->attempts + 1;
        $item->capturedby = $capturedby;
        $item->timecaptured = time();
        $item->timemodified = time();
        $item->lasterror = null;
        $DB->update_record('local_profilephoto_session_user', $item);

        $session = self::get_session($sessionid);
        $session->timemodified = time();
        $DB->update_record('local_profilephoto_session', $session);

        self::maybe_complete($sessionid);
    }

    /**
     * Auto-complete a session once no pending items remain.
     *
     * @param int $sessionid
     */
    private static function maybe_complete(int $sessionid): void {
        global $DB;

        $pending = $DB->count_records('local_profilephoto_session_user', ['sessionid' => $sessionid, 'status' => 'pending']);
        if ($pending > 0) {
            return;
        }

        $session = self::get_session($sessionid);
        if ($session->status === 'active') {
            $session->status = 'completed';
            $session->timefinished = time();
            $session->timemodified = time();
            $DB->update_record('local_profilephoto_session', $session);
        }
    }
}
