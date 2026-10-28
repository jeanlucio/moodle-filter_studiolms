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
 * Reads the rules of a quiz as the current user faces them.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

use mod_quiz\quiz_settings;

/**
 * The rules of a quiz for the person looking at it, with their overrides applied.
 */
class quiz_rules {
    /**
     * Returns the rules of a quiz for a user.
     *
     * The settings come from the quiz itself with the overrides of the user and of their groups applied, as the quiz
     * does when the user attempts it, so a student with more time sees their own time and no one else's.
     * The number of questions is what the quiz calls a question: the structure also holds the "description" items (text
     * with no grade), which a student never answers, so they are left out.
     *
     * @param int $quizid The quiz id.
     * @param int $userid The user the rules are for.
     * @return array The keys questions, attempts and timelimit (0 for no limit), passgrade and grade (0 when there is
     *               no grade to pass), and timeclose (0 when it never closes).
     */
    public static function get_rules(int $quizid, int $userid): array {
        global $DB;

        $quizobj = quiz_settings::create($quizid, $userid);
        $quiz = $quizobj->get_quiz();
        $passgrade = (float) $DB->get_field(
            'grade_items',
            'gradepass',
            ['itemtype' => 'mod', 'itemmodule' => 'quiz', 'iteminstance' => $quizid, 'itemnumber' => 0],
            IGNORE_MISSING
        );

        return [
            'questions' => self::count_questions($quizobj->get_structure()),
            'attempts' => (int) $quiz->attempts,
            'timelimit' => (int) $quiz->timelimit,
            'passgrade' => $passgrade,
            'grade' => (float) $quiz->grade,
            'timeclose' => (int) $quiz->timeclose,
        ];
    }

    /**
     * Counts the real questions of the structure of a quiz.
     *
     * @param \mod_quiz\structure $structure The structure of the quiz.
     * @return int
     */
    private static function count_questions(\mod_quiz\structure $structure): int {
        $count = 0;
        foreach ($structure->get_slots() as $slot) {
            if ($structure->is_real_question($slot->slot)) {
                $count++;
            }
        }
        return $count;
    }
}
