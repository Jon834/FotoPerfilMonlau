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
 * List the cohorts the operator may use for a "Control d'activitat" export.
 *
 * The whole feature is gated by local/profilephoto:exportactivity (system
 * level). Which cohorts an operator then sees follows the plugin's own
 * course scope (@see scope::get_allowed_cohortids): an unrestricted
 * operator (local/profilephoto:viewallusers) sees every cohort; anyone
 * else sees only cohorts with a member enrolled in a course they hold
 * local/profilephoto:capture in.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activity_cohorts extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * List visible cohorts.
     *
     * @return array
     */
    public static function execute(): array {
        global $DB, $USER;

        self::validate_parameters(self::execute_parameters(), []);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:exportactivity', $context);
        require_sesskey();

        $allowedcohortids = scope::get_allowed_cohortids((int) $USER->id);
        if ($allowedcohortids !== null && empty($allowedcohortids)) {
            return ['cohorts' => []];
        }

        $records = $DB->get_records('cohort', [], 'name', 'id, name', 0, 500);

        $cohorts = [];
        foreach ($records as $record) {
            if ($allowedcohortids !== null && !in_array((int) $record->id, $allowedcohortids, true)) {
                continue;
            }
            $cohorts[] = [
                'id' => (int) $record->id,
                'name' => format_string($record->name),
            ];
        }

        return ['cohorts' => $cohorts];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'cohorts' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Cohort id'),
                'name' => new external_value(PARAM_NOTAGS, 'Cohort name'),
            ])),
        ]);
    }
}
