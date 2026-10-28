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
     * New discussions and moved ones make the counts fresh; the page of the forum triggers these events itself.
     *
     * The events are built with the data of post.php and discuss.php, since no web service moves a discussion.
     *
     * @return void
     */
    public function test_discussion_events_invalidate_the_forums_they_name(): void {
        $this->resetAfterTest();
        $setup = $this->make_forum_course(2);
        $other = $this->make_forum_course(2);
        $context = \context_module::instance($setup['cm']->id);
        $forumid = (int) $setup['forum']->id;
        $discussion = $this->start_discussion($setup, $setup['teacher']);
        $this->assertSame(0, participation::get_counts($forumid, $context, null)['done']);
        $this->reply_to($discussion, $setup['students'][0]);

        participation::get_counts($forumid, $context, null);
        $this->reply_to($discussion, $setup['students'][1]);
        $this->assertSame(2, participation::get_counts($forumid, $context, null)['done']);

        // Written without the event, then announced by a discussion event naming the forum.
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $setup['students'][0]->id,
        ]);
        \mod_forum\event\discussion_created::create([
            'context' => $context,
            'objectid' => $discussion->id,
            'other' => ['forumid' => $forumid],
        ])->trigger();
        $this->assertSame(2, participation::get_counts($forumid, $context, null)['done']);

        $otherid = (int) $other['forum']->id;
        $othercontext = \context_module::instance($other['cm']->id);
        participation::get_counts($otherid, $othercontext, null);
        \mod_forum\event\discussion_moved::create([
            'context' => $context,
            'objectid' => $discussion->id,
            'other' => ['fromforumid' => $forumid, 'toforumid' => $otherid],
        ])->trigger();
        $this->assertSame(2, participation::get_counts($forumid, $context, null)['done']);
    }
}
