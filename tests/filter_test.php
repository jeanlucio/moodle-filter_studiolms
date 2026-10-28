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

require_once(__DIR__ . '/fixtures/forum_course_trait.php');

/**
 * Tests for filter_studiolms\text_filter.
 *
 * @covers \filter_studiolms\text_filter
 * @covers \filter_studiolms\widgets
 * @covers \filter_studiolms\widget\forumstats
 * @covers \filter_studiolms\output\bar
 * @covers \filter_studiolms\local\marker_options
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
        $this->assertSame([], widgets::enabled());
        set_config('enable_forumstats', 1, 'filter_studiolms');
        $this->assertSame(['forumstats'], widgets::enabled());

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

        $needles = ['slw-bar-track', 'slw-bar-done', 'slw-bar-todo', 'width:70%', 'width:30%', 'slw-bar-legend', 'slw-bar-value'];
        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $cleaned, $needle);
        }
        $this->assertSame(substr_count($html, 'slw-bar-segment'), substr_count($cleaned, 'slw-bar-segment'));
        $this->assertDoesNotMatchRegularExpression('/<svg|data-|aria-|role=/', $html);
    }

    /**
     * The labels follow the completion rule of the forum, and the bar counts what the rule asks.
     *
     * @return void
     */
    public function test_labels_and_numbers_follow_the_completion_rule(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        $cases = [
            ['completionreplies' => 1, 'completiondiscussions' => 0, 'label' => 'Sent replies', 'done' => 5],
            ['completionreplies' => 0, 'completiondiscussions' => 1, 'label' => 'Started discussions', 'done' => 2],
            ['completionreplies' => 0, 'completiondiscussions' => 0, 'label' => 'Participated', 'done' => 7],
        ];
        foreach ($cases as $case) {
            $setup = $this->make_forum_course(10, [
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionreplies' => $case['completionreplies'],
                'completiondiscussions' => $case['completiondiscussions'],
            ], ['enablecompletion' => 1]);
            $discussion = $this->start_discussion($setup, $setup['teacher']);
            for ($i = 0; $i < 5; $i++) {
                $this->reply_to($discussion, $setup['students'][$i]);
            }
            $this->start_discussion($setup, $setup['students'][5]);
            $this->start_discussion($setup, $setup['students'][6]);
            $this->setUser($setup['teacher']);

            $html = $this->make_filter(\context_module::instance($setup['cm']->id))->filter('[FORUMSTATS]');

            $this->assertStringContainsString($case['label'], $html, $case['label']);
            $this->assertStringContainsString($case['done'] . ' of 10', $html, $case['label']);
        }
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
