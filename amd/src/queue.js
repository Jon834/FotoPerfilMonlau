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
 * Session setup (course/cohort filter) and queue progression (encargo
 * section 12/4.1). Has no notion of cameras or search - capture.js
 * supplies onProgress/onNext callbacks and owns what happens with the
 * student the queue hands it.
 *
 * @module     local_profilephoto/queue
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';

const SELECTORS = {
    SETUP: '[data-region="lpp-session-setup"]',
    FILTER_TYPE: '#lpp-session-filtertype',
    FILTER_COURSE: '#lpp-session-course',
    FILTER_COHORT: '#lpp-session-cohort',
    ORDER_TYPE: '#lpp-session-order',
    START_BTN: '#lpp-session-start-btn',
    PROGRESS: '[data-region="lpp-session-progress"]',
    PROGRESS_TEXT: '#lpp-session-progress-text',
    END_BTN: '#lpp-session-end-btn',
};

/**
 * @param {string} filtertype
 * @param {number} filterid
 * @param {string} ordertype
 * @return {Promise}
 */
const createSession = (filtertype, filterid, ordertype) => Ajax.call([{
    methodname: 'local_profilephoto_create_session',
    args: {filtertype, filterid, ordertype},
}])[0];

/**
 * @param {number} sessionid
 * @return {Promise}
 */
const getQueueStatus = (sessionid) => Ajax.call([{
    methodname: 'local_profilephoto_get_queue_status',
    args: {sessionid},
}])[0];

/**
 * @param {number} sessionid
 * @param {number} userid
 * @param {string} status
 * @return {Promise}
 */
const updateQueueItem = (sessionid, userid, status) => Ajax.call([{
    methodname: 'local_profilephoto_update_queue_item',
    args: {sessionid, userid, status},
}])[0];

/**
 * @return {Promise}
 */
const getSessionOptions = () => Ajax.call([{
    methodname: 'local_profilephoto_get_session_options',
    args: {},
}])[0];

/**
 * Fill a <select> with {id, name} options.
 *
 * @param {HTMLSelectElement} selectEl
 * @param {Array} options
 */
const populateSelect = (selectEl, options) => {
    selectEl.innerHTML = '';
    options.forEach((option) => {
        const optionEl = document.createElement('option');
        optionEl.value = option.id;
        optionEl.textContent = option.name;
        selectEl.appendChild(optionEl);
    });
};

/**
 * Initialise the session setup form and queue progression.
 *
 * @param {Object} handlers {onProgress(status), onNext(userOrNull)}
 * @return {Object} {getSessionId(), loadNext(), updateCurrentItem(userid, status)}
 */
export const init = (handlers) => {
    const setup = document.querySelector(SELECTORS.SETUP);
    const filterType = document.querySelector(SELECTORS.FILTER_TYPE);
    const filterCourse = document.querySelector(SELECTORS.FILTER_COURSE);
    const filterCohort = document.querySelector(SELECTORS.FILTER_COHORT);
    const orderType = document.querySelector(SELECTORS.ORDER_TYPE);
    const startBtn = document.querySelector(SELECTORS.START_BTN);
    const progress = document.querySelector(SELECTORS.PROGRESS);
    const progressText = document.querySelector(SELECTORS.PROGRESS_TEXT);
    const endBtn = document.querySelector(SELECTORS.END_BTN);

    let sessionId = null;
    // Fetched once, unsubstituted (no $a passed): {$a->xxx} tokens are
    // replaced by hand on every update instead of round-tripping to the
    // server on each progress change (encargo section 11 performance goals).
    const progressTemplate = getString('session_progress_template', 'local_profilephoto');

    const updateFilterVisibility = () => {
        const isCourse = filterType.value === 'course';
        filterCourse.hidden = !isCourse;
        filterCohort.hidden = isCourse;
    };

    const renderProgress = (status) => {
        progressTemplate.then((template) => {
            progressText.textContent = template
                .replace('{$a->captured}', status.captured)
                .replace('{$a->total}', status.total)
                .replace('{$a->pending}', status.pending);
            return null;
        }).catch(Notification.exception);
        handlers.onProgress(status);
    };

    const loadNext = () => {
        if (!sessionId) {
            return Promise.resolve(null);
        }
        return getQueueStatus(sessionId).then((status) => {
            renderProgress(status);
            handlers.onNext(status.next || null);
            return status;
        }).catch(Notification.exception);
    };

    filterType.addEventListener('change', updateFilterVisibility);
    updateFilterVisibility();

    getSessionOptions().then((options) => {
        populateSelect(filterCourse, options.courses);
        populateSelect(filterCohort, options.cohorts);
        if (!options.cohorts.length) {
            // No cohort-based sessions available to this operator (encargo
            // section 17): drop it from the filter type choice entirely.
            const cohortOption = filterType.querySelector('option[value="cohort"]');
            if (cohortOption) {
                cohortOption.remove();
            }
        }
        return null;
    }).catch(Notification.exception);

    startBtn.addEventListener('click', () => {
        const isCourse = filterType.value === 'course';
        const filterid = parseInt(isCourse ? filterCourse.value : filterCohort.value, 10);

        if (!filterid) {
            return;
        }

        createSession(filterType.value, filterid, orderType.value).then((result) => {
            sessionId = result.sessionid;
            setup.hidden = true;
            progress.hidden = false;
            return loadNext();
        }).catch(Notification.exception);
    });

    endBtn.addEventListener('click', () => {
        sessionId = null;
        setup.hidden = false;
        progress.hidden = true;
        handlers.onNext(null);
    });

    return {
        getSessionId: () => sessionId,
        loadNext,
        updateCurrentItem: (userid, status) => {
            if (!sessionId) {
                return Promise.resolve(null);
            }
            return updateQueueItem(sessionId, userid, status).then(() => loadNext()).catch(Notification.exception);
        },
    };
};

export default {init};
