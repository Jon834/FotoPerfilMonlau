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

namespace local_profilephoto\external;

use context_system;
use core\user as core_user_class;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_profilephoto\local\access\scope;
use local_profilephoto\local\session\manager;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Progress counts plus the next pending student for a session, in one call
 * (avoids separate round-trips for progress and "who's next" - encargo
 * section 28 performance goals).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_queue_status extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Session id'),
        ]);
    }

    /**
     * Load progress and the next pending student.
     *
     * @param int $sessionid
     * @return array
     */
    public static function execute(int $sessionid): array {
        global $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), ['sessionid' => $sessionid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:capture', $context);
        require_sesskey();

        $session = manager::get_session($params['sessionid']);
        manager::require_owner($session, $USER->id);

        $progress = manager::get_progress($session->id);
        $nextitem = manager::get_next_pending($session->id);

        $next = null;
        if ($nextitem) {
            $user = core_user_class::get_user($nextitem->userid, 'id, firstname, lastname, email, username, idnumber, ' .
                'picture, imagealt, suspended, deleted', MUST_EXIST);
            $canviewids = scope::can_view_identifiers($USER->id);
            $picture = new user_picture($user);
            $picture->size = 300;

            $next = [
                'id' => (int) $user->id,
                'fullname' => fullname($user),
                'email' => $canviewids ? (string) $user->email : '',
                'idnumber' => $canviewids ? (string) $user->idnumber : '',
                'username' => $canviewids ? (string) $user->username : '',
                'suspended' => (bool) $user->suspended,
                'hasphoto' => ((int) $user->picture) > 0,
                'pictureurl' => $picture->get_url($PAGE)->out(false),
                'canupdate' => has_capability('local/profilephoto:updatepicture', $context),
            ];
        }

        $result = [
            'sessionid' => (int) $session->id,
            'status' => $session->status,
            'total' => $progress['total'],
            'pending' => $progress['pending'],
            'captured' => $progress['captured'],
            'skipped' => $progress['skipped'],
            'absent' => $progress['absent'],
            'error' => $progress['error'],
        ];

        // Omit the key entirely rather than sending null: VALUE_OPTIONAL on
        // an external_single_structure governs whether the key may be
        // absent, not whether its value may be null.
        if ($next !== null) {
            $result['next'] = $next;
        }

        return $result;
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sessionid' => new external_value(PARAM_INT, 'Session id'),
            'status' => new external_value(PARAM_ALPHA, 'active | completed | abandoned'),
            'total' => new external_value(PARAM_INT, 'Total students in the queue'),
            'pending' => new external_value(PARAM_INT, 'Pending count'),
            'captured' => new external_value(PARAM_INT, 'Captured count'),
            'skipped' => new external_value(PARAM_INT, 'Skipped count'),
            'absent' => new external_value(PARAM_INT, 'Absent count'),
            'error' => new external_value(PARAM_INT, 'Error count'),
            'next' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User id'),
                'fullname' => new external_value(PARAM_NOTAGS, 'Full name'),
                'email' => new external_value(PARAM_RAW, 'Email, empty string if not permitted'),
                'idnumber' => new external_value(PARAM_RAW, 'Idnumber, empty string if not permitted'),
                'username' => new external_value(PARAM_RAW, 'Username, empty string if not permitted'),
                'suspended' => new external_value(PARAM_BOOL, 'Whether the account is suspended'),
                'hasphoto' => new external_value(PARAM_BOOL, 'Whether the user already has a custom picture'),
                'pictureurl' => new external_value(PARAM_URL, 'Current profile picture URL'),
                'canupdate' => new external_value(PARAM_BOOL, 'Whether the operator may replace this picture'),
            ], 'Next pending student, absent if the queue has none left', VALUE_OPTIONAL),
        ]);
    }
}
