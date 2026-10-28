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
 * Public registry of the widgets, used by the filter and by the tiny_studiolms plugin.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

use filter_studiolms\widget\forumstats;
use filter_studiolms\widget\widget;

/**
 * Registry of the widgets of the filter.
 */
class widgets {
    /** @var int Version of the marker syntax, raised whenever the syntax changes (the Studio ignores a newer one). */
    public const CONTRACT = 1;

    /**
     * Returns every widget the plugin ships, enabled or not.
     *
     * @return widget[] The widgets indexed by marker name.
     */
    public static function all(): array {
        $widgets = [new forumstats()];
        $bymarker = [];
        foreach ($widgets as $widget) {
            $bymarker[$widget->get_marker()] = $widget;
        }
        return $bymarker;
    }

    /**
     * Tells whether the administrator has the widget turned on.
     *
     * @param string $id The identifier of the widget.
     * @return bool
     */
    public static function is_enabled(string $id): bool {
        $value = get_config('filter_studiolms', 'enable_' . $id);
        // A site that has not saved the settings yet gets the default, which is on.
        return $value === false || (bool) $value;
    }

    /**
     * Returns the identifiers of the widgets turned on in the site, for the tiny_studiolms plugin.
     *
     * @return string[]
     */
    public static function enabled(): array {
        $ids = [];
        foreach (self::all() as $widget) {
            if (self::is_enabled($widget->get_id())) {
                $ids[] = $widget->get_id();
            }
        }
        return $ids;
    }
}
