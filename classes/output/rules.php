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
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');

        $attempts = $rules['attempts'] > 0 ? (string) $rules['attempts'] : get_string('quiz_unlimited', 'filter_studiolms');
        $time = $rules['timelimit'] > 0 ? format_time($rules['timelimit']) : get_string('quiz_nolimit', 'filter_studiolms');
        $cards = [
            ['icon' => 'fa-circle-question', 'label' => get_string('quiz_questions', 'filter_studiolms'),
                'value' => (string) $rules['questions']],
            ['icon' => 'fa-clock', 'label' => get_string('quiz_time', 'filter_studiolms'), 'value' => $time],
            ['icon' => 'fa-rotate-right', 'label' => get_string('quiz_attempts', 'filter_studiolms'), 'value' => $attempts],
        ];
        if ($rules['passgrade'] > 0) {
            $cards[] = [
                'icon' => 'fa-medal',
                'label' => get_string('quiz_passgrade', 'filter_studiolms'),
                'value' => get_string('count_of', 'filter_studiolms', [
                    'count' => format_float($rules['passgrade'], 2, true, true),
                    'total' => format_float($rules['grade'], 2, true, true),
                ]),
            ];
        }
        // How the attempts make the grade only matters when there can be more than one.
        $methods = quiz_get_grading_options();
        if ($rules['attempts'] !== 1 && isset($methods[$rules['grademethod']])) {
            $cards[] = [
                'icon' => 'fa-scale-balanced',
                'label' => get_string('quiz_grademethod', 'filter_studiolms'),
                'value' => $methods[$rules['grademethod']],
            ];
        }
        if ($rules['timeclose'] > 0) {
            $cards[] = [
                'icon' => 'fa-calendar-days',
                'label' => get_string('quiz_closes', 'filter_studiolms'),
                'value' => userdate($rules['timeclose'], get_string('strftimedatetimeshort', 'langconfig')),
            ];
        }
        return ['title' => get_string('quiz_title', 'filter_studiolms'), 'cards' => $cards];
    }
}
