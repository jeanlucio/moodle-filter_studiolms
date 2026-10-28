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
 * Text filter of the StudioLMS widgets: swaps each known marker for its widget.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

use filter_studiolms\local\marker_options;

/**
 * Dispatcher of the filter: finds the markers and hands each one to its widget.
 */
class text_filter extends \core_filters\text_filter {
    #[\Override]
    public function filter($text, array $options = []) {
        if (!is_string($text) || strpos($text, '[') === false) {
            return $text;
        }
        return $this->replace_markers($text, true);
    }

    #[\Override]
    public function filter_stage_string(string $text, array $options): string {
        // A name or title is plain text, so a marker there is only removed.
        if (strpos($text, '[') === false) {
            return $text;
        }
        return $this->replace_markers($text, false);
    }

    /**
     * Replaces every known marker, with or without the span the Studio writes around it.
     *
     * Whatever follows the name inside the brackets is read option by option; what is not a valid option is dropped,
     * so a mistyped marker still disappears instead of showing on the page.
     *
     * @param string $text The text being filtered.
     * @param bool $draw False to remove the markers without drawing any widget.
     * @return string
     */
    private function replace_markers(string $text, bool $draw): string {
        $widgets = widgets::all();
        $names = implode('|', array_map('preg_quote', array_keys($widgets)));
        $pattern = '/(<span class="slms-widget-marker">\s*)?\[(' . $names . ')'
            . '((?:\s[^\]<]{0,200})?)\](?(1)\s*<\/span>)/';

        return preg_replace_callback($pattern, function (array $matches) use ($widgets, $draw): string {
            $widget = $widgets[$matches[2]];
            $usable = $draw && isloggedin() && !isguestuser() && widgets::is_enabled($widget->get_id())
                && $widget->supports_context($this->context);
            if (!$usable) {
                return '';
            }
            return $widget->render($this->context, marker_options::parse($matches[3]));
        }, $text);
    }
}
