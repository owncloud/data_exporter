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

use OCA\DataExporter\Command\ImportUser;
use OCA\DataExporter\Importer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportUserTest extends TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject|Importer */
	private $importer;

	/** @var CommandTester */
	private $commandTester;

	public function setUp() {
		$this->importer = $this->getMockBuilder(Importer::class)
			->disableOriginalConstructor()
			->getMock();

		$command = new ImportUser($this->importer);
		$this->commandTester = new CommandTester($command);
	}

	public function testImportReceivesCorrectArguments() {
		$this->importer->expects($this->once())
			->method('import')
			->with($this->equalTo('/tmp'), null);

		$this->commandTester->execute([
			'importDirectory' => '/tmp',
		]);
	}
}
