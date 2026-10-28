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
 * Hides and highlights sections of a course for the tests, with the API of each Moodle version.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

use core_courseformat\local\sectionactions;

/**
 * Course actions that moved to the format actions in Moodle 5.2 and are deprecated as functions since then.
 */
trait course_actions_trait {
    /**
     * Hides a section.
     *
     * @param \stdClass $course The course.
     * @param int $sectionnum The section number.
     * @return void
     */
    protected function hide_section(\stdClass $course, int $sectionnum): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        if (method_exists(sectionactions::class, 'set_visibility')) {
            $section = get_fast_modinfo($course)->get_section_info($sectionnum);
            (new sectionactions($course))->set_visibility($section, false);
            return;
        }
        set_section_visible($course->id, $sectionnum, 0);
    }

    /**
     * Highlights a section as the current one.
     *
     * @param \stdClass $course The course.
     * @param int $sectionnum The section number.
     * @return void
     */
    protected function highlight_section(\stdClass $course, int $sectionnum): void {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        if (method_exists(sectionactions::class, 'set_marker')) {
            $section = get_fast_modinfo($course)->get_section_info($sectionnum);
            (new sectionactions($course))->set_marker($section, true);
            return;
        }
        course_set_marker($course->id, $sectionnum);
    }
}
