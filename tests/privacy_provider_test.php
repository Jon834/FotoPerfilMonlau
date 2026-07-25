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

namespace local_profilephoto;

use advanced_testcase;
use context_system;
use core_privacy\local\request\approved_contextlist;
use local_profilephoto\local\audit\logger;
use local_profilephoto\local\session\manager;
use local_profilephoto\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the Privacy API provider (encargo section 19).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\privacy\provider
 */
final class privacy_provider_test extends advanced_testcase {

    private function create_operator_with_scope(): \stdClass {
        $operator = $this->getDataGenerator()->create_user();
        $context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/profilephoto:viewallusers', CAP_ALLOW, $roleid, $context->id, true);
        assign_capability('local/profilephoto:capture', CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $operator->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        return $operator;
    }

    public function test_user_with_no_data_has_no_contexts(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($user->id);

        $this->assertCount(0, $contextlist);
    }

    public function test_operator_and_target_both_get_the_system_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->create_operator_with_scope();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $sessionid = manager::create_session($operator->id, 'course', ['courseid' => $course->id], 'lastname');
        manager::mark_captured($sessionid, $student->id, $operator->id);
        logger::log('picture_updated', $operator->id, $student->id, $sessionid);

        $systemcontextid = context_system::instance()->id;

        $operatorcontexts = provider::get_contexts_for_userid($operator->id);
        $this->assertContains($systemcontextid, $operatorcontexts->get_contextids());

        $studentcontexts = provider::get_contexts_for_userid($student->id);
        $this->assertContains($systemcontextid, $studentcontexts->get_contextids());
    }

    public function test_delete_data_for_user_removes_their_rows(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->create_operator_with_scope();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $sessionid = manager::create_session($operator->id, 'course', ['courseid' => $course->id], 'lastname');
        manager::mark_captured($sessionid, $student->id, $operator->id);
        logger::log('picture_updated', $operator->id, $student->id, $sessionid);

        $this->assertGreaterThan(0, $DB->count_records('local_profilephoto_session', ['operatorid' => $operator->id]));

        $approvedlist = new approved_contextlist($operator, 'local_profilephoto', [context_system::instance()->id]);
        provider::delete_data_for_user($approvedlist);

        $this->assertSame(0, $DB->count_records('local_profilephoto_session', ['operatorid' => $operator->id]));
        $this->assertSame(0, $DB->count_records('local_profilephoto_session_user', ['sessionid' => $sessionid]));
        $this->assertSame(0, $DB->count_records('local_profilephoto_log', ['sessionid' => $sessionid]));
    }
}
