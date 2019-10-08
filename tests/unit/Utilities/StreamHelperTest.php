<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
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
namespace OCA\DataExporter\Tests\Unit\Utilities;

use OCA\DataExporter\Importer\ImportException;
use OCA\DataExporter\Model\File;
use OCA\DataExporter\Serializer;
use OCA\DataExporter\Utilities\StreamHelper;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Filesystem\Filesystem;
use Test\TestCase;

class StreamHelperTest extends TestCase {
	/**
	 * @var Filesystem
	 */
	private $filesystem;
	/**
	 * @var Serializer
	 */
	private $serializer;
	/**
	 * @var StreamHelper
	 */
	private $streamHelper;

	/**
	 * Setup Tests
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->filesystem = new Filesystem();
		$this->serializer = new Serializer();
		$this->streamHelper = new StreamHelper(
			$this->filesystem,
			$this->serializer
		);
	}

	public function testWrite() {
		$directory = [
			'testfolder' => [
				'files.jsonl' => ''
			]
		];
		$virtualFilesystem  = vfsStream::setup('testroot', '644', $directory);
		$fileModel = new File();
		$fileModel->setPath('files/welcome.txt');
		$fileModel->setType('file');
		$fileModel->setPermissions(31);
		$fileModel->setETag('234s34ser234');
		$fileModel->setMtime(1565267598);

		$fileModel2 = new File();
		$fileModel2->setPath('files/someFolder');
		$fileModel2->setType('folder');
		$fileModel2->setPermissions(31);
		$fileModel2->setETag('234s34ser234');
		$fileModel2->setMtime(1565267598);
		$resource = $this->streamHelper->initStream($virtualFilesystem->url() . '/testfolder/files.jsonl', 'a');
		$this->streamHelper->writelnToStream($resource, $fileModel);
		$this->streamHelper->writelnToStream($resource, $fileModel2);
		$this->streamHelper->closeStream($resource);
		$writtenData = \file_get_contents($virtualFilesystem->url() . '/testfolder/files.jsonl');
		$expectedData = <<< JSONL
{"type":"file","id":null,"path":"files\/welcome.txt","eTag":"234s34ser234","permissions":31,"mtime":1565267598}
{"type":"folder","id":null,"path":"files\/someFolder","eTag":"234s34ser234","permissions":31,"mtime":1565267598}

JSONL;
		$this->assertEquals($expectedData, $writtenData);
	}

	public function testRead() {
		$fileContent = <<< JSONL
{"type":"file","path":"files\/welcome.txt","eTag":"234s34ser234","permissions":31,"mtime":1565267598}
{"type":"folder","path":"files\/someFolder","eTag":"234s34ser234","permissions":31,"mtime":1565267598}

JSONL;
		$directory = [
			'testfolder' => [
				'files.jsonl' => $fileContent
			]
		];
		$expectedFileModel1 = new File();
		$expectedFileModel1->setPath('files/welcome.txt');
		$expectedFileModel1->setType('file');
		$expectedFileModel1->setPermissions(31);
		$expectedFileModel1->setETag('234s34ser234');
		$expectedFileModel1->setMtime(1565267598);

		$expectedFileModel2 = new File();
		$expectedFileModel2->setPath('files/someFolder');
		$expectedFileModel2->setType('folder');
		$expectedFileModel2->setPermissions(31);
		$expectedFileModel2->setETag('234s34ser234');
		$expectedFileModel2->setMtime(1565267598);

		$virtualFilesystem  = vfsStream::setup('testroot', '644', $directory);
		$resource = $this->streamHelper->initStream($virtualFilesystem->url(). '/testfolder/files.jsonl', 'r');

		$fileModel1 = $this->streamHelper->readlnFromStream($resource, File::class);
		$fileModel2 = $this->streamHelper->readlnFromStream($resource, File::class);

		$this->assertEquals($expectedFileModel1, $fileModel1);
		$this->assertEquals($expectedFileModel2, $fileModel2);
		$this->assertFalse($this->streamHelper->readlnFromStream($resource, File::class));
		$this->streamHelper->closeStream($resource);
	}

	/**
	 * @expectedException \OCA\DataExporter\Importer\ImportException
	 *
	 * @return void
	 */
	public function testReadCorruptedFile() {
		$fileContent = <<< JSONL
{"type":"file","path":"files\/welcome.txt","eTag":"234s34ser234","permissions":31,"mtime":1565267598}
{"type":"folder","path:"files\/someFolder","eTag":"234s34ser234","permissions":31,"mtime":1565267598}
{"type":"folder","path":"files\/someFolder","eTag":"234s34ser234","permissions":31,"mtime":1565267598}

JSONL;
		$directory = [
			'testfolder' => [
				'files.jsonl' => $fileContent
			]
		];
		$expectedFileModel1 = new File();
		$expectedFileModel1->setPath('files/welcome.txt');
		$expectedFileModel1->setType('file');
		$expectedFileModel1->setPermissions(31);
		$expectedFileModel1->setETag('234s34ser234');
		$expectedFileModel1->setMtime(1565267598);

		$virtualFilesystem  = vfsStream::setup('testroot', '644', $directory);
		$resource = $this->streamHelper->initStream(
			$virtualFilesystem->url() . '/testfolder/files.jsonl',
			'r'
		);

		$fileModel1 = $this->streamHelper->readlnFromStream($resource, File::class);
		$this->assertEquals($expectedFileModel1, $fileModel1);
		$this->streamHelper->readlnFromStream($resource, File::class);
	}
}
