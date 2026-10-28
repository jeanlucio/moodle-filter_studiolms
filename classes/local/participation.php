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
     * Works out what the bar measures for a forum: the rules of its activity completion, when it has any.
     *
     * Every rule the teacher ticked must be met, as in the completion of the activity, and "at least" the number the rule
     * asks for. A forum with no rule (or with completion that is not automatic) measures any message, topic or reply.
     * The rules only choose what to count: the widget does not read, give or change anyone's completion.
     *
     * @param \cm_info $cm The forum.
     * @return array The keys kind (posts, discussions or replies, which picks the labels), and the minimums
     *               posts, discussions and replies, 0 for a rule that is not used.
     */
    public static function get_measure(\cm_info $cm): array {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $rules = [];
        $tracking = (new \completion_info($cm->get_course()))->is_enabled($cm);
        if ($tracking == COMPLETION_TRACKING_AUTOMATIC) {
            $rules = $cm->customdata['customcompletionrules'] ?? [];
        }
        $discussions = max(0, (int) ($rules['completiondiscussions'] ?? 0));
        $replies = max(0, (int) ($rules['completionreplies'] ?? 0));
        $posts = max(0, (int) ($rules['completionposts'] ?? 0));
        if ($discussions === 0 && $replies === 0 && $posts === 0) {
            $posts = 1;
        }

        $kind = 'posts';
        if ($posts === 0 && $replies === 0) {
            $kind = 'discussions';
        } else if ($posts === 0 && $discussions === 0) {
            $kind = 'replies';
        }
        return ['kind' => $kind, 'posts' => $posts, 'discussions' => $discussions, 'replies' => $replies];
    }

    /**
     * Returns the counts of the forum for a set of groups, from the cache when it is fresh.
     *
     * @param int $forumid The forum id.
     * @param \context $context The context of the forum.
     * @param int[]|null $groupids Null for the whole class.
     * @param array $measure What to count, from get_measure().
     * @return int[] The keys total (the whole population) and done (those who did what the measure asks).
     */
    public static function get_counts(int $forumid, \context $context, ?array $groupids, array $measure): array {
        if ($groupids !== null && $groupids === []) {
            return ['total' => 0, 'done' => 0];
        }

        $cache = \cache::make('filter_studiolms', 'participation');
        // The rules may change while a forum is cached, so they are part of the key.
        $key = ($groupids === null ? 'all' : implode(',', $groupids))
            . ':' . $measure['posts'] . '-' . $measure['discussions'] . '-' . $measure['replies'];
        $entry = $cache->get($forumid);
        if (is_array($entry) && isset($entry[$key])) {
            return $entry[$key];
        }

        $counts = self::calculate($forumid, $context, $groupids, $measure);
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
     * Counts the whole population and those who did what the measure asks, in one query.
     *
     * The population is the active students (holders of moodle/course:isincompletionreports, the capability that
     * marks the student role) who can post in the forum. Teachers who post are left out because they are not in it.
     * Deleted posts and replies sent privately by a teacher never count.
     *
     * @param int $forumid The forum id.
     * @param \context $context The context of the forum.
     * @param int[]|null $groupids Null for the whole class.
     * @param array $measure What to count, from get_measure().
     * @return int[] The keys total and done.
     */
    public static function calculate(int $forumid, \context $context, ?array $groupids, array $measure): array {
        global $DB;

        [$studentsql, $studentparams] = get_enrolled_sql(
            $context,
            'moodle/course:isincompletionreports',
            $groupids ?? 0,
            true
        );
        [$postersql, $posterparams] = get_enrolled_sql($context, 'mod/forum:replypost', 0, true);

        $sql = "SELECT COUNT(u.id) AS total,
                       COALESCE(SUM(CASE WHEN COALESCE(a.topics, 0) >= :mindiscussions
                                          AND COALESCE(a.replies, 0) >= :minreplies
                                          AND COALESCE(a.posts, 0) >= :minposts THEN 1 ELSE 0 END), 0) AS done
                  FROM {user} u
             LEFT JOIN (SELECT p.userid,
                               SUM(CASE WHEN p.parent <> 0 THEN 1 ELSE 0 END) AS replies,
                               SUM(CASE WHEN p.parent = 0 THEN 1 ELSE 0 END) AS topics,
                               COUNT(p.id) AS posts
                          FROM {forum_posts} p
                          JOIN {forum_discussions} d ON d.id = p.discussion
                         WHERE d.forum = :forumid AND p.deleted = 0 AND p.privatereplyto = 0
                      GROUP BY p.userid) a ON a.userid = u.id
                 WHERE u.id IN ($studentsql) AND u.id IN ($postersql)";
        $params = [
            'forumid' => $forumid,
            'mindiscussions' => $measure['discussions'],
            'minreplies' => $measure['replies'],
            'minposts' => $measure['posts'],
        ] + $studentparams + $posterparams;
        $record = $DB->get_record_sql($sql, $params);

        return ['total' => (int) $record->total, 'done' => (int) $record->done];
    }

    /**
     * Turns the counts into the two whole percentages, which always add up to 100.
     *
     * Rounding never hides someone: while at least one person is in a group, that group shows at least 1%, and
     * while someone is outside the first one it never reaches 100%.
     *
     * @param int $done Those who did what the measure asks.
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
