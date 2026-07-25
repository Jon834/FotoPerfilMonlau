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
use local_profilephoto\local\session\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for photography sessions and their queue (encargo section 12).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\session\manager
 */
final class session_manager_test extends advanced_testcase {

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

    public function test_create_session_builds_queue_from_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->create_operator_with_scope();

        $students = [];
        foreach (['Zeta', 'Alfa', 'Mu'] as $lastname) {
            $student = $this->getDataGenerator()->create_user(['lastname' => $lastname]);
            $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
            $students[$lastname] = $student;
        }

        $sessionid = manager::create_session($operator->id, 'course', ['courseid' => $course->id], 'lastname');
        $queue = manager::get_queue($sessionid);

        $this->assertCount(3, $queue);
        foreach ($queue as $item) {
            $this->assertSame('pending', $item->status);
        }

        $progress = manager::get_progress($sessionid);
        $this->assertSame(3, $progress['total']);
        $this->assertSame(0, $progress['captured']);
        $this->assertSame(3, $progress['pending']);
    }

    public function test_mark_captured_advances_queue_and_autocompletes(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->create_operator_with_scope();

        $student1 = $this->getDataGenerator()->create_user(['lastname' => 'Aaa']);
        $student2 = $this->getDataGenerator()->create_user(['lastname' => 'Bbb']);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, 'student');

        $sessionid = manager::create_session($operator->id, 'course', ['courseid' => $course->id], 'lastname');

        $next = manager::get_next_pending($sessionid);
        $this->assertSame((int) $student1->id, (int) $next->userid, 'lastname order should put Aaa first.');

        manager::mark_captured($sessionid, $student1->id, $operator->id);
        $progress = manager::get_progress($sessionid);
        $this->assertSame(1, $progress['captured']);
        $this->assertSame(1, $progress['pending']);

        $session = manager::get_session($sessionid);
        $this->assertSame('active', $session->status);

        manager::mark_captured($sessionid, $student2->id, $operator->id);
        $progress = manager::get_progress($sessionid);
        $this->assertSame(2, $progress['captured']);
        $this->assertSame(0, $progress['pending']);
        $this->assertNull(manager::get_next_pending($sessionid));

        $session = manager::get_session($sessionid);
        $this->assertSame('completed', $session->status, 'Session should auto-complete once nothing is pending.');
    }

    public function test_skip_and_absent_remove_item_from_pending(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->create_operator_with_scope();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $sessionid = manager::create_session($operator->id, 'course', ['courseid' => $course->id], 'lastname');

        manager::set_status($sessionid, $student->id, 'skipped');
        $progress = manager::get_progress($sessionid);
        $this->assertSame(1, $progress['skipped']);
        $this->assertSame(0, $progress['pending']);

        manager::set_status($sessionid, $student->id, 'pending');
        $progress = manager::get_progress($sessionid);
        $this->assertSame(1, $progress['pending']);
    }

    public function test_require_owner_rejects_non_owner_without_managesessions(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->create_operator_with_scope();
        $intruder = $this->getDataGenerator()->create_user();

        $sessionid = manager::create_session($operator->id, 'course', ['courseid' => $course->id], 'lastname');
        $session = manager::get_session($sessionid);

        $this->expectException(\moodle_exception::class);
        manager::require_owner($session, $intruder->id);
    }
}
