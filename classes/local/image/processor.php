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

namespace local_profilephoto\local\image;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates and normalises a captured photo before it reaches Moodle's
 * official picture-update pipeline (see image\updater).
 *
 * Responsibilities (encargo section 8 and 20):
 *  - reject anything that is not really a JPEG/PNG image, regardless of the
 *    filename or the client-declared mimetype;
 *  - enforce size/dimension limits server-side (never trust the browser);
 *  - centre-crop to a square and resize to the configured target size;
 *  - strip EXIF metadata (a side effect of decoding into GD and
 *    re-encoding: GD's in-memory bitmap never carries EXIF, so the
 *    resulting JPEG has none);
 *  - always emit a JPEG at the configured quality.
 *
 * @package    local_profilephoto
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {

    /** @var int Minimum accepted source dimension (px), below this a capture is unusable. */
    const MIN_SOURCE_DIMENSION = 200;

    /** @var string[] Mimetypes accepted as input. */
    const ALLOWED_MIMETYPES = ['image/jpeg', 'image/png'];

    /**
     * Process raw binary image data into a normalised square JPEG.
     *
     * @param string $binarydata raw bytes as received from the client.
     * @param int $maxsourcebytes hard limit on input size, in bytes.
     * @param int $targetsize output width/height in pixels (square).
     * @param int $jpegquality 0-100.
     * @return string binary JPEG data, ready to be stored as a Moodle file.
     * @throws moodle_exception if the data is not a valid, sane image.
     */
    public static function process(
        string $binarydata,
        int $maxsourcebytes,
        int $targetsize,
        int $jpegquality
    ): string {
        if ($binarydata === '') {
            throw new moodle_exception('error_emptyimage', 'local_profilephoto');
        }

        if (strlen($binarydata) > $maxsourcebytes) {
            throw new moodle_exception('error_imagetoolarge', 'local_profilephoto');
        }

        $info = @getimagesizefromstring($binarydata);
        if ($info === false || !isset($info['mime'])) {
            throw new moodle_exception('error_invalidimage', 'local_profilephoto');
        }

        if (!in_array($info['mime'], self::ALLOWED_MIMETYPES, true)) {
            throw new moodle_exception('error_unsupportedmimetype', 'local_profilephoto');
        }

        [$sourcewidth, $sourceheight] = [$info[0], $info[1]];
        if ($sourcewidth < self::MIN_SOURCE_DIMENSION || $sourceheight < self::MIN_SOURCE_DIMENSION) {
            throw new moodle_exception('error_imagetoosmall', 'local_profilephoto');
        }

        $source = @imagecreatefromstring($binarydata);
        if ($source === false) {
            throw new moodle_exception('error_invalidimage', 'local_profilephoto');
        }

        try {
            $cropped = self::centre_crop_square($source, $sourcewidth, $sourceheight);
        } finally {
            imagedestroy($source);
        }

        $targetsize = max(64, $targetsize);
        $resized = imagecreatetruecolor($targetsize, $targetsize);
        // Flatten onto white, JPEG has no alpha channel.
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $white);

        $cropsize = imagesx($cropped);
        imagecopyresampled(
            $resized, $cropped,
            0, 0, 0, 0,
            $targetsize, $targetsize, $cropsize, $cropsize
        );
        imagedestroy($cropped);

        ob_start();
        $quality = max(0, min(100, $jpegquality));
        imagejpeg($resized, null, $quality);
        $output = ob_get_clean();
        imagedestroy($resized);

        if ($output === false || $output === '') {
            throw new moodle_exception('error_processingfailed', 'local_profilephoto');
        }

        return $output;
    }

    /**
     * Centre-crop a GD image resource to a square.
     *
     * @param \GdImage $source
     * @param int $width
     * @param int $height
     * @return \GdImage new square GD image resource; caller must imagedestroy() it.
     */
    private static function centre_crop_square($source, int $width, int $height) {
        $side = min($width, $height);
        $srcx = (int) (($width - $side) / 2);
        $srcy = (int) (($height - $side) / 2);

        $cropped = imagecreatetruecolor($side, $side);
        imagecopy($cropped, $source, 0, 0, $srcx, $srcy, $side, $side);

        return $cropped;
    }
}
