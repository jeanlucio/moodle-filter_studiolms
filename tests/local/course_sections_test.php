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
 * Tests of the steps of the course map.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

use filter_studiolms\course_actions_trait;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/../fixtures/course_actions_trait.php');

/**
 * Tests of filter_studiolms\local\course_sections.
 *
 * @covers \filter_studiolms\local\course_sections
 * @covers \filter_studiolms\output\map
 */
final class course_sections_test extends \advanced_testcase {
    use course_actions_trait;

    /**
     * Makes a course with sections, a student and a teacher enrolled by role.
     *
     * @param int $sections How many sections besides the general one.
     * @param array $options Extra options of the course.
     * @return array The keys course, student and teacher.
     */
    private function make_course(int $sections, array $options = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course($options + ['numsections' => $sections, 'format' => 'topics']);
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        return ['course' => $course, 'student' => $student, 'teacher' => $teacher];
    }

    /**
     * The steps are the sections after the general one, numbered in sequence, each with its name and link.
     *
     * @return void
     */
    public function test_one_step_per_section_without_the_general_one(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(4);
        $this->setUser($setup['student']);

        $steps = course_sections::get_steps((int) $setup['course']->id);

        $this->assertCount(4, $steps);
        $this->assertSame([1, 2, 3, 4], array_column($steps, 'number'));
        $this->assertSame(['none', 'none', 'none', 'none'], array_column($steps, 'state'));
        $this->assertStringContainsString('/course/', $steps[0]['url']);
        $this->assertNotSame($steps[0]['url'], $steps[1]['url']);
    }

    /**
     * With all the sections on one page every step still has its own address, with the anchor of its section.
     *
     * Regression guard: the topics format of Moodle 4.5 returns the address of the whole page for every section.
     *
     * @return void
     */
    public function test_single_page_course_gives_each_step_its_own_anchor(): void {
        global $CFG;
        $this->resetAfterTest();
        // A site installed in a folder, as the one of the CI, so the path of the address does not start at the course.
        $CFG->wwwroot .= '/moodle';
        $setup = $this->make_course(3, ['coursedisplay' => COURSE_DISPLAY_SINGLEPAGE]);
        $this->setUser($setup['student']);

        $steps = course_sections::get_steps((int) $setup['course']->id);

        $urls = array_column($steps, 'url');
        $this->assertCount(3, array_unique($urls));
        foreach ($urls as $position => $url) {
            $this->assertStringContainsString('#section-' . ($position + 1), $url);
        }
    }

    /**
     * A hidden section is left out for the student and kept for the teacher; numbering stays in sequence.
     *
     * @return void
     */
    public function test_hidden_sections_follow_the_visibility_of_the_viewer(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(4);
        $this->hide_section($setup['course'], 2);

        $this->setUser($setup['student']);
        $steps = course_sections::get_steps((int) $setup['course']->id);
        $this->assertCount(3, $steps);
        $this->assertSame([1, 2, 3], array_column($steps, 'number'));

        $this->setUser($setup['teacher']);
        $this->assertCount(4, course_sections::get_steps((int) $setup['course']->id));
    }

    /**
     * The section highlighted by the teacher is the current step; the ones before are done and the ones after upcoming.
     *
     * @return void
     */
    public function test_highlighted_section_marks_the_steps(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(4);
        $this->highlight_section($setup['course'], 2);
        $this->setUser($setup['student']);

        $steps = course_sections::get_steps((int) $setup['course']->id);

        $this->assertSame(['done', 'current', 'todo', 'todo'], array_column($steps, 'state'));
    }

    /**
     * A highlighted section the viewer cannot see marks nobody for them, and does for who can see it.
     *
     * @return void
     */
    public function test_hidden_current_section_marks_nobody_for_the_student(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(4);
        $this->highlight_section($setup['course'], 3);
        $this->hide_section($setup['course'], 3);

        $this->setUser($setup['student']);
        $this->assertSame(['none', 'none', 'none'], array_column(course_sections::get_steps((int) $setup['course']->id), 'state'));

        $this->setUser($setup['teacher']);
        $this->assertSame(
            ['done', 'done', 'current', 'todo'],
            array_column(course_sections::get_steps((int) $setup['course']->id), 'state')
        );
    }

