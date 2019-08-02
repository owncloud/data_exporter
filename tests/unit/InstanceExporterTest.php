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

use OCA\DataExporter\Exporter\InstanceExtractor;
use OCA\DataExporter\Instance;
use OCA\DataExporter\Model\Instance;
use OCA\DataExporter\Serializer;
use Symfony\Component\Filesystem\Filesystem;
use Test\TestCase;

class InstanceExporterTest extends TestCase {
	/**
	 * @var Serializer | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $serializer;

	/**
	 * @var InstanceExtractor | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $instanceExtractor;

	/**
	 * @var Filesystem | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $filesystem;

	/**
	 * Set up the tests
	 */
	public function setUp() {
		parent::setUp();
		$this->instanceExtractor = $this->createMock(InstanceExtractor::class);
		$this->serializer = $this->createMock(Serializer::class);
		$this->filesystem = $this->createMock(Filesystem::class);
		$this->filesystem
			->expects($this->once())
			->method('dumpFile')
			->with('/export/instancedata.json')
			->willReturn(true);
	}

	/**
	 * Test simple Instance Export
	 *
	 * @throws \Exception
	 */
	public function testSimpleExport() {
		$instanceData = new Instance();

		$this->instanceExtractor
			->expects($this->once())
			->method('extract')
			->willReturn($instanceData);
		$instanceExporter = new Instance(
			$this->serializer,
			$this->instanceExtractor,
			$this->filesystem
		);
		$instanceExporter->export('/export');
	}
}
