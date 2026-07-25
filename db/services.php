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
 * External functions and services for local_profilephoto.
 *
 * All functions are ajax-only (called from core/ajax on the capture
 * screen), require a logged-in session and are authenticated with sesskey
 * by the AJAX transport itself; capability/scope checks happen again
 * inside each function.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_profilephoto_search_users' => [
        'classname'   => 'local_profilephoto\external\search_users',
        'methodname'  => 'execute',
        'description' => 'Search for candidate users within the operator scope.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:searchusers',
    ],
    'local_profilephoto_get_user' => [
        'classname'   => 'local_profilephoto\external\get_user',
        'methodname'  => 'execute',
        'description' => 'Load full detail for a single candidate user.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:searchusers',
    ],
    'local_profilephoto_save_picture' => [
        'classname'   => 'local_profilephoto\external\save_picture',
        'methodname'  => 'execute',
        'description' => 'Process an uploaded photo and set it as the target user official profile picture.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:capture,local/profilephoto:updatepicture',
    ],
];

$services = [
    'Captura de fotografías de perfil' => [
        'functions' => [
            'local_profilephoto_search_users',
            'local_profilephoto_get_user',
            'local_profilephoto_save_picture',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_profilephoto',
    ],
];
