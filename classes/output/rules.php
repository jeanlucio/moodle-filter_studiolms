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
 * Builds the template context of the quiz rules.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\output;

/**
 * Template context of rules.mustache.
 */
class rules {
    /**
     * Exports the context of the template.
     *
     * @param array $rules The rules from quiz_rules::get_rules().
     * @return array
     */
    public static function export_context(array $rules): array {
        $attempts = $rules['attempts'] > 0 ? (string) $rules['attempts'] : get_string('quiz_unlimited', 'filter_studiolms');
        $time = $rules['timelimit'] > 0 ? format_time($rules['timelimit']) : get_string('quiz_nolimit', 'filter_studiolms');
        $cards = [
            ['label' => get_string('quiz_questions', 'filter_studiolms'), 'value' => (string) $rules['questions']],
            ['label' => get_string('quiz_attempts', 'filter_studiolms'), 'value' => $attempts],
            ['label' => get_string('quiz_time', 'filter_studiolms'), 'value' => $time],
        ];
        if ($rules['passgrade'] > 0) {
            $cards[] = [
                'label' => get_string('quiz_passgrade', 'filter_studiolms'),
                'value' => get_string('count_of', 'filter_studiolms', [
                    'count' => format_float($rules['passgrade'], 2, true, true),
                    'total' => format_float($rules['grade'], 2, true, true),
                ]),
            ];
        }
        if ($rules['timeclose'] > 0) {
            $cards[] = [
                'label' => get_string('quiz_closes', 'filter_studiolms'),
                'value' => userdate($rules['timeclose'], get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }
        return ['title' => get_string('quiz_title', 'filter_studiolms'), 'cards' => $cards];
    }
}
