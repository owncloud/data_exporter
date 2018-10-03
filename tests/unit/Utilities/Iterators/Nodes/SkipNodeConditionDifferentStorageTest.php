<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgeargroup.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
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
namespace OCA\DataExporter\Tests\Unit\Utilities\Iterators\Nodes;

use OCA\DataExporter\Utilities\Iterators\Nodes\SkipNodeConditionDifferentStorage;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class SkipNodeConditionDifferentStorageTest extends TestCase {
	public function testShouldSkipNodeIsFile() {
		$storageId = 'dummystorage';

		$node = $this->createMock(File::class);

		// files won't be skipped
		$skipCondition = new SkipNodeConditionDifferentStorage('dummystorage');
		$this->assertFalse($skipCondition->shouldSkipNode($node));
	}

	public function testShouldSkipNodeIsFolderSameStorage() {
		$storageId = 'dummystorage';

		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('dummystorage');

		$node = $this->createMock(Folder::class);
		$node->method('getStorage')->willReturn($storage);

		// files won't be skipped
		$skipCondition = new SkipNodeConditionDifferentStorage('dummystorage');
		$this->assertFalse($skipCondition->shouldSkipNode($node));
	}

	public function testShouldSkipNodeIsFolderDifferentStorage() {
		$storageId = 'dummystorage';

		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('non-dummystorage');

		$node = $this->createMock(Folder::class);
		$node->method('getStorage')->willReturn($storage);

		// files won't be skipped
		$skipCondition = new SkipNodeConditionDifferentStorage('dummystorage');
		$this->assertTrue($skipCondition->shouldSkipNode($node));
	}
}