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
use local_profilephoto\local\export\photo_xlsx_builder;
use PhpOffice\PhpSpreadsheet\IOFactory;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the Excel (.xlsx) counterpart of pdf_builder's orla/grid6/directory/signatures layouts.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\export\photo_xlsx_builder
 */
final class photo_xlsx_builder_test extends advanced_testcase {

    public function test_build_across_all_four_layouts(): void {
        global $CFG, $DB;
        $this->resetAfterTest();

        require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        $img = imagecreatetruecolor(10, 10);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagejpeg($img);
        $imagebytes = ob_get_clean();
        imagedestroy($img);

        $withphoto = $this->getDataGenerator()->create_user(['firstname' => 'Ada', 'lastname' => 'Bravo',
            'email' => 'ada.bravo@example.com']);
        $nophoto = $this->getDataGenerator()->create_user(['firstname' => 'Cyril', 'lastname' => 'Duarte',
            'email' => 'cyril.duarte@example.com']);

        $usercontext = context_user::instance($withphoto->id, IGNORE_MISSING);
        get_file_storage()->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'icon',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'f3.jpg',
        ], $imagebytes);
        $DB->set_field('user', 'picture', 1, ['id' => $withphoto->id]);

        foreach (['orla', 'grid6', 'directory', 'signatures'] as $layout) {
            $result = photo_xlsx_builder::build([$withphoto->id, $nophoto->id], 'Grup X', $layout, [
                'generatedby' => 'Tester',
            ]);

            $this->assertSame(2, $result['count'], "count for {$layout}");
            $this->assertStringEndsWith('.xlsx', $result['filename'], "filename for {$layout}");
            $this->assertFileExists($result['path']);

            $spreadsheet = IOFactory::load($result['path']);
            $sheet = $spreadsheet->getActiveSheet();

            // One embedded avatar per student, real photo or initials fallback alike.
            $this->assertSame(2, $sheet->getDrawingCollection()->count(), "drawings for {$layout}");

            $this->assertSame('Núm.', $sheet->getCell('A6')->getValue(), "num header for {$layout}");
            $this->assertSame('Foto', $sheet->getCell('B6')->getValue(), "foto header for {$layout}");
            $this->assertSame('Alumne', $sheet->getCell('C6')->getValue(), "alumne header for {$layout}");
            $this->assertSame('Bravo, Ada', $sheet->getCell('C7')->getValue(), "first row name for {$layout}");

            if ($layout === 'directory') {
                $this->assertSame('Correu', $sheet->getCell('D6')->getValue());
                $this->assertSame('ada.bravo@example.com', $sheet->getCell('D7')->getValue());
            } else if ($layout === 'signatures') {
                $this->assertSame('Signatura', $sheet->getCell('D6')->getValue());
                $this->assertSame('', (string) $sheet->getCell('D7')->getValue());
            } else {
                // orla / grid6: no fourth column.
                $this->assertSame('', (string) $sheet->getCell('D6')->getValue(), "no D6 header for {$layout}");
            }
        }
    }
}
