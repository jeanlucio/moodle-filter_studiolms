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
 * Observers that keep the cached counts of a forum fresh.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

use filter_studiolms\local\participation;

/**
 * Forgets the cached participation of a forum when its posts change.
 */
class observer {
    /**
     * A post or discussion was created or deleted.
     *
     * @param \core\event\base $event The forum event.
     */
    public static function forum_changed(\core\event\base $event): void {
        $forumid = (int) ($event->other['forumid'] ?? 0);
        if ($forumid > 0) {
            participation::invalidate($forumid);
        }
    }

    /**
     * A discussion moved between forums.
     *
     * @param \core\event\base $event The forum event.
     */
    public static function discussion_moved(\core\event\base $event): void {
        foreach (['fromforumid', 'toforumid'] as $key) {
            $forumid = (int) ($event->other[$key] ?? 0);
            if ($forumid > 0) {
                participation::invalidate($forumid);
            }
        }
    }
}
