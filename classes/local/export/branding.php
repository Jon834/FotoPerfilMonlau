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

namespace local_profilephoto\local\export;

defined('MOODLE_INTERNAL') || die();

/**
 * Stage branding shared by both PDF builders: header colour, logo asset and
 * the "[[monlauimage:...]]" token used to reference theme_monlau customimages.
 *
 * The logos live in the Monlau theme's customimages area. The last path
 * segment of that URL is a revision stamp Moodle bumps every time the image
 * is re-uploaded, so the base is an admin setting
 * (local_profilephoto/monlauimagesbase) rather than a constant.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class branding {

    /** @var string Fallback customimages base when the admin setting is empty. */
    public const DEFAULT_IMAGE_BASE =
        'https://lms.monlau.com/pluginfile.php/1/theme_monlau/customimages/1788711634';

    /** @var string[] The stages offered in the export UI, in display order. */
    public const STAGES = ['fp', 'eso', 'corporate', 'monlaugroup'];

    /** @var array<string,array{r:int,g:int,b:int}> Header bar colour per stage. */
    private const PALETTE = [
        'fp'          => ['r' => 12, 'g' => 80,  'b' => 160],
        'eso'         => ['r' => 60, 'g' => 168, 'b' => 83],
        'corporate'   => ['r' => 0,  'g' => 0,   'b' => 0],
        'monlaugroup' => ['r' => 0,  'g' => 0,   'b' => 0],
    ];

    /** @var array<string,string> customimages filename per stage. */
    private const LOGO_FILES = [
        'fp'          => 'monlau_fp.jpg',
        'eso'         => 'monlau_eso.jpg',
        'corporate'   => 'monlau_corp.jpg',
        'monlaugroup' => 'monlaugroup.svg',
    ];

    /**
     * Map any incoming stage value onto a supported one.
     *
     * "batx" (Batxillerat) is folded into "eso": the two share a header now
     * and the UI offers them as a single "ESO / Batxillerat" option, but old
     * saved/queued values and bookmarked forms may still send "batx".
     *
     * @param string|null $stage
     * @return string
     */
    public static function normalise_stage(?string $stage): string {
        $stage = (string) $stage;
        if ($stage === 'batx') {
            $stage = 'eso';
        }
        return in_array($stage, self::STAGES, true) ? $stage : 'fp';
    }

    /**
     * Header bar colour for a stage.
     *
     * @param string|null $stage
     * @return array{r:int,g:int,b:int}
     */
    public static function palette(?string $stage): array {
        return self::PALETTE[self::normalise_stage($stage)];
    }

    /**
     * The customimages base URL (no trailing slash).
     *
     * @return string
     */
    public static function image_base(): string {
        $base = trim((string) get_config('local_profilephoto', 'monlauimagesbase'));
        return rtrim($base !== '' ? $base : self::DEFAULT_IMAGE_BASE, '/');
    }

    /**
     * Resolve a customimages filename to an absolute URL.
     *
     * @param string $filename e.g. "monlau_corp.jpg"
     * @return string
     */
    public static function monlau_image_url(string $filename): string {
        return self::image_base() . '/' . ltrim($filename, '/');
    }

    /**
     * Replace "[[monlauimage:foo.png]]" tokens in free text with the resolved URL.
     *
     * @param string $text
     * @return string
     */
    public static function expand_tokens(string $text): string {
        return (string) preg_replace_callback(
            '/\[\[monlauimage:([A-Za-z0-9._-]+)\]\]/',
            static function (array $m): string {
                return self::monlau_image_url($m[1]);
            },
            $text
        );
    }

    /**
     * Logo asset URL for a stage.
     *
     * @param string|null $stage
     * @return string|null
     */
    public static function logo_url(?string $stage): ?string {
        $file = self::LOGO_FILES[self::normalise_stage($stage)] ?? null;
        return $file === null ? null : self::monlau_image_url($file);
    }

    /**
     * Draw the stage logo into a TCPDF header, fitted inside a box and never
     * distorted. Falls back to a generated mark if the asset can't be
     * fetched/decoded, so the header never comes out blank.
     *
     * @param \TCPDF $pdf
     * @param string|null $stage
     * @param float $x
     * @param float $y
     * @param float $maxw box width (mm)
     * @param float $maxh box height (mm)
     */
    public static function render_logo(\TCPDF $pdf, ?string $stage, float $x, float $y, float $maxw, float $maxh): void {
        $stage = self::normalise_stage($stage);
        $url = self::logo_url($stage);
        $issvg = $url !== null && preg_match('/\.svg(\?|$)/i', $url);

        try {
            if ($issvg) {
                $svg = self::fetch_svg($url);
                if ($svg !== null) {
                    // TCPDF scales the SVG to fit within maxw x maxh, keeping aspect ratio.
                    $pdf->ImageSVG('@' . $svg, $x, $y, $maxw, $maxh);
                    return;
                }
            } else if ($url !== null) {
                // 'LT' fitbox: scale to fit the maxw x maxh box, keep aspect ratio,
                // align top-left. Never hand an .svg URL here - the raster decoder
                // would render XML as noise.
                $pdf->Image($url, $x, $y, $maxw, $maxh, '', '', 'T', false, 300, '',
                    false, false, 0, 'LT', false, false);
                return;
            }
        } catch (\Throwable $e) {
            // Fall through to the generated fallback below.
            unset($e);
        }

        $pdf->Image('@' . self::fallback_logo($stage), $x, $y, $maxh, $maxh, 'PNG', '', 'T', false, 300, '',
            false, false, 0, false, false, false);
    }

    /**
     * Fetch a remote SVG and strip clip-path references TCPDF cannot resolve.
     *
     * The Monlau SVGs point at <clipPath> ids they do not define; TCPDF's SVG
     * parser then emits "Undefined array key" / "foreach() on null" warnings
     * without applying (or needing) the clip. Removing the references keeps
     * the same visual result and silences the warnings.
     *
     * @param string $url
     * @return string|null cleaned SVG markup, or null if it could not be fetched
     */
    public static function fetch_svg(string $url): ?string {
        static $cache = [];
        if (array_key_exists($url, $cache)) {
            return $cache[$url];
        }

        $svg = null;
        try {
            $curl = new \curl();
            $response = $curl->get($url, [], ['CURLOPT_TIMEOUT' => 5, 'CURLOPT_CONNECTTIMEOUT' => 5]);
            if (!$curl->get_errno() && is_string($response) && stripos($response, '<svg') !== false) {
                $svg = preg_replace('/<clipPath\b[^>]*>.*?<\/clipPath>/is', '', $response);
                $svg = preg_replace('/\s+clip-path\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $svg);
                $svg = preg_replace('/clip-path\s*:\s*url\([^)]*\)\s*;?/i', '', $svg);
            }
        } catch (\Throwable $e) {
            $svg = null;
        }

        return $cache[$url] = $svg;
    }

    /**
     * Fetch a remote raster image (jpg/png) as raw bytes. Used where the caller needs
     * the actual bytes rather than handing TCPDF a URL to fetch itself (e.g. to decode
     * into a GD resource for an Excel export) - see {@see xlsx_avatar::embed_logo()}.
     *
     * @param string $url
     * @return string|null raw image bytes, or null if it could not be fetched.
     */
    public static function fetch_raster(string $url): ?string {
        static $cache = [];
        if (array_key_exists($url, $cache)) {
            return $cache[$url];
        }

        $bytes = null;
        try {
            $curl = new \curl();
            $response = $curl->get($url, [], ['CURLOPT_TIMEOUT' => 5, 'CURLOPT_CONNECTTIMEOUT' => 5]);
            if (!$curl->get_errno() && is_string($response) && $response !== '') {
                $bytes = $response;
            }
        } catch (\Throwable $e) {
            $bytes = null;
        }

        return $cache[$url] = $bytes;
    }

    /**
     * A self-contained PNG mark used when the remote logo can't be loaded.
     *
     * @param string $stage
     * @return string raw PNG bytes
     */
    public static function fallback_logo(string $stage): string {
        $size = 64;
        $im = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $size, $size, $white);

        $brand = self::palette($stage);
        $inset = 5;
        $fill = imagecolorallocate($im, $brand['r'], $brand['g'], $brand['b']);
        // A pure-white brand (none here) would vanish on the white tile; guard anyway.
        if ($brand['r'] + $brand['g'] + $brand['b'] > 720) {
            $fill = imagecolorallocate($im, 15, 15, 15);
        }
        imagefilledrectangle($im, $inset, $inset, $size - $inset, $size - $inset, $fill);

        $text = 'M';
        $font = 5;
        $textw = imagefontwidth($font) * strlen($text);
        $texth = imagefontheight($font);
        imagestring($im, $font, (int) (($size - $textw) / 2), (int) (($size - $texth) / 2), $text,
            imagecolorallocate($im, 255, 255, 255));

        ob_start();
        imagepng($im);
        $png = ob_get_clean();
        imagedestroy($im);

        return $png;
    }
}
