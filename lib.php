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

/**
 * Library callbacks for local_profilephoto.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a "Captura de fotografías de perfil" node to the primary navigation
 * for anyone with local/profilephoto:view, without requiring access to
 * Site administration (photographers are typically not admins).
 *
 * @param global_navigation $navigation
 */
function local_profilephoto_extend_navigation(global_navigation $navigation) {
    $context = context_system::instance();

    if (!has_capability('local/profilephoto:view', $context)) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_profilephoto'),
        new moodle_url('/local/profilephoto/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_profilephoto',
        new pix_icon('icon', '', 'local_profilephoto')
    );

    $navigation->add_node($node);
}
