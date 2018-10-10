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
namespace OCA\DataExporter\Tests\Unit\Exporter\MetadataExtractor;

use OCA\DataExporter\Exporter\MetadataExtractor\SharesExtractor;
use OCA\DataExporter\Model\UserMetadata\User\Share;
use OCP\Share\IShare;
use OCP\Share as ShareConstants;
use OCP\Share\IManager;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Folder;
use Test\TestCase;

class SharesExtractorTest extends TestCase {
	/** @var SharesExtractor */
	private $sharesExtractor;

	/** @var IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $manager;
	/** @var IRootFolder | \PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	protected function setUp() {
		parent::setUp();

		$this->manager = $this->createMock(IManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->sharesExtractor = new SharesExtractor($this->manager, $this->rootFolder);
	}

	private function getFakeShare(string $path, string $owner, string $sharedBy, string $sharedWith, int $permissions) {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn($path);

		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($node);
		$share->method('getShareOwner')->willReturn($owner);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getPermissions')->willReturn($permissions);
		return $share;
	}

	private function getFakeLinkShare(string $path, string $owner, string $sharedBy, string $permissions, string $name, $expiration, $password) {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn($path);

		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($node);
		$share->method('getShareOwner')->willReturn($owner);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getName')->willReturn($name);
		if ($expiration) {
			$share->method('getExpirationDate')->willReturn($expiration);
		}
		if ($password) {
			$share->method('getPassword')->willReturn($password);
		}
		return $share;
	}

	public function testExtract() {
		$user = 'usertest';
		$userShare1 = $this->getFakeShare('/usertest/files/path/to/file', 'usertest', 'usertest', 'usertest2', 1);
		$userShare2 = $this->getFakeShare('/usertest/files/path/to/file', 'usertest', 'initiator', 'usertest2', 1);
		$groupShare1 = $this->getFakeShare('/usertest/files/path/to/file', 'usertest', 'initiator', 'group', 31);
		$linkShare1 = $this->getFakeLinkShare('/usertest/files/path/to/file', 'usertest', 'initiator', 31, 'my link name', null, null);
		$remoteShare1 = $this->getFakeShare('/usertest/files/path/to/file', 'usertest', 'initiator', 'user@remote', 1);

		$this->manager->method('getSharesBy')
			->will($this->returnValueMap([
				[$user, ShareConstants::SHARE_TYPE_USER, null, true, 50, 0, [$userShare1, $userShare2]],
				[$user, ShareConstants::SHARE_TYPE_GROUP, null, true, 50, 0, [$groupShare1]],
				[$user, ShareConstants::SHARE_TYPE_LINK, null, true, 50, 0, [$linkShare1]],
				[$user, ShareConstants::SHARE_TYPE_REMOTE, null, true, 50, 0, [$remoteShare1]],
			]));

		$this->rootFolder->method('getUserFolder')
			->will($this->returnCallback(function ($userid) {
				$node = $this->createMock(Folder::class);
				$node->method('getPath')->willReturn("/$userid/files");
				$node->method('getRelativePath')->will($this->returnCallback(function ($path) use ($userid) {
					if (\strpos($path, "/$userid/files/") !== 0) {
						return null;
					} else {
						return \substr($path, \strlen("/$userid/files"));
					}
				}));
				return $node;
			}));

		$realModels = $this->sharesExtractor->extract($user);

		$expectedShareModels = [
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_USER)
				->setOwner('usertest')
				->setSharedBy('usertest')
				->setSharedWith('usertest2')
				->setPermissions(1),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_USER)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setSharedWith('usertest2')
				->setPermissions(1),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_GROUP)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setSharedWith('group')
				->setPermissions(31),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_LINK)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setPermissions(31)
				->setName('my link name'),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_REMOTE)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setSharedWith('user@remote')
				->setPermissions(1),
		];

		$this->assertEquals(\count($expectedShareModels), \count($realModels));

		foreach ($expectedShareModels as $key => $expectedShareModel) {
			$this->assertEquals($expectedShareModel->getPath(), $realModels[$key]->getPath());
			$this->assertEquals($expectedShareModel->getShareType(), $realModels[$key]->getShareType());
			$this->assertEquals($expectedShareModel->getOwner(), $realModels[$key]->getOwner());
			$this->assertEquals($expectedShareModel->getSharedBy(), $realModels[$key]->getSharedBy());
			$this->assertEquals($expectedShareModel->getSharedWith(), $realModels[$key]->getSharedWith());
			$this->assertEquals($expectedShareModel->getPermissions(), $realModels[$key]->getPermissions());
			$this->assertEquals($expectedShareModel->getExpirationDate(), $realModels[$key]->getExpirationDate());
			$this->assertEquals($expectedShareModel->getPassword(), $realModels[$key]->getPassword());
			$this->assertEquals($expectedShareModel->getName(), $realModels[$key]->getName());
		}
	}

	public function testExtractNoData() {
		$user = 'usertest';

		$this->manager->method('getSharesBy')
			->will($this->returnValueMap([
				[$user, ShareConstants::SHARE_TYPE_USER, null, true, 50, 0, []],
				[$user, ShareConstants::SHARE_TYPE_GROUP, null, true, 50, 0, []],
				[$user, ShareConstants::SHARE_TYPE_LINK, null, true, 50, 0, []],
				[$user, ShareConstants::SHARE_TYPE_REMOTE, null, true, 50, 0, []],
			]));

		$realModels = $this->sharesExtractor->extract($user);

		$this->assertEmpty($realModels);
	}

	public function testExtractLinksWithExpirationAndPassword() {
		$user = 'usertest';
		$testDateTime = new \DateTime();
		$testDateTime->setTimestamp(12345678);

		$linkShare1 = $this->getFakeLinkShare('/usertest/files/path/to/file', 'usertest', 'initiator', 31, 'my link name', null, null);
		$linkShare2 = $this->getFakeLinkShare('/usertest/files/path/to/file', 'usertest', 'initiator', 31, 'my link name', $testDateTime, null);
		$linkShare3 = $this->getFakeLinkShare('/usertest/files/path/to/file', 'usertest', 'initiator', 31, 'my link name', null, 'hashed#Password');
		$linkShare4 = $this->getFakeLinkShare('/usertest/files/path/to/file', 'usertest', 'initiator', 31, 'my link name', $testDateTime, 'hashed#Password');

		$this->manager->method('getSharesBy')
			->will($this->returnValueMap([
				[$user, ShareConstants::SHARE_TYPE_USER, null, true, 50, 0, []],
				[$user, ShareConstants::SHARE_TYPE_GROUP, null, true, 50, 0, []],
				[$user, ShareConstants::SHARE_TYPE_LINK, null, true, 50, 0, [$linkShare1, $linkShare2, $linkShare3, $linkShare4]],
				[$user, ShareConstants::SHARE_TYPE_REMOTE, null, true, 50, 0, []],
			]));

		$this->rootFolder->method('getUserFolder')
			->will($this->returnCallback(function ($userid) {
				$node = $this->createMock(Folder::class);
				$node->method('getPath')->willReturn("/$userid/files");
				$node->method('getRelativePath')->will($this->returnCallback(function ($path) use ($userid) {
					if (\strpos($path, "/$userid/files/") !== 0) {
						return null;
					} else {
						return \substr($path, \strlen("/$userid/files"));
					}
				}));
				return $node;
			}));

		$realModels = $this->sharesExtractor->extract($user);

		$expectedShareModels = [
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_LINK)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setPermissions(31)
				->setName('my link name'),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_LINK)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setPermissions(31)
				->setName('my link name')
				->setExpirationDate(12345678),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_LINK)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setPermissions(31)
				->setName('my link name')
				->setPassword('hashed#Password'),
			(new Share())
				->setPath('/path/to/file')
				->setShareType(SHARE::SHARETYPE_LINK)
				->setOwner('usertest')
				->setSharedBy('initiator')
				->setPermissions(31)
				->setName('my link name')
				->setExpirationDate(12345678)
				->setPassword('hashed#Password'),
		];

		$this->assertEquals(\count($expectedShareModels), \count($realModels));

		foreach ($expectedShareModels as $key => $expectedShareModel) {
			$this->assertEquals($expectedShareModel->getPath(), $realModels[$key]->getPath());
			$this->assertEquals($expectedShareModel->getShareType(), $realModels[$key]->getShareType());
			$this->assertEquals($expectedShareModel->getOwner(), $realModels[$key]->getOwner());
			$this->assertEquals($expectedShareModel->getSharedBy(), $realModels[$key]->getSharedBy());
			$this->assertEquals($expectedShareModel->getSharedWith(), $realModels[$key]->getSharedWith());
			$this->assertEquals($expectedShareModel->getPermissions(), $realModels[$key]->getPermissions());
			$this->assertEquals($expectedShareModel->getExpirationDate(), $realModels[$key]->getExpirationDate());
			$this->assertEquals($expectedShareModel->getPassword(), $realModels[$key]->getPassword());
			$this->assertEquals($expectedShareModel->getName(), $realModels[$key]->getName());
		}
	}
}
