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
 * Search, select and (Entrega 1) manual-file capture logic for the
 * profile photo capture screen.
 *
 * Entrega 2 replaces the manual <input type=file> flow with a live
 * getUserMedia() camera module, but reuses the same selectors
 * (#lpp-save-btn, [data-region="lpp-user-panel"], selectedUserId state)
 * and the same save_picture/get_user/search_users external functions, so
 * this module's public surface (init()) does not change.
 *
 * @module     local_profilephoto/search
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';
import {getStrings} from 'core/str';

const SELECTORS = {
    SEARCH_INPUT: '#lpp-search-input',
    RESULTS: '[data-region="lpp-search-results"]',
    USER_PANEL: '[data-region="lpp-user-panel"]',
    STATUS: '#lpp-status',
    MANUAL_FORM: '#lpp-manual-capture-form',
    MANUAL_FILE: '#lpp-manual-file',
    MANUAL_PREVIEW: '#lpp-manual-preview',
    SAVE_BTN: '#lpp-save-btn',
};

const SEARCH_DEBOUNCE_MS = 250;
const MIN_QUERY_LENGTH = 2;

let debounceTimer = null;
let selectedUserId = null;
let selectedFile = null;
let saving = false;

/**
 * Call the search_users external function.
 *
 * @param {String} query
 * @return {Promise}
 */
const searchUsers = (query) => Ajax.call([{
    methodname: 'local_profilephoto_search_users',
    args: {query, limit: 20},
}])[0];

/**
 * Call the get_user external function.
 *
 * @param {Number} userid
 * @return {Promise}
 */
const getUser = (userid) => Ajax.call([{
    methodname: 'local_profilephoto_get_user',
    args: {userid},
}])[0];

/**
 * Call the save_picture external function.
 *
 * @param {Number} userid
 * @param {String} imagedata base64-encoded JPEG/PNG, no data: prefix
 * @param {String} operationid idempotency token
 * @return {Promise}
 */
const savePicture = (userid, imagedata, operationid) => Ajax.call([{
    methodname: 'local_profilephoto_save_picture',
    args: {userid, imagedata, operationid},
}])[0];

/**
 * Render the search results list.
 *
 * @param {Array} users
 * @return {Promise}
 */
const renderResults = (users) => {
    const target = document.querySelector(SELECTORS.RESULTS);
    return Templates.renderForPromise('local_profilephoto/search_results', {
        hasusers: users.length > 0,
        users,
    }).then(({html, js}) => {
        Templates.replaceNodeContents(target, html, js);
        return null;
    });
};

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
 * Read a File as a base64 string, stripping the data: URL prefix.
 *
 * @param {File} file
 * @return {Promise<String>}
 */
const fileToBase64 = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result).split(',')[1] || '');
    reader.onerror = () => reject(reader.error);
    reader.readAsDataURL(file);
});

/**
 * Initialise the capture screen.
 */
export const init = () => {
    const input = document.querySelector(SELECTORS.SEARCH_INPUT);
    const resultsRegion = document.querySelector(SELECTORS.RESULTS);
    const userPanel = document.querySelector(SELECTORS.USER_PANEL);
    const fileInput = document.querySelector(SELECTORS.MANUAL_FILE);
    const preview = document.querySelector(SELECTORS.MANUAL_PREVIEW);
    const form = document.querySelector(SELECTORS.MANUAL_FORM);
    const saveBtn = document.querySelector(SELECTORS.SAVE_BTN);
    const status = document.querySelector(SELECTORS.STATUS);

    const updateSaveButtonState = () => {
        saveBtn.disabled = !(selectedUserId && selectedFile) || saving;
    };

    const resetAfterSave = () => {
        selectedUserId = null;
        selectedFile = null;
        fileInput.value = '';
        preview.hidden = true;
        userPanel.innerHTML = '';
        updateSaveButtonState();
        input.focus();
    };

    const selectUser = (userid) => {
        getUser(userid).then((user) => {
            selectedUserId = user.id;
            resultsRegion.innerHTML = '';
            input.value = '';
            updateSaveButtonState();
            return Templates.renderForPromise('local_profilephoto/user_card', user);
        }).then(({html, js}) => {
            Templates.replaceNodeContents(userPanel, html, js);
            return null;
        }).catch(Notification.exception);
    };

    input.addEventListener('input', () => {
        const query = input.value.trim();
        window.clearTimeout(debounceTimer);

        if (query.length < MIN_QUERY_LENGTH) {
            resultsRegion.innerHTML = '';
            return;
        }

        debounceTimer = window.setTimeout(() => {
            searchUsers(query).then(renderResults).catch(Notification.exception);
        }, SEARCH_DEBOUNCE_MS);
    });

    resultsRegion.addEventListener('click', (event) => {
        const button = event.target.closest('[data-userid]');
        if (button) {
            selectUser(parseInt(button.dataset.userid, 10));
        }
    });

    fileInput.addEventListener('change', () => {
        selectedFile = fileInput.files[0] || null;

        if (selectedFile) {
            preview.src = URL.createObjectURL(selectedFile);
            preview.hidden = false;
        } else {
            preview.hidden = true;
        }

        updateSaveButtonState();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!selectedUserId || !selectedFile || saving) {
            return;
        }

        saving = true;
        updateSaveButtonState();

        fileToBase64(selectedFile).then((imagedata) => {
            return savePicture(selectedUserId, imagedata, generateOperationId());
        }).then((result) => {
            return getStrings([
                {key: 'save_success', component: 'local_profilephoto', param: result.fullname},
            ]);
        }).then(([message]) => {
            status.textContent = message;
            resetAfterSave();
            return null;
        }).catch(Notification.exception).finally(() => {
            saving = false;
            updateSaveButtonState();
        });
    });
};

export default {init};
