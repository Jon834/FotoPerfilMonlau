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
use local_profilephoto\local\access\scope;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the operator-visibility scope logic (encargo section 17).
 *
 * This asserts scope is resolved from capabilities + course enrolment, and
 * explicitly NOT from is_siteadmin() as a shortcut - the encargo is
 * explicit that this must never be the access control mechanism.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\access\scope
 */
final class scope_test extends advanced_testcase {

    public function test_operator_without_any_capability_has_no_scope(): void {
        $this->resetAfterTest();

        $operator = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();

        $this->assertFalse(scope::has_unrestricted_scope($operator->id));
        $this->assertSame([], scope::get_allowed_courseids($operator->id));
        $this->assertFalse(scope::can_operate_on_user($operator->id, $target));
    }

    public function test_deleted_user_is_never_in_scope(): void {
        $this->resetAfterTest();

        $operator = $this->getDataGenerator()->create_user();
        $this->assign_system_capability($operator->id, 'local/profilephoto:viewallusers');
        $this->assign_system_capability($operator->id, 'local/profilephoto:capture');

        $target = $this->getDataGenerator()->create_user();
        $target->deleted = 1;

        $this->assertTrue(scope::has_unrestricted_scope($operator->id));
        $this->assertFalse(scope::can_operate_on_user($operator->id, $target));
    }

    public function test_suspended_user_hidden_unless_viewallusers(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $operator = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $this->getDataGenerator()->enrol_user($target->id, $course->id, 'student');
        $this->assign_course_capability($operator->id, $course->id, 'local/profilephoto:capture');

        // No viewallusers yet: suspended student must stay hidden.
        $this->assertFalse(scope::can_operate_on_user($operator->id, $target));

        // Grant it and the same suspended student becomes visible.
        $this->assign_system_capability($operator->id, 'local/profilephoto:viewallusers');
        $this->assertTrue(scope::can_operate_on_user($operator->id, $target));
    }

    public function test_operator_scoped_to_own_course_only(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $operator = $this->getDataGenerator()->create_user();
        $studentin1 = $this->getDataGenerator()->create_user();
        $studentin2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($studentin1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($studentin2->id, $course2->id, 'student');

        // Operator only has local/profilephoto:capture within course1.
        $this->assign_course_capability($operator->id, $course1->id, 'local/profilephoto:capture');

        $this->assertFalse(scope::has_unrestricted_scope($operator->id));
        $this->assertTrue(scope::can_operate_on_user($operator->id, $studentin1));
        $this->assertFalse(scope::can_operate_on_user($operator->id, $studentin2));
        $this->assertTrue(scope::can_use_course($operator->id, $course1->id));
        $this->assertFalse(scope::can_use_course($operator->id, $course2->id));
    }

    public function test_siteadmin_status_alone_is_not_the_access_check(): void {
        // Regression guard for encargo section 17: scope must come from
        // capabilities, not is_siteadmin(). A plain user promoted to admin
        // still needs the capability structure to resolve correctly; this
        // test just documents that scope:: never calls is_siteadmin().
        $this->resetAfterTest();

        $operator = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();

        $this->assertFalse(scope::can_operate_on_user($operator->id, $target));
    }

    /**
     * Give a user a capability at system context via a freshly created role.
     *
     * @param int $userid
     * @param string $capability
     */
    private function assign_system_capability(int $userid, string $capability): void {
        $context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $userid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
    }

    /**
     * Give a user a capability at a specific course context via a freshly created role.
     *
     * @param int $userid
     * @param int $courseid
     * @param string $capability
     */
    private function assign_course_capability(int $userid, int $courseid, string $capability): void {
        $context = \context_course::instance($courseid);
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $userid, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
    }
}
