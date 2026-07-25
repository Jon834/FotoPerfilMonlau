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

defined('MOODLE_INTERNAL') || die();

/**
 * List courses and cohorts the operator may build a photography session
 * from, to populate the session-setup filter dropdowns on index.php.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_session_options extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Load the available filter options.
     *
     * @return array
     */
    public static function execute(): array {
        global $DB, $USER;

        self::validate_parameters(self::execute_parameters(), []);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:capture', $context);
        require_sesskey();

        $unrestricted = scope::has_unrestricted_scope($USER->id);

        $courses = [];
        if ($unrestricted) {
            $records = enrol_get_users_courses($USER->id, true, 'id, fullname');
        } else {
            $courseids = scope::get_allowed_courseids($USER->id) ?? [];
            $records = [];
            if ($courseids) {
                [$insql, $sqlparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
                $records = $DB->get_records_select('course', "id {$insql}", $sqlparams, 'fullname', 'id, fullname');
            }
        }
        foreach ($records as $course) {
            $courses[] = ['id' => (int) $course->id, 'name' => format_string($course->fullname)];
        }

        $cohorts = [];
        if ($unrestricted) {
            $records = $DB->get_records('cohort', [], 'name', 'id, name');
            foreach ($records as $cohort) {
                $cohorts[] = ['id' => (int) $cohort->id, 'name' => format_string($cohort->name)];
            }
        }

        return [
            'courses' => $courses,
            'cohorts' => $cohorts,
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course id'),
                'name' => new external_value(PARAM_NOTAGS, 'Course full name'),
            ])),
            'cohorts' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Cohort id'),
                'name' => new external_value(PARAM_NOTAGS, 'Cohort name'),
            ])),
        ]);
    }
}
