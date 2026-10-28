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
 * Tests of the dispatcher of the filter.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/fixtures/forum_course_trait.php');

/**
 * Tests for filter_studiolms\text_filter.
 *
 * @covers \filter_studiolms\text_filter
 * @covers \filter_studiolms\widgets
 * @covers \filter_studiolms\widget\forumstats
 * @covers \filter_studiolms\output\bar
 * @covers \filter_studiolms\local\marker_options
 * @covers \filter_studiolms\widget\coursemap
 * @covers \filter_studiolms\widget\quizinfo
 */
final class filter_test extends \advanced_testcase {
    use forum_course_trait;

    /**
     * Makes the filter for a context.
     *
     * @param \context $context The context of the text.
     * @return text_filter
     */
    private function make_filter(\context $context): text_filter {
        return new text_filter($context, []);
    }

    /**
     * A forum of ten students: five reply, two open a topic, three do nothing (so seven took part). Logs in the first student.
     *
     * @return array The setup of make_forum_course().
     */
    private function make_active_forum(): array {
        $setup = $this->make_forum_course(10);
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        for ($i = 0; $i < 5; $i++) {
            $this->reply_to($discussion, $setup['students'][$i]);
        }
        $this->start_discussion($setup, $setup['students'][5]);
        $this->start_discussion($setup, $setup['students'][6]);
        $this->setUser($setup['students'][7]);
        return $setup;
    }

    /**
     * Text without a marker leaves untouched and costs no query.
     *
     * @return void
     */
    public function test_text_without_marker_is_untouched_and_costs_no_query(): void {
        global $DB;
        $this->resetAfterTest();
        $setup = $this->make_forum_course(1);
        $this->setUser($setup['students'][0]);
        $filter = $this->make_filter(\context_module::instance($setup['cm']->id));

        $reads = $DB->perf_get_reads();
        $this->assertSame('<p>Hello</p>', $filter->filter('<p>Hello</p>'));
        $this->assertSame('<p>[OTHER] [forumstats]</p>', $filter->filter('<p>[OTHER] [forumstats]</p>'));
        $this->assertSame($reads, $DB->perf_get_reads());
    }

    /**
     * The marker becomes the bar with the three percentages, in the forum it belongs to.
     *
     * @return void
     */
    public function test_marker_becomes_the_bar_in_a_forum(): void {
        $this->resetAfterTest();
        $setup = $this->make_active_forum();
        $filter = $this->make_filter(\context_module::instance($setup['cm']->id));

        $html = $filter->filter('<p>Before</p>[FORUMSTATS]<p>After</p>');

        $this->assertStringContainsString('<p>Before</p>', $html);
        $this->assertStringContainsString('<p>After</p>', $html);
        $this->assertStringNotContainsString('[FORUMSTATS]', $html);
        $this->assertStringContainsString('slw-bar', $html);
        $this->assertStringContainsString('70%', $html);
        $this->assertStringContainsString('30%', $html);
        $this->assertStringContainsString('width: 70.00%', $html);
        // The counts are shown to students too.
        $this->assertStringContainsString('7 of 10', $html);
        $this->assertStringContainsString('3 of 10', $html);
    }

    /**
     * The span the Studio writes around the marker is replaced whole, and an unknown option is ignored.
     *
     * @return void
     */
    public function test_wrapper_is_replaced_whole_and_unknown_option_is_ignored(): void {
        $this->resetAfterTest();
        $setup = $this->make_active_forum();
        $filter = $this->make_filter(\context_module::instance($setup['cm']->id));

        $html = $filter->filter(
            '<p><span class="slms-widget-marker">[FORUMSTATS color=red "x"]</span></p>'
            . '<p><span class="slms-widget-marker">[FORUMSTATS bogus="a b" other=ok]</span></p>'
        );

        $this->assertStringNotContainsString('slms-widget-marker', $html);
        $this->assertStringNotContainsString('FORUMSTATS', $html);
        $this->assertSame(2, substr_count($html, 'slw-bar-title'));
    }

