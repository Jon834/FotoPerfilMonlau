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
use context_user;
use local_profilephoto\local\export\pdf_builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the PDF roster export builder.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\export\pdf_builder
 */
final class pdf_builder_test extends advanced_testcase {

    public function test_build_creates_pdf_with_users_sorted_by_lastname(): void {
        $this->resetAfterTest();

        $img = imagecreatetruecolor(10, 10);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);
        ob_start();
        imagejpeg($img);
        $imagebytes = ob_get_clean();
        imagedestroy($img);

        $zorro = $this->getDataGenerator()->create_user(['firstname' => 'Ana', 'lastname' => 'Zorro']);
        $alonso = $this->getDataGenerator()->create_user(['firstname' => 'Luis', 'lastname' => 'Alonso']);

        foreach ([$zorro, $alonso] as $user) {
            $usercontext = context_user::instance($user->id, IGNORE_MISSING);
            $fs = get_file_storage();
            $fs->create_file_from_string([
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'icon',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'f3.jpg',
            ], $imagebytes);
            $DB = new \stdClass();
            $DB->id = $user->id;
            set_user_preference('user_picture_defaults', 0, $user->id);
        }

        $result = pdf_builder::build([$zorro->id, $alonso->id], 'Curso de prueba', 'roster');

        $this->assertSame(2, $result['count']);
        $this->assertStringEndsWith('.pdf', $result['filename']);
        $this->assertFileExists($result['path']);
        $this->assertStringContainsString('%PDF', file_get_contents($result['path']));
    }
}
