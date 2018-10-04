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

use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIterator;
use OCA\DataExporter\Utilities\Iterators\Nodes\ISkipNodeCondition;
use OCP\Files\Folder;
use OCP\Files\Node;
use Test\TestCase;

class RecursiveNodeIteratorTest extends TestCase {
	/** @var Folder */
	private $folder;

	/** RecursiveNodeIterator */
	private $iterator;

	protected function setUp() {
		$this->folder = $this->createMock(Folder::class);

		$this->iterator = new RecursiveNodeIterator($this->folder);
	}

	private function setFolderStructure() {
		$node1 = $this->createMock(Node::class);
		$node1->method('getPath')->willReturn('/foo/bar1.txt');

		$node2 = $this->createMock(Node::class);
		$node2->method('getPath')->willReturn('/foo/bar2.txt');

		$node3 = $this->createMock(Folder::class);
		$node3->method('getPath')->willReturn('/foo/bar');

		$node31 = $this->createMock(Node::class);
		$node31->method('getPath')->willReturn('/foo/bar/zzz1.png');

		$node32 = $this->createMock(Node::class);
		$node32->method('getPath')->willReturn('/foo/bar/zzz2.png');

		$node4 = $this->createMock(Folder::class);
		$node4->method('getPath')->willReturn('/foo/pom');

		$node41 = $this->createMock(Folder::class);
		$node41->method('getPath')->willReturn('/foo/pom/food');

		$node411 = $this->createMock(Node::class);
		$node411->method('getPath')->willReturn('/foo/pom/food/aaa.txt');

		$node42 = $this->createMock(Node::class);
		$node42->method('getPath')->willReturn('/foo/pom/hue.txt');

		$node41->method('getDirectoryListing')->willReturn([$node411]);
		$node4->method('getDirectoryListing')->willReturn([$node41, $node42]);
		$node3->method('getDirectoryListing')->willReturn([$node31, $node32]);
		$this->folder->method('getDirectoryListing')->willReturn([$node1, $node2, $node3, $node4]);
		$this->folder->method('getPath')->willReturn('/foo');
	}

	public function testBasicIteratorEmptyFolder() {
		$this->folder->method('getDirectoryListing')->willReturn([]);
		$expectedPathList = [];
		$currentPathList = [];
		foreach ($this->iterator as $item) {
			$currentPathList[] = $item->getPath();
		}
		$this->assertEquals($expectedPathList, $currentPathList);
	}

	public function testBasicIterator() {
		$this->setFolderStructure();

		// it won't iterate recursively
		$expectedPathList = ['/foo/bar1.txt', '/foo/bar2.txt', '/foo/bar', '/foo/pom'];
		$currentPathList = [];
		foreach ($this->iterator as $item) {
			$currentPathList[] = $item->getPath();
		}
		$this->assertEquals($expectedPathList, $currentPathList);
	}

	public function testRecursiveIterator() {
		$this->setFolderStructure();

		$expectedPathList = [
			'/foo/bar1.txt',
			'/foo/bar2.txt',
			'/foo/bar',
			'/foo/bar/zzz1.png',
			'/foo/bar/zzz2.png',
			'/foo/pom',
			'/foo/pom/food',
			'/foo/pom/food/aaa.txt',
			'/foo/pom/hue.txt'
		];
		$currentPathList = [];
		$recIterator = new \RecursiveIteratorIterator($this->iterator, \RecursiveIteratorIterator::SELF_FIRST);
		foreach ($recIterator as $item) {
			$currentPathList[] = $item->getPath();
		}
		$this->assertEquals($expectedPathList, $currentPathList);
	}

	public function testAddAndGetSkipNodeCondition() {
		$condition = $this->createMock(ISkipNodeCondition::class);

		$this->assertEmpty($this->iterator->getSkipConditions());

		$this->iterator->addSkipCondition($condition);
		$this->assertEquals([$condition], $this->iterator->getSkipConditions());
	}

	public function testClearSkipConditions() {
		$condition = $this->createMock(ISkipNodeCondition::class);

		$this->assertEmpty($this->iterator->getSkipConditions());

		$this->iterator->addSkipCondition($condition);
		$this->iterator->clearSkipConditions();

		$this->assertEmpty($this->iterator->getSkipConditions());
	}

	public function testBasicIteratorWithSkipCondition() {
		$condition = $this->createMock(ISkipNodeCondition::class);
		$condition->method('shouldSkipNode')
			->will($this->returnCallback(function (Node $node) {
				if (\strpos($node->getPath(), 'pom') !== false) {
					return true;
				}
				return false;
			}));
		$this->iterator->addSkipCondition($condition);

		$this->setFolderStructure();

		// it won't iterate recursively
		$expectedPathList = ['/foo/bar1.txt', '/foo/bar2.txt', '/foo/bar'];
		$currentPathList = [];
		foreach ($this->iterator as $item) {
			$currentPathList[] = $item->getPath();
		}
		$this->assertEquals($expectedPathList, $currentPathList);
	}

	public function testRecursiveIteratorWithSkipCondition() {
		$condition = $this->createMock(ISkipNodeCondition::class);
		$condition->method('shouldSkipNode')
			->will($this->returnCallback(function (Node $node) {
				if (\strpos($node->getPath(), 'pom') !== false || \strpos($node->getPath(), 'zzz1.png') !== false) {
					return true;
				}
				return false;
			}));
		$this->iterator->addSkipCondition($condition);

		$this->setFolderStructure();

		$expectedPathList = [
			'/foo/bar1.txt',
			'/foo/bar2.txt',
			'/foo/bar',
			'/foo/bar/zzz2.png',
		];
		$currentPathList = [];
		$recIterator = new \RecursiveIteratorIterator($this->iterator, \RecursiveIteratorIterator::SELF_FIRST);
		foreach ($recIterator as $item) {
			$currentPathList[] = $item->getPath();
		}
		$this->assertEquals($expectedPathList, $currentPathList);
	}
}
