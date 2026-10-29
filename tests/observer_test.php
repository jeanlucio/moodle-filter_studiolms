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
 * Tests of the observers that keep the cached counts fresh.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

use filter_studiolms\local\participation;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/forum_course_trait.php');

/**
 * Tests of filter_studiolms\observer.
 *
 * @covers \filter_studiolms\observer
 */
final class observer_test extends \advanced_testcase {
    use forum_course_trait;

    /**
     * A new reply makes the counts of that forum fresh, and leaves the cache of another forum alone.
     *
     * The reply goes through the web service of the forum, which triggers post_created as the page of the forum does.
     *
     * @return void
     */
    public function test_new_reply_invalidates_only_its_forum(): void {
        $this->resetAfterTest();
        $first = $this->make_forum_course(2);
        $second = $this->make_forum_course(2);
        $firstcontext = \context_module::instance($first['cm']->id);
        $secondcontext = \context_module::instance($second['cm']->id);
        $firstdiscussion = $this->start_discussion($first, $first['teacher']);
        $seconddiscussion = $this->start_discussion($second, $second['teacher']);
        $this->assertSame(0, participation::get_counts((int) $first['forum']->id, $firstcontext, null)['done']);
        $this->assertSame(0, participation::get_counts((int) $second['forum']->id, $secondcontext, null)['done']);

        $this->reply_to($firstdiscussion, $first['students'][0]);
        $this->assertSame(1, participation::get_counts((int) $first['forum']->id, $firstcontext, null)['done']);

        // A reply written straight to the table, without the event, leaves the second forum on its cached counts.
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $seconddiscussion->id,
            'parent' => $seconddiscussion->firstpost,
            'userid' => $second['students'][0]->id,
        ]);
        $this->assertSame(0, participation::get_counts((int) $second['forum']->id, $secondcontext, null)['done']);
        $this->reply_to($seconddiscussion, $second['students'][1]);
        $this->assertSame(2, participation::get_counts((int) $second['forum']->id, $secondcontext, null)['done']);
    }

    /**
     * Deleting a post through the web service of the forum also makes the counts fresh.
     *
     * @return void
     */
    public function test_deleting_a_post_invalidates_the_forum(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2);
        $context = \context_module::instance($setup['cm']->id);
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $post = $this->reply_to($discussion, $setup['students'][0]);
        $forumid = (int) $setup['forum']->id;
        $this->assertSame(1, participation::get_counts($forumid, $context, null)['done']);

        $this->setUser($setup['students'][0]);
        \mod_forum_external::delete_post($post->id);

        $this->assertSame(0, participation::get_counts($forumid, $context, null)['done']);
    }

    /**
     * Writes a reply straight to the table, without the event the page of the forum would trigger.
     *
     * @param \stdClass $discussion The discussion.
     * @param \stdClass $user The author, who must not have taken part in the forum yet for the count to change.
     * @return void
     */
    private function write_reply_without_event(\stdClass $discussion, \stdClass $user): void {
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $user->id,
        ]);
    }

    /**
     * Counts of a forum as the cache gives them.
     *
     * @param array $setup The forum course.
     * @return int How many students have taken part.
     */
    private function done(array $setup): int {
        return participation::get_counts(
            (int) $setup['forum']->id,
            \context_module::instance($setup['cm']->id),
            null
        )['done'];
    }

    /**
     * A new discussion makes the counts fresh.
     *
     * Each step is checked, so the test fails if the observer is gone: a student who has not taken part writes a post
     * with no event, the cache still says 0, and only the event makes it say 1. The page of the forum triggers the
     * event itself; here it is built with the data of post.php.
     *
     * @return void
     */
    public function test_a_new_discussion_invalidates_its_forum(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2);
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $this->assertSame(0, $this->done($setup));

        $this->write_reply_without_event($discussion, $setup['students'][0]);
        $this->assertSame(0, $this->done($setup), 'without the event the cache is still the old one');

        \mod_forum\event\discussion_created::create([
            'context' => \context_module::instance($setup['cm']->id),
            'objectid' => $discussion->id,
            'other' => ['forumid' => (int) $setup['forum']->id],
        ])->trigger();

        $this->assertSame(1, $this->done($setup));
    }

    /**
     * A deleted discussion makes the counts fresh.
     *
     * @return void
     */
    public function test_a_deleted_discussion_invalidates_its_forum(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2);
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $this->assertSame(0, $this->done($setup));

        $this->write_reply_without_event($discussion, $setup['students'][0]);
        $this->assertSame(0, $this->done($setup), 'without the event the cache is still the old one');

        \mod_forum\event\discussion_deleted::create([
            'context' => \context_module::instance($setup['cm']->id),
            'objectid' => $discussion->id,
            'other' => ['forumid' => (int) $setup['forum']->id],
        ])->trigger();

        $this->assertSame(1, $this->done($setup));
    }

    /**
     * A discussion that moves between two forums makes the counts of both fresh, the one it left and the one it
     * went to, and leaves a third alone.
     *
     * @return void
     */
    public function test_a_moved_discussion_invalidates_both_forums_and_no_other(): void {
        $this->resetAfterTest();
        $from = $this->make_forum_course(2);
        $to = $this->make_forum_course(2);
        $third = $this->make_forum_course(2);
        $fromdiscussion = $this->start_discussion($from, $from['teacher']);
        $todiscussion = $this->start_discussion($to, $to['teacher']);
        $thirddiscussion = $this->start_discussion($third, $third['teacher']);
        foreach ([$from, $to, $third] as $setup) {
            $this->assertSame(0, $this->done($setup));
        }

        $this->write_reply_without_event($fromdiscussion, $from['students'][0]);
        $this->write_reply_without_event($todiscussion, $to['students'][0]);
        $this->write_reply_without_event($thirddiscussion, $third['students'][0]);
        foreach ([$from, $to, $third] as $setup) {
            $this->assertSame(0, $this->done($setup), 'without the event the cache is still the old one');
        }

        \mod_forum\event\discussion_moved::create([
            'context' => \context_module::instance($from['cm']->id),
            'objectid' => $fromdiscussion->id,
            'other' => ['fromforumid' => (int) $from['forum']->id, 'toforumid' => (int) $to['forum']->id],
        ])->trigger();

        $this->assertSame(1, $this->done($from), 'the forum it left');
        $this->assertSame(1, $this->done($to), 'the forum it went to');
        $this->assertSame(0, $this->done($third), 'a forum the event does not name keeps its cache');
    }

    /**
     * A forum event that names no forum changes nothing, and does not fail.
     *
     * @return void
     */
    public function test_an_event_that_names_no_forum_is_ignored(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2);
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $this->assertSame(0, $this->done($setup));
        $this->write_reply_without_event($discussion, $setup['students'][0]);

        foreach (['discussion_created', 'discussion_deleted'] as $name) {
            $class = '\\mod_forum\\event\\' . $name;
            $this->assertNull(\filter_studiolms\observer::forum_changed($class::create([
                'context' => \context_module::instance($setup['cm']->id),
                'objectid' => $discussion->id,
                'other' => ['forumid' => 0],
            ])));
        }
        \filter_studiolms\observer::discussion_moved(\mod_forum\event\discussion_moved::create([
            'context' => \context_module::instance($setup['cm']->id),
            'objectid' => $discussion->id,
            'other' => ['fromforumid' => 0, 'toforumid' => 0],
        ]));

        $this->assertSame(0, $this->done($setup), 'nothing was invalidated');
    }
}
