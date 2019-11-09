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

namespace OCA\DataExporter\Tests\Unit;

use OC\Files\Node\Folder;
use OCA\DataExporter\Exporter;
use OCA\DataExporter\Extractor\FilesExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor;
use OCA\DataExporter\Serializer;
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use Symfony\Component\Filesystem\Filesystem;
use Test\TestCase;

/**
 * Class ExporterTest
 *
 * @package OCA\DataExporter\Tests\Unit
 */
class ExporterTest extends TestCase {
	/** @var Serializer | \PHPUnit_Framework_MockObject_MockObject */
	private $serializer;
	/** @var MetadataExtractor | \PHPUnit_Framework_MockObject_MockObject  */
	private $metadataExtractor;
	/** @var FilesExtractor | \PHPUnit_Framework_MockObject_MockObject  */
	private $filesExtractor;
	/** @var Filesystem | \PHPUnit_Framework_MockObject_MockObject  */
	private $filesystem;
	/** @var RecursiveNodeIteratorFactory| \PHPUnit_Framework_MockObject_MockObject  */
	private $iteratorFactory;

	/**
	 * Set up the test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->serializer = $this->createMock(Serializer::class);
		$this->metadataExtractor = $this->createMock(MetadataExtractor::class);
		$this->filesExtractor = $this->createMock(FilesExtractor::class);
		$this->filesystem = $this->createMock(Filesystem::class);
		$this->iteratorFactory = $this->createMock(RecursiveNodeIteratorFactory::class);
	}

	/**
	 * Test Exporter
	 *
	 * @return void
	 */
	public function testExporter() {
		$this->serializer
			->expects($this->once())
			->method('serialize');
		$this->metadataExtractor
			->expects($this->once())
			->method('extract')
			->with('testuser');

		$iter = [new \ArrayIterator([]), $this->createMock(Folder::class)];
		$this->filesExtractor
			->expects($this->exactly(2))
			->method('export')->withConsecutive(
				[$iter[0], $iter[1], '/tmp/testuser/files'],
				[$iter[0], $iter[1], '/tmp/testuser/files_trashbin']
			);
		$this->filesystem
			->expects($this->once())
			->method('dumpFile')
			->with('/tmp/testuser/user.json');
		$this->iteratorFactory
			->method('getUserFolderRecursiveIterator')
			->willReturn($iter);
		$this->iteratorFactory
			->method('getTrashBinRecursiveIterator')
			->willReturn($iter);
		$exporter = new Exporter(
			$this->serializer,
			$this->metadataExtractor,
			$this->filesExtractor,
			$this->filesystem,
			$this->iteratorFactory
		);

		$exporter->export('testuser', '/tmp', ['trashBinAvailable' => true]);
	}
}
