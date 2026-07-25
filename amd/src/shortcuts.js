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
 * Keyboard shortcut binding for the capture screen (encargo section 6).
 *
 * Only binds keys that already do something useful in the current
 * entrega (Space, Enter, R, B, Esc). ArrowLeft/ArrowRight/S are reserved
 * for queue navigation, introduced once a real queue exists (Entrega 3),
 * to avoid shipping keys that silently do nothing.
 *
 * A USB capture trigger that emulates the Space or Enter key works
 * automatically through this same binding, with no special-casing
 * needed, as long as the capture screen (not a text field) has focus.
 *
 * @module     local_profilephoto/shortcuts
 * @copyright  2026 Centre Educatiu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const isTypingTarget = (target) => {
    if (!target) {
        return false;
    }
    const tag = target.tagName ? target.tagName.toLowerCase() : '';
    return tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable === true;
};

/**
 * Bind a map of key => handler to the document.
 *
 * @param {Object} handlers map from KeyboardEvent.key (or ' ' for space) to a function(event).
 * @param {Boolean} enabled whether shortcuts should be active initially.
 * @return {Object} {setEnabled(bool), destroy()}
 */
export const bind = (handlers, enabled = true) => {
    let active = enabled;

    const normalise = (key) => (key.length === 1 ? key.toLowerCase() : key);
    const normalisedHandlers = {};
    Object.keys(handlers).forEach((key) => {
        normalisedHandlers[normalise(key)] = handlers[key];
    });

    const listener = (event) => {
        if (!active || isTypingTarget(event.target)) {
            return;
        }

        const handler = normalisedHandlers[normalise(event.key)];
        if (handler) {
            event.preventDefault();
            handler(event);
        }
    };

    document.addEventListener('keydown', listener);

    return {
        setEnabled: (value) => {
            active = value;
        },
        destroy: () => document.removeEventListener('keydown', listener),
    };
};

export default {bind};
