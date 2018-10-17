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
namespace OCA\DataExporter\Tests\Unit\Utilities\FSAccess;

use org\bovigo\vfs\vfsStream;
use OCA\DataExporter\Utilities\FSAccess\FSAccess;
use Test\TestCase;

class FSAccessTest extends TestCase {
	private $openedFiles = [];

	private $fsaccess;

	private $baseVfsDir;

	public function setUp() {
		$this->openedFiles = [];
		$vfsRoot = vfsStream::setup('root');
		$prefix = \uniqid();
		$this->baseVfsDir = vfsStream::newDirectory($prefix)->at($vfsRoot);
		$this->fsaccess = new FSAccess($this->baseVfsDir->url());
	}

	public function tearDown() {
		foreach ($this->openedFiles as $openedFile) {
			\fclose($openedFile);
		}
	}

	public function testGetRoot() {
		$this->assertEquals($this->baseVfsDir->url(), $this->fsaccess->getRoot());
	}

	public function mkdirSimpleDataProvider() {
		return [
			['/test1'],
			['test2'],
		];
	}

	/**
	 * @dataProvider mkdirSimpleDataProvider
	 */
	public function testMkdirSimple($dir) {
		$base = $this->baseVfsDir->url();
		$this->assertTrue($this->fsaccess->mkdir($dir));

		$this->assertTrue(\file_exists("$base/$dir"));
		$this->assertTrue(\is_dir("$base/$dir"));
	}

	public function testMkdirRecursive() {
		$base = $this->baseVfsDir->url();
		$this->assertTrue($this->fsaccess->mkdir('/test2/test3/test4'));

		$this->assertTrue(\file_exists("$base/test2"));
		$this->assertTrue(\file_exists("$base/test2/test3"));
		$this->assertTrue(\file_exists("$base/test2/test3/test4"));
		$this->assertTrue(\is_dir("$base/test2"));
		$this->assertTrue(\is_dir("$base/test2/test3"));
		$this->assertTrue(\is_dir("$base/test2/test3/test4"));
	}

	public function testMkdirCannotCreateWrongPermissions() {
		$this->baseVfsDir->chmod(0444);
		$this->assertFalse($this->fsaccess->mkdir('/test2/test3/test4'));
	}

	public function testFileExistsRoot() {
		$this->assertTrue($this->fsaccess->fileExists('/'));
		$this->assertTrue($this->fsaccess->fileExists(''));
	}

	public function testFileExistsNewFiles() {
		$base = $this->baseVfsDir->url();
		$file = vfsStream::newFile('new_file.txt')->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$folder = vfsStream::newDirectory('foo')->at($this->baseVfsDir);

		$this->assertTrue($this->fsaccess->fileExists('/new_file.txt'));
		$this->assertTrue($this->fsaccess->fileExists('foo'));
	}

	public function testGetStream() {
		$base = $this->baseVfsDir->url();
		\file_put_contents("$base/content1.txt", "my tailor is rich");

		$stream = $this->fsaccess->getStream('/content1.txt');
		$fileContents = \stream_get_contents($stream);
		$this->assertEquals('my tailor is rich', $fileContents);
	}

	public function testGetStreamMissingFile() {
		$this->assertFalse($this->fsaccess->getStream('/missingFile000.egg'));
	}

	public function testCopyContentToPath() {
		$base = $this->baseVfsDir->url();
		$content = 'This might not be random enough';

		$this->assertEquals(\strlen($content), $this->fsaccess->copyContentToPath($content, '/copiedContent.txt'));
		$this->assertEquals($content, \file_get_contents("$base/copiedContent.txt"));
	}

	public function testCopyContentToPathRecursive() {
		$base = $this->baseVfsDir->url();
		$content = 'This might not be random enough';

		$this->assertEquals(\strlen($content), $this->fsaccess->copyContentToPath($content, '/foo/bar/copiedContent.txt'));
		$this->assertEquals($content, \file_get_contents("$base/foo/bar/copiedContent.txt"));
	}

	public function testCopyContentToPathRecursiveWrongPermissions() {
		$this->baseVfsDir->chmod(0444);
		$base = $this->baseVfsDir->url();
		$content = 'This might not be random enough';

		$this->assertFalse($this->fsaccess->copyContentToPath($content, '/foo/bar/copiedContent.txt'));
	}

	public function testCopyContentToPathRecursiveWrongPermissionsNotRecursive() {
		$this->baseVfsDir->chmod(0444);
		$base = $this->baseVfsDir->url();
		$content = 'This might not be random enough';

		$this->assertFalse($this->fsaccess->copyContentToPath($content, '/copiedContent.txt'));
	}

