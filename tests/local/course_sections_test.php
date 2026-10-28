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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Tests of filter_studiolms\local\course_sections.
 *
 * @covers \filter_studiolms\local\course_sections
 * @covers \filter_studiolms\output\map
 */
final class course_sections_test extends \advanced_testcase {
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
     * A hidden section is left out for the student and kept for the teacher; numbering stays in sequence.
     *
     * @return void
     */
    public function test_hidden_sections_follow_the_visibility_of_the_viewer(): void {
        $this->resetAfterTest();
        $setup = $this->make_course(4);
        set_section_visible($setup['course']->id, 2, 0);

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
        course_set_marker($setup['course']->id, 2);
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
        course_set_marker($setup['course']->id, 3);
        set_section_visible($setup['course']->id, 3, 0);

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
        $this->assertSame('Done', $context['rows'][0]['steps'][0]['statetext']);
        $this->assertSame('Current step', $context['rows'][0]['steps'][1]['statetext']);
        $this->assertSame('', $context['rows'][0]['steps'][2]['statetext']);

        $tiles = \filter_studiolms\output\map::export_context($steps, 'tiles');
        $this->assertCount(1, $tiles['rows']);
        $this->assertCount(8, $tiles['rows'][0]['steps']);
    }
}
