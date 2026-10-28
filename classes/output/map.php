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
 * Builds the template context of the course map.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\output;

use filter_studiolms\local\course_sections;

/**
 * Template context of map.mustache.
 */
class map {
    /**
     * Exports the context of the template.
     *
     * @param array[] $steps The steps from course_sections::get_steps().
     * @param string $layout steps or tiles.
     * @return array
     */
    public static function export_context(array $steps, string $layout): array {
        $texts = [
            'done' => get_string('map_done', 'filter_studiolms'),
            'current' => get_string('map_current', 'filter_studiolms'),
        ];
        foreach ($steps as &$step) {
            $step['statetext'] = $texts[$step['state']] ?? '';
            $step['isdone'] = $step['state'] === 'done';
        }
        unset($step);

        // Steps break into rows of six; tiles are one grid.
        $rows = $layout === 'tiles' ? [$steps] : array_chunk($steps, course_sections::PER_ROW);
        return [
            'title' => get_string('map_title', 'filter_studiolms'),
            'layout' => $layout,
            'rows' => array_map(fn(array $row): array => ['steps' => $row], $rows),
        ];
    }
}
