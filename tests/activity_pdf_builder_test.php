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
use local_profilephoto\local\export\activity_pdf_builder;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the "Control d'activitat" PDF builder.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\export\activity_pdf_builder
 */
final class activity_pdf_builder_test extends advanced_testcase {

    /**
     * Build a fake cohort-member row (activity_pdf_builder only needs id/firstname/lastname/picture).
     *
     * @param int $id
     * @param string $firstname
     * @param string $lastname
     * @param int $picture
     * @return stdClass
     */
    private function member(int $id, string $firstname, string $lastname, int $picture = 0): stdClass {
        $member = new stdClass();
        $member->id = $id;
        $member->firstname = $firstname;
        $member->lastname = $lastname;
        $member->picture = $picture;
        return $member;
    }

    public function test_build_creates_pdf_with_default_columns(): void {
        $this->resetAfterTest();

        $withphoto = $this->getDataGenerator()->create_user(['firstname' => 'Ada', 'lastname' => 'Bravo']);
        $usercontext = context_user::instance($withphoto->id, IGNORE_MISSING);

        $img = imagecreatetruecolor(10, 10);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagejpeg($img);
        $imagebytes = ob_get_clean();
        imagedestroy($img);

        get_file_storage()->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'icon',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'f3.jpg',
        ], $imagebytes);

        $members = [
            $this->member((int) $withphoto->id, 'Ada', 'Bravo', 1),
            $this->member(999001, 'Cyril', 'Duarte', 0),
        ];

        $result = activity_pdf_builder::build($members, '1ESO-B 2026-27', [
            'name' => 'Visita CosmoCaixa',
            'date' => '2026-09-18',
            'place' => 'Barcelona',
            'responsables' => 'Jonatan Núñez, Marta Solé',
        ], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'observacions', 'label' => '', 'type' => 'text'],
        ], [
            'language' => 'ca',
            'stage' => 'eso',
            'generatedby' => 'Tester',
        ]);

        $this->assertSame(2, $result['count']);
        $this->assertStringEndsWith('.pdf', $result['filename']);
        $this->assertStringContainsString('control-activitat', $result['filename']);
        $this->assertStringContainsString('20260918', $result['filename']);
        $this->assertFileExists($result['path']);
        $this->assertStringContainsString('%PDF', file_get_contents($result['path']));
    }

    public function test_build_includes_custom_columns(): void {
        $this->resetAfterTest();

        $members = [$this->member(1, 'Nora', 'Assali Arhoun')];

        $result = activity_pdf_builder::build($members, 'Grup X', [], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'custom1', 'label' => 'Samarreta', 'type' => 'checkbox'],
            ['key' => 'observacions', 'label' => '', 'type' => 'text'],
        ]);

        $this->assertSame(1, $result['count']);
        $this->assertFileExists($result['path']);
    }

    public function test_columns_fit(): void {
        // Fits: a handful of narrow columns + Observacions.
        $this->assertTrue(activity_pdf_builder::columns_fit([
            ['key' => 'present', 'type' => 'checkbox'],
            ['key' => 'autoritzacio', 'type' => 'checkbox'],
            ['key' => 'email', 'type' => 'value'],
            ['key' => 'observacions', 'type' => 'text'],
        ]));
        // Doesn't fit: many checkbox columns + email + phone leave no room for Observacions.
        $this->assertFalse(activity_pdf_builder::columns_fit([
            ['key' => 'present', 'type' => 'checkbox'],
            ['key' => 'autoritzacio', 'type' => 'checkbox'],
            ['key' => 'transport', 'type' => 'checkbox'],
            ['key' => 'pagament', 'type' => 'checkbox'],
            ['key' => 'menu', 'type' => 'checkbox'],
            ['key' => 'epi', 'type' => 'checkbox'],
            ['key' => 'material', 'type' => 'checkbox'],
            ['key' => 'email', 'type' => 'value'],
            ['key' => 'phone', 'type' => 'value'],
            ['key' => 'observacions', 'type' => 'text'],
        ]));
        // "short" Observacions needs less room than "normal", so it can only ever help.
        $borderline = [
            ['key' => 'present', 'type' => 'checkbox'],
            ['key' => 'autoritzacio', 'type' => 'checkbox'],
            ['key' => 'transport', 'type' => 'checkbox'],
            ['key' => 'pagament', 'type' => 'checkbox'],
            ['key' => 'menu', 'type' => 'checkbox'],
            ['key' => 'epi', 'type' => 'checkbox'],
            ['key' => 'email', 'type' => 'value'],
            ['key' => 'observacions', 'type' => 'text', 'short' => true],
        ];
        $normal = $borderline;
        $normal[7]['short'] = false;
        $this->assertTrue(
            activity_pdf_builder::columns_fit($borderline) || !activity_pdf_builder::columns_fit($normal),
            'short Observacions never makes a fitting selection stop fitting'
        );
    }

    public function test_build_rejects_columns_that_do_not_fit(): void {
        $this->resetAfterTest();

        $columns = [];
        foreach (['present', 'autoritzacio', 'transport', 'pagament', 'menu', 'epi', 'material', 'email',
                'phone'] as $key) {
            $columns[] = ['key' => $key, 'label' => '', 'type' => activity_pdf_builder::standard_column_type($key)];
        }
        $columns[] = ['key' => 'observacions', 'label' => '', 'type' => 'text'];
        $this->assertFalse(activity_pdf_builder::columns_fit($columns));

        $this->expectException(moodle_exception::class);
        activity_pdf_builder::build([$this->member(1, 'Nora', 'Assali')], 'Grup X', [], $columns);
    }

    public function test_build_paginates_large_cohorts(): void {
        $this->resetAfterTest();

        $members = [];
        for ($i = 1; $i <= 40; $i++) {
            $members[] = $this->member($i, 'Nom' . $i, 'Cognom' . $i);
        }

        $result = activity_pdf_builder::build($members, 'Grup Gran', [], [
            ['key' => 'present', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'autoritzacio', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'transport', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'menu', 'label' => '', 'type' => 'checkbox'],
            ['key' => 'observacions', 'label' => '', 'type' => 'text'],
        ]);

        $this->assertSame(40, $result['count']);
        $this->assertFileExists($result['path']);
        // Expect more than one page for 40 students with 5 extra columns.
        $content = file_get_contents($result['path']);
        $this->assertGreaterThan(1, preg_match_all('/\/Type\s*\/Page[^s]/', $content));
    }

    public function test_is_standard_column_and_type_helpers(): void {
        $this->assertTrue(activity_pdf_builder::is_standard_column('present'));
        $this->assertSame('checkbox', activity_pdf_builder::standard_column_type('present'));
        $this->assertSame('text', activity_pdf_builder::standard_column_type('observacions'));
        $this->assertFalse(activity_pdf_builder::is_standard_column('samarreta'));
        $this->assertNull(activity_pdf_builder::standard_column_type('samarreta'));
    }
}
