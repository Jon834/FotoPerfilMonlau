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
 * Export filter screen (encargo section 15), plus the "Control d'activitat"
 * mode (cohort roster PDF for outings/activities).
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
    MODE_STANDARD: '[data-mode="standard"]',
    MODE_ACTIVITY: '[data-mode="activity"]',
    ACTIVITY_COHORT: '#lpp-activity-cohort',
    ACTIVITY_COHORT_INFO: '#lpp-activity-cohort-info',
    ACTIVITY_NAME: '#lpp-activity-name',
    ACTIVITY_DATE: '#lpp-activity-date',
    ACTIVITY_PLACE: '#lpp-activity-place',
    ACTIVITY_RESPONSABLES: '#lpp-activity-responsables',
    ACTIVITY_TEMPLATE: '#lpp-activity-template',
    ACTIVITY_COLUMNS_GRID: '#lpp-activity-columns-grid',
    ACTIVITY_CUSTOMCOLUMNS: '#lpp-activity-customcolumns',
    ACTIVITY_ADDCOLUMN_BTN: '#lpp-activity-addcolumn-btn',
    ACTIVITY_COLUMNS_WARNING: '#lpp-activity-columns-warning',
    ACTIVITY_ORDER_LIST: '#lpp-activity-order-list',
    ACTIVITY_SHOWPHOTOS: '#lpp-activity-showphotos',
    ACTIVITY_SHOWGENERALOBS: '#lpp-activity-showgeneralobs',
    ACTIVITY_ORDER: '#lpp-activity-order',
    ACTIVITY_STAGE: '#lpp-activity-stage',
    ACTIVITY_LANGUAGE: '#lpp-activity-language',
    ACTIVITY_PREVIEW_BTN: '#lpp-activity-preview-btn',
    ACTIVITY_GENERATE_BTN: '#lpp-activity-generate-btn',
};

/** @var {number} Maximum extra columns (beyond Núm./Alumne), mirrors activity_pdf_builder::MAX_EXTRA_COLUMNS. */
const MAX_EXTRA_COLUMNS = 6;

/** @var {number} Maximum custom (user-defined) columns. */
const MAX_CUSTOM_COLUMNS = 4;

