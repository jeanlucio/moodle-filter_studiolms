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
 * The [COURSEMAP] widget: the sections of the course as a path.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\widget;

use filter_studiolms\local\course_sections;
use filter_studiolms\output\map;

/**
 * Draws the sections of the course the text belongs to, marking the current one.
 */
class coursemap implements widget {
    #[\Override]
    public function get_id(): string {
        return 'coursemap';
    }

    #[\Override]
    public function get_marker(): string {
        return 'COURSEMAP';
    }

    #[\Override]
    public function supports_context(\context $context): bool {
        return $this->get_course_id($context) > 0;
    }

    #[\Override]
    public function render(\context $context, array $options): string {
        global $OUTPUT;

        $courseid = $this->get_course_id($context);
        $mode = course_sections::get_mode($options['current'] ?? '');
        $steps = $courseid > 0 ? course_sections::get_steps($courseid, $mode) : null;
        if ($steps === null) {
            return '';
        }

        $layout = course_sections::get_layout(count($steps), $options['style'] ?? '');
        return $OUTPUT->render_from_template('filter_studiolms/map', map::export_context($steps, $layout, $mode));
    }

    /**
     * Returns the course the context belongs to, taken from the context and never from the marker.
     *
     * @param \context $context The context of the text being filtered.
     * @return int The course id, or 0 outside a course (the front page is not a course for this).
     */
    private function get_course_id(\context $context): int {
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext || (int) $coursecontext->instanceid === SITEID) {
            return 0;
        }
        return (int) $coursecontext->instanceid;
    }
}
