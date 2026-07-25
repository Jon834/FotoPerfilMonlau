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

namespace local_profilephoto\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Deletes export ZIPs left over in the temp area past their retention
 * period (encargo section 15: "eliminar los ZIP temporales
 * automáticamente"). Safety net for exports that were built but never
 * downloaded - export.php itself deletes its file immediately via
 * send_temp_file() on the normal successful-download path.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_exports extends \core\task\scheduled_task {

    /**
     * Task name, shown in Site administration > Server > Scheduled tasks.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cleanup_exports', 'local_profilephoto');
    }

    /**
     * Delete stale export ZIPs.
     */
    public function execute() {
        $retentionminutes = (int) get_config('local_profilephoto', 'exportretentionminutes') ?: 60;
        $cutoff = time() - ($retentionminutes * 60);

        $tempdir = make_temp_directory('local_profilephoto/exports');
        $files = glob($tempdir . '/*.zip');
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
