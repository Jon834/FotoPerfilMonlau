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
 * Only settings actually consulted by plugin code are declared here - no
 * dead configuration for unimplemented features (e.g. there is no
 * "restore previous picture" setting, since that feature is not built in
 * this version; see docs/technical-design.md).
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

    $settings->add(new admin_setting_configcheckbox(
        'local_profilephoto/enableshortcuts',
        get_string('settings_enableshortcuts', 'local_profilephoto'),
        get_string('settings_enableshortcuts_desc', 'local_profilephoto'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_profilephoto/enablecountdown',
        get_string('settings_enablecountdown', 'local_profilephoto'),
        get_string('settings_enablecountdown_desc', 'local_profilephoto'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/countdownseconds',
        get_string('settings_countdownseconds', 'local_profilephoto'),
        get_string('settings_countdownseconds_desc', 'local_profilephoto'),
        3,
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'local_profilephoto/exportheading',
        get_string('settings_exportheading', 'local_profilephoto'),
        ''
    ));

    $filenamechoices = [
        'idnumber' => 'idnumber',
        'username' => 'username',
        'email' => 'email',
        'userid' => 'userid',
        'fullname' => 'fullname',
    ];

    $settings->add(new admin_setting_configselect(
        'local_profilephoto/exportfilenamestrategy',
        get_string('settings_exportfilenamestrategy', 'local_profilephoto'),
        get_string('settings_exportfilenamestrategy_desc', 'local_profilephoto'),
        'idnumber',
        $filenamechoices
    ));

    $settings->add(new admin_setting_configselect(
        'local_profilephoto/exportfallbackstrategy',
        get_string('settings_exportfallbackstrategy', 'local_profilephoto'),
        get_string('settings_exportfallbackstrategy_desc', 'local_profilephoto'),
        'username',
        $filenamechoices
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/maxsyncexportusers',
        get_string('settings_maxsyncexportusers', 'local_profilephoto'),
        get_string('settings_maxsyncexportusers_desc', 'local_profilephoto'),
        300,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_profilephoto/exportretentionminutes',
        get_string('settings_exportretentionminutes', 'local_profilephoto'),
        get_string('settings_exportretentionminutes_desc', 'local_profilephoto'),
        60,
        PARAM_INT
    ));
}
