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

use OCA\DataExporter\Importer\InstanceDataImporter;
use OCA\DataExporter\InstanceImporter;
use OCA\DataExporter\Serializer;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use org\bovigo\vfs\vfsStreamWrapper;
use Symfony\Component\Filesystem\Filesystem;
use Test\TestCase;

/**
 * Class InstanceImporterTest
 *
 * @package OCA\DataExporter\Tests\Unit
 */
class InstanceImporterTest extends TestCase {
	/**
	 * @var InstanceDataImporter | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $instanceDataImporter;

	/**
	 * @var Serializer | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $serializer;

	/**
	 * @var Filesystem | \PHPUnit_Framework_MockObject_MockObject
	 */
	private $filesystem;

	/**
	 * Setup the tests
	 *
	 * @throws \org\bovigo\vfs\vfsStreamException
	 */
	public function setUp(): void {
		parent::setUp();
		$this->instanceDataImporter = $this->createMock(InstanceDataImporter::class);
		$this->serializer = $this->createMock(Serializer::class);
		$this->filesystem = $this->createMock(Filesystem::class);

		$this->filesystem
			->method('exists')
			->willReturnMap(
				[
					['vfs://instance/instancedata.json', false],
					['vfs://root/instancedata.json', true]
				]
			);

		vfsStreamWrapper::register();
		$root = vfsStreamWrapper::setRoot(new vfsStreamDirectory('root'));
		$vfile = new vfsStreamFile('instancedata.json');
		$vfile->setContent('test');
		$root->addChild($vfile);
	}

	/**
	 */
	public function testImportFileNotValidPath() {
		$this->expectException(\OCA\DataExporter\Importer\ImportException::class);

		$instanceImporter = new InstanceImporter(
			$this->serializer,
			$this->instanceDataImporter,
			$this->filesystem
		);
		$instanceImporter->import('vfs://instance');
	}

	/**
	 * Test Simple import
	 */
	public function testImportFile() {
		$instanceImporter = new InstanceImporter(
			$this->serializer,
			$this->instanceDataImporter,
			$this->filesystem
		);
		$this->instanceDataImporter
			->expects($this->once())
			->method('import');
		$instanceImporter->import('vfs://root');
	}
}
