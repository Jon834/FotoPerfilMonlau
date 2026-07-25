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
use local_profilephoto\local\audit\logger;
use local_profilephoto\local\session\manager;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Skip a student, mark them absent, or put them back to pending
 * (encargo section 4.4).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_queue_item extends external_api {

    /** @var string[] Statuses an operator may set directly from the UI. */
    const ALLOWED_STATUSES = ['pending', 'skipped', 'absent'];

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sessionid' => new external_value(PARAM_INT, 'Session id'),
            'userid' => new external_value(PARAM_INT, 'Target user id'),
            'status' => new external_value(PARAM_ALPHA, 'pending | skipped | absent'),
        ]);
    }

    /**
     * Update the queue entry.
     *
     * @param int $sessionid
     * @param int $userid
     * @param string $status
     * @return array
     */
    public static function execute(int $sessionid, int $userid, string $status): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'sessionid' => $sessionid,
            'userid' => $userid,
            'status' => $status,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/profilephoto:capture', $context);
        require_sesskey();

        if (!in_array($params['status'], self::ALLOWED_STATUSES, true)) {
            throw new moodle_exception('error_invalidstatus', 'local_profilephoto');
        }

        $session = manager::get_session($params['sessionid']);
        manager::require_owner($session, $USER->id);

        manager::set_status($session->id, $params['userid'], $params['status']);
        logger::log('queue_' . $params['status'], $USER->id, $params['userid'], $session->id);

        $progress = manager::get_progress($session->id);

        return [
            'sessionid' => (int) $session->id,
            'pending' => $progress['pending'],
            'captured' => $progress['captured'],
            'skipped' => $progress['skipped'],
            'absent' => $progress['absent'],
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sessionid' => new external_value(PARAM_INT, 'Session id'),
            'pending' => new external_value(PARAM_INT, 'Pending count'),
            'captured' => new external_value(PARAM_INT, 'Captured count'),
            'skipped' => new external_value(PARAM_INT, 'Skipped count'),
            'absent' => new external_value(PARAM_INT, 'Absent count'),
        ]);
    }
}
