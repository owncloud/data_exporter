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

use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class RecursiveNodeIteratorFactoryTest extends TestCase {
	/** @var IRootFolder */
	private $rootFolder;

	/** @var Folder */
	private $userFolder;

	/** @var RecursiveNodeIteratorFactory */
	private $factory;

	protected function setUp() {
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->setUpFolderStructure();  // $this->userFolder set in this method
		$this->factory = new RecursiveNodeIteratorFactory($this->rootFolder);
	}

	private function setUpFolderStructure() {
		$storage1 = $this->createMock(IStorage::class);
		$storage1->method('getId')->willReturn('locallyStored');

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getPath')->willReturn("/usertest/files");
		$userFolder->method('getStorage')->willReturn($storage1);
		$userFolder->method('getDirectoryListing')->willReturn($this->getBaseDirectoryList($userFolder->getPath(), $storage1));

		$parentUserFolder = $this->createMock(Folder::class);
		$parentUserFolder->method('getPath')->willReturn("/usertest");
		$parentUserFolder->method('getStorage')->willReturn($storage1);
		$parentUserFolder->method('getRelativePath')
			->will($this->returnCallback(function ($path) {
				if (\strpos($path, '/usertest/') === 0) {
					return \substr($path, \strlen('/usertest'));
				} else {
					return null;
				}
			}));
		$parentUserFolderChildren = $this->getOuterDirectoryList($parentUserFolder->getPath(), $storage1);
		//include the userFolder in the list
		$parentUserFolderChildren[] = $userFolder;
		$parentUserFolder->method('getDirectoryListing')->willReturn($parentUserFolderChildren);

		// set the parent folder of the userFolder
		$userFolder->method('getParent')->willReturn($parentUserFolder);

		$this->userFolder = $userFolder;

		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);
	}

	private function getBaseDirectoryList($basePath, $storage1) {
		$storage2 = $this->createMock(IStorage::class);
		$storage2->method('getId')->willReturn('TheSpace');

		$node1 = $this->createMock(Node::class);
		$node1->method('getPath')->willReturn("$basePath/foo/bar1.txt");
		$node1->method('getStorage')->willReturn($storage1);

		$node2 = $this->createMock(Node::class);
		$node2->method('getPath')->willReturn("$basePath/foo/bar2.txt");
		$node2->method('getStorage')->willReturn($storage1);

		$node3 = $this->createMock(Folder::class);
		$node3->method('getPath')->willReturn("$basePath/foo/bar");
		$node3->method('getStorage')->willReturn($storage1);

		$node31 = $this->createMock(Node::class);
		$node31->method('getPath')->willReturn("$basePath/foo/bar/zzz1.png");
		$node31->method('getStorage')->willReturn($storage1);

		$node32 = $this->createMock(Node::class);
		$node32->method('getPath')->willReturn("$basePath/foo/bar/zzz2.png");
		$node32->method('getStorage')->willReturn($storage1);

		$node4 = $this->createMock(Folder::class);
		$node4->method('getPath')->willReturn("$basePath/foo/pom");
		$node4->method('getStorage')->willReturn($storage1);

		$node41 = $this->createMock(Folder::class);
		$node41->method('getPath')->willReturn("$basePath/foo/pom/food");
		$node41->method('getStorage')->willReturn($storage2);

		$node411 = $this->createMock(Node::class);
		$node411->method('getPath')->willReturn("$basePath/foo/pom/food/aaa.txt");
		$node411->method('getStorage')->willReturn($storage2);

		$node42 = $this->createMock(Node::class);
		$node42->method('getPath')->willReturn("$basePath/foo/pom/hue.txt");
		$node42->method('getStorage')->willReturn($storage1);

		$node41->method('getDirectoryListing')->willReturn([$node411]);
		$node4->method('getDirectoryListing')->willReturn([$node41, $node42]);
		$node3->method('getDirectoryListing')->willReturn([$node31, $node32]);

		return [$node1, $node2, $node3, $node4];
	}

	private function getOuterDirectoryList($basePath, $storage1) {
		$node1 = $this->createMock(Folder::class);
		$node1->method('getPath')->willReturn("$basePath/thumbnails");
		$node1->method('getStorage')->willReturn($storage1);

		$node2 = $this->createMock(Folder::class);
		$node2->method('getPath')->willReturn("$basePath/cache");
		$node2->method('getStorage')->willReturn($storage1);

		$node3 = $this->createMock(Folder::class);
		$node3->method('getPath')->willReturn("$basePath/files_versions");
		$node3->method('getStorage')->willReturn($storage1);

		$node31 = $this->createMock(Node::class);
		$node31->method('getPath')->willReturn("$basePath/files_versions/bar1.txt.v001");
		$node31->method('getStorage')->willReturn($storage1);

		$node3->method('getDirectoryListing')->willReturn([$node31]);
		$node2->method('getDirectoryListing')->willReturn([]);
		$node1->method('getDirectoryListing')->willReturn([]);

		return [$node1, $node2, $node3];
	}

	public function testGetUserFolderRecursiveIterator() {
		list($iterator, $userFolder) = $this->factory->getUserFolderRecursiveIterator('usertest');

		$fooFolder = "/usertest/files/foo";
		$expectedList = [
			"$fooFolder/bar1.txt" => "$fooFolder/bar1.txt",
			"$fooFolder/bar2.txt" => "$fooFolder/bar2.txt",
			"$fooFolder/bar" => "$fooFolder/bar",
			"$fooFolder/bar/zzz1.png" => "$fooFolder/bar/zzz1.png",
			"$fooFolder/bar/zzz2.png" => "$fooFolder/bar/zzz2.png",
			"$fooFolder/pom" => "$fooFolder/pom",
			"$fooFolder/pom/hue.txt" => "$fooFolder/pom/hue.txt",
		];
		$currentList = [];
		foreach ($iterator as $key => $item) {
			$currentList[$key] = $item->getPath();
		}
		$this->assertEquals($expectedList, $currentList);
		$this->assertSame($this->userFolder, $userFolder);
	}

	public function testGetUserFolderParentRecursiveIterator() {
		list($iterator, $parentUserFolder) = $this->factory->getUserFolderParentRecursiveIterator('usertest');

		$fooFolder = "/usertest/files/foo";
		$expectedList = [
			"/usertest/files_versions" => "/usertest/files_versions",
			"/usertest/files_versions/bar1.txt.v001" => "/usertest/files_versions/bar1.txt.v001",
			"/usertest/files" => "/usertest/files",
			"$fooFolder/bar1.txt" => "$fooFolder/bar1.txt",
			"$fooFolder/bar2.txt" => "$fooFolder/bar2.txt",
			"$fooFolder/bar" => "$fooFolder/bar",
			"$fooFolder/bar/zzz1.png" => "$fooFolder/bar/zzz1.png",
			"$fooFolder/bar/zzz2.png" => "$fooFolder/bar/zzz2.png",
			"$fooFolder/pom" => "$fooFolder/pom",
			"$fooFolder/pom/hue.txt" => "$fooFolder/pom/hue.txt",
		];
		$currentList = [];
		foreach ($iterator as $key => $item) {
			$currentList[$key] = $item->getPath();
		}
		$this->assertEquals($expectedList, $currentList);
		$this->assertSame($this->userFolder->getParent(), $parentUserFolder);
	}
}
