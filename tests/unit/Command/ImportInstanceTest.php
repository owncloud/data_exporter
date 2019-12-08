<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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
namespace OCA\DataExporter\Tests\Unit\Command;

use OCA\DataExporter\Command\ImportInstance;
use OCA\DataExporter\InstanceImporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportInstanceTest extends TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject|InstanceImporter */
	private $importer;

	/** @var CommandTester */
	private $commandTester;

	public function setUp(): void {
		$this->importer = $this->getMockBuilder(InstanceImporter::class)
			->disableOriginalConstructor()
			->getMock();

		$command = new ImportInstance($this->importer);
		$this->commandTester = new CommandTester($command);
	}

	public function testInstanceImportReceivesCorrectArguments() {
		$this->importer->expects($this->once())
			->method('import')
			->with($this->equalTo('/tmp'));

		$this->commandTester->execute([
			'importDirectory' => '/tmp',
		]);
	}
}
