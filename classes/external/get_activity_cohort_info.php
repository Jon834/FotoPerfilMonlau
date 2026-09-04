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
use core_external\external_single_structure;
use core_external\external_value;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolve a cohort's name and current member count, for the "Cohort ...
 * 30 alumnes" readout shown right after picking a cohort on the "Control
 * d'activitat" screen.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activity_cohort_info extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cohortid' => new external_value(PARAM_INT, 'Cohort id'),
        ]);
    }

    /**
     * Load the cohort info.
     *
     * @param int $cohortid
     * @return array
     */
    public static function execute(int $cohortid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['cohortid' => $cohortid]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:exportactivity', $context);
        require_sesskey();

        $cohort = $DB->get_record('cohort', ['id' => $params['cohortid']], 'id, name, contextid', IGNORE_MISSING);
        if (!$cohort) {
            throw new moodle_exception('error_activitycohortnotfound', 'local_profilephoto');
        }

        $cohortcontext = context::instance_by_id((int) $cohort->contextid, IGNORE_MISSING);
        if (!$cohortcontext || !has_capability('moodle/cohort:view', $cohortcontext)) {
            throw new moodle_exception('error_outofscope', 'local_profilephoto');
        }

        // No core API returns just a cohort's member count; {cohort_members} is the
        // canonical source and a plain count is the cheapest possible read of it.
        $membercount = $DB->count_records('cohort_members', ['cohortid' => $cohort->id]);

        return [
            'id' => (int) $cohort->id,
            'name' => format_string($cohort->name),
            'membercount' => $membercount,
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Cohort id'),
            'name' => new external_value(PARAM_NOTAGS, 'Cohort name'),
            'membercount' => new external_value(PARAM_INT, 'Current number of cohort members'),
        ]);
    }
}