    /**
     * In the weekly format the current step is the week that holds today, with no marking by the teacher.
     *
     * @return void
     */
    public function test_weekly_format_marks_the_week_of_today(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(4, ['format' => 'weeks', 'startdate' => time() - 8 * DAYSECS]);
        $this->setUser($setup['student']);

        $steps = course_sections::get_steps((int) $setup['course']->id);

        $this->assertSame(['done', 'current', 'todo', 'todo'], array_column($steps, 'state'));
    }

    /**
     * A format with no sections has no map.
     *
     * @return void
     */
    public function test_format_without_sections_has_no_map(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(0, ['format' => 'social']);
        $this->setUser($setup['student']);

        $this->assertNull(course_sections::get_steps((int) $setup['course']->id));
    }

    /**
     * A delegated section (a subsection) is not a step. Only Moodle 5.0 and later has them.
     *
     * @return void
     */
    public function test_delegated_sections_are_left_out(): void {
        $this->resetAfterTest();
        if (\core_component::get_component_directory('mod_subsection') === null) {
            $this->markTestSkipped('This Moodle has no subsections.');
        }
        $setup = $this->make_course(2);
        $this->getDataGenerator()->create_module('subsection', ['course' => $setup['course']->id, 'section' => 1]);
        $this->setUser($setup['student']);

        $this->assertCount(2, course_sections::get_steps((int) $setup['course']->id));
    }

    /**
     * The name is plain text, so the template escapes it once, whatever formatstringstriptags says.
     *
     * @return void
     */
    public function test_section_name_is_plain_text(): void {
        global $CFG;
        $this->resetAfterTest();
        $setup = $this->make_course(2);
        $section = get_fast_modinfo($setup['course'])->get_section_info(1);
        course_update_section($setup['course'], $section, ['name' => 'A & B > 3']);
        $this->setUser($setup['student']);

        foreach ([1, 0] as $striptags) {
            $CFG->formatstringstriptags = $striptags;
            \core\di::reset_container();
            $steps = course_sections::get_steps((int) $setup['course']->id);
            $this->assertSame('A & B > 3', $steps[0]['name'], "formatstringstriptags $striptags");
        }
    }

    /**
     * The state of each step of the map of a course for the current user.
     *
     * @param int $courseid The course id.
     * @param string $mode date or progress.
     * @return string[]
     */
    private function states(int $courseid, string $mode = 'date'): array {
        return array_column(course_sections::get_steps($courseid, $mode), 'state');
    }

    /**
     * Makes a course with completion and sections holding activities with and without a completion rule.
     *
     * Section 1 has two tracked pages, section 2 one tracked page, section 3 a page with no rule and section 4 one
     * tracked page.
     *
     * @return array The keys course, student, other (a second student), teacher and cms (the tracked ones by name).
     */
    private function make_progress_course(): array {
        global $CFG;
        $CFG->enablecompletion = true;
        $setup = $this->make_course(4, ['enablecompletion' => 1]);
        $generator = $this->getDataGenerator();
        $setup['other'] = $generator->create_user();
        $generator->enrol_user($setup['other']->id, $setup['course']->id, 'student');
        $tracked = ['completion' => COMPLETION_TRACKING_MANUAL];
        $course = $setup['course']->id;
        $setup['cms'] = [
            'a' => $generator->create_module('page', ['course' => $course, 'section' => 1] + $tracked),
            'b' => $generator->create_module('page', ['course' => $course, 'section' => 1] + $tracked),
            'c' => $generator->create_module('page', ['course' => $course, 'section' => 2] + $tracked),
            'd' => $generator->create_module('page', ['course' => $course, 'section' => 3]),
            'e' => $generator->create_module('page', ['course' => $course, 'section' => 4] + $tracked),
        ];
        return $setup;
    }

    /**
     * Completes an activity for a user through the completion API, as the tick of the page does.
     *
     * @param array $setup The result of make_progress_course().
     * @param string $key The activity.
     * @param \stdClass $user The user.
     * @return void
     */
    private function complete(array $setup, string $key, \stdClass $user): void {
        $this->setUser($user);
        $cm = get_fast_modinfo($setup['course']->id)->get_cm($setup['cms'][$key]->cmid);
        (new \completion_info($setup['course']))->update_state($cm, COMPLETION_COMPLETE, $user->id);
    }

