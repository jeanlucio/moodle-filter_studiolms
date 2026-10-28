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
 * Tests of the aggregated participation of a forum.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

use filter_studiolms\forum_course_trait;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/forum_course_trait.php');

/**
 * Tests of filter_studiolms\local\participation.
 *
 * @covers \filter_studiolms\local\participation
 */
final class participation_test extends \advanced_testcase {
    use forum_course_trait;

    /**
     * The measure of a forum with no completion rule: any message counts.
     *
     * @return array
     */
    private function anymeasure(): array {
        return ['kind' => 'posts', 'posts' => 1, 'discussions' => 0, 'replies' => 0];
    }

    /**
     * Ten students: five reply, two only open a topic (one more opens a topic and also replies), three do nothing.
     * The teacher posts and replies, and is not counted.
     *
     * @param array $options Extra options of the forum.
     * @return array The setup of make_forum_course().
     */
    private function make_class(array $options = []): array {
        $setup = $this->make_forum_course(10, $options);
        $students = $setup['students'];
        $teacherdiscussion = $this->start_discussion($setup, $setup['teacher']);
        $this->reply_to($teacherdiscussion, $setup['teacher']);
        for ($i = 0; $i < 5; $i++) {
            $this->reply_to($teacherdiscussion, $students[$i]);
        }
        $this->start_discussion($setup, $students[5]);
        $this->start_discussion($setup, $students[6]);
        // A student who opened a topic and also replied.
        $this->start_discussion($setup, $students[0]);
        return $setup;
    }

    /**
     * With no rule, seven of ten took part (five replied, two only opened a topic); teachers who post do not count.
     *
     * @return void
     */
    public function test_any_message_counts_when_the_forum_has_no_rule(): void {
        $this->resetAfterTest();
        $setup = $this->make_class();
        $context = \context_module::instance($setup['cm']->id);

        $counts = participation::calculate((int) $setup['forum']->id, $context, null, $this->anymeasure());

        $this->assertSame(['total' => 10, 'done' => 7], $counts);
        $this->assertSame(['done' => 70, 'todo' => 30], participation::percentages(7, 10));
    }

    /**
     * The measure comes from the rules of the completion of the activity, and only when it is automatic.
     *
     * @return void
     */
    public function test_measure_follows_the_completion_rules(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablecompletion = true;
        $course = ['enablecompletion' => 1];
        $cases = [
            [['completionreplies' => 1], ['kind' => 'replies', 'posts' => 0, 'discussions' => 0, 'replies' => 1]],
            [['completiondiscussions' => 2], ['kind' => 'discussions', 'posts' => 0, 'discussions' => 2, 'replies' => 0]],
            [['completionposts' => 3], ['kind' => 'posts', 'posts' => 3, 'discussions' => 0, 'replies' => 0]],
            [
                ['completionreplies' => 1, 'completiondiscussions' => 1],
                ['kind' => 'posts', 'posts' => 0, 'discussions' => 1, 'replies' => 1],
            ],
            [[], $this->anymeasure()],
        ];
        foreach ($cases as [$rules, $expected]) {
            $setup = $this->make_forum_course(1, ['completion' => COMPLETION_TRACKING_AUTOMATIC] + $rules, $course);
            $cm = get_fast_modinfo($setup['course']->id)->get_cm($setup['cm']->id);
            $this->assertSame($expected, participation::get_measure($cm), json_encode($rules));
        }

        // Rules left on a forum whose completion is manual are ignored, as the completion itself ignores them.
        $manual = $this->make_forum_course(1, ['completion' => COMPLETION_TRACKING_MANUAL, 'completionreplies' => 1], $course);
        $cm = get_fast_modinfo($manual['course']->id)->get_cm($manual['cm']->id);
        $this->assertSame($this->anymeasure(), participation::get_measure($cm));

        // With completion switched off in the site, the rules are not in force either.
        $active = $this->make_forum_course(1, ['completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionreplies' => 1], $course);
        $CFG->enablecompletion = false;
        $cm = get_fast_modinfo($active['course']->id)->get_cm($active['cm']->id);
        $this->assertSame($this->anymeasure(), participation::get_measure($cm));
    }

