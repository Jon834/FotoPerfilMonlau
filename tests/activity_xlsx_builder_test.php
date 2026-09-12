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
use local_profilephoto\local\export\activity_xlsx_builder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the "Control d'activitat" Excel (.xlsx) builder.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\export\activity_xlsx_builder
 */
final class activity_xlsx_builder_test extends advanced_testcase {

    /**
     * Build a fake cohort-member row (id/firstname/lastname/email/picture).
     *
     * @param int $id
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param int $picture
     * @return stdClass
     */
    private function member(int $id, string $firstname, string $lastname, string $email, int $picture = 0): stdClass {
        $member = new stdClass();
        $member->id = $id;
        $member->firstname = $firstname;
        $member->lastname = $lastname;
        $member->email = $email;
        $member->picture = $picture;
        return $member;
    }

    public function test_build_creates_xlsx_with_expected_layout(): void {
        global $CFG;
        $this->resetAfterTest();

        require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        $members = [
            $this->member(1, 'Ada', 'Bravo', 'ada.bravo@example.com'),
            $this->member(2, 'Cyril', 'Duarte', 'cyril.duarte@example.com'),
        ];

        $result = activity_xlsx_builder::build($members, '1ESO-B 2026-27', [
            'name' => 'Visita CosmoCaixa',
            'date' => '2026-09-18',
            'place' => 'Barcelona',
            'responsables' => 'Jonatan Núñez, Marta Solé',
        ], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'email', 'label' => '', 'type' => 'value'],
            ['key' => 'observacions', 'label' => '', 'type' => 'text'],
        ], [
            'language' => 'ca',
            'stage' => 'eso',
            'generatedby' => 'Tester',
            'showphotos' => false,
        ]);

        $this->assertSame(2, $result['count']);
        $this->assertStringEndsWith('.xlsx', $result['filename']);
        $this->assertStringContainsString('control-activitat', $result['filename']);
        $this->assertStringContainsString('20260918', $result['filename']);
        $this->assertFileExists($result['path']);

        $spreadsheet = IOFactory::load($result['path']);
        $sheet = $spreadsheet->getActiveSheet();

        // Header row (row 7): Núm., Alumne, Present, Correu, Observacions.
        $this->assertSame('Núm.', $sheet->getCell('A7')->getValue());
        $this->assertSame('Alumne', $sheet->getCell('B7')->getValue());
        $this->assertSame('Present', $sheet->getCell('C7')->getValue());
        $this->assertSame('Correu', $sheet->getCell('D7')->getValue());
        $this->assertSame('Observacions', $sheet->getCell('E7')->getValue());

        // First student row (row 8): Núm. = 1, name, blank "present" checkbox cell,
        // real email value, blank observacions.
        $this->assertSame(1, $sheet->getCell('A8')->getValue());
        $this->assertSame('Bravo, Ada', $sheet->getCell('B8')->getValue());
        $this->assertSame('', (string) $sheet->getCell('C8')->getValue());
        $this->assertSame('ada.bravo@example.com', $sheet->getCell('D8')->getValue());
        $this->assertSame('', (string) $sheet->getCell('E8')->getValue());
    }

    public function test_build_without_general_obs_omits_the_trailing_box(): void {
        global $CFG;
        $this->resetAfterTest();

        require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        $members = [$this->member(1, 'Nora', 'Assali', 'nora@example.com')];

        $result = activity_xlsx_builder::build($members, 'Grup X', [], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
        ], [
            'showgeneralobs' => false,
        ]);

        $spreadsheet = IOFactory::load($result['path']);
        $sheet = $spreadsheet->getActiveSheet();

        // Header row 7, one student row 8: nothing should be written at row 9.
        $this->assertSame('', (string) $sheet->getCell('A9')->getValue());
    }

    public function test_build_with_photos_inserts_a_foto_column_and_embeds_avatars(): void {
        global $CFG;
        $this->resetAfterTest();

        require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        $members = [
            $this->member(1, 'Ada', 'Bravo', 'ada.bravo@example.com'),
            $this->member(2, 'Cyril', 'Duarte', 'cyril.duarte@example.com'),
        ];

        $result = activity_xlsx_builder::build($members, 'Grup X', [], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'email', 'label' => '', 'type' => 'value'],
            ['key' => 'observacions', 'label' => '', 'type' => 'text'],
        ], [
            'showphotos' => true,
        ]);

        $spreadsheet = IOFactory::load($result['path']);
        $sheet = $spreadsheet->getActiveSheet();

        // Foto column at B pushes Alumne to C and every extra column one letter later.
        $this->assertSame('Foto', $sheet->getCell('B7')->getValue());
        $this->assertSame('Alumne', $sheet->getCell('C7')->getValue());
        $this->assertSame('Present', $sheet->getCell('D7')->getValue());
        $this->assertSame('Correu', $sheet->getCell('E7')->getValue());
        $this->assertSame('Observacions', $sheet->getCell('F7')->getValue());
        $this->assertSame('Bravo, Ada', $sheet->getCell('C8')->getValue());
        $this->assertSame('ada.bravo@example.com', $sheet->getCell('E8')->getValue());

        // One embedded avatar per student (none of them have a real photo, so both are
        // initials-avatar fallbacks, but xlsx_avatar::embed() always draws something),
        // plus one stage logo in the header (always embedded, regardless of showphotos).
        $this->assertSame(3, $spreadsheet->getActiveSheet()->getDrawingCollection()->count());
    }

    public function test_build_without_photos_only_has_the_header_logo(): void {
        global $CFG;
        $this->resetAfterTest();

        require_once($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');

        $members = [$this->member(1, 'Nora', 'Assali', 'nora@example.com')];

        $result = activity_xlsx_builder::build($members, 'Grup X', [], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
        ], [
            'showphotos' => false,
        ]);

        $spreadsheet = IOFactory::load($result['path']);
        // No student avatars, but the header logo is always embedded.
        $this->assertSame(1, $spreadsheet->getActiveSheet()->getDrawingCollection()->count());
        // No Foto column: Alumne stays at B.
        $this->assertSame('Alumne', $spreadsheet->getActiveSheet()->getCell('B7')->getValue());
    }
}