    /**
     * Outside a forum, with the widget off, for a visitor and in a plain string, the marker just disappears.
     *
     * @return void
     */
    public function test_marker_is_removed_where_it_does_not_apply(): void {
        $this->resetAfterTest();
        $setup = $this->make_active_forum();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $setup['course']->id]);
        $text = '<p>A [FORUMSTATS] B</p>';

        $outside = $this->make_filter(\context_module::instance($page->cmid))->filter($text);
        $this->assertSame('<p>A  B</p>', $outside);
        $course = $this->make_filter(\context_course::instance($setup['course']->id))->filter($text);
        $this->assertSame('<p>A  B</p>', $course);

        $forumfilter = $this->make_filter(\context_module::instance($setup['cm']->id));
        $this->assertStringContainsString('slw-bar', $forumfilter->filter($text));

        set_config('enable_forumstats', 0, 'filter_studiolms');
        $this->assertSame('<p>A  B</p>', $forumfilter->filter($text));
        $this->assertSame(['coursemap', 'quizinfo'], widgets::enabled());
        set_config('enable_forumstats', 1, 'filter_studiolms');
        $this->assertSame(['forumstats', 'coursemap', 'quizinfo'], widgets::enabled());

        $this->setGuestUser();
        $this->assertSame('<p>A  B</p>', $forumfilter->filter($text));
        $this->setUser($setup['students'][7]);

        $this->assertSame('Title ', $forumfilter->filter_stage_string('Title [FORUMSTATS]', []));
    }

    /**
     * Below the minimum class size students see nothing, and who manages the course sees the bar and a warning.
     *
     * @return void
     */
    public function test_small_class_is_hidden_from_students_but_not_from_teachers(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(3);
        $this->reply_to($this->start_discussion($setup, $setup['teacher']), $setup['students'][0]);
        $filter = $this->make_filter(\context_module::instance($setup['cm']->id));

        $this->setUser($setup['students'][1]);
        $this->assertSame('', $filter->filter('[FORUMSTATS]'));

        $this->setUser($setup['teacher']);
        $html = $filter->filter('[FORUMSTATS]');
        $this->assertStringContainsString('slw-bar', $html);
        $this->assertStringContainsString('1 of 3', $html);
        $this->assertStringContainsString(get_string('hiddenforstudents', 'filter_studiolms', 5), $html);
    }

    /**
     * Who manages the course sees the same bar, and no warning when the class is big enough.
     *
     * @return void
     */
    public function test_teacher_sees_the_bar_without_the_warning(): void {
        $this->resetAfterTest();
        $setup = $this->make_active_forum();
        $this->setUser($setup['teacher']);

        $html = $this->make_filter(\context_module::instance($setup['cm']->id))->filter('[FORUMSTATS]');

        $this->assertStringContainsString('7 of 10', $html);
        $this->assertStringNotContainsString(get_string('hiddenforstudents', 'filter_studiolms', 5), $html);
    }

    /**
     * The output keeps every class and width after the HTML cleaner, with no SVG and no data or aria attributes.
     *
     * @return void
     */
    public function test_output_survives_the_html_cleaner(): void {
        $this->resetAfterTest();
        $setup = $this->make_active_forum();
        $html = $this->make_filter(\context_module::instance($setup['cm']->id))->filter('[FORUMSTATS]');

        $cleaned = clean_text($html, FORMAT_HTML);

        $needles = [
            'slw-bar-track', 'slw-bar-done', 'slw-bar-todo', 'width:70%', 'width:30%', 'slw-bar-legend', 'slw-bar-value',
            'slw-bar-note',
        ];
        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $cleaned, $needle);
        }
        $this->assertSame(substr_count($html, 'slw-bar-segment'), substr_count($cleaned, 'slw-bar-segment'));
        $this->assertDoesNotMatchRegularExpression('/<svg|data-|aria-|role=/', $html);
    }

    /**
     * The bar says what it counts, whatever the completion rules of the forum are.
     *
     * @return void
     */
    public function test_the_bar_counts_any_message_and_says_so(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        $setup = $this->make_forum_course(
            10,
            ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionreplies' => 3],
            ['enablecompletion' => 1]
        );
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $this->reply_to($discussion, $setup['students'][0]);
        $this->start_discussion($setup, $setup['students'][1]);
        $this->setUser($setup['teacher']);

        $html = $this->make_filter(\context_module::instance($setup['cm']->id))->filter('[FORUMSTATS]');

        $this->assertStringContainsString('Participated', $html);
        $this->assertStringContainsString('2 of 10', $html);
        $this->assertStringContainsString(get_string('barnote', 'filter_studiolms'), $html);
    }

    /**
     * The course map in a page of the course: one step per visible section, and a hidden one does not leak to students.
     *
     * @return void
     */
    public function test_course_map_shows_the_sections_the_viewer_sees(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 4, 'format' => 'topics']);
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $info = get_fast_modinfo($course);
        course_update_section($course, $info->get_section_info(2), ['name' => 'Secret & hidden']);
        course_update_section($course, $info->get_section_info(1), ['name' => 'A & B > 3']);
        set_section_visible($course->id, 2, 0);
        course_set_marker($course->id, 3);
        $page = $generator->create_module('page', ['course' => $course->id]);
        $filter = $this->make_filter(\context_module::instance($page->cmid));

        $this->setUser($student);
        $html = $filter->filter('<p><span class="slms-widget-marker">[COURSEMAP]</span></p>');
        $this->assertStringNotContainsString('slms-widget-marker', $html);
        $this->assertSame(3, substr_count($html, 'slw-map-step slw-map-'));
        $this->assertStringNotContainsString('Secret', $html);
        $this->assertStringContainsString('A &amp; B &gt; 3', $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringContainsString('slw-map-current', $html);
        $this->assertStringContainsString('Current step', $html);
        $this->assertStringContainsString('Done', $html);

        $this->setUser($teacher);
        $teacherhtml = $filter->filter('[COURSEMAP]');
        $this->assertSame(4, substr_count($teacherhtml, 'slw-map-step slw-map-'));
        $this->assertStringContainsString('Secret &amp; hidden', $teacherhtml);

        $tiles = $filter->filter('[COURSEMAP style=tiles]');
        $this->assertStringContainsString('slw-map-tiles', $tiles);
        $this->assertStringContainsString('slw-map-steps', $filter->filter('[COURSEMAP style=bogus]'));
    }

    /**
     * The course map survives the HTML cleaner, with its links, and is removed outside a course.
     *
     * @return void
     */
    public function test_course_map_survives_the_cleaner_and_needs_a_course(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['numsections' => 3, 'format' => 'topics']);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        course_set_marker($course->id, 2);
        $this->setUser($student);

        $html = $this->make_filter(\context_course::instance($course->id))->filter('[COURSEMAP]');
        $cleaned = clean_text($html, FORMAT_HTML);

        foreach (['slw-map-row', 'slw-map-done', 'slw-map-current', 'slw-map-todo', 'slw-map-state', 'href="'] as $needle) {
            $this->assertStringContainsString($needle, $cleaned, $needle);
        }
        $this->assertSame(substr_count($html, 'slw-map-step'), substr_count($cleaned, 'slw-map-step'));
        $this->assertDoesNotMatchRegularExpression('/<svg|data-|aria-|role=/', $html);

        $this->assertSame('', $this->make_filter(\context_system::instance())->filter('[COURSEMAP]'));
        $this->assertSame('', $this->make_filter(\context_course::instance(SITEID))->filter('[COURSEMAP]'));
        set_config('enable_coursemap', 0, 'filter_studiolms');
        $this->assertSame('', $this->make_filter(\context_course::instance($course->id))->filter('[COURSEMAP]'));
    }

    /**
     * The quiz rules in the description of a quiz: real numbers, the student's own override, and nothing elsewhere.
     *
     * @return void
     */
    public function test_quiz_rules_show_the_numbers_of_the_viewer(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $colleague = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($colleague->id, $course->id, 'student');
        $quiz = $generator->create_module('quiz', ['course' => $course->id, 'attempts' => 0, 'timelimit' => 0]);
        $generator->get_plugin_generator('mod_quiz')->create_override([
            'quiz' => $quiz->id,
            'userid' => $student->id,
            'attempts' => 4,
            'timelimit' => 30 * MINSECS,
        ]);
        $filter = $this->make_filter(\context_module::instance($quiz->cmid));

        $this->setUser($colleague);
        $plain = $filter->filter('<p><span class="slms-widget-marker">[QUIZINFO]</span></p>');
        $this->assertStringNotContainsString('slms-widget-marker', $plain);
        $this->assertStringContainsString('slw-quiz', $plain);
        $this->assertStringContainsString('Unlimited', $plain);
        $this->assertStringContainsString('No limit', $plain);

        $this->setUser($student);
        $own = $filter->filter('[QUIZINFO]');
        $this->assertStringContainsString('>4<', $own);
        $this->assertStringContainsString(format_time(30 * MINSECS), $own);
        $this->assertStringNotContainsString('Unlimited', $own);

        $cleaned = clean_text($own, FORMAT_HTML);
        $this->assertSame(substr_count($own, 'slw-quiz-card'), substr_count($cleaned, 'slw-quiz-card'));
        $this->assertDoesNotMatchRegularExpression('/<svg|data-|aria-|role=/', $own);

        // Anywhere but a quiz, or with the widget off, the marker is removed.
        $page = $generator->create_module('page', ['course' => $course->id]);
        $this->assertSame('', $this->make_filter(\context_module::instance($page->cmid))->filter('[QUIZINFO]'));
        $this->assertSame('', $this->make_filter(\context_course::instance($course->id))->filter('[QUIZINFO]'));
        set_config('enable_quizinfo', 0, 'filter_studiolms');
        $this->assertSame('', $filter->filter('[QUIZINFO]'));
        $this->assertSame(['forumstats', 'coursemap'], widgets::enabled());
    }

    /**
     * The options of a marker only keep what fits the syntax.
     *
     * @return void
     */
    public function test_marker_options_parser(): void {
        $this->assertSame([], local\marker_options::parse(''));
        $this->assertSame(['style' => 'tiles'], local\marker_options::parse(' style=tiles'));
        $this->assertSame(['label' => 'a b', 'n' => 'x-1'], local\marker_options::parse(' label="a b" n=x-1'));
        $this->assertSame([], local\marker_options::parse(' Style=Tiles bad=A$ 9=x'));
    }
}
