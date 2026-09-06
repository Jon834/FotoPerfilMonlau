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
use local_profilephoto\local\export\branding;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the shared stage-branding helper.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_profilephoto\local\export\branding
 */
final class branding_test extends advanced_testcase {

    public function test_normalise_stage_folds_batx_into_eso_and_defaults_unknown_to_fp(): void {
        $this->assertSame('eso', branding::normalise_stage('batx'));
        $this->assertSame('eso', branding::normalise_stage('eso'));
        $this->assertSame('monlaugroup', branding::normalise_stage('monlaugroup'));
        $this->assertSame('fp', branding::normalise_stage('fp'));
        $this->assertSame('fp', branding::normalise_stage('nonsense'));
        $this->assertSame('fp', branding::normalise_stage(null));
        $this->assertSame('fp', branding::normalise_stage(''));
    }

    public function test_palette_shares_a_colour_for_eso_and_batx(): void {
        $eso = ['r' => 60, 'g' => 168, 'b' => 83];
        $this->assertSame($eso, branding::palette('eso'));
        $this->assertSame($eso, branding::palette('batx'));
        $this->assertSame(['r' => 0, 'g' => 0, 'b' => 0], branding::palette('corporate'));
        $this->assertSame(['r' => 0, 'g' => 0, 'b' => 0], branding::palette('monlaugroup'));
        $this->assertSame(['r' => 12, 'g' => 80, 'b' => 160], branding::palette('fp'));
    }

    public function test_image_base_uses_the_setting_and_falls_back_to_the_default(): void {
        $this->resetAfterTest();

        $this->assertSame(branding::DEFAULT_IMAGE_BASE, branding::image_base());

        set_config('monlauimagesbase', 'https://example.test/theme_monlau/customimages/999/', 'local_profilephoto');
        $this->assertSame('https://example.test/theme_monlau/customimages/999', branding::image_base());
    }

    public function test_logo_url_resolves_per_stage_against_the_base(): void {
        $this->resetAfterTest();
        set_config('monlauimagesbase', 'https://example.test/base', 'local_profilephoto');

        $this->assertSame('https://example.test/base/monlau_fp.jpg', branding::logo_url('fp'));
        $this->assertSame('https://example.test/base/monlau_eso.jpg', branding::logo_url('eso'));
        $this->assertSame('https://example.test/base/monlau_eso.jpg', branding::logo_url('batx'));
        $this->assertSame('https://example.test/base/monlau_corp.jpg', branding::logo_url('corporate'));
        $this->assertSame('https://example.test/base/monlaugroup.svg', branding::logo_url('monlaugroup'));
    }

    public function test_expand_tokens_replaces_monlauimage_placeholders(): void {
        $this->resetAfterTest();
        set_config('monlauimagesbase', 'https://example.test/base', 'local_profilephoto');

        $this->assertSame(
            'Portada https://example.test/base/monlau_corp.jpg fin',
            branding::expand_tokens('Portada [[monlauimage:monlau_corp.jpg]] fin')
        );
        $this->assertSame('sin token', branding::expand_tokens('sin token'));
    }
}
