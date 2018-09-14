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
namespace OCA\DataExporter\Tests\Unit\Utilities;

use OC\Share20\Share as PrivateShare;
use OCA\DataExporter\Utilities\ShareConverter;
use OCP\Share\IManager;
use OCP\Share as ShareConstants;
use OCP\Share\IShare;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUserManager;
use Test\TestCase;

class ShareConverterTest extends TestCase {
	/** @var IManager */
	private $shareManager;
	/** @var ShareConverter */
	private $shareConverter;

	protected function setUp() {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);

		$rootFolder = $this->createMock(IRootFolder::class);
		$userManager = $this->createMock(IUserManager::class);
		$this->shareManager->method('newShare')->will($this->returnCallback(function () use ($rootFolder, $userManager) {
			return new PrivateShare($rootFolder, $userManager);
		}));

		$this->shareConverter = new ShareConverter($this->shareManager);
	}

	private function getFakeShare(
		string $path,
		int $shareType,
		string $owner,
		string $sharedBy,
		string $sharedWith,
		int $permissions
	) {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn($path);

		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($node);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getShareOwner')->willReturn($owner);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getPermissions')->willReturn($permissions);
		return $share;
	}

	private function isSameShare(IShare $share, array $shareData) {
		return $share->getNode()->getPath() === $shareData['path'] &&
			$share->getShareType() === $shareData['type'] &&
			$share->getSharedWith() === $shareData['with'] &&
			$share->getPermissions() == $shareData['permissions'] &&
			$share->getSharedBy() === $shareData['by'] &&
			$share->getShareOwner() === $shareData['owner'];
	}

	/**
	 * Convert a remote share to a local share. This is expected to create a new
	 * local share based on the remote one. No share deletion will be done
	 */
	public function testConvertRemoteUserShareToLocalUserShare() {
		$share1 = $this->getFakeShare(
			'/usertest2/files/path/to/file',
			ShareConstants::SHARE_TYPE_REMOTE,
			'usertest2',
			'usertest2',
			'usertest@https://current.host/oc',
			1
		);
		$share2 = $this->getFakeShare(
			'/usertest2/files/path/to/file2',
			ShareConstants::SHARE_TYPE_REMOTE,
			'usertest3',
			'usertest2',
			'usertest@https://current.host/oc',
			31
		);

		$shareList = [$share1, $share2];

		$this->shareManager->method('getSharedWith')->willReturn($shareList);

		$this->shareManager->expects($this->exactly(\count($shareList)))
			->method('createShare')
			->withConsecutive(
				[
					$this->callback(function ($newShare) {
						$shareData = [
							'path' => '/usertest2/files/path/to/file',
							'type' => ShareConstants::SHARE_TYPE_USER,
							'with' => 'usertest',
							'permissions' => 1,
							'by' => 'usertest2',
							'owner' => 'usertest2',
						];
						return $this->isSameShare($newShare, $shareData);
					})
				],
				[
					$this->callback(function ($newShare) {
						$shareData = [
							'path' => '/usertest2/files/path/to/file2',
							'type' => ShareConstants::SHARE_TYPE_USER,
							'with' => 'usertest',
							'permissions' => 31,
							'by' => 'usertest2',
							'owner' => 'usertest3',
						];
						return $this->isSameShare($newShare, $shareData);
					})
				]
			);

		$this->shareManager->expects($this->never())
			->method('deleteShare');
		// targetRemoteHost is only used to fetch the shareWith list, which is mocked
		$this->shareConverter->convertRemoteUserShareToLocalUserShare('usertest', 'https://remote.host/');
	}

	public function testConvertRemoteUserShareToLocalUserShareNoData() {
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareManager->expects($this->never())
			->method('deleteShare');

		$this->shareConverter->convertRemoteUserShareToLocalUserShare('usertest', 'https://remote.host/');
	}

	/**
	 * Convert a local share into a remote one. This is expected just to create the remote share
	 * based on the local share, but it won't delete the remote share
	 */
	public function testConvertLocalUserShareToRemoteUserShare() {
		$share1 = $this->getFakeShare(
			'/usertest2/files/path/to/file',
			ShareConstants::SHARE_TYPE_USER,
			'usertest2',
			'usertest2',
			'usertest',
			1
		);
		$share2 = $this->getFakeShare(
			'/usertest2/files/path/to/file2',
			ShareConstants::SHARE_TYPE_USER,
			'usertest3',
			'usertest2',
			'usertest',
			31
		);

		$shareList = [$share1, $share2];

		$this->shareManager->method('getSharedWith')->willReturn($shareList);

		$this->shareManager->expects($this->exactly(\count($shareList)))
			->method('createShare')
			->withConsecutive(
				[
					$this->callback(function ($newShare) {
						$shareData = [
							'path' => '/usertest2/files/path/to/file',
							'type' => ShareConstants::SHARE_TYPE_REMOTE,
							'with' => 'usertest@https://remote.host/oc',
							'permissions' => 1,
							'by' => 'usertest2',
							'owner' => 'usertest2',
						];
						return $this->isSameShare($newShare, $shareData);
					})
				],
				[
					$this->callback(function ($newShare) {
						$shareData = [
							'path' => '/usertest2/files/path/to/file2',
							'type' => ShareConstants::SHARE_TYPE_REMOTE,
							'with' => 'usertest@https://remote.host/oc',
							'permissions' => 31,
							'by' => 'usertest2',
							'owner' => 'usertest3',
						];
						return $this->isSameShare($newShare, $shareData);
					})
				]
			);

		$this->shareManager->expects($this->never())
			->method('deleteShare');
			
		// targetRemoteHost is only used to fetch the shareWith list, which is mocked
		$this->shareConverter->convertLocalUserShareToRemoteUserShare('usertest', 'https://remote.host/oc');
	}

	public function testConvertLocalUserShareToRemoteUserShareNoData() {
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareManager->expects($this->never())
			->method('deleteShare');

		$this->shareConverter->convertLocalUserShareToRemoteUserShare('usertest', 'https://remote.host/oc');
	}
}
