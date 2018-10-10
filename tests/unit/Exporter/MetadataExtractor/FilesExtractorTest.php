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
namespace OCA\DataExporter\Tests\Unit\Exporter\MetadataExtractor;

use OCA\DataExporter\Exporter\MetadataExtractor\FilesExtractor;
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCA\DataExporter\Model\User\File;
use OCP\Files\Node;
use OCP\Files\Folder;
use Test\TestCase;

class FilesExtractorTest extends TestCase {
	/** @var RecursiveNodeIteratorFactory  */
	private $iteratorFactory;

	/** @var FilesExtractor */
	private $filesExtractor;

	protected function setUp() {
		$this->iteratorFactory = $this->createMock(RecursiveNodeIteratorFactory::class);

		$this->filesExtractor = new FilesExtractor($this->iteratorFactory);
	}

	public function testExtract() {
		$mockFolder1 = $this->createMock(Node::class);
		$mockFolder1->method('getPath')->willReturn('/usertest/files/foo');
		$mockFolder1->method('getEtag')->willReturn('123qweasdzxc');
		$mockFolder1->method('getPermissions')->willReturn(31);
		$mockFolder1->method('getType')->willReturn(Node::TYPE_FOLDER);

		$mockFolder2 = $this->createMock(Node::class);
		$mockFolder2->method('getPath')->willReturn('/usertest/files/foo/courses');
		$mockFolder2->method('getEtag')->willReturn('zaqxswcde');
		$mockFolder2->method('getPermissions')->willReturn(31);
		$mockFolder2->method('getType')->willReturn(Node::TYPE_FOLDER);

		$mockFile1 = $this->createMock(Node::class);
		$mockFile1->method('getPath')->willReturn('/usertest/files/foo/courses/awesome qwerty');
		$mockFile1->method('getEtag')->willReturn('poiulkjhmnbv');
		$mockFile1->method('getPermissions')->willReturn(1);
		$mockFile1->method('getType')->willReturn(Node::TYPE_FILE);

		$mockFile2 = $this->createMock(Node::class);
		$mockFile2->method('getPath')->willReturn('/usertest/files/foo/bar.txt');
		$mockFile2->method('getEtag')->willReturn('123456789');
		$mockFile2->method('getPermissions')->willReturn(9);
		$mockFile2->method('getType')->willReturn(Node::TYPE_FILE);

		$userFolderParent = $this->createMock(Folder::class);
		$userFolderParent->method('getRelativePath')
			->will($this->returnCallback(function ($path) {
				if (\strpos($path, '/usertest/') === 0) {
					return \substr($path, \strlen('/usertest'));
				} else {
					return null;
				}
			}));

		// iterator can return an array because will just need to traverse it
		$this->iteratorFactory->method('getUserFolderParentRecursiveIterator')
			->willReturn([[$mockFolder1, $mockFolder2, $mockFile1, $mockFile2], $userFolderParent]);

		$expectedFolder1 = new File();
		$expectedFolder1->setPath('/files/foo')
			->setEtag('123qweasdzxc')
			->setPermissions(31)
			->setType(File::TYPE_FOLDER);

		$expectedFolder2 = new File();
		$expectedFolder2->setPath('/files/foo/courses')
			->setEtag('zaqxswcde')
			->setPermissions(31)
			->setType(File::TYPE_FOLDER);

		$expectedFile1 = new File();
		$expectedFile1->setPath('/files/foo/courses/awesome qwerty')
			->setEtag('poiulkjhmnbv')
			->setPermissions(1)
			->setType(File::TYPE_FILE);

		$expectedFile2 = new File();
		$expectedFile2->setPath('/files/foo/bar.txt')
			->setEtag('123456789')
			->setPermissions(9)
			->setType(File::TYPE_FILE);

		$expectedFileModels = [$expectedFolder1, $expectedFolder2, $expectedFile1, $expectedFile2];
		$currentFileModels = $this->filesExtractor->extract('usertest');
		$this->assertEquals($expectedFileModels, $currentFileModels);
	}
}
