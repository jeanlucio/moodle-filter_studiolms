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
 * The [FORUMSTATS] widget: how much of the class has taken part in a forum.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\widget;

use filter_studiolms\local\participation;
use filter_studiolms\output\bar;

/**
 * Draws the participation bar of the forum the text belongs to.
 */
class forumstats implements widget {
    #[\Override]
    public function get_id(): string {
        return 'forumstats';
    }

    #[\Override]
    public function get_marker(): string {
        return 'FORUMSTATS';
    }

    #[\Override]
    public function supports_context(\context $context): bool {
        return $this->get_cm($context) !== null;
    }

    #[\Override]
    public function render(\context $context, array $options): string {
        global $OUTPUT;

        $cm = $this->get_cm($context);
        if ($cm === null || !$cm->uservisible) {
            return '';
        }

        $groupids = participation::get_group_restriction($cm, $context);
        $counts = participation::get_counts((int) $cm->instance, $context, $groupids);
        if ($counts['total'] === 0) {
            return '';
        }

        $manager = has_capability('moodle/course:manageactivities', $context);
        $minimum = participation::get_minimum_class_size();
        $hidden = $counts['total'] < $minimum;
        if ($hidden && !$manager) {
            return '';
        }

        return $OUTPUT->render_from_template(
            'filter_studiolms/bar',
            bar::export_context($counts, $manager, $hidden ? $minimum : 0)
        );
    }

    /**
     * Returns the forum the context belongs to, as seen by the current user.
     *
     * @param \context $context The context of the text being filtered.
     * @return \cm_info|null Null when the context is not a forum.
     */
    private function get_cm(\context $context): ?\cm_info {
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return null;
        }
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return null;
        }
        $cm = get_fast_modinfo($coursecontext->instanceid)->cms[$context->instanceid] ?? null;
        return ($cm !== null && $cm->modname === 'forum') ? $cm : null;
    }
}
