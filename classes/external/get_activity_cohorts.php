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

use context;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

/**
 * List the cohorts the operator may use for a "Control d'activitat" export.
 *
 * Scope is the intersection of the plugin's own
 * local/profilephoto:exportactivity capability (system level: gates the
 * whole feature) and the core moodle/cohort:view capability on each
 * cohort's own context (category or system: reuses Moodle's own notion of
 * "cohorts I may see" instead of inventing a parallel one, and naturally
 * separates "own cohorts" from "all cohorts" by context).
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
        global $DB;

        self::validate_parameters(self::execute_parameters(), []);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:exportactivity', $context);
        require_sesskey();

        $records = $DB->get_records('cohort', [], 'name', 'id, name, contextid', 0, 500);

        $cohorts = [];
        foreach ($records as $record) {
            try {
                $cohortcontext = context::instance_by_id((int) $record->contextid, IGNORE_MISSING);
            } catch (\Throwable $e) {
                $cohortcontext = null;
            }
            if (!$cohortcontext || !has_capability('moodle/cohort:view', $cohortcontext)) {
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
