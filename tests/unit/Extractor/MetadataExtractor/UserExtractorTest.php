<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
namespace OCA\DataExporter\Tests\Unit\Exporter\MetadataExtractor;

use OCA\DataExporter\Extractor\MetadataExtractor\UserExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor\UserExtractor\GetPasswordHashQuery;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class UserExtractorTest extends TestCase {

	/** @var UserExtractor*/
	private $userExtractor;

	/** @var IUserManager | \PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	private $groupManager;

	/** @var GetPasswordHashQuery | \PHPUnit\Framework\MockObject\MockObject   */
	private $queryMock;

	public function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$mockUser = $this->createMock(IUser::class);
		$mockUser->method('getUid')
			->willReturn('jcache');
		$mockUser->method('getEMailAddress')
			->willReturn('jcache@example.com');
		$mockUser->method('getQuota')
			->willReturn('9001');
		$mockUser->method('getBackendClassName')
			->willReturn("\SomeBackend\User");
		$mockUser->method('getDisplayName')
			->willReturn('Johnny Cache');
		$mockUser->method('isEnabled')
			->willReturn(true);
		$this->userManager
			->method('get')->willReturnMap([
				['jcache', false, $mockUser],
				['doesnotexist', false, null]
		]);

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->groupManager->method('getUserGroupIds')
			->willReturn(['admin', 'accounting']);

		$this->queryMock = $this->createMock(GetPasswordHashQuery::class);
		$this->queryMock->method('execute')
			->willReturn('1|SomeHashdeadf8832ff.345353');

		$this->userExtractor = new UserExtractor(
			$this->userManager,
			$this->groupManager,
			$this->queryMock
		);
	}

	public function testUserDataIsReadCorrectly() {
		$user = $this->userExtractor->extract('jcache');
		$this->assertEquals('jcache', $user->getUserId());
		$this->assertEquals('jcache@example.com', $user->getEmail());
		$this->assertEquals('9001', $user->getQuota());
		$this->assertEquals("\SomeBackend\User", $user->getBackend());
		$this->assertEquals('Johnny Cache', $user->getDisplayName());
		$this->assertEquals(true, $user->isEnabled());
		$this->assertEquals(['admin', 'accounting'], $user->getGroups());
	}

	/**
	 */
	public function testUnknownUser() {
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Could not extract user metadata for user \'doesnotexist\'');

		$this->userExtractor->extract('doesnotexist');
	}
}
