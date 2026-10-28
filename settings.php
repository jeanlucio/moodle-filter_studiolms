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
 * Admin settings of filter_studiolms.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'filter_studiolms/enable_forumstats',
        new lang_string('enable_forumstats', 'filter_studiolms'),
        new lang_string('enable_forumstats_desc', 'filter_studiolms'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'filter_studiolms/minclasssize',
        new lang_string('minclasssize', 'filter_studiolms'),
        new lang_string('minclasssize_desc', 'filter_studiolms'),
        5,
        PARAM_INT
    ));
}
