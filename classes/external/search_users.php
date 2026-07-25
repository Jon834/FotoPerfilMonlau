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
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_profilephoto\local\access\scope;
use local_profilephoto\local\search\user_search;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * AJAX search for candidate users (encargo section 4.2).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_users extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW_TRIMMED, 'Search text, minimum 2 characters'),
            'limit' => new external_value(PARAM_INT, 'Maximum number of results', VALUE_DEFAULT, 20),
        ]);
    }

    /**
     * Search for candidate users.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public static function execute(string $query, int $limit = 20): array {
        global $USER, $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'limit' => $limit,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:searchusers', $context);
        require_sesskey();

        $maxresults = (int) get_config('local_profilephoto', 'maxsearchresults') ?: 20;
        $limit = min($params['limit'], $maxresults);

        $rows = user_search::search($params['query'], $USER->id, $limit);
        $canviewids = scope::can_view_identifiers($USER->id);

        $out = [];
        foreach ($rows as $row) {
            $picture = new user_picture($row);
            $picture->size = 100;

            $out[] = [
                'id' => (int) $row->id,
                'fullname' => fullname($row),
                'email' => $canviewids ? (string) $row->email : '',
                'idnumber' => $canviewids ? (string) $row->idnumber : '',
                'username' => $canviewids ? (string) $row->username : '',
                'suspended' => (bool) $row->suspended,
                'hasphoto' => ((int) $row->picture) > 0,
                'pictureurl' => $picture->get_url($PAGE)->out(false),
            ];
        }

        return $out;
    }

    /**
     * Return definition.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'User id'),
                'fullname' => new external_value(PARAM_NOTAGS, 'Full name'),
                'email' => new external_value(PARAM_RAW, 'Email, empty string if not permitted'),
                'idnumber' => new external_value(PARAM_RAW, 'Idnumber, empty string if not permitted'),
                'username' => new external_value(PARAM_RAW, 'Username, empty string if not permitted'),
                'suspended' => new external_value(PARAM_BOOL, 'Whether the account is suspended'),
                'hasphoto' => new external_value(PARAM_BOOL, 'Whether the user already has a custom picture'),
                'pictureurl' => new external_value(PARAM_URL, 'Current profile picture URL'),
            ])
        );
    }
}
