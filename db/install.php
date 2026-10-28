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
 * Post install hook of filter_studiolms.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Turns the filter on in the whole site on a fresh install.
 *
 * Without this the filter would install in the default "Off, but available" state and the markers written in a course
 * would stay as text until an administrator turned it on in Site administration > Plugins > Filters > Manage filters.
 * The cost of having it on is one search for an opening bracket in each text; a text without a marker is not touched.
 * A site that already has the plugin keeps the state it has: this only runs on the first install.
 *
 * @return void
 */
function xmldb_filter_studiolms_install(): void {
    global $CFG;
    require_once($CFG->libdir . '/filterlib.php');

    filter_set_global_state('studiolms', TEXTFILTER_ON);
}
