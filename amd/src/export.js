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
import * as Autocomplete from 'core/form-autocomplete';

const SELECTORS = {
    FILTER_TYPE: '#lpp-export-filtertype',
    SESSION: '#lpp-export-session',
    COURSE: '#lpp-export-course',
    COHORT: '#lpp-export-cohort',
    TARGET: '.lpp-export-target',
    ROLESET: '#lpp-export-roleset',
    ROLE_FILTER: '[data-role-filter]',
    FILENAME_STRATEGY: '#lpp-export-filenamestrategy',
    EXPORT_TYPE: '#lpp-export-type',
    DENSITY: '#lpp-export-density',
    LANGUAGE: '#lpp-export-language',
    STAGE: '#lpp-export-stage',
    HEADING: '#lpp-export-heading',
    SECTION_DOCUMENT: '[data-section="document"]',
    SECTION_ZIP: '[data-section="zip"]',
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
const createExport = (filtertype, filterid, filenamestrategy, exporttype, language, stage, heading, density, roleset) =>
    Ajax.call([{
        methodname: 'local_profilephoto_create_export',
        args: {
            filtertype, filterid, filenamestrategy, fallbackstrategy: 'username',
            exporttype, language, stage, heading, density, roleset,
        },
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
 * Turn a plain <select> into a type-to-search autocomplete field.
 *
 * @param {string} selector
 * @return {Promise}
 */
const makeSearchable = (selector) => getString('export_search', 'local_profilephoto')
    .then((placeholder) => getString('export_target_none', 'local_profilephoto')
        .then((noSelection) => Autocomplete.enhance(selector, false, '', placeholder, false, true, noSelection, true)))
    .catch(Notification.exception);

/**
 * Initialise the export screen.
 */
export const init = () => {
    const filterType = document.querySelector(SELECTORS.FILTER_TYPE);
    const sessionSelect = document.querySelector(SELECTORS.SESSION);
    const courseSelect = document.querySelector(SELECTORS.COURSE);
    const cohortSelect = document.querySelector(SELECTORS.COHORT);
    const targets = document.querySelectorAll(SELECTORS.TARGET);
    const roleset = document.querySelector(SELECTORS.ROLESET);
    const roleFilter = document.querySelector(SELECTORS.ROLE_FILTER);
    const filenameStrategy = document.querySelector(SELECTORS.FILENAME_STRATEGY);
    const exportType = document.querySelector(SELECTORS.EXPORT_TYPE);
    const density = document.querySelector(SELECTORS.DENSITY);
    const language = document.querySelector(SELECTORS.LANGUAGE);
    const stage = document.querySelector(SELECTORS.STAGE);
    const heading = document.querySelector(SELECTORS.HEADING);
    const documentSections = document.querySelectorAll(SELECTORS.SECTION_DOCUMENT);
    const zipSections = document.querySelectorAll(SELECTORS.SECTION_ZIP);
    const generateBtn = document.querySelector(SELECTORS.GENERATE_BTN);
    const status = document.querySelector(SELECTORS.STATUS);

    // Show only the target select matching the chosen source (cohort / course / session).
    // The role filter only makes sense for a course (cohorts have no roles).
    const updateFilterVisibility = () => {
        targets.forEach((target) => {
            target.hidden = target.dataset.target !== filterType.value;
        });
        if (roleFilter) {
            roleFilter.hidden = filterType.value !== 'course';
        }
    };

    // The filename strategy only applies to the ZIP; density / stage / language /
    // heading only apply to the PDF layouts. Show whichever block is relevant.
    const updateSectionVisibility = () => {
        const isZip = exportType.value === 'zip';
        documentSections.forEach((el) => {
            el.hidden = isZip;
        });
        zipSections.forEach((el) => {
            el.hidden = !isZip;
        });
    };

    filterType.addEventListener('change', updateFilterVisibility);
    exportType.addEventListener('change', updateSectionVisibility);
    updateFilterVisibility();
    updateSectionVisibility();

    getExportOptions().then((options) => {
        populateSelect(sessionSelect, options.sessions, (s) => s.label);
        populateSelect(courseSelect, options.courses, (c) => c.name);
        populateSelect(cohortSelect, options.cohorts, (c) => c.name);
        // Enhance after the options exist - form-autocomplete snapshots them on enhance.
        return Promise.all([
            makeSearchable(SELECTORS.COHORT),
            makeSearchable(SELECTORS.COURSE),
        ]);
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
            return createExport(
                filtertype,
                filterid,
                filenameStrategy.value,
                exportType.value,
                language.value,
                stage.value,
                heading.value.trim(),
                density.value,
                roleset.value
            );
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
