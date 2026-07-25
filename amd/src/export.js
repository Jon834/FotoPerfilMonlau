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
 * Export filter screen (encargo section 15).
 *
 * @module     local_profilephoto/export
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

const SELECTORS = {
    FILTER_TYPE: '#lpp-export-filtertype',
    SESSION: '#lpp-export-session',
    COURSE: '#lpp-export-course',
    COHORT: '#lpp-export-cohort',
    FILENAME_STRATEGY: '#lpp-export-filenamestrategy',
    GENERATE_BTN: '#lpp-export-btn',
    STATUS: '#lpp-export-status',
};

/**
 * @return {Promise}
 */
const getExportOptions = () => Ajax.call([{
    methodname: 'local_profilephoto_get_export_options',
    args: {},
}])[0];

/**
 * @param {string} filtertype
 * @param {number} filterid
 * @param {string} filenamestrategy
 * @return {Promise}
 */
const createExport = (filtertype, filterid, filenamestrategy) => Ajax.call([{
    methodname: 'local_profilephoto_create_export',
    args: {filtertype, filterid, filenamestrategy, fallbackstrategy: 'username'},
}])[0];

/**
 * @param {HTMLSelectElement} selectEl
 * @param {Array} options
 * @param {Function} labelFn
 */
const populateSelect = (selectEl, options, labelFn) => {
    selectEl.innerHTML = '';
    options.forEach((option) => {
        const optionEl = document.createElement('option');
        optionEl.value = option.id;
        optionEl.textContent = labelFn(option);
        selectEl.appendChild(optionEl);
    });
};

/**
 * Initialise the export screen.
 */
export const init = () => {
    const filterType = document.querySelector(SELECTORS.FILTER_TYPE);
    const sessionSelect = document.querySelector(SELECTORS.SESSION);
    const courseSelect = document.querySelector(SELECTORS.COURSE);
    const cohortSelect = document.querySelector(SELECTORS.COHORT);
    const filenameStrategy = document.querySelector(SELECTORS.FILENAME_STRATEGY);
    const generateBtn = document.querySelector(SELECTORS.GENERATE_BTN);
    const status = document.querySelector(SELECTORS.STATUS);

    const updateFilterVisibility = () => {
        sessionSelect.hidden = filterType.value !== 'session';
        courseSelect.hidden = filterType.value !== 'course';
        cohortSelect.hidden = filterType.value !== 'cohort';
    };

    filterType.addEventListener('change', updateFilterVisibility);
    updateFilterVisibility();

    getExportOptions().then((options) => {
        populateSelect(sessionSelect, options.sessions, (s) => s.label);
        populateSelect(courseSelect, options.courses, (c) => c.name);
        populateSelect(cohortSelect, options.cohorts, (c) => c.name);
        return null;
    }).catch(Notification.exception);

    generateBtn.addEventListener('click', () => {
        const filtertype = filterType.value;
        const select = filtertype === 'session' ? sessionSelect : (filtertype === 'course' ? courseSelect : cohortSelect);
        const filterid = parseInt(select.value, 10);

        if (!filterid) {
            return;
        }

        generateBtn.disabled = true;
        getString('export_generating', 'local_profilephoto').then((message) => {
            status.textContent = message;
            return createExport(filtertype, filterid, filenameStrategy.value);
        }).then((result) => {
            return getString('export_ready', 'local_profilephoto', result.count).then((message) => {
                status.textContent = message;
                window.location.href = M.cfg.wwwroot + '/local/profilephoto/export.php?token=' + result.token;
                return null;
            });
        }).catch(Notification.exception).finally(() => {
            generateBtn.disabled = false;
        });
    });
};

export default {init};
