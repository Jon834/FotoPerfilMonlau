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
use local_profilephoto\local\search\user_search;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the priority-ordered search (encargo section 11).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\search\user_search
 */
final class user_search_test extends advanced_testcase {

    /**
     * Give the admin unrestricted scope is implicit; use a viewallusers operator instead
     * so the test exercises real capability-based scope, not admin bypass.
     *
     * @return \stdClass
     */
    private function create_unrestricted_operator(): \stdClass {
        $operator = $this->getDataGenerator()->create_user();
        $context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/profilephoto:viewallusers', CAP_ALLOW, $roleid, $context->id, true);
        assign_capability('local/profilephoto:searchusers', CAP_ALLOW, $roleid, $context->id, true);
        role_assign($roleid, $operator->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();
        return $operator;
    }

    public function test_exact_idnumber_beats_partial_name_match(): void {
        $this->resetAfterTest();
        $operator = $this->create_unrestricted_operator();

        // "Garcia" appears partially in both users' names, but only one has
        // idnumber exactly "GARCIA1".
        $exact = $this->getDataGenerator()->create_user([
            'firstname' => 'Zzz', 'lastname' => 'Nomatch', 'idnumber' => 'GARCIA1',
        ]);
        $partial = $this->getDataGenerator()->create_user([
            'firstname' => 'Laura', 'lastname' => 'Garcia Perez', 'idnumber' => 'X9',
        ]);

        $results = user_search::search('GARCIA1', $operator->id, 20);
        $ids = array_map(static fn($u) => (int) $u->id, $results);

        $this->assertNotEmpty($results);
        $this->assertSame((int) $exact->id, $ids[0], 'Exact idnumber match must rank first.');
        $this->assertContains((int) $partial->id, $ids);
    }

    public function test_short_query_returns_no_results(): void {
        $this->resetAfterTest();
        $operator = $this->create_unrestricted_operator();
        $this->getDataGenerator()->create_user(['firstname' => 'A']);

        $this->assertSame([], user_search::search('a', $operator->id, 20));
    }

    public function test_deleted_users_never_returned(): void {
        global $DB;
        $this->resetAfterTest();
        $operator = $this->create_unrestricted_operator();

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Deleteme', 'lastname' => 'Ghost']);
        delete_user($user);

        $results = user_search::search('Deleteme', $operator->id, 20);
        $this->assertSame([], $results);
    }

    public function test_operator_without_scope_gets_no_results(): void {
        $this->resetAfterTest();
        $operator = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user(['firstname' => 'Findme', 'lastname' => 'Student']);

        $this->assertSame([], user_search::search('Findme', $operator->id, 20));
    }
}
