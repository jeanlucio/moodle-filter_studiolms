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
 * Reads the sections of a course as the steps of a map.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

/**
 * The sections the current user sees, with the step the format of the course says is the current one.
 */
class course_sections {
    /** @var int Steps in a row of the "steps" layout. */
    public const PER_ROW = 6;

    /** @var int Above this many sections the automatic layout is the compact tiles. */
    public const TILES_ABOVE = 12;

    /**
     * Builds the steps of the map of a course for the current user.
     *
     * Section 0, sections the user cannot see and delegated sections (subsections) are left out. The current step is
     * asked to the format of the course, which for topics is the section the teacher highlighted and for weeks the one
     * that holds today. Steps before it are done, steps after it are upcoming; with no current section, or one the user
     * cannot see, no step is marked.
     *
     * @param int $courseid The course id.
     * @return array[]|null The steps, each with number, name, url and state (done, current, todo or none); null when
     *                      the format has no sections or the user sees none.
     */
    public static function get_steps(int $courseid): ?array {
        $course = get_course($courseid);
        $format = course_get_format($course);
        if (!$format->uses_sections()) {
            return null;
        }

        $currentnumber = 0;
        $included = [];
        foreach (get_fast_modinfo($course)->get_section_info_all() as $number => $section) {
            if ($number < 1) {
                continue;
            }
            if ($format->is_section_current($section)) {
                $currentnumber = (int) $number;
            }
            // The API of delegated sections is newer than the oldest Moodle this plugin supports.
            $delegated = method_exists($section, 'is_delegated') && $section->is_delegated();
            if ($section->uservisible && !$delegated) {
                $included[(int) $number] = $section;
            }
        }
        if (!$included) {
            return null;
        }

        $marked = isset($included[$currentnumber]);
        $steps = [];
        $position = 0;
        foreach ($included as $number => $section) {
            $position++;
            $state = 'none';
            if ($marked) {
                $state = $number < $currentnumber ? 'done' : ($number === $currentnumber ? 'current' : 'todo');
            }
            $url = $format->get_view_url($section);
            $steps[] = [
                'number' => $position,
                'name' => self::get_name($format, $section, $position),
                'url' => $url ? $url->out(false) : '',
                'state' => $state,
            ];
        }
        return $steps;
    }

    /**
     * Returns the name of a section as plain text, since the template escapes it once.
     *
     * @param \core_courseformat\base $format The format of the course.
     * @param \section_info $section The section.
     * @param int $position The position of the step, for a section with no name.
     * @return string
     */
    private static function get_name(\core_courseformat\base $format, \section_info $section, int $position): string {
        $name = html_entity_decode(strip_tags((string) $format->get_section_name($section)), ENT_QUOTES);
        return trim($name) !== '' ? $name : get_string('section') . ' ' . $position;
    }

    /**
     * Picks the layout: the one the marker asks for, or by the number of steps.
     *
     * @param int $count How many steps there are.
     * @param string $style The style option of the marker; anything but steps or tiles means automatic.
     * @return string steps or tiles.
     */
    public static function get_layout(int $count, string $style): string {
        if ($style === 'steps' || $style === 'tiles') {
            return $style;
        }
        return $count > self::TILES_ABOVE ? 'tiles' : 'steps';
    }
}
