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
 * Student search box: debounced AJAX search, results rendering, and
 * fetching full detail for whichever result the operator picks.
 *
 * This module only knows how to find a student - it has no notion of
 * cameras, previews or saving. capture.js owns that orchestration and
 * supplies an onSelect callback.
 *
 * @module     local_profilephoto/search
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';

const SELECTORS = {
    SEARCH_INPUT: '#lpp-search-input',
    RESULTS: '[data-region="lpp-search-results"]',
};

const SEARCH_DEBOUNCE_MS = 250;
const MIN_QUERY_LENGTH = 2;

let debounceTimer = null;

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
 * Render the search results list.
 *
 * @param {HTMLElement} target
 * @param {Array} users
 * @return {Promise}
 */
const renderResults = (target, users) => Templates.renderForPromise('local_profilephoto/search_results', {
    hasusers: users.length > 0,
    users,
}).then(({html, js}) => {
    Templates.replaceNodeContents(target, html, js);
    return null;
});

/**
 * Initialise the search box.
 *
 * @param {Function} onSelect called with the full user object once the operator picks a result.
 * @return {Object} {focus(), clear()}
 */
export const init = (onSelect) => {
    const input = document.querySelector(SELECTORS.SEARCH_INPUT);
    const resultsRegion = document.querySelector(SELECTORS.RESULTS);

    const clear = () => {
        resultsRegion.innerHTML = '';
        input.value = '';
    };

    input.addEventListener('input', () => {
        const query = input.value.trim();
        window.clearTimeout(debounceTimer);

        if (query.length < MIN_QUERY_LENGTH) {
            resultsRegion.innerHTML = '';
            return;
        }

        debounceTimer = window.setTimeout(() => {
            searchUsers(query).then((users) => renderResults(resultsRegion, users)).catch(Notification.exception);
        }, SEARCH_DEBOUNCE_MS);
    });

    resultsRegion.addEventListener('click', (event) => {
        const button = event.target.closest('[data-userid]');
        if (!button) {
            return;
        }

        const userid = parseInt(button.dataset.userid, 10);
        getUser(userid).then((user) => {
            clear();
            onSelect(user);
            return null;
        }).catch(Notification.exception);
    });

    return {
        focus: () => input.focus(),
        clear,
    };
};

export default {init};
