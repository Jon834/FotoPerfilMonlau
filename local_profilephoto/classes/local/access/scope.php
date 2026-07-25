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
