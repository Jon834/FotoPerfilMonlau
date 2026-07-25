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
 * Admin settings for local_profilephoto.
 *
 * Only settings actually consulted by Entrega 1 code are declared here.
 * Later entregas (camera defaults, QR, countdown, autosave, export, scope
 * selector, retention...) add their own settings alongside the features
 * that use them, per docs/technical-design.md.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Direct entry point to the capture screen itself, listed alongside the
    // settings page under Site administration > Plugins > Local plugins,
    // since photographers otherwise have no reason to browse Site
    // administration and would only find index.php via the primary
    // navigation node added in lib.php (easy to miss in the Boost drawer).
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_profilephoto_launch',
        get_string('opencapturescreen', 'local_profilephoto'),
        new moodle_url('/local/profilephoto/index.php'),
        'local/profilephoto:view'
    ));

    $settings = new admin_settingpage('local_profilephoto', get_string('pluginname', 'local_profilephoto'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_profilephoto/enabled',
        get_string('settings_enabled', 'local_profilephoto'),
        get_string('settings_enabled_desc', 'local_profilephoto'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/targetsize',
        get_string('settings_targetsize', 'local_profilephoto'),
        get_string('settings_targetsize_desc', 'local_profilephoto'),
        500,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/jpegquality',
        get_string('settings_jpegquality', 'local_profilephoto'),
        get_string('settings_jpegquality_desc', 'local_profilephoto'),
        88,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/maxsourcebytes',
        get_string('settings_maxsourcebytes', 'local_profilephoto'),
        get_string('settings_maxsourcebytes_desc', 'local_profilephoto'),
        8 * 1024 * 1024,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/maxsearchresults',
        get_string('settings_maxsearchresults', 'local_profilephoto'),
        get_string('settings_maxsearchresults_desc', 'local_profilephoto'),
        20,
        PARAM_INT
    ));
}
