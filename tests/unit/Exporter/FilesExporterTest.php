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
namespace OCA\DataExporter\Tests\Unit\Exporter;

use OCA\DataExporter\Exporter\FilesExporter;
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCA\DataExporter\FSAccess\FSAccess;
use OCP\Files\File;
use OCP\Files\Folder;
use Test\TestCase;

class FilesExporterTest extends TestCase {
	/** @var RecursiveNodeIteratorFactory  */
	private $iteratorFactory;

	/** @var FilesExporter */
	private $filesExporter;

	/** @var FSAccess */
	private $fsAccess;

	protected function setUp() {
		$this->iteratorFactory = $this->createMock(RecursiveNodeIteratorFactory::class);

		$this->filesExporter = new FilesExporter($this->iteratorFactory);
		// FSAccess instance for the export calls
		$this->fsAccess = $this->createMock(FSAccess::class);
	}

	public function testExportFile() {
		$mockFile = $this->createMock(File::class);
		$mockFile->method('getPath')->willReturn('/usertest/files/foo/bar.txt');
		$mockFile->method('fopen')
			->will($this->returnCallback(function ($mode) {
				return \fopen('php://memory', $mode);
			}));

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

		$this->fsAccess->expects($this->once())
			->method('copyStreamToPath')
			->with($this->anything(), $this->equalTo('/files/files/foo/bar.txt'));

		$this->filesExporter->export('usertest', $this->fsAccess);
	}

	public function testExportFolder() {
		$mockFolder = $this->createMock(Folder::class);
		$mockFolder->method('getPath')->willReturn('/usertest/files/foo/courses');

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

		$this->fsAccess->expects($this->once())
			->method('mkdir')
			->with($this->equalTo('/files/files/foo/courses'));

		$this->filesExporter->export('usertest', $this->fsAccess);
	}

	public function testExportFileAndFolder() {
		$mockFolder1 = $this->createMock(Folder::class);
		$mockFolder1->method('getPath')->willReturn('/usertest/files/foo');

		$mockFolder2 = $this->createMock(Folder::class);
		$mockFolder2->method('getPath')->willReturn('/usertest/files/foo/courses');

		$mockFile1 = $this->createMock(File::class);
		$mockFile1->method('getPath')->willReturn('/usertest/files/foo/courses/awesome qwerty');
		$mockFile1->method('fopen')
			->will($this->returnCallback(function ($mode) {
				return \fopen('php://memory', $mode);
			}));

		$mockFile2 = $this->createMock(File::class);
		$mockFile2->method('getPath')->willReturn('/usertest/files/foo/bar.txt');
		$mockFile2->method('fopen')
			->will($this->returnCallback(function ($mode) {
				return \fopen('php://memory', $mode);
			}));

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

		$this->fsAccess->expects($this->exactly(2))
			->method('mkdir')
			->withConsecutive(
				[$this->equalTo('/files/files/foo')],
				[$this->equalTo('/files/files/foo/courses')]
			);

		$this->fsAccess->expects($this->exactly(2))
			->method('copyStreamToPath')
			->withConsecutive(
				[$this->anything(), $this->equalTo('/files/files/foo/courses/awesome qwerty')],
				[$this->anything(), $this->equalTo('/files/files/foo/bar.txt')]
			);

		$this->filesExporter->export('usertest', $this->fsAccess);
	}
}
