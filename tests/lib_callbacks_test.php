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
 * Tests of the callback that adds the help to the forum form.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

/**
 * Tests of filter_studiolms_coursemodule_standard_elements().
 *
 * @covers ::filter_studiolms_coursemodule_standard_elements
 */
final class lib_callbacks_test extends \advanced_testcase {
    /**
     * Builds a form with a description editor and the field below it, then runs the callback.
     *
     * @param string $modname The module the form belongs to.
     * @param \context $context The context the form works in.
     * @param bool $withdescription Whether the form has the "show description" field.
     * @return \MoodleQuickForm
     */
    private function build_form(string $modname, \context $context, bool $withdescription = true): \MoodleQuickForm {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');
        require_once($CFG->dirroot . '/filter/studiolms/lib.php');

        $mform = new \MoodleQuickForm('studiolmstest', 'post', '');
        $mform->addElement('text', 'name', 'Name');
        $mform->addElement('text', 'introeditor', 'Description');
        if ($withdescription) {
            $mform->addElement('advcheckbox', 'showdescription', 'Show description');
        }
        $mform->addElement('text', 'other', 'Other');

        $wrapper = new class ($modname, $context) {
            /**
             * Constructor.
             *
             * @param string $modname The module name.
             * @param \context $context The context of the form.
             */
            public function __construct(
                /** @var string The module name. */
                private string $modname,
                /** @var \context The context of the form. */
                private \context $context
            ) {
            }

            /**
             * The current activity data, as moodleform_mod::get_current() returns it.
             *
             * @return \stdClass
             */
            public function get_current(): \stdClass {
                return (object) ['modulename' => $this->modname];
            }

            /**
             * The context of the form, as moodleform_mod::get_context() returns it.
             *
             * @return \context
             */
            public function get_context(): \context {
                return $this->context;
            }
        };
        filter_studiolms_coursemodule_standard_elements($wrapper, $mform);
        return $mform;
    }

    /**
     * The names of the elements, in order.
     *
     * @param \MoodleQuickForm $mform The form.
     * @return string[]
     */
    private function element_names(\MoodleQuickForm $mform): array {
        return array_map(fn($element) => $element->getName(), $mform->_elements);
    }

    /**
     * The forum form gets the help right before the field below the description editor.
     *
     * @return void
     */
    public function test_forum_form_gets_the_help_before_showdescription(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $names = $this->element_names($this->build_form('forum', $context));

        $this->assertSame(
            ['name', 'introeditor', 'studiolmsforumstatshelp', 'showdescription', 'other'],
            $names
        );
    }

    /**
     * Other activities get nothing, and neither does a form without the field to anchor to.
     *
     * @return void
     */
    public function test_other_activities_and_forms_without_the_field_get_nothing(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $this->assertNotContains('studiolmsforumstatshelp', $this->element_names($this->build_form('assign', $context)));
        $this->assertNotContains('studiolmsforumstatshelp', $this->element_names($this->build_form('forum', $context, false)));
    }

    /**
     * With the widget off the help is not shown.
     *
     * @return void
     */
    public function test_no_help_when_the_widget_is_off(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        set_config('enable_forumstats', 0, 'filter_studiolms');

        $names = $this->element_names($this->build_form('forum', \context_course::instance($course->id)));

        $this->assertNotContains('studiolmsforumstatshelp', $names);
    }

    /**
     * The warning that the filter is off appears only when the filter is not active in the course.
     *
     * @return void
     */
    public function test_warning_depends_on_the_filter_being_active_in_the_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $warning = get_string('formhelpinactive', 'filter_studiolms');

        filter_set_global_state('studiolms', TEXTFILTER_DISABLED);
        $this->assertStringContainsString($warning, $this->build_form('forum', $context)->toHtml());

        filter_set_global_state('studiolms', TEXTFILTER_ON);
        $html = $this->build_form('forum', $context)->toHtml();
        $this->assertStringNotContainsString($warning, $html);
        $this->assertStringContainsString(get_string('formhelp', 'filter_studiolms'), $html);
    }
}
