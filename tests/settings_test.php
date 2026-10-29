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
 * Tests of the observers that keep the cached counts fresh.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

/**
 * Tests for the admin settings of the filter.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class settings_test extends \advanced_testcase {
    /**
     * The settings page has one switch for each widget, all on, and the smallest class size that shows the bar.
     *
     * @return void
     */
    public function test_the_settings_turn_each_widget_on_and_set_the_minimum_class(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->libdir . '/adminlib.php');

        $settings = new \admin_settingpage('filtersettingstudiolms', 'StudioLMS widgets');
        $ADMIN = (object) ['fulltree' => true];
        include($CFG->dirroot . '/filter/studiolms/settings.php');

        $found = [];
        foreach ((array) $settings->settings as $setting) {
            $found[$setting->name] = $setting->get_defaultsetting();
        }
        $this->assertSame(
            ['enable_forumstats' => 1, 'enable_coursemap' => 1, 'enable_quizinfo' => 1, 'minclasssize' => 5],
            array_map(fn($value) => is_numeric($value) ? (int) $value : $value, $found)
        );

        $ADMIN = (object) ['fulltree' => false];
        $none = new \admin_settingpage('filtersettingstudiolms', 'StudioLMS widgets');
        $settings = $none;
        include($CFG->dirroot . '/filter/studiolms/settings.php');
        $this->assertCount(0, (array) $none->settings, 'without the full tree nothing is loaded');
    }
}
