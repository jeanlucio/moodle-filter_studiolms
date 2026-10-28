<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Callbacks of filter_studiolms that core finds only in this file.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_studiolms\widgets;

/**
 * Shows, just below the description editor of a forum, how to write the participation marker.
 *
 * @param moodleform_mod $formwrapper The form wrapper of the activity.
 * @param MoodleQuickForm $mform The form being built.
 * @return void
 */
function filter_studiolms_coursemodule_standard_elements($formwrapper, $mform): void {
    global $OUTPUT;

    if (($formwrapper->get_current()->modulename ?? '') !== 'forum' || !widgets::is_enabled('forumstats')) {
        return;
    }
    if (!$mform->elementExists('showdescription')) {
        return;
    }

    $filters = filter_get_active_in_context($formwrapper->get_context());
    $text = get_string('formhelp', 'filter_studiolms');
    $html = $OUTPUT->render_from_template('filter_studiolms/form_help', [
        'text' => $text,
        'warning' => isset($filters['studiolms']) ? '' : get_string('formhelpinactive', 'filter_studiolms'),
    ]);
    $element = $mform->createElement('static', 'studiolmsforumstatshelp', get_string('formlabel', 'filter_studiolms'), $html);
    $mform->insertElementBefore($element, 'showdescription');
}