    /**
     * Each kind of rule counts what it asks, with its minimum, and every ticked rule has to be met.
     *
     * @return void
     */
    public function test_each_rule_counts_what_it_asks(): void {
        $this->resetAfterTest();
        $setup = $this->make_class();
        $context = \context_module::instance($setup['cm']->id);
        $forumid = (int) $setup['forum']->id;
        $measure = fn(int $posts, int $discussions, int $replies): array => [
            'kind' => 'posts', 'posts' => $posts, 'discussions' => $discussions, 'replies' => $replies,
        ];

        // Replies: the five who replied.
        $this->assertSame(5, participation::calculate($forumid, $context, null, $measure(0, 0, 1))['done']);
        // Discussions: the two who only opened one and the one who also replied.
        $this->assertSame(3, participation::calculate($forumid, $context, null, $measure(0, 1, 0))['done']);
        // Two messages at least: the student who opened a topic and replied.
        $this->assertSame(1, participation::calculate($forumid, $context, null, $measure(2, 0, 0))['done']);
        // A topic and a reply: only that same student.
        $this->assertSame(1, participation::calculate($forumid, $context, null, $measure(0, 1, 1))['done']);
        // Two replies: nobody.
        $this->assertSame(0, participation::calculate($forumid, $context, null, $measure(0, 0, 2))['done']);
    }

    /**
     * A suspended enrolment leaves the population, and a deleted reply no longer counts.
     *
     * @return void
     */
    public function test_ignores_suspended_students_and_deleted_posts(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest();
        $setup = $this->make_forum_course(4);
        $context = \context_module::instance($setup['cm']->id);
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $this->reply_to($discussion, $setup['students'][0]);
        $deleted = $this->reply_to($discussion, $setup['students'][1]);
        $this->reply_to($discussion, $setup['students'][2]);

        $enrol = enrol_get_plugin('manual');
        $manualinstance = null;
        foreach (enrol_get_instances($setup['course']->id, true) as $candidate) {
            if ($candidate->enrol === 'manual') {
                $manualinstance = $candidate;
            }
        }
        $enrol->update_user_enrol($manualinstance, $setup['students'][2]->id, ENROL_USER_SUSPENDED);

        $forumrecord = $setup['forum'];
        forum_delete_post($deleted, true, $setup['course'], $setup['cm'], $forumrecord);

        $counts = participation::calculate((int) $setup['forum']->id, $context, null, $this->anymeasure());

        $this->assertSame(['total' => 3, 'done' => 1], $counts);
    }

    /**
     * The participation of another forum, in the same course or in another one, never enters the count.
     *
     * @return void
     */
    public function test_does_not_mix_forums_or_courses(): void {
        $this->resetAfterTest();
        $first = $this->make_forum_course(3);
        $other = $this->getDataGenerator()->create_module('forum', ['course' => $first['course']->id]);
        $second = $this->make_forum_course(3);

        foreach ($first['students'] as $student) {
            $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion([
                'course' => $first['course']->id,
                'forum' => $other->id,
                'userid' => $first['teacher']->id,
            ]);
            $this->reply_to($discussion, $student);
        }
        $this->reply_to($this->start_discussion($second, $second['teacher']), $second['students'][0]);

        $context = \context_module::instance($first['cm']->id);
        $counts = participation::calculate((int) $first['forum']->id, $context, null, $this->anymeasure());
        $this->assertSame(['total' => 3, 'done' => 0], $counts);

        $secondcontext = \context_module::instance($second['cm']->id);
        $secondcounts = participation::calculate((int) $second['forum']->id, $secondcontext, null, $this->anymeasure());
        $this->assertSame(['total' => 3, 'done' => 1], $secondcounts);
    }

