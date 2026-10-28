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
 * Tests of the rules of a quiz as the current user faces them.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Tests of filter_studiolms\local\quiz_rules.
 *
 * @covers \filter_studiolms\local\quiz_rules
 * @covers \filter_studiolms\output\rules
 */
final class quiz_rules_test extends \advanced_testcase {
    /**
     * Makes a quiz in a new course with a student, and adds questions through the quiz API.
     *
     * @param array $options Options of the quiz.
     * @param int $truefalse How many true or false questions to add.
     * @param int $descriptions How many description "questions" (text with no grade) to add.
     * @return array The keys course, quiz and student.
     */
    private function make_quiz(array $options, int $truefalse, int $descriptions = 0): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $quiz = $generator->create_module('quiz', ['course' => $course->id] + $options);

        $questions = $generator->get_plugin_generator('core_question');
        $category = $questions->create_question_category(['contextid' => \context_module::instance($quiz->cmid)->id]);
        for ($i = 0; $i < $truefalse; $i++) {
            quiz_add_quiz_question($questions->create_question('truefalse', null, ['category' => $category->id])->id, $quiz);
        }
        for ($i = 0; $i < $descriptions; $i++) {
            quiz_add_quiz_question($questions->create_question('description', null, ['category' => $category->id])->id, $quiz);
        }
        return ['course' => $course, 'quiz' => $quiz, 'student' => $student];
    }

    /**
     * The numbers come from the quiz, and zero means no limit.
     *
     * @return void
     */
    public function test_reads_the_rules_of_the_quiz(): void {
        $this->resetAfterTest();
        $setup = $this->make_quiz(['attempts' => 3, 'timelimit' => HOURSECS, 'timeclose' => 2000000000, 'grade' => 10], 4);

        $rules = quiz_rules::get_rules((int) $setup['quiz']->id, (int) $setup['student']->id);

        $this->assertSame(4, $rules['questions']);
        $this->assertSame(3, $rules['attempts']);
        $this->assertSame(HOURSECS, $rules['timelimit']);
        $this->assertSame(2000000000, $rules['timeclose']);
        $this->assertSame(10.0, $rules['grade']);
        $this->assertSame(0.0, $rules['passgrade']);

        $open = $this->make_quiz(['attempts' => 0, 'timelimit' => 0, 'timeclose' => 0], 2);
        $openrules = quiz_rules::get_rules((int) $open['quiz']->id, (int) $open['student']->id);
        $this->assertSame(0, $openrules['attempts']);
        $this->assertSame(0, $openrules['timelimit']);
        $this->assertSame(0, $openrules['timeclose']);
    }

    /**
     * The numbers follow the quiz when the teacher changes it, and the grade to pass shows only when there is one.
     *
     * @return void
     */
    public function test_follows_the_changes_of_the_quiz_and_the_grade_to_pass(): void {
        global $DB;
        $this->resetAfterTest();
        $setup = $this->make_quiz(['attempts' => 2, 'grade' => 10, 'gradepass' => 6], 2);
        $quizid = (int) $setup['quiz']->id;
        $userid = (int) $setup['student']->id;

        $this->assertSame(6.0, quiz_rules::get_rules($quizid, $userid)['passgrade']);

        $DB->set_field('quiz', 'attempts', 7, ['id' => $quizid]);
        $this->assertSame(7, quiz_rules::get_rules($quizid, $userid)['attempts']);
    }

    /**
     * A student with an override sees their own numbers and a colleague without one sees the quiz default.
     *
     * @return void
     */
    public function test_overrides_apply_only_to_who_has_them(): void {
        $this->resetAfterTest();
        $setup = $this->make_quiz(['attempts' => 1, 'timelimit' => HOURSECS, 'timeclose' => 2000000000], 2);
        $generator = $this->getDataGenerator();
        $colleague = $generator->create_user();
        $generator->enrol_user($colleague->id, $setup['course']->id, 'student');
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizgenerator->create_override([
            'quiz' => $setup['quiz']->id,
            'userid' => $setup['student']->id,
            'attempts' => 5,
            'timelimit' => 2 * HOURSECS,
            'timeclose' => 2100000000,
        ]);
        $group = $generator->create_group(['courseid' => $setup['course']->id]);

        $own = quiz_rules::get_rules((int) $setup['quiz']->id, (int) $setup['student']->id);
        $other = quiz_rules::get_rules((int) $setup['quiz']->id, (int) $colleague->id);

        $this->assertSame([5, 2 * HOURSECS, 2100000000], [$own['attempts'], $own['timelimit'], $own['timeclose']]);
        $this->assertSame([1, HOURSECS, 2000000000], [$other['attempts'], $other['timelimit'], $other['timeclose']]);

        // An override for a group reaches its members and nobody else.
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $colleague->id]);
        $quizgenerator->create_override(['quiz' => $setup['quiz']->id, 'groupid' => $group->id, 'attempts' => 9]);
        $this->assertSame(9, quiz_rules::get_rules((int) $setup['quiz']->id, (int) $colleague->id)['attempts']);
        $this->assertSame(5, quiz_rules::get_rules((int) $setup['quiz']->id, (int) $setup['student']->id)['attempts']);
    }

    /**
     * Questions are the ones of the quiz, without the text-only "description" items.
     *
     * @return void
     */
    public function test_description_items_are_not_questions(): void {
        $this->resetAfterTest();
        $setup = $this->make_quiz([], 3, 2);

        $rules = quiz_rules::get_rules((int) $setup['quiz']->id, (int) $setup['student']->id);

        $this->assertSame(3, $rules['questions']);
    }

    /**
     * Two quizzes of the same course never share their numbers.
     *
     * @return void
     */
    public function test_two_quizzes_do_not_mix(): void {
        $this->resetAfterTest();
        $first = $this->make_quiz(['attempts' => 1], 2);
        $second = $this->getDataGenerator()->create_module('quiz', ['course' => $first['course']->id, 'attempts' => 4]);

        $this->assertSame(1, quiz_rules::get_rules((int) $first['quiz']->id, (int) $first['student']->id)['attempts']);
        $this->assertSame(4, quiz_rules::get_rules((int) $second->id, (int) $first['student']->id)['attempts']);
    }

    /**
     * The cards say "Unlimited" and "No limit" for zero, and the grade and the date only when they exist.
     *
     * @return void
     */
    public function test_cards(): void {
        $this->resetAfterTest();
        $base = ['questions' => 5, 'attempts' => 0, 'timelimit' => 0, 'passgrade' => 0.0, 'grade' => 10.0, 'timeclose' => 0];

        $context = \filter_studiolms\output\rules::export_context($base);
        $this->assertSame(['Questions', 'Attempts', 'Time'], array_column($context['cards'], 'label'));
        $this->assertSame(['5', 'Unlimited', 'No limit'], array_column($context['cards'], 'value'));

        $full = ['attempts' => 2, 'timelimit' => 90 * MINSECS, 'passgrade' => 6.5, 'timeclose' => 2000000000] + $base;
        $context = \filter_studiolms\output\rules::export_context($full);
        $this->assertSame(['Questions', 'Attempts', 'Time', 'Grade to pass', 'Closes'], array_column($context['cards'], 'label'));
        $this->assertSame('2', $context['cards'][1]['value']);
        $this->assertSame(format_time(90 * MINSECS), $context['cards'][2]['value']);
        $this->assertSame('6.5 of 10', $context['cards'][3]['value']);
        $this->assertSame(userdate(2000000000, get_string('strftimedatetimeshort', 'langconfig')), $context['cards'][4]['value']);
    }
}
