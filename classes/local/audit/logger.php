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

namespace local_profilephoto\local\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Writes rows to local_profilephoto_log.
 *
 * Never pass image data, base64 blobs, tokens or passwords in $details
 * (encargo section 18) - only short technical messages.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logger {

    /**
     * Record one audit log entry.
     *
     * @param string $action e.g. 'picture_updated', 'session_started', 'export_created'.
     * @param int $operatorid
     * @param int|null $targetuserid null for actions with no single target student.
     * @param int|null $sessionid
     * @param string $result 'success' or 'error'.
     * @param string|null $details short technical message, no personal binary data.
     */
    public static function log(
        string $action,
        int $operatorid,
        ?int $targetuserid = null,
        ?int $sessionid = null,
        string $result = 'success',
        ?string $details = null
    ): void {
        global $DB;

        $DB->insert_record('local_profilephoto_log', (object) [
            'sessionid' => $sessionid,
            'operatorid' => $operatorid,
            'targetuserid' => $targetuserid,
            'action' => $action,
            'result' => $result,
            'details' => $details,
            'ipaddress' => getremoteaddr(null),
            'timecreated' => time(),
        ]);
    }
}