    /**
     * In separate groups a student without access to all groups sees only their own group.
     *
     * @return void
     */
    public function test_separate_groups_limit_the_class(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(4, ['groupmode' => SEPARATEGROUPS]);
        $context = \context_module::instance($setup['cm']->id);
        $generator = $this->getDataGenerator();
        $groupa = $generator->create_group(['courseid' => $setup['course']->id]);
        $groupb = $generator->create_group(['courseid' => $setup['course']->id]);
        $generator->create_group_member(['groupid' => $groupa->id, 'userid' => $setup['students'][0]->id]);
        $generator->create_group_member(['groupid' => $groupa->id, 'userid' => $setup['students'][1]->id]);
        $generator->create_group_member(['groupid' => $groupb->id, 'userid' => $setup['students'][2]->id]);
        $generator->create_group_member(['groupid' => $groupb->id, 'userid' => $setup['students'][3]->id]);
        // In separate groups a student can only reply in the discussion of their own group.
        $forumgenerator = $generator->get_plugin_generator('mod_forum');
        $discussiona = $forumgenerator->create_discussion([
            'course' => $setup['course']->id,
            'forum' => $setup['forum']->id,
            'userid' => $setup['teacher']->id,
            'groupid' => $groupa->id,
        ]);
        $discussionb = $forumgenerator->create_discussion([
            'course' => $setup['course']->id,
            'forum' => $setup['forum']->id,
            'userid' => $setup['teacher']->id,
            'groupid' => $groupb->id,
        ]);
        $this->reply_to($discussiona, $setup['students'][0]);
        $this->reply_to($discussionb, $setup['students'][2]);
        $this->reply_to($discussionb, $setup['students'][3]);

        $cm = get_fast_modinfo($setup['course']->id)->get_cm($setup['cm']->id);
        $forumid = (int) $setup['forum']->id;

        $this->setUser($setup['students'][0]);
        $restriction = participation::get_group_restriction($cm, $context);
        $this->assertSame([(int) $groupa->id], $restriction);
        $this->assertSame(
            ['total' => 2, 'done' => 1],
            participation::get_counts($forumid, $context, $restriction, $this->anymeasure())
        );

        $this->setUser($setup['teacher']);
        $this->assertNull(participation::get_group_restriction($cm, $context));
        $this->assertSame(
            ['total' => 4, 'done' => 3],
            participation::get_counts($forumid, $context, null, $this->anymeasure())
        );
    }

    /**
     * A student in no group of a separate-groups forum sees nobody.
     *
     * @return void
     */
    public function test_student_without_group_sees_nobody(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2, ['groupmode' => SEPARATEGROUPS]);
        $context = \context_module::instance($setup['cm']->id);
        $cm = get_fast_modinfo($setup['course']->id)->get_cm($setup['cm']->id);

        $this->setUser($setup['students'][0]);
        $restriction = participation::get_group_restriction($cm, $context);

        $this->assertSame([], $restriction);
        $this->assertSame(
            ['total' => 0, 'done' => 0],
            participation::get_counts((int) $setup['forum']->id, $context, $restriction, $this->anymeasure())
        );
    }

    /**
     * The percentages add up to 100, and rounding never hides a person or says 100% while someone is missing.
     *
     * @return void
     */
    public function test_percentages_add_up_and_never_hide_anyone(): void {
        for ($total = 1; $total <= 300; $total += 7) {
            for ($done = 0; $done <= $total; $done++) {
                $percentages = participation::percentages($done, $total);
                $this->assertSame(100, array_sum($percentages), "$done/$total");
                $this->assertSame($done > 0, $percentages['done'] > 0, "$done/$total");
                $this->assertSame($done < $total, $percentages['done'] < 100, "$done/$total");
            }
        }
        $this->assertSame(['done' => 1, 'todo' => 99], participation::percentages(1, 300));
        $this->assertSame(['done' => 99, 'todo' => 1], participation::percentages(299, 300));
        $this->assertSame(['done' => 0, 'todo' => 0], participation::percentages(0, 0));
    }

    /**
     * The counts are kept for a while, an event or an explicit call makes them fresh, and a change of rule does not
     * reuse counts made for another one.
     *
     * @return void
     */
    public function test_cache_keeps_counts_until_invalidated(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2);
        $context = \context_module::instance($setup['cm']->id);
        $forumid = (int) $setup['forum']->id;
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $measure = $this->anymeasure();

        $this->assertSame(0, participation::get_counts($forumid, $context, null, $measure)['done']);

        // A reply written straight to the table triggers no event, so the cached counts stay as they were.
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $setup['students'][0]->id,
        ]);
        $this->assertSame(0, participation::get_counts($forumid, $context, null, $measure)['done']);

        // Another rule is another measure: it is not answered from the counts of the first.
        $replies = ['kind' => 'replies', 'posts' => 0, 'discussions' => 0, 'replies' => 1];
        $this->assertSame(1, participation::get_counts($forumid, $context, null, $replies)['done']);

        participation::invalidate($forumid);
        $this->assertSame(1, participation::get_counts($forumid, $context, null, $measure)['done']);
    }

    /**
     * The minimum class size is 5 until the administrator saves another value.
     *
     * @return void
     */
    public function test_minimum_class_size_default_and_setting(): void {
        $this->resetAfterTest();
        $this->assertSame(5, participation::get_minimum_class_size());

        set_config('minclasssize', 8, 'filter_studiolms');
        $this->assertSame(8, participation::get_minimum_class_size());
    }
}