    /**
     * The progress mode follows the activities the student finished: a section is done when all of its tracked
     * activities are, the first with something left is the current step, and a section with no rule is not marked.
     *
     * @return void
     */
    public function test_progress_mode_follows_the_completion_of_the_student(): void {
        $this->resetAfterTest();
        $setup = $this->make_progress_course();
        $courseid = (int) $setup['course']->id;

        $this->setUser($setup['student']);
        $this->assertSame(['current', 'todo', 'none', 'todo'], $this->states($courseid, 'progress'));

        $this->complete($setup, 'a', $setup['student']);
        $this->assertSame(['current', 'todo', 'none', 'todo'], $this->states($courseid, 'progress'));

        $this->complete($setup, 'b', $setup['student']);
        $this->assertSame(['done', 'current', 'none', 'todo'], $this->states($courseid, 'progress'));

        $this->complete($setup, 'c', $setup['student']);
        $this->assertSame(['done', 'done', 'none', 'current'], $this->states($courseid, 'progress'));

        $this->complete($setup, 'e', $setup['student']);
        $this->assertSame(['done', 'done', 'none', 'done'], $this->states($courseid, 'progress'));
    }

    /**
     * Each student sees only their own progress, and the date mode ignores completion altogether.
     *
     * @return void
     */
    public function test_progress_is_the_viewers_own(): void {
        $this->resetAfterTest();
        $setup = $this->make_progress_course();
        $courseid = (int) $setup['course']->id;
        $this->complete($setup, 'a', $setup['student']);
        $this->complete($setup, 'b', $setup['student']);

        $this->setUser($setup['other']);
        $this->assertSame(['current', 'todo', 'none', 'todo'], $this->states($courseid, 'progress'));
        $this->assertSame(['none', 'none', 'none', 'none'], $this->states($courseid, 'date'));
        $this->assertSame(['none', 'none', 'none', 'none'], $this->states($courseid));

        $this->setUser($setup['student']);
        $this->assertSame('done', course_sections::get_steps($courseid, 'progress')[0]['state']);
    }

    /**
     * A hidden activity does not count, and with completion off in the course nothing is marked.
     *
     * @return void
     */
    public function test_progress_ignores_hidden_activities_and_needs_completion_enabled(): void {
        global $CFG;
        $this->resetAfterTest();
        $setup = $this->make_progress_course();
        $courseid = (int) $setup['course']->id;
        $this->complete($setup, 'a', $setup['student']);
        set_coursemodule_visible($setup['cms']['b']->cmid, 0);
        rebuild_course_cache($courseid, true);

        $this->setUser($setup['student']);
        $this->assertSame('done', course_sections::get_steps($courseid, 'progress')[0]['state']);

        $CFG->enablecompletion = false;
        $this->assertSame(['none', 'none', 'none', 'none'], $this->states($courseid, 'progress'));
    }

    /**
     * Makes a course with completion whose section 1 holds a tracked page of its own and a subsection that holds
     * another, and whose section 2 holds a third.
     *
     * @param bool $ownpage False to leave the section 1 with the subsection alone.
     * @return array The keys course, student and cms (the tracked ones by name).
     */
    private function make_subsection_course(bool $ownpage = true): array {
        global $CFG;
        if (!array_key_exists('subsection', \core\plugininfo\mod::get_enabled_plugins() ?? [])) {
            $this->markTestSkipped('Subsections are not available or not enabled in this Moodle.');
        }
        $CFG->enablecompletion = true;
        $setup = $this->make_course(2, ['enablecompletion' => 1]);
        $generator = $this->getDataGenerator();
        $course = $setup['course']->id;
        $tracked = ['completion' => COMPLETION_TRACKING_MANUAL];
        $setup['cms'] = [];
        if ($ownpage) {
            $setup['cms']['a'] = $generator->create_module('page', ['course' => $course, 'section' => 1] + $tracked);
        }
        $generator->create_module('subsection', ['course' => $course, 'section' => 1]);
        rebuild_course_cache($course, true);
        $subsections = get_fast_modinfo($course)->get_instances_of('subsection');
        $delegated = reset($subsections)->get_delegated_section_info();
        $setup['cms']['b'] = $generator->create_module(
            'page',
            ['course' => $course, 'section' => $delegated->section] + $tracked
        );
        $setup['cms']['c'] = $generator->create_module('page', ['course' => $course, 'section' => 2] + $tracked);
        return $setup;
    }

