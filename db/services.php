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
    'local_profilephoto_create_session' => [
        'classname'   => 'local_profilephoto\external\create_session',
        'methodname'  => 'execute',
        'description' => 'Build a new photography session (queue) from a course or cohort filter.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:capture',
    ],
    'local_profilephoto_get_queue_status' => [
        'classname'   => 'local_profilephoto\external\get_queue_status',
        'methodname'  => 'execute',
        'description' => 'Progress counts plus the next pending student for a session.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:capture',
    ],
    'local_profilephoto_update_queue_item' => [
        'classname'   => 'local_profilephoto\external\update_queue_item',
        'methodname'  => 'execute',
        'description' => 'Skip a student, mark them absent, or put them back to pending.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:capture',
    ],
    'local_profilephoto_get_session_options' => [
        'classname'   => 'local_profilephoto\external\get_session_options',
        'methodname'  => 'execute',
        'description' => 'List courses and cohorts the operator may build a photography session from.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:capture',
    ],
    'local_profilephoto_get_export_options' => [
        'classname'   => 'local_profilephoto\external\get_export_options',
        'methodname'  => 'execute',
        'description' => 'List sessions, courses and cohorts the operator may export from.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:exportsession,local/profilephoto:exportall',
    ],
    'local_profilephoto_create_export' => [
        'classname'   => 'local_profilephoto\external\create_export',
        'methodname'  => 'execute',
        'description' => 'Build a ZIP export of profile photos matching a filter.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:exportsession,local/profilephoto:exportall',
    ],
    'local_profilephoto_get_activity_cohorts' => [
        'classname'   => 'local_profilephoto\external\get_activity_cohorts',
        'methodname'  => 'execute',
        'description' => 'List the cohorts the operator may use for a Control d\'activitat export.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:exportactivity',
    ],
    'local_profilephoto_get_activity_cohort_info' => [
        'classname'   => 'local_profilephoto\external\get_activity_cohort_info',
        'methodname'  => 'execute',
        'description' => 'Resolve a cohort\'s name and current member count.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:exportactivity',
    ],
    'local_profilephoto_create_activity_export' => [
        'classname'   => 'local_profilephoto\external\create_activity_export',
        'methodname'  => 'execute',
        'description' => 'Build a Control d\'activitat PDF roster for a cohort.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/profilephoto:exportactivity',
    ],
];

$services = [
    'Captura de fotografías de perfil' => [
        'functions' => [
            'local_profilephoto_search_users',
            'local_profilephoto_get_user',
            'local_profilephoto_save_picture',
            'local_profilephoto_create_session',
            'local_profilephoto_get_queue_status',
            'local_profilephoto_update_queue_item',
            'local_profilephoto_get_session_options',
            'local_profilephoto_get_export_options',
            'local_profilephoto_create_export',
            'local_profilephoto_get_activity_cohorts',
            'local_profilephoto_get_activity_cohort_info',
            'local_profilephoto_create_activity_export',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_profilephoto',
    ],
];
