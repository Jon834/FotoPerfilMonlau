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
 * Main page controller for the capture screen.
 *
 * Orchestrates local_profilephoto/search (find a student manually),
 * local_profilephoto/queue (session/queue auto-advance),
 * local_profilephoto/camera (live capture, with a manual file-upload
 * fallback when the camera is unavailable) and
 * local_profilephoto/shortcuts (keyboard bindings), and owns the
 * save_picture call and the confirm-before-you-can-cause-harm rule: a
 * captured-but-unsaved photo is always discarded when the operator picks
 * a different student, so a photo can never end up attributed to the
 * wrong person (encargo section 13/32, priority 2).
 *
 * @module     local_profilephoto/capture
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';
import {getStrings, getString} from 'core/str';
import * as Search from 'local_profilephoto/search';
import * as Queue from 'local_profilephoto/queue';
import * as Camera from 'local_profilephoto/camera';
import * as Shortcuts from 'local_profilephoto/shortcuts';

const SELECTORS = {
    STATUS: '#lpp-status',
    USER_PANEL: '[data-region="lpp-user-panel"]',
    CAMERA_WARNING: '#lpp-camera-warning',
    CAMERA_STATUS: '#lpp-camera-status',
    VIDEO: '#lpp-video',
    CAMERA_PLACEHOLDER: '#lpp-camera-placeholder',
    CAMERA_SELECT: '#lpp-camera-select',
    CAMERA_START_BTN: '#lpp-camera-start-btn',
    CAPTURE_BTN: '#lpp-capture-btn',
    COUNTDOWN: '#lpp-countdown',
    LIVE_REGION: '[data-region="lpp-camera-live"]',
    PREVIEW_REGION: '[data-region="lpp-camera-preview"]',
    PREVIEW_IMG: '#lpp-preview-img',
    LIVE_CONTROLS: '[data-region="lpp-controls-live"]',
    PREVIEW_CONTROLS: '[data-region="lpp-controls-preview"]',
    REPEAT_BTN: '#lpp-repeat-btn',
    SAVE_BTN: '#lpp-save-btn',
    SKIP_BTN: '#lpp-skip-btn',
    ABSENT_BTN: '#lpp-absent-btn',
    MANUAL_FALLBACK: '[data-region="lpp-manual-fallback"]',
    MANUAL_FILE: '#lpp-manual-file',
};

/**
 * Call the save_picture external function.
 *
 * @param {Number} userid
 * @param {String} imagedata base64-encoded JPEG/PNG, no data: prefix
 * @param {String} operationid idempotency token
 * @param {Number} sessionid 0 if this capture is not part of a queue session.
 * @return {Promise}
 */
const savePicture = (userid, imagedata, operationid, sessionid) => Ajax.call([{
    methodname: 'local_profilephoto_save_picture',
    args: {userid, imagedata, operationid, sessionid},
}])[0];

/**
 * Generate a random hex idempotency token for one capture confirmation.
 *
 * @return {String}
 */
const generateOperationId = () => {
    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);
    return Array.from(bytes).map((b) => b.toString(16).padStart(2, '0')).join('');
};

/**
 * Read a Blob (or File) as a base64 string, stripping the data: URL prefix.
 *
 * @param {Blob} blob
 * @return {Promise<String>}
 */
const blobToBase64 = (blob) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result).split(',')[1] || '');
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(blob);
});

/**
 * Initialise the capture screen.
 *
 * @param {Object} config {countdownEnabled, countdownSeconds, shortcutsEnabled}
 */
