<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General,
 * Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */
namespace OCA\DataExporter\Tests\Unit\Utilities;

use OCA\DataExporter\Utilities\Path;
use Test\TestCase;

class PathTest extends TestCase {
	public function joinProvider() {
		return [
			[['/a', 'b', 'c/'], '/a/b/c/'],
			[['/a', 'b', 'c'], '/a/b/c'],
			[['a', 'b', 'c'], 'a/b/c'],
			[['', 'a', '', 'b', '', '', 'c'], 'a/b/c'],

			[['//a/', '/b/', '/c/'], '/a/b/c/'],
			[['/a', 'b', 'c//d'], '/a/b/c/d'],
			[['a', 'b', 'c'], 'a/b/c'],

			[['vfs://a', '/b', '/c'], 'vfs://a/b/c'],

			[['vfs:a', '/b', '/c'], 'vfs:a/b/c'],
			[['vfs:/a', '/b', '/c'], 'vfs:/a/b/c'],

			[['', '', ''], '']
		];
	}

	/**
	 * @dataProvider joinProvider
	 */
	public function testJoin(array $input, $expectedResult) {
		$actual = \call_user_func_array(Path::class . '::join', $input);
		$this->assertEquals($expectedResult, $actual);
	}
}
