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
namespace OCA\DataExporter\Tests\Unit\Extractor\MetadataExtractor;

use OCA\DataExporter\Extractor\MetadataExtractor\FilesMetadataExtractor;
use OCA\DataExporter\Model\File;
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCA\DataExporter\Utilities\StreamHelper;
use OCP\Files\Folder;
use OCP\Files\Node;
use org\bovigo\vfs\vfsStream;
use Test\TestCase;

class FilesMetadataExtractorTest extends TestCase {
	/**
	 * @var RecursiveNodeIteratorFactory  | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $iteratorFactory;
	/**
	 * @var FilesMetadataExtractor | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $filesMetadataExtractor;
	/**
	 * @var StreamHelper | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $streamHelper;

	protected function setUp() {
		$this->iteratorFactory = $this->createMock(RecursiveNodeIteratorFactory::class);
		$this->streamHelper = $this->createMock(StreamHelper::class);

		$this->filesMetadataExtractor = new FilesMetadataExtractor($this->iteratorFactory, $this->streamHelper);
	}

	public function testExtract() {
		$directoryTree = [
			'testexport' => [
				'usertest' => [
					'files.jsonl' => ''
				]
			]
		];
		$fileSystem = vfsStream::setup('testroot', '644', $directoryTree);
		$resource = \fopen($fileSystem->url() . '/testexport/usertest/files.jsonl', 'ab');
		$mockFolder1 = $this->createMock(Node::class);
		$mockFolder1->method('getPath')->willReturn('/usertest/files/foo');
		$mockFolder1->method('getEtag')->willReturn('123qweasdzxc');
		$mockFolder1->method('getMTime')->willReturn(1565074220);
		$mockFolder1->method('getPermissions')->willReturn(31);
		$mockFolder1->method('getType')->willReturn(Node::TYPE_FOLDER);

		$mockFolder2 = $this->createMock(Node::class);
		$mockFolder2->method('getPath')->willReturn('/usertest/files/foo/courses');
		$mockFolder2->method('getEtag')->willReturn('zaqxswcde');
		$mockFolder2->method('getMTime')->willReturn(1565074223);
		$mockFolder2->method('getPermissions')->willReturn(31);
		$mockFolder2->method('getType')->willReturn(Node::TYPE_FOLDER);

		$mockFile1 = $this->createMock(Node::class);
		$mockFile1->method('getPath')->willReturn('/usertest/files/foo/courses/awesome qwerty');
		$mockFile1->method('getEtag')->willReturn('poiulkjhmnbv');
		$mockFile1->method('getMTime')->willReturn(1565074221);
		$mockFile1->method('getPermissions')->willReturn(1);
		$mockFile1->method('getType')->willReturn(Node::TYPE_FILE);

		$mockFile2 = $this->createMock(Node::class);
		$mockFile2->method('getPath')->willReturn('/usertest/files/foo/bar.txt');
		$mockFile2->method('getEtag')->willReturn('123456789');
		$mockFile2->method('getMTime')->willReturn(1565074120);
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
			->setMtime(1565074220)
			->setPermissions(31)
			->setType(File::TYPE_FOLDER);

		$expectedFolder2 = new File();
		$expectedFolder2->setPath('/files/foo/courses')
			->setEtag('zaqxswcde')
			->setMtime(1565074223)
			->setPermissions(31)
			->setType(File::TYPE_FOLDER);

		$expectedFile1 = new File();
		$expectedFile1->setPath('/files/foo/courses/awesome qwerty')
			->setEtag('poiulkjhmnbv')
			->setMtime(1565074221)
			->setPermissions(1)
			->setType(File::TYPE_FILE);

		$expectedFile2 = new File();
		$expectedFile2->setPath('/files/foo/bar.txt')
			->setEtag('123456789')
			->setMtime(1565074120)
			->setPermissions(9)
			->setType(File::TYPE_FILE);

		$this->streamHelper
			->expects($this->exactly(4))
			->method('writelnToStream')
			->withConsecutive(
				[$resource, $expectedFolder1],
				[$resource, $expectedFolder2],
				[$resource, $expectedFile1],
				[$resource, $expectedFile2]
			);
		$this->streamHelper
			->expects($this->once())
			->method('initStream')
			->willReturn($resource);

		$this->streamHelper
			->expects($this->once())
			->method('closeStream');

		$this->filesMetadataExtractor->extract('usertest', '/usertest');
	}
}
