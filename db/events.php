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
 * Event observers of filter_studiolms.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Observing mod_forum is observing a core component, which needs no declared dependency.
$observers = [];
foreach (['post_created', 'post_deleted', 'discussion_created', 'discussion_deleted'] as $name) {
    $observers[] = [
        'eventname' => '\\mod_forum\\event\\' . $name,
        'callback' => '\\filter_studiolms\\observer::forum_changed',
    ];
}
$observers[] = [
    'eventname' => '\\mod_forum\\event\\discussion_moved',
    'callback' => '\\filter_studiolms\\observer::discussion_moved',
];
