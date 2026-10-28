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
 * Builds a course with a forum and a class of students for the tests.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

/**
 * Helpers shared by the tests of the forum widget; the data is made by the generators of mod_forum.
 */
trait forum_course_trait {
    /**
     * Creates a course with a forum and a number of students.
     *
     * @param int $students How many students to enrol.
     * @param array $forumoptions Extra options for the forum.
     * @return array The keys course, forum, cm, students (user records) and teacher.
     */
    protected function make_forum_course(int $students, array $forumoptions = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $forum = $generator->create_module('forum', ['course' => $course->id] + $forumoptions);
        $studentlist = [];
        for ($i = 0; $i < $students; $i++) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $course->id, 'student');
            $studentlist[] = $user;
        }
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        return [
            'course' => $course,
            'forum' => $forum,
            'cm' => get_coursemodule_from_instance('forum', $forum->id),
            'students' => $studentlist,
            'teacher' => $teacher,
        ];
    }

    /**
     * Starts a discussion in a forum.
     *
     * @param array $setup The result of make_forum_course().
     * @param \stdClass $user The author.
     * @return \stdClass The discussion record.
     */
    protected function start_discussion(array $setup, \stdClass $user): \stdClass {
        return $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion([
            'course' => $setup['course']->id,
            'forum' => $setup['forum']->id,
            'userid' => $user->id,
        ]);
    }

    /**
     * Replies to the first post of a discussion through the web service of the forum, as the page of the forum does.
     *
     * The web service saves the post and triggers the post_created event; the data generator does neither the
     * second, which the observers of the plugin depend on. The current user is restored afterwards.
     *
     * @param \stdClass $discussion The discussion record.
     * @param \stdClass $user The author.
     * @return \stdClass The post record.
     */
    protected function reply_to(\stdClass $discussion, \stdClass $user): \stdClass {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/forum/externallib.php');

        $before = $USER->id ? $USER : null;
        $this->setUser($user);
        $result = \mod_forum_external::add_discussion_post($discussion->firstpost, 'Re: topic', 'A reply');
        $result = \core_external\external_api::clean_returnvalue(\mod_forum_external::add_discussion_post_returns(), $result);
        $this->setUser($before);
        return $DB->get_record('forum_posts', ['id' => $result['postid']], '*', MUST_EXIST);
    }
}
