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

/**
 * Live camera capture (encargo section 7): device enumeration, stream
 * lifecycle, and square-cropped snapshot capture. Deliberately has no
 * notion of students or saving - capture.js owns that orchestration.
 *
 * @module     local_profilephoto/camera
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const STORAGE_KEY = 'local_profilephoto_camera_device';
const IDEAL_DIMENSION = 1280;
const MAX_CAPTURE_DIMENSION = 1600;
const JPEG_QUALITY = 0.92;

/**
 * Map a getUserMedia failure to a short error reason code.
 *
 * @param {Error} error
 * @return {String} one of 'insecure', 'permission', 'notfound', 'inuse', 'generic'
 */
const classifyError = (error) => {
    if (!window.isSecureContext) {
        return 'insecure';
    }
    switch (error && error.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'permission';
        case 'NotFoundError':
        case 'OverconstrainedError':
            return 'notfound';
        case 'NotReadableError':
        case 'TrackStartError':
            return 'inuse';
        default:
            return 'generic';
    }
};

/**
 * Whether this browser/context can possibly support live capture.
 *
 * @return {Boolean}
 */
export const isSupported = () => {
    return Boolean(window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
};

/**
 * Create a camera controller bound to a given <video> element.
 *
 * @param {HTMLVideoElement} videoEl
 * @return {Object} controller with start/stop/listDevices/captureFrame/isActive
 */
export const createController = (videoEl) => {
    let stream = null;
    let currentDeviceId = null;

    const stop = () => {
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
        videoEl.srcObject = null;
    };

    /**
     * List available video input devices. Labels are only populated once
     * permission has been granted at least once in this browser.
     *
     * @return {Promise<Array>}
     */
    const listDevices = () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            return Promise.resolve([]);
        }
        return navigator.mediaDevices.enumerateDevices().then(
            (devices) => devices.filter((device) => device.kind === 'videoinput')
        );
    };

    /**
     * Start (or restart, if already running) the camera stream.
     *
     * @param {String|null} deviceId explicit device to use, or null for the default/remembered one.
     * @return {Promise} resolves once the stream is attached and playing.
     */
    const start = (deviceId) => {
        stop();

        const videoConstraints = {
            width: {ideal: IDEAL_DIMENSION},
            height: {ideal: IDEAL_DIMENSION},
        };
        if (deviceId) {
            videoConstraints.deviceId = {exact: deviceId};
        }

        return navigator.mediaDevices.getUserMedia({video: videoConstraints, audio: false}).then((mediastream) => {
            stream = mediastream;
            currentDeviceId = deviceId || null;
            videoEl.srcObject = stream;

            if (deviceId) {
                try {
                    window.localStorage.setItem(STORAGE_KEY, deviceId);
                } catch (ignored) {
                    // Storage may be unavailable (private browsing); not critical.
                    // eslint-disable-line no-empty
                }
            }

            return videoEl.play();
        }).catch((error) => {
            throw Object.assign(new Error('camera-error'), {reason: classifyError(error), cause: error});
        });
    };

    /**
     * The device id remembered from a previous session, if any.
     *
     * @return {String|null}
     */
    const getRememberedDeviceId = () => {
        try {
            return window.localStorage.getItem(STORAGE_KEY);
        } catch (ignored) {
            return null;
        }
    };

    /**
     * Capture the current video frame as a centre-cropped square JPEG blob.
     *
     * The server independently re-validates, re-crops and re-compresses
     * this image (classes/local/image/processor.php) - this client-side
     * crop only exists so the operator's live preview and the saved photo
     * frame the subject the same way.
     *
     * @return {Promise<Blob>}
     */
    const captureFrame = () => {
        if (!stream || !videoEl.videoWidth) {
            return Promise.reject(new Error('camera-not-active'));
        }

        const side = Math.min(videoEl.videoWidth, videoEl.videoHeight, MAX_CAPTURE_DIMENSION);
        const srcSide = Math.min(videoEl.videoWidth, videoEl.videoHeight);
        const srcX = (videoEl.videoWidth - srcSide) / 2;
        const srcY = (videoEl.videoHeight - srcSide) / 2;

        const canvas = document.createElement('canvas');
        canvas.width = side;
        canvas.height = side;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(videoEl, srcX, srcY, srcSide, srcSide, 0, 0, side, side);

        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('capture-failed'));
                }
            }, 'image/jpeg', JPEG_QUALITY);
        });
    };

    return {
        start,
        stop,
        listDevices,
        captureFrame,
        getRememberedDeviceId,
        isActive: () => stream !== null,
        getCurrentDeviceId: () => currentDeviceId,
    };
};

export default {isSupported, createController};
