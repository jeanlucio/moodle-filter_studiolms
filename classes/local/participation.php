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
 * Counts how much of the class has taken part in a forum.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

/**
 * Aggregated participation of the students of a forum: one query, never one per student.
 */
class participation {
    /**
     * Returns the minimum class size under which students are not shown the numbers.
     *
     * @return int
     */
    public static function get_minimum_class_size(): int {
        $value = get_config('filter_studiolms', 'minclasssize');
        return $value === false ? 5 : max(0, (int) $value);
    }

    /**
     * Returns the groups the current user is limited to in the forum, if any.
     *
     * @param \cm_info $cm The forum.
     * @param \context $context The context of the forum.
     * @return int[]|null Null when the user sees the whole class; otherwise the ids of their groups (maybe none).
     */
    public static function get_group_restriction(\cm_info $cm, \context $context): ?array {
        global $USER;

        if ((int) groups_get_activity_groupmode($cm) !== SEPARATEGROUPS) {
            return null;
        }
        if (has_capability('moodle/site:accessallgroups', $context)) {
            return null;
        }
        $groups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid, 'g.id');
        $ids = array_map('intval', array_keys($groups));
        sort($ids);
        return $ids;
    }

    /**
     * Returns the counts of the forum for a set of groups, from the cache when it is fresh.
     *
     * @param int $forumid The forum id.
     * @param \context $context The context of the forum.
     * @param int[]|null $groupids Null for the whole class.
     * @return int[] The keys total (the whole population) and done (those with at least one message).
     */
    public static function get_counts(int $forumid, \context $context, ?array $groupids): array {
        if ($groupids !== null && $groupids === []) {
            return ['total' => 0, 'done' => 0];
        }

        $cache = \cache::make('filter_studiolms', 'participation');
        $key = $groupids === null ? 'all' : implode(',', $groupids);
        $entry = $cache->get($forumid);
        if (is_array($entry) && isset($entry[$key])) {
            return $entry[$key];
        }

        $counts = self::calculate($forumid, $context, $groupids);
        $entry = is_array($entry) ? $entry : [];
        $entry[$key] = $counts;
        $cache->set($forumid, $entry);
        return $counts;
    }

    /**
     * Forgets the cached counts of a forum, since a post was added or removed.
     *
     * @param int $forumid The forum id.
     */
    public static function invalidate(int $forumid): void {
        \cache::make('filter_studiolms', 'participation')->delete($forumid);
    }

    /**
     * Counts the whole population and those who sent at least one message, in one query.
     *
     * The population is the active students (holders of moodle/course:isincompletionreports, the capability that
     * marks the student role) who can post in the forum. Teachers who post are left out because they are not in it.
     * Deleted posts and replies sent privately by a teacher never count.
     *
     * @param int $forumid The forum id.
     * @param \context $context The context of the forum.
     * @param int[]|null $groupids Null for the whole class.
     * @return int[] The keys total and done.
     */
    public static function calculate(int $forumid, \context $context, ?array $groupids): array {
        global $DB;

        [$studentsql, $studentparams] = get_enrolled_sql(
            $context,
            'moodle/course:isincompletionreports',
            $groupids ?? 0,
            true
        );
        [$postersql, $posterparams] = get_enrolled_sql($context, 'mod/forum:replypost', 0, true);

        $sql = "SELECT COUNT(u.id) AS total,
                       COALESCE(SUM(CASE WHEN a.userid IS NOT NULL THEN 1 ELSE 0 END), 0) AS done
                  FROM {user} u
             LEFT JOIN (SELECT DISTINCT p.userid
                          FROM {forum_posts} p
                          JOIN {forum_discussions} d ON d.id = p.discussion
                         WHERE d.forum = :forumid AND p.deleted = 0 AND p.privatereplyto = 0) a ON a.userid = u.id
                 WHERE u.id IN ($studentsql) AND u.id IN ($postersql)";
        $record = $DB->get_record_sql($sql, ['forumid' => $forumid] + $studentparams + $posterparams);

        return ['total' => (int) $record->total, 'done' => (int) $record->done];
    }

    /**
     * Turns the counts into the two whole percentages, which always add up to 100.
     *
     * Rounding never hides someone: while at least one person is in a group, that group shows at least 1%, and
     * while someone is outside the first one it never reaches 100%.
     *
     * @param int $done Those with at least one message.
     * @param int $total The whole population.
     * @return int[] The keys done and todo.
     */
    public static function percentages(int $done, int $total): array {
        if ($total <= 0) {
            return ['done' => 0, 'todo' => 0];
        }
        $done = min($done, $total);
        $percent = (int) round($done * 100 / $total);
        if ($done > 0) {
            $percent = max($percent, 1);
        }
        if ($done < $total) {
            $percent = min($percent, 99);
        }
        return ['done' => $percent, 'todo' => 100 - $percent];
    }
}
