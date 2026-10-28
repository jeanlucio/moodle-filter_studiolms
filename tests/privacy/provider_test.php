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
 * Tests for the filter_studiolms Privacy API provider.
 *
 * @package    filter_studiolms
 * @category   test
 * @copyright  2026 Jean Lúcio
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_studiolms\privacy;

use core_privacy\local\metadata\null_provider;
use core_privacy\tests\provider_testcase;

/**
 * Tests for filter_studiolms\privacy\provider.
 *
 * @covers \filter_studiolms\privacy\provider
 */
final class provider_test extends provider_testcase {
    public function test_provider_implements_null_provider_interface(): void {
        $this->assertInstanceOf(null_provider::class, new provider());
    }

    public function test_get_reason_key_resolves_to_valid_string(): void {
        $reason = provider::get_reason();
        $this->assertNotEmpty($reason);
        $this->assertNotEmpty(get_string($reason, 'filter_studiolms'));
    }
}
