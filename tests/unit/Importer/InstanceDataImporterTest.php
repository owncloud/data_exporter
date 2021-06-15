<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

use OCA\DataExporter\Importer\InstanceDataImporter;
use OCA\DataExporter\Model\Instance;
use OCP\IConfig;
use OCP\IGroupManager;
use Test\TestCase;

class InstanceDataImporterTest extends TestCase {

	/** @var InstanceDataImporter */
	private $importer;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;

	public function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->groupManager = $this->getMockBuilder(IGroupManager::class)->getMock();
		$this->importer = new InstanceDataImporter(
			$this->config,
			$this->groupManager
		);
	}

	public function testImportSetsConfig() {
		$instance = new Instance();
		$instance->setSecret('secret')
			->setInstanceId('someid')
			->setPasswordSalt('somesalt');

		$this->config->expects($this->once())
			->method('setSystemValues')
			->with($this->equalTo(
				[
					'instanceid' => 'someid',
					'passwordsalt' => 'somesalt',
					'secret' => 'secret'
				]
			));

		$this->importer->import($instance);
	}

	public function testImportCreatesGroups() {
		$instance = new Instance();
		$instance->setGroups(['g1', 'g2']);

		$this->groupManager->expects($this->exactly(2))
			->method('createGroup')
			->withConsecutive(
				[$this->equalTo('g1')],
				[$this->equalTo('g2')]
			);

		$this->importer->import($instance);
	}
}
