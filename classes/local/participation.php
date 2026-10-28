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
     * @return int[] The keys total, replied and startedonly.
     */
    public static function get_counts(int $forumid, \context $context, ?array $groupids): array {
        if ($groupids !== null && $groupids === []) {
            return ['total' => 0, 'replied' => 0, 'startedonly' => 0];
        }

        $cache = \cache::make('filter_studiolms', 'participation');
        $groupkey = $groupids === null ? 'all' : implode(',', $groupids);
        $entry = $cache->get($forumid);
        if (is_array($entry) && isset($entry[$groupkey])) {
            return $entry[$groupkey];
        }

        $counts = self::calculate($forumid, $context, $groupids);
        $entry = is_array($entry) ? $entry : [];
        $entry[$groupkey] = $counts;
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
     * Counts the students who replied, who only started a topic, and the whole population.
     *
     * The population is the active students (holders of moodle/course:isincompletionreports, the capability that
     * marks the student role) who can post in the forum. Teachers who post are left out because they are not in it.
     * Replies sent privately by a teacher and deleted posts never count.
     *
     * @param int $forumid The forum id.
     * @param \context $context The context of the forum.
     * @param int[]|null $groupids Null for the whole class.
     * @return int[] The keys total, replied and startedonly.
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
                       COALESCE(SUM(CASE WHEN a.replies > 0 THEN 1 ELSE 0 END), 0) AS replied,
                       COALESCE(SUM(CASE WHEN a.replies IS NULL OR a.replies = 0 THEN
                                         CASE WHEN a.topics > 0 THEN 1 ELSE 0 END
                                    ELSE 0 END), 0) AS startedonly
                  FROM {user} u
             LEFT JOIN (SELECT p.userid,
                               SUM(CASE WHEN p.parent <> 0 THEN 1 ELSE 0 END) AS replies,
                               SUM(CASE WHEN p.parent = 0 THEN 1 ELSE 0 END) AS topics
                          FROM {forum_posts} p
                          JOIN {forum_discussions} d ON d.id = p.discussion
                         WHERE d.forum = :forumid AND p.deleted = 0 AND p.privatereplyto = 0
                      GROUP BY p.userid) a ON a.userid = u.id
                 WHERE u.id IN ($studentsql) AND u.id IN ($postersql)";
        $record = $DB->get_record_sql($sql, ['forumid' => $forumid] + $studentparams + $posterparams);

        return [
            'total' => (int) $record->total,
            'replied' => (int) $record->replied,
            'startedonly' => (int) $record->startedonly,
        ];
    }

    /**
     * Turns the three counts into whole percentages that add up to 100 (largest remainder method).
     *
     * @param int $replied Students who replied.
     * @param int $startedonly Students who only started a topic.
     * @param int $total The whole population.
     * @return int[] The keys replied, startedonly and notparticipated.
     */
    public static function percentages(int $replied, int $startedonly, int $total): array {
        if ($total <= 0) {
            return ['replied' => 0, 'startedonly' => 0, 'notparticipated' => 0];
        }
        $counts = [
            'replied' => $replied,
            'startedonly' => $startedonly,
            'notparticipated' => max(0, $total - $replied - $startedonly),
        ];
        $sum = array_sum($counts);
        $floors = [];
        $remainders = [];
        foreach ($counts as $key => $count) {
            $exact = $count * 100 / $sum;
            $floors[$key] = (int) floor($exact);
            $remainders[$key] = $exact - $floors[$key];
        }
        // The fixed order of the keys breaks ties, so the same counts always give the same bar.
        arsort($remainders, SORT_NUMERIC);
        $missing = 100 - array_sum($floors);
        foreach (array_keys($remainders) as $key) {
            if ($missing <= 0) {
                break;
            }
            $floors[$key]++;
            $missing--;
        }
        return $floors;
    }
}
