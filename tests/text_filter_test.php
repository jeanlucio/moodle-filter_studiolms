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
 * Tests for the filter_studiolms text filter.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms;

/**
 * Tests for filter_studiolms\text_filter.
 *
 * @covers \filter_studiolms\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    public function test_filter_leaves_plain_text_untouched(): void {
        $this->resetAfterTest();

        $filter = new text_filter(\context_system::instance(), []);

        $this->assertSame('<p>Hello</p>', $filter->filter('<p>Hello</p>'));
    }
}
