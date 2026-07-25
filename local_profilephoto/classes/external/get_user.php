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
use moodle_exception;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * AJAX load of full detail for a single candidate user, used once the
 * operator selects someone (from search results, a QR scan, or manual
 * navigation) - encargo section 5, "Zona derecha: alumno".
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Target user id'),
        ]);
    }

    /**
     * Load a single user's detail, enforcing scope even though the caller
     * already passed the search screen (identifiers can be manipulated
     * client-side - encargo section 20).
     *
     * @param int $userid
     * @return array
     */
    public static function execute(int $userid): array {
        global $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), ['userid' => $userid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:searchusers', $context);
        require_sesskey();

        $user = core_user_class::get_user($params['userid'], 'id, firstname, lastname, email, username, idnumber, ' .
            'picture, imagealt, suspended, deleted', MUST_EXIST);

        if (!scope::can_operate_on_user($USER->id, $user)) {
            throw new moodle_exception('error_outofscope', 'local_profilephoto');
        }

        $canviewids = scope::can_view_identifiers($USER->id);
        $picture = new user_picture($user);
        $picture->size = 300;

        return [
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

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'User id'),
            'fullname' => new external_value(PARAM_NOTAGS, 'Full name'),
            'email' => new external_value(PARAM_RAW, 'Email, empty string if not permitted'),
            'idnumber' => new external_value(PARAM_RAW, 'Idnumber, empty string if not permitted'),
            'username' => new external_value(PARAM_RAW, 'Username, empty string if not permitted'),
            'suspended' => new external_value(PARAM_BOOL, 'Whether the account is suspended'),
            'hasphoto' => new external_value(PARAM_BOOL, 'Whether the user already has a custom picture'),
            'pictureurl' => new external_value(PARAM_URL, 'Current profile picture URL'),
            'canupdate' => new external_value(PARAM_BOOL, 'Whether the operator may replace this picture'),
        ]);
    }
}
