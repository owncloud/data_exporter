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

use OC\Share20\Share as PrivateShare;
use OCA\DataExporter\Importer\MetadataImporter\ShareImporter;
use OCA\DataExporter\Model\User\Share;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\Files\Node;
use OCP\Files\Folder;
use OCP\ILogger;
use OCP\Share as ShareConstants;
use OCA\DataExporter\Utilities\ShareConverter;
use Test\TestCase;

class ShareImporterTest extends TestCase {
	/** @var IManager | \PHPUnit_Framework_MockObject_MockObject */
	private $shareManager;
	/** @var IRootFolder | \PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;
	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IGroupManager | \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;
	/** @var ShareConverter | \PHPUnit_Framework_MockObject_MockObject */
	private $shareConverter;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var ShareImporter | \PHPUnit_Framework_MockObject_MockObject */
	private $shareImporter;
	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	protected function setUp() {
		$this->shareManager = $this->createMock(IManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->shareConverter = $this->createMock(ShareConverter::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->shareImporter = new ShareImporter(
			$this->shareManager,
			$this->rootFolder,
			$this->userManager,
			$this->groupManager,
			$this->shareConverter,
			$this->urlGenerator,
			$this->logger
		);
	}

	private function isSameShare(IShare $share, array $shareData) {
		return $share->getNode()->getPath() === $shareData['path'] &&
			$share->getShareType() === $shareData['type'] &&
			$share->getSharedWith() === $shareData['with'] &&
			$share->getPermissions() == $shareData['permissions'] &&
			$share->getSharedBy() === $shareData['by'] &&
			$share->getShareOwner() === $shareData['owner'];
	}

	private function isSameLinkShare(IShare $share, array $shareData) {
		$isSameDate = false;
		if ($share->getExpirationDate() && $shareData['expiration']) {
			$isSameDate = $share->getExpirationDate()->getTimestamp() === $shareData['expiration']->getTimestamp();
		}
		return $share->getNode()->getPath() === $shareData['path'] &&
			$share->getShareType() === $shareData['type'] &&
			$share->getPermissions() == $shareData['permissions'] &&
			$share->getSharedBy() === $shareData['by'] &&
			$share->getShareOwner() === $shareData['owner'] &&
			$share->getName() === $shareData['name'] &&
			$share->getPassword() === $shareData['password'] &&
			$share->getToken() === $shareData['token'] &&
			($share->getExpirationDate() === $shareData['expiration'] || $isSameDate);
	}

	private function setupRootFolderForTests() {
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
				$node->method('get')->will($this->returnCallback(function ($path) use ($userid) {
					$path = \ltrim($path, '/');
					$innerNode = $this->createMock(Node::class);
					$innerNode->method('getPath')->willReturn("/$userid/files/$path");
					return $innerNode;
				}));
				return $node;
			}));
	}

	/**
	 * Share owner in the model matches the importing userid AND the shared with user exists
	 * This should replicate the share
	 */
	public function testImportLocalUserShare() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_USER)
			->setOwner('usertest')
			->setSharedBy('usertest')
			->setSharedWith('usertest2')
			->setPermissions(1);

		$this->userManager->method('userExists')->willReturn(true);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->exactly(2))
			// expects 2 calls, one for the remote share and another one for the local one
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData1 = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_USER,
					'with' => 'usertest2',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				$shareData2 = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_REMOTE,
					'with' => 'usertest2@https://random.host/oc',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				return $this->isSameShare($share, $shareData1) || $this->isSameShare($share, $shareData2);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share owner in the model matches the importing userid AND the shared with user doesn't exists
	 * This should create a remote share targeting the remote "shared with" user in the remote server
	 */
	public function testImportRemoteUserShareFromLocalShare() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_USER)
			->setOwner('usertest')
			->setSharedBy('usertest')
			->setSharedWith('usertest2')
			->setPermissions(1);

		$this->userManager->method('userExists')->willReturn(false);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_REMOTE,
					'with' => 'usertest2@https://random.host/oc',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				return $this->isSameShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Shared with in the model matches the importing userid, the share is local
	 * The expectation is that nothing happens. We'll rely on the share to be created
	 * when the owner gets imported, or when the the migrate:share command is executed
	 * in the source server
	 */
	public function testImportLocalUserShareSharedWithTargetsUser() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_USER)
			->setOwner('usertest2')
			->setSharedBy('usertest3')
			->setSharedWith('usertest')
			->setPermissions(1);

		$this->userManager->method('userExists')->willReturn(true);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share information doesn't match the importing user. This shouldn't happen under
	 * normal conditions
	 */
	public function testImportLocalUserShareWeirdData() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_USER)
			->setOwner('usertest2')
			->setSharedBy('usertest3')
			->setSharedWith('usertest4')
			->setPermissions(1);

		$this->userManager->method('userExists')->willReturn(true);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share owner in the model matches the importing userid AND the shared with group exists
	 */
	public function testImportGroupShare() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_GROUP)
			->setOwner('usertest')
			->setSharedBy('usertest2')
			->setSharedWith('my group')
			->setPermissions(1);

		$this->groupManager->method('groupExists')->willReturn(true);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_GROUP,
					'with' => 'my group',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				return $this->isSameShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share owner in the model doesn't match the importing userid AND the shared with group exists
	 */
	public function testImportGroupShareWrongImportingUser() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_GROUP)
			->setOwner('usertest2')
			->setSharedBy('usertest2')
			->setSharedWith('my group')
			->setPermissions(1);

		$this->groupManager->method('groupExists')->willReturn(true);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share owner in the model matches the importing userid AND the shared with group doesn't exists
	 */
	public function testImportGroupShareMissingGroup() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_GROUP)
			->setOwner('usertest2')
			->setSharedBy('usertest2')
			->setSharedWith('my group')
			->setPermissions(1);

		$this->groupManager->method('groupExists')->willReturn(false);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share owner in the model matches the importing userid
	 */
	public function testImportLinkShare() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_LINK)
			->setOwner('usertest')
			->setSharedBy('usertest')
			->setName('my link name')
			->setToken('secret')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => null,
					'token' => null,
					'expiration' => null,
				];
				return $this->isSameLinkShare($share, $shareData);
			}))
			->willReturnArgument(0);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => null,
					'token' => 'secret',
					'expiration' => null,
				];
				return $this->isSameLinkShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	public function testImportLinkShareWithPassword() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_LINK)
			->setOwner('usertest')
			->setSharedBy('usertest')
			->setName('my link name')
			->setPassword('aWeSoMe')
			->setToken('secret')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => 'aWeSoMe',  // password might be hashed inside the createShare method, but not outside
					'token' => null,
					'expiration' => null,
				];
				return $this->isSameLinkShare($share, $shareData);
			}))
			->willReturnArgument(0);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => 'aWeSoMe',
					'token' => 'secret',
					'expiration' => null,
				];
				return $this->isSameLinkShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	public function testImportLinkShareWithExpiration() {
		$expiration = new \DateTime();

		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_LINK)
			->setOwner('usertest')
			->setSharedBy('usertest')
			->setName('my link name')
			->setExpirationDate($expiration->getTimestamp())
			->setToken('secret')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) use ($expiration) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'token' => null,
					'password' => null,
					'expiration' => $expiration,
				];
				return $this->isSameLinkShare($share, $shareData);
			}))
			->willReturnArgument(0);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with($this->callback(function ($share) use ($expiration) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => null,
					'token' => 'secret',
					'expiration' => $expiration,
				];
				return $this->isSameLinkShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	public function testImportLinkShareWithPasswordAndExpiration() {
		$expiration = new \DateTime();

		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_LINK)
			->setOwner('usertest')
			->setSharedBy('usertest')
			->setName('my link name')
			->setPassword('aWeSoMe')
			->setToken('secret')
			->setExpirationDate($expiration->getTimestamp())
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) use ($expiration) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => 'aWeSoMe',
					'token' => null,
					'expiration' => $expiration,
				];
				return $this->isSameLinkShare($share, $shareData);
			}))
			->willReturnArgument(0);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with($this->callback(function ($share) use ($expiration) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_LINK,
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
					'name' => 'my link name',
					'password' => 'aWeSoMe',
					'token' => 'secret',
					'expiration' => $expiration,
				];
				return $this->isSameLinkShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Share owner in the model doesn't match the importing userid
	 */
	public function testImportLinkShareWrongImportingUser() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_LINK)
			->setOwner('usertest2')
			->setSharedBy('usertest2')
			->setName('my link name')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Import a remote share targeting a different share than the current one
	 */
	public function testImportRemoteShare() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_REMOTE)
			->setOwner('usertest')
			->setSharedBy('usertest2')
			->setSharedWith('user@https://another.dif.host/oc')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->urlGenerator->method('getAbsoluteURL')->willReturn('https://current.host/oc');

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_REMOTE,
					'with' => 'user@https://another.dif.host/oc',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				return $this->isSameShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Import a remote share targeting the source server. There should't be any difference
	 */
	public function testImportRemoteShareTargetingSource() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_REMOTE)
			->setOwner('usertest')
			->setSharedBy('usertest2')
			->setSharedWith('user@https://random.host/oc')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->urlGenerator->method('getAbsoluteURL')->willReturn('https://current.host/oc');

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_REMOTE,
					'with' => 'user@https://random.host/oc',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				return $this->isSameShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Import a remote share targeting the current server. The share is converted to a local share
	 */
	public function testImportRemoteShareTargetingCurrent() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_REMOTE)
			->setOwner('usertest')
			->setSharedBy('usertest2')
			->setSharedWith('user@https://current.host/oc')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->urlGenerator->method('getAbsoluteURL')->willReturn('https://current.host/oc');

		$this->shareManager->expects($this->once())
			->method('createShare')
			->with($this->callback(function ($share) {
				$shareData = [
					'path' => '/usertest/files/path/to/file',
					'type' => ShareConstants::SHARE_TYPE_USER,
					'with' => 'user',
					'permissions' => 1,
					'by' => 'usertest',  // sharedBy overwritten for now
					'owner' => 'usertest',
				];
				return $this->isSameShare($share, $shareData);
			}));

		$this->shareImporter->import('usertest', [$shareModel], 'https://random.host/oc');
	}

	/**
	 * Import a remote share targeting the current server, but the importing user is wrong
	 */
	public function testImportRemoteShareWrongImportingUser() {
		$shareModel = new Share();
		$shareModel->setPath('/path/to/file')
			->setShareType(Share::SHARETYPE_REMOTE)
			->setOwner('usertest')
			->setSharedBy('usertest2')
			->setSharedWith('user@https://current.host/oc')
			->setPermissions(1);

		$this->setupRootFolderForTests();

		$this->shareManager->method('newShare')->will($this->returnCallback(function () {
			return new PrivateShare($this->rootFolder, $this->userManager);
		}));

		$this->urlGenerator->method('getAbsoluteURL')->willReturn('https://current.host/oc');

		$this->shareManager->expects($this->never())
			->method('createShare');

		$this->shareImporter->import('usertest2', [$shareModel], 'https://random.host/oc');
	}
}
