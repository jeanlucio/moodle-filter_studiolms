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
 * Reads the options written inside a marker.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\local;

/**
 * Parser of the options of a marker, which come from text anyone can edit.
 */
class marker_options {
    /**
     * Parses the part of a marker after its name, such as ` style=tiles label="a b"`.
     *
     * An unquoted value holds only a-z, 0-9, underscore and hyphen; a quoted one holds anything but a double quote.
     * Anything that does not fit is left out, so the caller falls back to the default of the option.
     *
     * @param string $raw The text between the marker name and the closing bracket.
     * @return array The options by name, with string values.
     */
    public static function parse(string $raw): array {
        $options = [];
        $pattern = '/\s+([a-z]+)=(?:"([^"]*)"|([a-z0-9_-]+))/';
        if (preg_match_all($pattern, $raw, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $options[$match[1]] = $match[3] ?? $match[2];
            }
        }
        return $options;
    }
}
