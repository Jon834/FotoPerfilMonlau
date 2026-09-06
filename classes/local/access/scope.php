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

namespace local_profilephoto\local\access;

use context_course;
use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves which students an operator is allowed to see and photograph.
 *
 * This intentionally does NOT rely on is_siteadmin() as an access control
 * shortcut (encargo section 17). The allowed set is the intersection of:
 *  - the local/profilephoto:viewallusers capability (unrestricted scope), or
 *    otherwise the courses where the operator holds
 *    local/profilephoto:capture;
 *  - the target user not being deleted;
 *  - the target user not being suspended, unless the operator can view
 *    suspended accounts.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scope {

    /**
     * Whether the operator has an unrestricted scope (all users in the site).
     *
     * @param int $operatorid
     * @return bool
     */
    public static function has_unrestricted_scope(int $operatorid): bool {
        return has_capability('local/profilephoto:viewallusers', context_system::instance(), $operatorid);
    }

    /**
     * Course ids the operator is allowed to photograph students in.
     *
     * Returns null when the operator has an unrestricted scope (no course
     * filtering should be applied). Returns an empty array when the
     * operator has no scope at all.
     *
     * @param int $operatorid
     * @return array|null
     */
    public static function get_allowed_courseids(int $operatorid): ?array {
        if (self::has_unrestricted_scope($operatorid)) {
            return null;
        }

        $courses = get_user_capability_course('local/profilephoto:capture', $operatorid, false, 'id');
        if (!$courses) {
            return [];
        }

        return array_map(static function(stdClass $course): int {
            return (int) $course->id;
        }, $courses);
    }

    /**
     * Whether the operator may view/capture the given target user.
     *
     * Always re-checked server-side right before any write (encargo
     * section 20) - callers must not cache the result across requests.
     *
     * @param int $operatorid
     * @param stdClass $targetuser must contain at least id, deleted, suspended.
     * @return bool
     */
    public static function can_operate_on_user(int $operatorid, stdClass $targetuser): bool {
        if (!empty($targetuser->deleted)) {
            return false;
        }

        if (!empty($targetuser->suspended) && !self::can_view_suspended($operatorid)) {
            return false;
        }

        if (!has_capability('local/profilephoto:capture', context_system::instance(), $operatorid)) {
            return false;
        }

        if (self::has_unrestricted_scope($operatorid)) {
            return true;
        }

        $courseids = self::get_allowed_courseids($operatorid);
        if (empty($courseids)) {
            return false;
        }

        foreach ($courseids as $courseid) {
            if (is_enrolled(context_course::instance($courseid), $targetuser->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the operator may build a session from this course.
     *
     * Cheap single check used by session creation: if the course itself is
     * within the operator's allowed courses, every actively enrolled user
     * in it is automatically in scope, without needing a per-user
     * is_enrolled() check (encargo section 28 performance goals).
     *
     * @param int $operatorid
     * @param int $courseid
     * @return bool
     */
    public static function can_use_course(int $operatorid, int $courseid): bool {
        if (!has_capability('local/profilephoto:capture', context_system::instance(), $operatorid)) {
            return false;
        }

        if (self::has_unrestricted_scope($operatorid)) {
            return true;
        }

        $courseids = self::get_allowed_courseids($operatorid);
        return $courseids !== null && in_array($courseid, $courseids, true);
    }

    /**
     * Cohort ids the operator may build a "Control d'activitat" roster from.
     *
     * Core does not tie cohorts to teaching roles, so "the operator's
     * cohorts" is resolved through the same course scope as everything else
     * in this plugin: a cohort is in scope when at least one of its members
     * is actively enrolled in a course where the operator holds
     * local/profilephoto:capture.
     *
     * Returns null when the operator has an unrestricted scope (no cohort
     * filtering should be applied). Returns an empty array when the operator
     * has no scope at all.
     *
     * @param int $operatorid
     * @return array|null
     */
    public static function get_allowed_cohortids(int $operatorid): ?array {
        global $DB;

        $courseids = self::get_allowed_courseids($operatorid);
        if ($courseids === null) {
            return null;
        }
        if (empty($courseids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');
        $params['active'] = ENROL_USER_ACTIVE;
        $params['enabled'] = ENROL_INSTANCE_ENABLED;
        $sql = "SELECT DISTINCT cm.cohortid
                  FROM {cohort_members} cm
                  JOIN {user_enrolments} ue ON ue.userid = cm.userid AND ue.status = :active
                  JOIN {enrol} e ON e.id = ue.enrolmentid AND e.status = :enabled AND e.courseid $insql";

        return array_map('intval', array_keys($DB->get_records_sql($sql, $params)));
    }

    /**
     * Whether the operator may build a "Control d'activitat" roster from this cohort.
     *
     * @param int $operatorid
     * @param int $cohortid
     * @return bool
     */
    public static function can_use_cohort(int $operatorid, int $cohortid): bool {
        $cohortids = self::get_allowed_cohortids($operatorid);

        return $cohortids === null || in_array($cohortid, $cohortids, true);
    }

    /**
     * Whether the operator is allowed to see suspended accounts.
     *
     * @param int $operatorid
     * @return bool
     */
    public static function can_view_suspended(int $operatorid): bool {
        return has_capability('local/profilephoto:viewallusers', context_system::instance(), $operatorid);
    }

    /**
     * Whether the operator may see sensitive identifiers (idnumber, email, DNI field).
     *
     * @param int $operatorid
     * @return bool
     */
    public static function can_view_identifiers(int $operatorid): bool {
        return has_capability('local/profilephoto:viewidentifiers', context_system::instance(), $operatorid);
    }
}