/** @var {Object} Quick-template column presets. "personalitzat" leaves the current selection untouched. */
const COLUMN_TEMPLATES = {
    sortida: ['present', 'autoritzacio', 'transport', 'menu', 'observacions'],
    activitat: ['present', 'material', 'observacions'],
    taller: ['present', 'epi', 'grupequip', 'material', 'observacions'],
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
 * @return {Promise}
 */
const getActivityCohorts = () => Ajax.call([{
    methodname: 'local_profilephoto_get_activity_cohorts',
    args: {},
}])[0];

/**
 * @param {number} cohortid
 * @return {Promise}
 */
const getActivityCohortInfo = (cohortid) => Ajax.call([{
    methodname: 'local_profilephoto_get_activity_cohort_info',
    args: {cohortid},
}])[0];

/**
 * @param {Object} args
 * @return {Promise}
 */
const createActivityExport = (args) => Ajax.call([{
    methodname: 'local_profilephoto_create_activity_export',
    args,
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
    initStandardMode();
    initModeToggle();
    if (document.querySelector(SELECTORS.MODE_ACTIVITY)) {
        initActivityMode();
    }
};

/**
 * Wire up the pre-existing ZIP/PDF export flow (sessions, courses, cohorts).
 */
const initStandardMode = () => {
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

/**
 * Toggle between the pre-existing form and the "Control d'activitat" mode
 * based on the shared #lpp-export-type select.
 */
const initModeToggle = () => {
    const exportType = document.querySelector(SELECTORS.EXPORT_TYPE);
    const standardBlocks = document.querySelectorAll(SELECTORS.MODE_STANDARD);
    const activityBlocks = document.querySelectorAll(SELECTORS.MODE_ACTIVITY);

    const update = () => {
        const isActivity = exportType.value === 'activity';
        standardBlocks.forEach((el) => {
            el.hidden = isActivity;
        });
        activityBlocks.forEach((el) => {
            el.hidden = !isActivity;
        });
    };

    exportType.addEventListener('change', update);
    update();
};

/**
 * Wire up the "Control d'activitat" screen: cohort search, template presets,
 * configurable/custom columns with manual reordering, and the two
 * (preview/generate) actions.
 */
const initActivityMode = () => {
    const cohortSelect = document.querySelector(SELECTORS.ACTIVITY_COHORT);
    const cohortInfo = document.querySelector(SELECTORS.ACTIVITY_COHORT_INFO);
    const template = document.querySelector(SELECTORS.ACTIVITY_TEMPLATE);
    const columnsGrid = document.querySelector(SELECTORS.ACTIVITY_COLUMNS_GRID);
    const customColumnsEl = document.querySelector(SELECTORS.ACTIVITY_CUSTOMCOLUMNS);
    const addColumnBtn = document.querySelector(SELECTORS.ACTIVITY_ADDCOLUMN_BTN);
    const columnsWarning = document.querySelector(SELECTORS.ACTIVITY_COLUMNS_WARNING);
    const orderList = document.querySelector(SELECTORS.ACTIVITY_ORDER_LIST);
    const previewBtn = document.querySelector(SELECTORS.ACTIVITY_PREVIEW_BTN);
    const generateBtn = document.querySelector(SELECTORS.ACTIVITY_GENERATE_BTN);
    const status = document.querySelector(SELECTORS.STATUS);

    // Ordered list of extra column keys (beyond Núm./Alumne). Custom columns'
    // name/type live in customMeta, keyed by the same generated key.
    const state = {
        orderedKeys: [],
        customMeta: {},
        customCounter: 0,
    };

    const standardCheckboxes = () => Array.from(columnsGrid.querySelectorAll('input[type="checkbox"]'));

    const standardLabel = (key) => {
        const checkbox = columnsGrid.querySelector(`[data-col-key="${key}"]`);
        return checkbox ? checkbox.closest('label').textContent.trim() : key;
    };

    const columnType = (key) => {
        if (state.customMeta[key]) {
            return state.customMeta[key].type;
        }
        const checkbox = columnsGrid.querySelector(`[data-col-key="${key}"]`);
        return checkbox ? checkbox.dataset.colType : 'checkbox';
    };

    const columnLabel = (key) => (state.customMeta[key] ? (state.customMeta[key].label || '') : standardLabel(key));

    /**
     * Disable further additions once the limit is reached; keep everything
     * already selected untouched. Purely defensive - the state is always
     * kept at/under the limit by construction.
     */
    const enforceColumnLimit = () => {
        const atLimit = state.orderedKeys.length >= MAX_EXTRA_COLUMNS;
        columnsWarning.hidden = state.orderedKeys.length <= MAX_EXTRA_COLUMNS;
        standardCheckboxes().forEach((checkbox) => {
            if (!checkbox.checked) {
                checkbox.disabled = atLimit;
            }
        });
        addColumnBtn.disabled = atLimit || Object.keys(state.customMeta).length >= MAX_CUSTOM_COLUMNS;
    };

    const renderOrderList = () => {
        orderList.innerHTML = '';
        state.orderedKeys.forEach((key, index) => {
            const li = document.createElement('li');
            li.className = 'lpp-activity-order-item';

            const label = document.createElement('span');
            label.className = 'lpp-activity-order-label';
            label.textContent = columnLabel(key) || key;
            li.appendChild(label);

            const controls = document.createElement('span');
            controls.className = 'lpp-activity-order-controls';

            const upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'btn btn-sm btn-link';
            upBtn.textContent = '↑';
            upBtn.disabled = index === 0;
            upBtn.addEventListener('click', () => moveColumn(key, -1));
            controls.appendChild(upBtn);

            const downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'btn btn-sm btn-link';
            downBtn.textContent = '↓';
            downBtn.disabled = index === state.orderedKeys.length - 1;
            downBtn.addEventListener('click', () => moveColumn(key, 1));
            controls.appendChild(downBtn);

            li.appendChild(controls);
            orderList.appendChild(li);
        });
    };

    const moveColumn = (key, delta) => {
        const index = state.orderedKeys.indexOf(key);
        const target = index + delta;
        if (index === -1 || target < 0 || target >= state.orderedKeys.length) {
            return;
        }
        [state.orderedKeys[index], state.orderedKeys[target]] = [state.orderedKeys[target], state.orderedKeys[index]];
        renderOrderList();
    };

    const syncFromCheckboxes = () => {
        standardCheckboxes().forEach((checkbox) => {
            const key = checkbox.dataset.colKey;
            const present = state.orderedKeys.includes(key);
            if (checkbox.checked && !present) {
                state.orderedKeys.push(key);
            } else if (!checkbox.checked && present) {
                state.orderedKeys = state.orderedKeys.filter((k) => k !== key);
            }
        });
        enforceColumnLimit();
        renderOrderList();
    };

    const renderCustomColumnRow = (key) => {
        getString('activity_customcolumn_name', 'local_profilephoto').then((nameLabel) => {
            const row = document.createElement('div');
            row.className = 'lpp-activity-customcolumn-row';
            row.dataset.key = key;

            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.className = 'form-control';
            nameInput.setAttribute('aria-label', nameLabel);
            nameInput.maxLength = 40;
            nameInput.addEventListener('input', () => {
                state.customMeta[key].label = nameInput.value.trim();
                renderOrderList();
            });

            const typeSelect = document.createElement('select');
            typeSelect.className = 'form-control';
            return Promise.all([
                getString('activity_customcolumn_type_checkbox', 'local_profilephoto'),
                getString('activity_customcolumn_type_text', 'local_profilephoto'),
                getString('activity_customcolumn_remove', 'local_profilephoto'),
                getString('activity_customcolumn_placeholder', 'local_profilephoto'),
            ]).then(([checkboxLabel, textLabel, removeLabel, placeholder]) => {
                nameInput.placeholder = placeholder;

                const checkboxOption = document.createElement('option');
                checkboxOption.value = 'checkbox';
                checkboxOption.textContent = checkboxLabel;
                typeSelect.appendChild(checkboxOption);

                const textOption = document.createElement('option');
                textOption.value = 'text';
                textOption.textContent = textLabel;
                typeSelect.appendChild(textOption);

                typeSelect.addEventListener('change', () => {
                    state.customMeta[key].type = typeSelect.value;
                });

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-link lpp-activity-customcolumn-remove';
                removeBtn.textContent = '✕';
                removeBtn.setAttribute('aria-label', removeLabel);
                removeBtn.addEventListener('click', () => removeCustomColumn(key));

                row.appendChild(nameInput);
                row.appendChild(typeSelect);
                row.appendChild(removeBtn);
                customColumnsEl.appendChild(row);
                return null;
            });
        }).catch(Notification.exception);
    };

    const addCustomColumn = () => {
        if (state.orderedKeys.length >= MAX_EXTRA_COLUMNS
                || Object.keys(state.customMeta).length >= MAX_CUSTOM_COLUMNS) {
            enforceColumnLimit();
            return;
        }
        state.customCounter++;
        const key = 'custom' + state.customCounter;
        state.customMeta[key] = {label: '', type: 'checkbox'};
        state.orderedKeys.push(key);
        renderCustomColumnRow(key);
        enforceColumnLimit();
        renderOrderList();
    };

    const removeCustomColumn = (key) => {
        delete state.customMeta[key];
        state.orderedKeys = state.orderedKeys.filter((k) => k !== key);
        const row = customColumnsEl.querySelector(`[data-key="${key}"]`);
        if (row) {
            row.remove();
        }
        enforceColumnLimit();
        renderOrderList();
    };

    const applyTemplate = (name) => {
        if (name === 'personalitzat' || !COLUMN_TEMPLATES[name]) {
            return;
        }
        // Clear custom columns.
        Object.keys(state.customMeta).forEach((key) => removeCustomColumn(key));

        const keys = COLUMN_TEMPLATES[name];
        standardCheckboxes().forEach((checkbox) => {
            checkbox.disabled = false;
            checkbox.checked = keys.includes(checkbox.dataset.colKey);
        });
        state.orderedKeys = keys.slice();
        enforceColumnLimit();
        renderOrderList();
    };

    standardCheckboxes().forEach((checkbox) => checkbox.addEventListener('change', syncFromCheckboxes));
    addColumnBtn.addEventListener('click', addCustomColumn);
    template.addEventListener('change', () => applyTemplate(template.value));

    // Apply the default template (Sortida) on load, matching the pre-checked columns.
    applyTemplate(template.value);

    // Cohort search + member-count readout.
    getActivityCohorts().then((options) => {
        populateSelect(cohortSelect, options.cohorts, (c) => c.name);
        return makeSearchable(SELECTORS.ACTIVITY_COHORT);
    }).catch(Notification.exception);

    cohortSelect.addEventListener('change', () => {
        const cohortid = parseInt(cohortSelect.value, 10);
        cohortInfo.textContent = '';
        if (!cohortid) {
            return;
        }
        getActivityCohortInfo(cohortid).then((info) => {
            return getString('activity_cohort_membercount', 'local_profilephoto', info.membercount).then((message) => {
                cohortInfo.textContent = message;
                return null;
            });
        }).catch(Notification.exception);
    });

    const buildColumnsPayload = () => state.orderedKeys.map((key) => ({
        key,
        label: columnLabel(key),
        type: columnType(key),
    }));

    const collectActivityFields = () => ({
        name: document.querySelector(SELECTORS.ACTIVITY_NAME).value.trim(),
        date: document.querySelector(SELECTORS.ACTIVITY_DATE).value,
        place: document.querySelector(SELECTORS.ACTIVITY_PLACE).value.trim(),
        responsables: document.querySelector(SELECTORS.ACTIVITY_RESPONSABLES).value.trim(),
    });

    const runGeneration = (onReady) => {
        const cohortid = parseInt(cohortSelect.value, 10);
        if (!cohortid) {
            getString('activity_nocohort', 'local_profilephoto').then((message) => {
                status.textContent = message;
                return null;
            }).catch(Notification.exception);
            return;
        }

        previewBtn.disabled = true;
        generateBtn.disabled = true;

        getString('activity_generating', 'local_profilephoto').then((message) => {
            status.textContent = message;
            return createActivityExport({
                cohortid,
                activity: collectActivityFields(),
                columns: buildColumnsPayload(),
                language: document.querySelector(SELECTORS.ACTIVITY_LANGUAGE).value,
                stage: document.querySelector(SELECTORS.ACTIVITY_STAGE).value,
                showphotos: document.querySelector(SELECTORS.ACTIVITY_SHOWPHOTOS).value === '1',
                showgeneralobs: document.querySelector(SELECTORS.ACTIVITY_SHOWGENERALOBS).checked,
                order: document.querySelector(SELECTORS.ACTIVITY_ORDER).value,
            });
        }).then((result) => {
            return getString('export_ready', 'local_profilephoto', result.count).then((message) => {
                status.textContent = message;
                const url = M.cfg.wwwroot + '/local/profilephoto/export.php?token=' + result.token;
                onReady(url);
                return null;
            });
        }).catch(Notification.exception).finally(() => {
            previewBtn.disabled = false;
            generateBtn.disabled = false;
        });
    };

    generateBtn.addEventListener('click', () => runGeneration((url) => {
        window.location.href = url;
    }));

    previewBtn.addEventListener('click', () => runGeneration((url) => {
        window.open(url, '_blank');
    }));
};

export default {init};