    /**
     * An activity inside a subsection counts for the section that holds the subsection.
     *
     * Regression test: the subsection is left out of the steps, and its activities were left out of the count with it,
     * so a section with a page of its own done and another in its subsection to do came out as done, and the current
     * step jumped to the next section.
     *
     * @return void
     */
    public function test_progress_counts_the_activities_inside_a_subsection(): void {
        $this->resetAfterTest();
        $setup = $this->make_subsection_course();
        $courseid = (int) $setup['course']->id;

        $this->setUser($setup['student']);
        $this->assertSame(['current', 'todo'], $this->states($courseid, 'progress'));

        $this->complete($setup, 'a', $setup['student']);
        $this->assertSame(
            ['current', 'todo'],
            $this->states($courseid, 'progress'),
            'the page in the subsection is still to do'
        );

        $this->complete($setup, 'b', $setup['student']);
        $this->assertSame(['done', 'current'], $this->states($courseid, 'progress'));
    }

    /**
     * A section that holds only a subsection is marked by the activities inside it, and not left without a state.
     *
     * @return void
     */
    public function test_a_section_with_only_a_subsection_is_marked_by_its_activities(): void {
        $this->resetAfterTest();
        $setup = $this->make_subsection_course(false);
        $courseid = (int) $setup['course']->id;

        $this->setUser($setup['student']);
        $this->assertSame(['current', 'todo'], $this->states($courseid, 'progress'));

        $this->complete($setup, 'b', $setup['student']);
        $this->assertSame(['done', 'current'], $this->states($courseid, 'progress'));
    }

    /**
     * What a student cannot see inside a subsection does not count, and hiding the subsection hides all of it.
     *
     * @return void
     */
    public function test_progress_leaves_out_a_hidden_subsection(): void {
        $this->resetAfterTest();
        $setup = $this->make_subsection_course(false);
        $courseid = (int) $setup['course']->id;
        $subsection = array_values(get_fast_modinfo($courseid)->get_instances_of('subsection'))[0];
        set_coursemodule_visible($subsection->id, 0);
        rebuild_course_cache($courseid, true);

        $this->setUser($setup['student']);

        $this->assertSame(['none', 'current'], $this->states($courseid, 'progress'));
    }

    /**
     * The marker picks the mode, and anything unknown is the date.
     *
     * @return void
     */
    public function test_mode(): void {
        $this->assertSame('progress', course_sections::get_mode('progress'));
        $this->assertSame('date', course_sections::get_mode(''));
        $this->assertSame('date', course_sections::get_mode('Progress'));
        $this->assertSame('date', course_sections::get_mode('x" onclick="y'));
    }

    /**
     * Steps up to twelve, tiles above, and the marker can force either.
     *
     * @return void
     */
    public function test_layout(): void {
        $this->assertSame('steps', course_sections::get_layout(12, ''));
        $this->assertSame('tiles', course_sections::get_layout(13, ''));
        $this->assertSame('tiles', course_sections::get_layout(3, 'tiles'));
        $this->assertSame('steps', course_sections::get_layout(30, 'steps'));
        $this->assertSame('steps', course_sections::get_layout(3, 'bogus'));
    }

    /**
     * The steps break into rows of six, with the states in visible text; tiles are a single grid.
     *
     * @return void
     */
    public function test_export_context_breaks_rows_and_writes_the_states(): void {
        $this->resetAfterTest();
        $steps = [];
        for ($i = 1; $i <= 8; $i++) {
            $steps[] = ['number' => $i, 'name' => "S$i", 'url' => '', 'state' => $i < 2 ? 'done' : ($i === 2 ? 'current' : 'todo')];
        }

        $context = \filter_studiolms\output\map::export_context($steps, 'steps');
        $this->assertCount(2, $context['rows']);
        $this->assertCount(6, $context['rows'][0]['steps']);
        $this->assertCount(2, $context['rows'][1]['steps']);
        $this->assertSame('Previous step', $context['rows'][0]['steps'][0]['statetext']);
        $this->assertSame('Current step', $context['rows'][0]['steps'][1]['statetext']);
        $this->assertSame('', $context['rows'][0]['steps'][2]['statetext']);

        // In the progress mode the same state means the section was finished.
        $progress = \filter_studiolms\output\map::export_context($steps, 'steps', 'progress');
        $this->assertSame('Done', $progress['rows'][0]['steps'][0]['statetext']);

        $tiles = \filter_studiolms\output\map::export_context($steps, 'tiles');
        $this->assertCount(1, $tiles['rows']);
        $this->assertCount(8, $tiles['rows'][0]['steps']);
    }
}
