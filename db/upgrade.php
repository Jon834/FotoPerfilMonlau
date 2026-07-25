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
 * Upgrade steps for local_profilephoto.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for local_profilephoto.
 *
 * @param int $oldversion the version we are upgrading from.
 * @return bool always true.
 */
function xmldb_local_profilephoto_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026072700) {
        // Entrega 3: sessions, queue and audit log tables. Field/key/index
        // definitions here MUST stay identical to db/install.xml, since
        // fresh installs use install.xml directly while upgrades run this.

        $table = new xmldb_table('local_profilephoto_session');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('operatorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('filtertype', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null);
        $table->add_field('filterdata', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('ordertype', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'lastname');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'active');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('timefinished', XMLDB_TYPE_INTEGER, '10', null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('operatorid', XMLDB_KEY_FOREIGN, ['operatorid'], 'user', ['id']);
        $table->add_index('operatorid_status', XMLDB_INDEX_NOTUNIQUE, ['operatorid', 'status']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_profilephoto_session_user');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('capturedby', XMLDB_TYPE_INTEGER, '10', null, null, null);
        $table->add_field('timecaptured', XMLDB_TYPE_INTEGER, '10', null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('lasterror', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, ['sessionid'], 'local_profilephoto_session', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('capturedby', XMLDB_KEY_FOREIGN, ['capturedby'], 'user', ['id']);
        $table->add_index('sessionid_userid', XMLDB_INDEX_UNIQUE, ['sessionid', 'userid']);
        $table->add_index('sessionid_status_sortorder', XMLDB_INDEX_NOTUNIQUE, ['sessionid', 'status', 'sortorder']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_profilephoto_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null);
        $table->add_field('operatorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_field('targetuserid', XMLDB_TYPE_INTEGER, '10', null, null, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null);
        $table->add_field('result', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'success');
        $table->add_field('details', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('ipaddress', XMLDB_TYPE_CHAR, '45', null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('sessionid', XMLDB_KEY_FOREIGN, ['sessionid'], 'local_profilephoto_session', ['id']);
        $table->add_key('operatorid', XMLDB_KEY_FOREIGN, ['operatorid'], 'user', ['id']);
        $table->add_key('targetuserid', XMLDB_KEY_FOREIGN, ['targetuserid'], 'user', ['id']);
        $table->add_index('operatorid_timecreated', XMLDB_INDEX_NOTUNIQUE, ['operatorid', 'timecreated']);
        $table->add_index('targetuserid_idx', XMLDB_INDEX_NOTUNIQUE, ['targetuserid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072700, 'local', 'profilephoto');
    }

    return true;
}
