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
 * Tests of the post install hook.
 *
 * @package    filter_studiolms
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/studiolms/db/install.php');
require_once($CFG->libdir . '/filterlib.php');

/**
 * Tests of the post install hook of the filter.
 *
 * @covers ::xmldb_filter_studiolms_install
 */
final class install_test extends \advanced_testcase {
    /**
     * A fresh install leaves the filter on, not in the default "Off, but available" state.
     *
     * @return void
     */
    public function test_install_turns_the_filter_on(): void {
        $this->resetAfterTest();
        filter_set_global_state('studiolms', TEXTFILTER_DISABLED);

        xmldb_filter_studiolms_install();

        $states = filter_get_global_states();
        $this->assertArrayHasKey('studiolms', $states);
        $this->assertEquals(TEXTFILTER_ON, $states['studiolms']->active);
    }
}
