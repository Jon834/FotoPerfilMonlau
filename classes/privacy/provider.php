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

namespace local_profilephoto\privacy;

use core_privacy\local\metadata\null_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for local_profilephoto.
 *
 * Entrega 1 does not persist any personal data of its own:
 *  - the captured JPEG only ever exists as an HTTP request body and a
 *    transient Moodle core draft file (component 'user', filearea
 *    'draft'), which core's own file cleanup / privacy provider already
 *    manages;
 *  - the resulting profile picture is stored entirely through
 *    \core\user::update_picture() under the core 'user' component, so it
 *    is already covered by core_user's own privacy provider;
 *  - no local_profilephoto-owned table exists yet (see
 *    docs/technical-design.md section 6).
 *
 * This MUST be replaced with a full \core_privacy\local\request provider
 * (implementing metadata_provider, plugin\provider, etc.) as soon as the
 * session/queue/log tables from Entrega 3 are introduced, since those will
 * store operator/target identifiers that are themselves personal data.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements null_provider {

    /**
     * Get the language string identifier explaining why this is a null provider.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata:null_reason';
    }
}
