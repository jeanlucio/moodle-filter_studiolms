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
 * Contract of a widget the filter can draw in place of a marker.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\widget;

/**
 * A widget is one marker (such as FORUMSTATS) and the code that draws it.
 */
interface widget {
    /**
     * Returns the identifier of the widget, which is also the suffix of its enable_<id> admin setting.
     *
     * @return string
     */
    public function get_id(): string;

    /**
     * Returns the marker name written between square brackets, in capitals.
     *
     * @return string
     */
    public function get_marker(): string;

    /**
     * Tells whether the widget can be drawn in this context (the marker is removed elsewhere).
     *
     * @param \context $context The context of the text being filtered.
     * @return bool
     */
    public function supports_context(\context $context): bool;

    /**
     * Draws the widget for the current user.
     *
     * @param \context $context The context of the text being filtered.
     * @param array $options The options of the marker, already validated by the dispatcher.
     * @return string The HTML of the widget, or an empty string when nothing is to be shown.
     */
    public function render(\context $context, array $options): string;
}
