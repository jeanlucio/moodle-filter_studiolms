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
 * Builds the template context of the participation bar.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\output;

use filter_studiolms\local\participation;

/**
 * Template context of bar.mustache.
 */
class bar {
    /**
     * Exports the context of the template.
     *
     * @param int[] $counts The keys total, replied and startedonly.
     * @param bool $showcounts Whether to show "n of N" beside each percentage (people who manage the course).
     * @param int $hiddenbelow When above 0, the minimum class size that hides the bar from students.
     * @return array
     */
    public static function export_context(array $counts, bool $showcounts, int $hiddenbelow = 0): array {
        $total = $counts['total'];
        $notparticipated = max(0, $total - $counts['replied'] - $counts['startedonly']);
        $numbers = [
            'replied' => $counts['replied'],
            'startedonly' => $counts['startedonly'],
            'notparticipated' => $notparticipated,
        ];
        $percentages = participation::percentages($counts['replied'], $counts['startedonly'], $total);

        $segments = [];
        foreach ($numbers as $key => $count) {
            $segments[] = [
                'key' => $key,
                'label' => get_string($key, 'filter_studiolms'),
                'percent' => $percentages[$key],
                'width' => number_format($count * 100 / $total, 2, '.', ''),
                'hasbar' => $count > 0,
                'counttext' => $showcounts
                    ? get_string('count_of', 'filter_studiolms', ['count' => $count, 'total' => $total])
                    : '',
            ];
        }

        return [
            'title' => get_string('participation', 'filter_studiolms'),
            'segments' => $segments,
            'notice' => $hiddenbelow > 0 ? get_string('hiddenforstudents', 'filter_studiolms', $hiddenbelow) : '',
        ];
    }
}
