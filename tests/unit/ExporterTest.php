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

use OCA\DataExporter\Exporter;
use OCA\DataExporter\Extractor\FilesExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor;
use OCA\DataExporter\Serializer;
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

	/**
	 * Set up the test
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->serializer = $this->createMock(Serializer::class);
		$this->metadataExtractor = $this->createMock(MetadataExtractor::class);
		$this->filesExtractor = $this->createMock(FilesExtractor::class);
		$this->filesystem = $this->createMock(Filesystem::class);
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
		$this->filesExtractor
			->expects($this->once())
			->method('export')
			->with('testuser', '/tmp/testuser/files');
		$this->filesystem
			->expects($this->once())
			->method('dumpFile')
			->with('/tmp/testuser/user.json');
		$exporter = new Exporter(
			$this->serializer,
			$this->metadataExtractor,
			$this->filesExtractor,
			$this->filesystem
		);

		$exporter->export('testuser', '/tmp');
	}
}
