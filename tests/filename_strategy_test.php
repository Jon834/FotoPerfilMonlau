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
use local_profilephoto\local\export\filename_strategy;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for export filename sanitisation and deduplication (encargo section 15).
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\export\filename_strategy
 */
final class filename_strategy_test extends advanced_testcase {

    private function user(int $id, string $idnumber = '', string $username = 'user' . 0): stdClass {
        return (object) [
            'id' => $id,
            'idnumber' => $idnumber,
            'username' => $username,
            'email' => 'user' . $id . '@example.com',
            'firstname' => 'Test',
            'lastname' => 'User' . $id,
        ];
    }

    public function test_uses_primary_strategy_when_available(): void {
        $this->resetAfterTest();
        $used = [];
        $user = $this->user(1, 'ID123', 'jdoe');

        $filename = filename_strategy::build_filename($user, 'idnumber', 'username', $used);

        $this->assertSame('ID123.jpg', $filename);
    }

    public function test_falls_back_when_primary_is_empty(): void {
        $this->resetAfterTest();
        $used = [];
        $user = $this->user(2, '', 'jdoe2');

        $filename = filename_strategy::build_filename($user, 'idnumber', 'username', $used);

        $this->assertSame('jdoe2.jpg', $filename);
    }

    public function test_falls_back_to_userid_when_both_empty(): void {
        $this->resetAfterTest();
        $used = [];
        $user = $this->user(3, '', '');

        $filename = filename_strategy::build_filename($user, 'idnumber', 'username', $used);

        $this->assertSame('user_3.jpg', $filename);
    }

    public function test_duplicates_get_numeric_suffixes(): void {
        $this->resetAfterTest();
        $used = [];
        $userA = $this->user(4, 'SAME', 'a');
        $userB = $this->user(5, 'SAME', 'b');
        $userC = $this->user(6, 'SAME', 'c');

        $first = filename_strategy::build_filename($userA, 'idnumber', 'username', $used);
        $second = filename_strategy::build_filename($userB, 'idnumber', 'username', $used);
        $third = filename_strategy::build_filename($userC, 'idnumber', 'username', $used);

        $this->assertSame('SAME.jpg', $first);
        $this->assertSame('SAME_2.jpg', $second);
        $this->assertSame('SAME_3.jpg', $third);
    }

    public function test_path_traversal_attempt_is_neutralised(): void {
        $this->resetAfterTest();
        $used = [];
        $user = $this->user(7, '../../etc/passwd', 'x');

        $filename = filename_strategy::build_filename($user, 'idnumber', 'username', $used);

        // No path separator can survive PARAM_FILE cleaning: without one,
        // a lone ".." is just an odd filename, not a traversal (single
        // path component, never crosses a directory boundary).
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('\\', $filename);
        $this->assertStringEndsWith('.jpg', $filename);
    }
}