export const init = (config) => {
    config = config || {};
    const countdownEnabled = Boolean(config.countdownEnabled);
    const countdownSeconds = parseInt(config.countdownSeconds, 10) || 3;

    const status = document.querySelector(SELECTORS.STATUS);
    const userPanel = document.querySelector(SELECTORS.USER_PANEL);
    const cameraWarning = document.querySelector(SELECTORS.CAMERA_WARNING);
    const cameraStatus = document.querySelector(SELECTORS.CAMERA_STATUS);
    const video = document.querySelector(SELECTORS.VIDEO);
    const cameraPlaceholder = document.querySelector(SELECTORS.CAMERA_PLACEHOLDER);
    const cameraSelect = document.querySelector(SELECTORS.CAMERA_SELECT);
    const cameraStartBtn = document.querySelector(SELECTORS.CAMERA_START_BTN);
    const captureBtn = document.querySelector(SELECTORS.CAPTURE_BTN);
    const countdownEl = document.querySelector(SELECTORS.COUNTDOWN);
    const liveRegion = document.querySelector(SELECTORS.LIVE_REGION);
    const previewRegion = document.querySelector(SELECTORS.PREVIEW_REGION);
    const previewImg = document.querySelector(SELECTORS.PREVIEW_IMG);
    const liveControls = document.querySelector(SELECTORS.LIVE_CONTROLS);
    const previewControls = document.querySelector(SELECTORS.PREVIEW_CONTROLS);
    const repeatBtn = document.querySelector(SELECTORS.REPEAT_BTN);
    const saveBtn = document.querySelector(SELECTORS.SAVE_BTN);
    const skipBtn = document.querySelector(SELECTORS.SKIP_BTN);
    const absentBtn = document.querySelector(SELECTORS.ABSENT_BTN);
    const manualFallback = document.querySelector(SELECTORS.MANUAL_FALLBACK);
    const manualFile = document.querySelector(SELECTORS.MANUAL_FILE);

    let selectedUser = null;
    let capturedBlob = null;
    let saving = false;
    let cameraReady = false;
    let countdownTimer = null;
    const camera = Camera.createController(video);
    // Whether the live-camera UI should be offered at all: starts out
    // matching browser/context support, but is permanently downgraded to
    // the manual fallback the first time an actual start() attempt fails
    // (denied permission, no device, insecure context...), so the
    // fallback stays visible on every later showLive() call too. Forced
    // off under Behat (see index.php), which cannot reliably drive a
    // real/fake webcam.
    let useCameraUi = !config.forceManualCapture && Camera.isSupported();

    const updateCaptureButtonState = () => {
        captureBtn.disabled = !(selectedUser && cameraReady) || saving;
    };

    const updateSaveButtonState = () => {
        saveBtn.disabled = !(selectedUser && capturedBlob) || saving;
    };

    const updateQueueButtonsState = () => {
        const inQueue = Boolean(queueHandle.getSessionId());
        skipBtn.hidden = !inQueue;
        absentBtn.hidden = !inQueue;
        skipBtn.disabled = !selectedUser || saving;
        absentBtn.disabled = !selectedUser || saving;
    };

    /**
     * Show the "not yet captured" stage: the live camera view when
     * supported, or the manual file-upload fallback otherwise.
     */
    const showLive = () => {
        liveRegion.hidden = !useCameraUi;
        manualFallback.hidden = useCameraUi;
        previewRegion.hidden = true;
        liveControls.hidden = !useCameraUi;
        previewControls.hidden = true;
    };

    const showPreview = () => {
        liveRegion.hidden = true;
        manualFallback.hidden = true;
        previewRegion.hidden = false;
        liveControls.hidden = true;
        previewControls.hidden = false;
    };

    /**
     * Discard any unsaved captured photo and go back to the live/idle state.
     */
    const discardCapture = () => {
        if (capturedBlob) {
            URL.revokeObjectURL(previewImg.src);
        }
        capturedBlob = null;
        previewImg.src = '';
        showLive();
        updateSaveButtonState();
    };

    /**
     * Common landing point for a freshly captured (or manually chosen) photo.
     *
     * @param {Blob} blob
     */
    const handleCaptured = (blob) => {
        capturedBlob = blob;
        previewImg.src = URL.createObjectURL(blob);
        showPreview();
        updateSaveButtonState();
    };

    const runCountdownThenCapture = () => {
        if (!countdownEnabled) {
            camera.captureFrame().then(handleCaptured).catch(Notification.exception);
            return;
        }

        let remaining = countdownSeconds;
        countdownEl.hidden = false;
        countdownEl.textContent = String(remaining);

        countdownTimer = window.setInterval(() => {
            remaining -= 1;
            if (remaining > 0) {
                countdownEl.textContent = String(remaining);
                return;
            }
            window.clearInterval(countdownTimer);
            countdownTimer = null;
            countdownEl.hidden = true;
            camera.captureFrame().then(handleCaptured).catch(Notification.exception);
        }, 1000);
    };

    const doCapture = () => {
        if (captureBtn.disabled) {
            return;
        }
        runCountdownThenCapture();
    };

    /**
     * Select a student, whichever source picked them (manual search or
     * queue auto-advance). Always discards any unsaved capture first, so
     * a pending photo can never end up attributed to the wrong person.
     *
     * @param {Object} user
     */
    const selectUser = (user) => {
        if (capturedBlob) {
            discardCapture();
        }

        selectedUser = user;
        updateCaptureButtonState();
        updateQueueButtonsState();

        Templates.renderForPromise('local_profilephoto/user_card', user).then(({html, js}) => {
            Templates.replaceNodeContents(userPanel, html, js);
            return null;
        }).catch(Notification.exception);
    };

    /**
     * Clear the current selection (queue finished, or session ended).
     */
    const clearSelection = () => {
        if (capturedBlob) {
            discardCapture();
        }
        selectedUser = null;
        userPanel.innerHTML = '';
        updateCaptureButtonState();
        updateQueueButtonsState();
    };

    const doSave = () => {
        if (saveBtn.disabled || saving) {
            return;
        }

        const targetUser = selectedUser;
        const sessionid = queueHandle.getSessionId() || 0;
        saving = true;
        updateSaveButtonState();
        updateQueueButtonsState();

        blobToBase64(capturedBlob).then((imagedata) => {
            return savePicture(targetUser.id, imagedata, generateOperationId(), sessionid);
        }).then((result) => {
            return getStrings([
                {key: 'save_success', component: 'local_profilephoto', param: result.fullname},
            ]);
        }).then(([message]) => {
            status.textContent = message;
            discardCapture();
            manualFile.value = '';

            if (sessionid) {
                // Auto-advances via queueHandle's onNext callback (selectUser or clearSelection).
                return queueHandle.loadNext();
            }

            clearSelection();
            searchHandle.focus();
            return null;
        }).catch(Notification.exception).finally(() => {
            saving = false;
            updateCaptureButtonState();
            updateSaveButtonState();
            updateQueueButtonsState();
        });
    };

    const doQueueAction = (queuestatus) => {
        if (!selectedUser || saving) {
            return;
        }
        queueHandle.updateCurrentItem(selectedUser.id, queuestatus);
    };

    // Camera setup (or manual fallback if unsupported).
    const startCamera = (deviceId) => {
        cameraStatus.textContent = '';
        return camera.start(deviceId).then(() => {
            cameraReady = true;
            cameraStatus.textContent = '';
            cameraPlaceholder.hidden = true;
            updateCaptureButtonState();
            return camera.listDevices();
        }).then((devices) => {
            if (devices.length > 1) {
                cameraSelect.hidden = false;
                cameraSelect.innerHTML = '';
                devices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.textContent = device.label || String(index + 1);
                    if (device.deviceId === camera.getCurrentDeviceId()) {
                        option.selected = true;
                    }
                    cameraSelect.appendChild(option);
                });
            }
            return null;
        }).catch((error) => {
            cameraReady = false;
            useCameraUi = false;
            updateCaptureButtonState();
            const reason = error && error.reason ? error.reason : 'generic';
            return getString('camera_error_' + reason, 'local_profilephoto').then((message) => {
                cameraStatus.textContent = message;
                showLive();
                return null;
            });
        });
    };

    if (useCameraUi) {
        cameraStartBtn.addEventListener('click', () => {
            startCamera(camera.getRememberedDeviceId());
        });
        cameraSelect.addEventListener('change', () => {
            startCamera(cameraSelect.value);
        });
    } else {
        cameraWarning.hidden = false;
        cameraStartBtn.hidden = true;
        captureBtn.hidden = true;
    }

    showLive();
    captureBtn.addEventListener('click', doCapture);
    repeatBtn.addEventListener('click', discardCapture);
    saveBtn.addEventListener('click', doSave);
    skipBtn.addEventListener('click', () => doQueueAction('skipped'));
    absentBtn.addEventListener('click', () => doQueueAction('absent'));

    manualFile.addEventListener('change', () => {
        const file = manualFile.files[0];
        if (file) {
            handleCaptured(file);
        }
    });

    const searchHandle = Search.init(selectUser);

    const queueHandle = Queue.init({
        onProgress: updateQueueButtonsState,
        onNext: (user) => {
            if (user) {
                selectUser(user);
            } else {
                clearSelection();
            }
        },
    });

    if (config.shortcutsEnabled !== false) {
        Shortcuts.bind({
            ' ': () => {
                if (!captureBtn.disabled && liveRegion.hidden === false) {
                    doCapture();
                }
            },
            'Enter': () => {
                if (!saveBtn.disabled && previewRegion.hidden === false) {
                    doSave();
                }
            },
            'r': () => {
                if (previewRegion.hidden === false) {
                    discardCapture();
                }
            },
            'b': () => searchHandle.focus(),
            'Escape': () => {
                if (previewRegion.hidden === false) {
                    discardCapture();
                }
            },
            's': () => doQueueAction('skipped'),
        });
    }

    window.addEventListener('beforeunload', () => camera.stop());
    window.addEventListener('pagehide', () => camera.stop());
};

export default {init};
