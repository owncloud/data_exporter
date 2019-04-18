<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\DataExporter\Tests\Unit\Importer;

use OC\Files\Cache\Cache;
use OC\Files\Node\Folder;
use OC\Files\Storage\Storage;
use OCA\DataExporter\Importer\FilesImporter;
use OCA\DataExporter\Model\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OC\Files\Node\File as FileNode;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamException;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\Filesystem\Filesystem;
use Test\TestCase;

/**
 * Class FilesImporterTest
 *
 * @package OCA\DataExporter\Tests\Unit\Importer
 */
class FilesImporterTest extends TestCase {
	/**
	 * @var Filesystem | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $filesystem;

	/**
	 * @var IRootFolder | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $rootFolder;

	/**
	 * Set up the Tests
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->filesystem = $this->createMock(Filesystem::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
	}

	/**
	 * Simple Import Test
	 *
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @throws vfsStreamException
	 *
	 * @return void
	 */
	public function testFilesImporter() {
		// User Folder
		$mockFolder1 = $this->createMock(Folder::class);
		$mockFolder1->method('getPath')->willReturn('/testuser/files');
		$mockFolder1->method('getEtag')->willReturn('123qweasdzxc');
		$mockFolder1->method('getPermissions')->willReturn(31);
		$mockFolder1->method('getType')->willReturn(Node::TYPE_FOLDER);

		// User Folder Parent
		$mockFolder2 = $this->createMock(Folder::class);
		$mockFolder2->method('getPath')->willReturn('/testuser');
		$mockFolder2->method('getEtag')->willReturn('123qweasdzxc');
		$mockFolder2->method('getPermissions')->willReturn(31);
		$mockFolder2->method('getType')->willReturn(Node::TYPE_FOLDER);

		// Test Folder
		$mockFolder3 = $this->createMock(Folder::class);
		$mockFolder3->method('getPath')->willReturn('files/AFolder');
		$mockFolder3->method('getId')->willReturn(1);
		$mockFolder3->method('getEtag')->willReturn('5bc8867cc2375');
		$mockFolder3->method('getPermissions')->willReturn(31);
		$mockFolder3->method('getType')->willReturn(Node::TYPE_FOLDER);
		$storage3 = $this->createMock(Storage::class);
		$cache = $this->createMock(Cache::class);
		// Test that the File Cache is updated for this folder
		$cache->expects($this->once())
			->method('update')
			->with(1, ['etag' => '5bc8867cc2375', 'permissions' => 31]);
		$storage3
			->expects($this->once())
			->method('getCache')->willReturn($cache);
		$mockFolder3->method('getStorage')->willReturn($storage3);

		// Test File
		$mockFile1 = $this->createMock(FileNode::class);
		$mockFile1->method('getPath')->willReturn('files/AFolder/afile.txt');
		$mockFile1->method('getId')->willReturn(2);
		$mockFile1->method('getEtag')->willReturn('533c8d4b4c45b62e68cc09e810db7a23');
		$mockFile1->method('getPermissions')->willReturn(27);
		$mockFile1->method('getType')->willReturn(Node::TYPE_FILE);
		$storageFile = $this->createMock(Storage::class);
		$cacheFile = $this->createMock(Cache::class);
		// Test that the File Cache is updated for this file
		$cacheFile->expects($this->once())
			->method('update')
			->with(2, ['etag' => '533c8d4b4c45b62e68cc09e810db7a23', 'permissions' => 27]);
		$storageFile->expects($this->once())
			->method('getCache')->willReturn($cacheFile);
		$mockFile1->method('getStorage')->willReturn($storageFile);
		$mockFile1->method('putContent')->willReturn(true);
		vfsStreamWrapper::register();
		$root = vfsStreamWrapper::setRoot(new vfsStreamDirectory('tmp'));
		$vfile = new vfsStreamFile('testuser/files/AFolder/afile.txt');
		$vfile->setContent('test');
		$root->addChild($vfile);

		$mockFolder2->method('newFolder')
			->willReturn($mockFolder3);
		$mockFolder2->method('newFile')
			->willReturn($mockFile1);

		$mockFolder1->method('getParent')->willReturn($mockFolder2);
		$this->rootFolder->method('getUserFolder')->willReturn($mockFolder1);

		$this->filesystem
			->method('exists')
			->willReturnMap(
				[
					['vfs://tmp/testuser/files/AFolder/afile.txt', true],
					['vfs://tmp/testuser/files/AFolder', true]
				]
			);
		$filesImporter = new FilesImporter(
			$this->filesystem,
			$this->rootFolder
		);
		$fileMetadata = new File();
		$fileMetadata
			->setPath('files/AFolder/afile.txt')
			->setType(File::TYPE_FILE)
			->setETag('533c8d4b4c45b62e68cc09e810db7a23')
			->setPermissions(27);
		$folderMetadata = new File();
		$folderMetadata
			->setPath('files/AFolder')
			->setType(File::TYPE_FOLDER)
			->setETag('5bc8867cc2375')
			->setPermissions(31);
		$filesMetadata = [$folderMetadata, $fileMetadata];

		$filesImporter->import(
			'testuser',
			$filesMetadata,
			'vfs://tmp/testuser'
		);
		vfsStreamWrapper::unregister();
	}
}