	public function testGetContentFromPath() {
		$base = $this->baseVfsDir->url();
		$content = 'This might not be random enough';
		\file_put_contents("$base/tmpfile.txt", $content);

		$this->assertEquals($content, $this->fsaccess->GetContentFromPath('/tmpfile.txt'));
	}

	public function testGetContentFromPathMissingFile() {
		$this->assertFalse($this->fsaccess->GetContentFromPath('/missing_tmpfile.txt'));
	}

	public function testGetContentFromPathWrongPermissions() {
		$base = $this->baseVfsDir->url();
		$content = 'This might not be random enough';
		\file_put_contents("$base/tmpfile.txt", $content);
		\chmod("$base/tmpfile.txt", 0333);

		$this->assertFalse($this->fsaccess->GetContentFromPath('/tmpfile.txt'));
	}

	public function copyStreamToPathDataProvider() {
		return [
			['/another_file.txt'],
			['another_file.txt']
		];
	}

	/**
	 * @dataProvider copyStreamToPathDataProvider
	 */
	public function testCopyStreamToPathSimple($filename) {
		$base = $this->baseVfsDir->url();
		$file = vfsStream::newFile('new_file.txt')->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$fileStream = \fopen($file->url(), 'rb');
		$this->openedFiles[] = $fileStream;

		$this->assertEquals(\strlen($content), $this->fsaccess->copyStreamToPath($fileStream, $filename));
		$this->assertTrue(\file_exists("$base/$filename"));
		$this->assertEquals($content, \file_get_contents("$base/$filename"));
	}

	public function testCopyStreamToPathWithRecursiveCreation() {
		$base = $this->baseVfsDir->url();
		$file = vfsStream::newFile('new_file2.txt')->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$fileStream = \fopen($file->url(), 'rb');
		$this->openedFiles[] = $fileStream;

		$this->assertEquals(\strlen($content), $this->fsaccess->copyStreamToPath($fileStream, '/foo1/foo2/foo3/another_file.txt'));
		$this->assertTrue(\file_exists("$base/foo1/foo2/foo3/another_file.txt"));
		$this->assertEquals($content, \file_get_contents("$base/foo1/foo2/foo3/another_file.txt"));
	}

	public function testCopyStreamToPathWrong() {
		$file = vfsStream::newFile('new_file2.txt')->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$fileStream = \fopen($file->url(), 'rb');
		$this->openedFiles[] = $fileStream;

		$this->baseVfsDir->chmod(0444);

		$this->assertFalse($this->fsaccess->copyStreamToPath($fileStream, '/another_file.txt'));
	}

	public function testCopyStreamToPathWrongRecursiveCreation() {
		$file = vfsStream::newFile('new_file2.txt')->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$fileStream = \fopen($file->url(), 'rb');
		$this->openedFiles[] = $fileStream;

		$this->baseVfsDir->chmod(0444);

		$this->assertFalse($this->fsaccess->copyStreamToPath($fileStream, '/foo1/foo2/foo3/another_file.txt'));
	}

	public function copyPathToStreamDataProvider() {
		return [
			['/new_file.txt'],
			['new_file.txt']
		];
	}

	/**
	 * @dataProvider copyPathToStreamDataProvider
	 */
	public function testCopyPathToStreamSimple($filename) {
		$base = $this->baseVfsDir->url();
		$file = vfsStream::newFile(\ltrim($filename, '/'))->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$copiedFile = vfsStream::newFile('copied_file.txt')->at($this->baseVfsDir);

		$fileStream = \fopen($copiedFile->url(), 'wb');
		$this->openedFiles[] = $fileStream;

		$this->assertEquals(\strlen($content), $this->fsaccess->copyPathToStream($filename, $fileStream));

		$this->assertTrue(\file_exists("$base/copied_file.txt"));
		$this->assertEquals($content, \file_get_contents("$base/copied_file.txt"));
	}

	public function testCopyPathToStreamWrong() {
		$file = vfsStream::newFile('new_file.txt')->at($this->baseVfsDir);
		$content = 'The not-so-random content for the file';
		$file->setContent($content);

		$file->chmod(0300);

		$copiedFile = vfsStream::newFile('copied_file.txt')->at($this->baseVfsDir);

		$fileStream = \fopen($copiedFile->url(), 'wb');
		$this->openedFiles[] = $fileStream;

		$this->assertFalse($this->fsaccess->copyPathToStream('/new_file.txt', $fileStream));
	}
}
