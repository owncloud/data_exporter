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
namespace OCA\DataExporter\Tests\Unit\Extractor;

use OCA\DataExporter\Extractor\FilesExtractor;
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use Symfony\Component\Filesystem\Filesystem;
use OCP\Files\File;
use OCP\Files\Folder;
use Test\TestCase;

class FilesExtractorTest extends TestCase {
	/** @var RecursiveNodeIteratorFactory  */
	private $iteratorFactory;

	/** @var Filesystem */
	private $filesystem;

	/** @var FilesExporter */
	private $filesExporter;

	protected function setUp() {
		$this->iteratorFactory = $this->createMock(RecursiveNodeIteratorFactory::class);
		$this->filesystem = $this->createMock(Filesystem::class);

		$this->filesExporter = new FilesExtractor($this->iteratorFactory, $this->filesystem);
	}

	public function testExportFile() {
		$mockFile = $this->createMock(File::class);
		$mockFile->method('getPath')->willReturn('/usertest/files/foo/bar.txt');
		$mockFile->method('getMTime')->willReturn(1565074220);
		$stream = \fopen('php://memory', 'r+');
		\fwrite($stream, "Somecontent");
		\rewind($stream);
		$mockFile->method('fopen')->willReturn($stream);

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
		$this->iteratorFactory->method('getUserFolderParentRecursiveIterator')->willReturn([[$mockFile], $userFolderParent]);

		$this->filesExporter->export('usertest', '/tmp/randomF');
		$content = \file_get_contents('/tmp/randomF/files/foo/bar.txt');
		$this->assertEquals('Somecontent', $content);
	}

	public function testExportFolder() {
		$mockFolder = $this->createMock(Folder::class);
		$mockFolder->method('getPath')->willReturn('/usertest/files/foo/courses');
		$mockFolder->method('getMTime')->willReturn(1565074220);

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
		$this->iteratorFactory->method('getUserFolderParentRecursiveIterator')->willReturn([[$mockFolder], $userFolderParent]);

		$this->filesystem->expects($this->once())
			->method('mkdir')
			->with($this->equalTo('/tmp/randomF/files/foo/courses'));

		$this->filesExporter->export('usertest', '/tmp/randomF');
	}

	public function testExportFileAndFolder() {
		$mockFolder1 = $this->createMock(Folder::class);
		$mockFolder1->method('getPath')->willReturn('/usertest/files/foo');
		$mockFolder1->method('getMTime')->willReturn(1565074232);

		$mockFolder2 = $this->createMock(Folder::class);
		$mockFolder2->method('getPath')->willReturn('/usertest/files/foo/courses');
		$mockFolder2->method('getMTime')->willReturn(1565074200);

		$mockFile1 = $this->createMock(File::class);
		$mockFile1->method('getPath')->willReturn('/usertest/files/foo/courses/awesome qwerty');
		$mockFile1->method('getMTime')->willReturn(1565074520);
		$stream1 = \fopen('php://memory', 'r+');
		\fwrite($stream1, "Somecontent");
		\rewind($stream1);
		$mockFile1->method('fopen')->willReturn($stream1);

		$mockFile2 = $this->createMock(File::class);
		$mockFile2->method('getPath')->willReturn('/usertest/files/foo/bar.txt');
		$mockFile2->method('getMTime')->willReturn(1565074221);
		$stream2 = \fopen('php://memory', 'r+');
		\fwrite($stream2, "Somecontent");
		\rewind($stream2);
		$mockFile2->method('fopen')->willReturn($stream2);

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

		$this->filesystem->expects($this->exactly(2))
			->method('mkdir')
			->withConsecutive(
				[$this->equalTo('/tmp/randomF/files/foo')],
				[$this->equalTo('/tmp/randomF/files/foo/courses')]
			);

		$this->filesExporter->export('usertest', '/tmp/randomF');
	}
}
