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
namespace OCA\DataExporter\Tests\Unit\Importer\MetadataImporter;

use OCA\DataExporter\Importer\MetadataImporter\UserImporter;
use OCA\DataExporter\Importer\MetadataImporter\UserImporter\UpdatePasswordHashQuery;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserImporterTest extends TestCase {
	/** @var IUserManager|MockObject */
	private $userManager;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var UpdatePasswordHashQuery|MockObject */
	private $updateQuery;
	/** @var IUser|MockObject */
	private $user;
	/** @var UserImporter */
	private $importer;

	public function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->updateQuery = $this->createMock(UpdatePasswordHashQuery::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getBackendClassName')->willReturn('Database');

		$this->userManager->method('createUser')->willReturn($this->user);

		$this->importer = new UserImporter(
			$this->userManager,
			$this->groupManager,
			$this->updateQuery
		);

		$this->importer->setAllowedBackends(['Database']);
	}

	public function testPasswordHashIsUpdated() {
		$this->updateQuery->expects($this->once())->method('execute')->with(
			$this->equalTo('foobarbaz'),
			$this->equalTo('SOMEHASH')
		);

		$import = new \OCA\DataExporter\Model\User();
		$import->setUserId('foobarbaz');
		$import->setBackend('Database');
		$import->setEmail('foo@example.com');
		$import->setEnabled(true);
		$import->setDisplayName('foo');
		$import->setPasswordHash('SOMEHASH');
		$import->setGroups(['groupA', 'groupB']);

		$this->importer->import($import);
	}
}
