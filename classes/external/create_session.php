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
use core_external\external_single_structure;
use core_external\external_value;
use local_profilephoto\event\session_started;
use local_profilephoto\local\audit\logger;
use local_profilephoto\local\session\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Build a new photography session (queue) from a course or cohort filter.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_session extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filtertype' => new external_value(PARAM_ALPHA, 'course or cohort'),
            'filterid' => new external_value(PARAM_INT, 'courseid or cohortid, matching filtertype'),
            'ordertype' => new external_value(PARAM_ALPHA, 'Ordering strategy', VALUE_DEFAULT, 'lastname'),
        ]);
    }

    /**
     * Create the session.
     *
     * @param string $filtertype
     * @param int $filterid
     * @param string $ordertype
     * @return array
     */
    public static function execute(string $filtertype, int $filterid, string $ordertype = 'lastname'): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'filtertype' => $filtertype,
            'filterid' => $filterid,
            'ordertype' => $ordertype,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:capture', $context);
        require_sesskey();

        $filterkey = $params['filtertype'] === 'cohort' ? 'cohortid' : 'courseid';
        $sessionid = manager::create_session(
            $USER->id,
            $params['filtertype'],
            [$filterkey => $params['filterid']],
            $params['ordertype']
        );

        $progress = manager::get_progress($sessionid);

        logger::log('session_started', $USER->id, null, $sessionid, 'success',
            $params['filtertype'] . ':' . $params['filterid'] . ' (' . $progress['total'] . ' students)');

        session_started::create([
            'objectid' => $sessionid,
            'context' => $context,
        ])->trigger();

        return [
            'sessionid' => $sessionid,
            'total' => $progress['total'],
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sessionid' => new external_value(PARAM_INT, 'New session id'),
            'total' => new external_value(PARAM_INT, 'Number of students queued'),
        ]);
    }
}
