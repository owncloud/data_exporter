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

namespace OCA\DataExporter\Importer\MetadataImporter;

use OCA\DataExporter\Importer\ImportException;
use OCA\DataExporter\Model\Share;
use OCA\DataExporter\Utilities\ShareConverter;
use OCA\DataExporter\Utilities\StreamHelper;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Share as ShareConstants;
use OCP\Share\IManager;

class ShareImporter {
	public const FILE_NAME = 'shares.jsonl';
	/** @var IManager */
	private $shareManager;
	/** @var IRootFolder */
	private $rootFolder;
	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var ShareConverter */
	private $shareConverter;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ILogger */
	private $logger;
	/**
	 * @var StreamHelper
	 */
	private $streamHelper;
	/**
	 * @var resource
	 */
	private $streamFile;
	/**
	 * @var int
	 */
	private $currentLine;

	/**
	 * ShareImporter constructor.
	 *
	 * @param IManager $shareManager
	 * @param IRootFolder $rootFolder
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param ShareConverter $shareConverter
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 * @param StreamHelper $streamHelper
	 */
	public function __construct(
		IManager $shareManager,
		IRootFolder $rootFolder,
		IUserManager $userManager,
		IGroupManager $groupManager,
		ShareConverter $shareConverter,
		IURLGenerator $urlGenerator,
		ILogger $logger,
		StreamHelper $streamHelper
	) {
		$this->shareManager = $shareManager;
		$this->rootFolder = $rootFolder;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->shareConverter = $shareConverter;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		$this->streamHelper = $streamHelper;
	}

	/**
	 * Cases not considered:
	 * - local user share whose "shared with" matches the imported user:
	 *     either created when importing the share owner or handled with migrate:share command
	 * - remote share targeting the importing server, but missing "shared with" user (might has been deleted after the share creation)
	 *     this shouldn't happen, it will likely crash.
	 *
	 * @param string $userId the importing user Id
	 * @param string $exportPath the share models to be imported
	 * @param string $targetRemoteHost the exporting host where the shares come from.
	 * This is the origin of the shares, in case we need to convert some share
	 * to remote shares.
	 *
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in any
	 * share model. Note that this will abort the process and might leave the importing account in a bad state.
	 *
	 * @return void
	 */
	public function import($userId, $exportPath, $targetRemoteHost) {
		$currentHostUrl = \rtrim($this->urlGenerator->getAbsoluteURL('/'), '/');
		$filename = $exportPath . '/' . $this::FILE_NAME;
		$this->streamFile = $this
			->streamHelper
			->initStream($filename, 'rb');
		$this->currentLine = 0;

		try {
			while ((
				/**
				 * @var Share $shareModel
				 */
				$shareModel = $this->streamHelper->readlnFromStream(
					$this->streamFile,
					Share::class
				)
			) !== false
			) {
				switch ($shareModel->getShareType()) {
					case Share::SHARETYPE_USER:
						if ($shareModel->getOwner() === $userId) {
							$this->createRemoteUserShareFromLocalUserShare($shareModel, $targetRemoteHost);
							if ($this->userManager->userExists($shareModel->getSharedWith())) {
								$this->createLocalUserShareFromLocalUserShare($shareModel);
							}
						} else {
							$this->logger->info('Ignoring user share {path} . The owner {owner} does not match {user}', [
								'app' => 'data_exporter',
								'path' => $shareModel->getPath(),
								'owner' => $shareModel->getOwner(),
								'user' => $userId
							]);
						}
						break;
					case Share::SHARETYPE_GROUP:
						// check if the group exists before trying to create the share
						if ($shareModel->getOwner() === $userId) {
							if ($this->groupManager->groupExists($shareModel->getSharedWith())) {
								$this->createLocalGroupShareFromLocalGroupShare($shareModel);
							} else {
								$this->logger->info('Ignoring group share {path} . Target group {group} missing', [
									'app' => 'data_exporter',
									'path' => $shareModel->getPath(),
									'group' => $shareModel->getSharedWith()
								]);
							}
						} else {
							$this->logger->info('Ignoring group share {path} . The owner {owner} does not match {user}', [
								'app' => 'data_exporter',
								'path' => $shareModel->getPath(),
								'owner' => $shareModel->getOwner(),
								'user' => $userId
							]);
						}
						break;
					case Share::SHARETYPE_LINK:
						if ($shareModel->getOwner() === $userId) {
							$this->createLinkShareFromLinkShare($shareModel);
						} else {
							$this->logger->info('Ignoring link share {path} . The owner {owner} does not match {user}', [
								'app' => 'data_exporter',
								'path' => $shareModel->getPath(),
								'owner' => $shareModel->getOwner(),
								'user' => $userId
							]);
						}
						break;
					case Share::SHARETYPE_REMOTE:
						if ($shareModel->getOwner() === $userId) {
							$pieces = \explode('@', $shareModel->getSharedWith());
							$sharedWithRemoteHost = $pieces[\count($pieces) - 1];
							if ($sharedWithRemoteHost === $currentHostUrl) {
								$this->createLocalUserShareFromRemoteUserShare($shareModel);
							} else {
								$this->createRemoteUserShareFromRemoteUserShare($shareModel);
							}
						} else {
							$this->logger->info('Ignoring remote share {path} . The owner {owner} does not match {user}', [
								'app' => 'data_exporter',
								'path' => $shareModel->getPath(),
								'owner' => $shareModel->getOwner(),
								'user' => $userId
							]);
						}
						break;
				}
				$this->currentLine++;
			}
		} catch (ImportException $exception) {
			$message = $exception->getMessage();
			$line = $this->currentLine + 1;
			throw new ImportException("Import failed on $filename on line $line: $message");
		}
		// the userId exists as it has been imported (we shouldn't reach here otherwise)
		$this->shareConverter->convertRemoteUserShareToLocalUserShare($userId, $targetRemoteHost);
		$this->streamHelper->checkEndOfStream($this->streamFile);
		$this->streamHelper->closeStream($this->streamFile);
	}

	/**
	 * @param Share $shareModel the model to be used in order to create the share
	 * @param string $targetRemoteHost the remote ownCloud server to be used for the remote share,
	 * for example, "https://my.server:8080/owncloud"
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in the share model.
	 */
	private function createRemoteUserShareFromLocalUserShare(Share $shareModel, string $targetRemoteHost) {
		// find the node id of the share
		$userId = $shareModel->getOwner();
		$userFolder = $this->rootFolder->getUserFolder($userId);
		// userFolder should exists because the user information should have been imported at this point
		$node = $userFolder->get($shareModel->getPath());  // this might throw an OCP\Files\NotFoundException

		$remoteUser = \rtrim($shareModel->getSharedWith() . "@{$targetRemoteHost}", '/');
		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setShareType(ShareConstants::SHARE_TYPE_REMOTE)
			->setSharedWith($remoteUser)// sharedWith existence was checked before
			->setPermissions($shareModel->getPermissions())
			->setSharedBy($shareModel->getOwner())// Override the initiator with the owner to avoid problems
			->setShareOwner($shareModel->getOwner());

		$this->shareManager->createShare($share);
	}

	/**
	 * @param Share $shareModel the model to be used in order to create the share
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in the share model.
	 */
	private function createLocalUserShareFromLocalUserShare(Share $shareModel) {
		// find the node id of the share
		$userId = $shareModel->getOwner();
		$userFolder = $this->rootFolder->getUserFolder($userId);
		// userFolder should exists because the user information should have been imported at this point
		$node = $userFolder->get($shareModel->getPath());  // this might throw an OCP\Files\NotFoundException

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setShareType(ShareConstants::SHARE_TYPE_USER)
			->setSharedWith($shareModel->getSharedWith())// sharedWith existence was checked before
			->setPermissions($shareModel->getPermissions())
			->setSharedBy($shareModel->getOwner())// Override the initiator with the owner to avoid problems
			->setShareOwner($shareModel->getOwner());

		try {
			$this->shareManager->createShare($share);
		} catch (\Exception $e) {
			// FIXME: REALLY needs better detection, but core doesn't provide
			// anything better
			if ($e->getMessage() !== 'Path already shared with this user') {
				// rethrow the exception if creation didn't fail due to the share was created already
				throw $e;
			} else {
				$this->logger->debug('Ignoring shared path {path}', [
					'app' => 'data_exporter',
					'path' => $shareModel->getPath()
				]);
			}
		}
	}

	/**
	 * @param Share $shareModel the model to be used in order to create the share
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in the share model.
	 */
	private function createLocalGroupShareFromLocalGroupShare(Share $shareModel) {
		// we assume the target group exists in the new server
		// find the node id of the share
		$userId = $shareModel->getOwner();
		$userFolder = $this->rootFolder->getUserFolder($userId);
		// userFolder should exists because the user information should have been imported at this point
		$node = $userFolder->get($shareModel->getPath());  // this might throw an OCP\Files\NotFoundException

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setShareType(ShareConstants::SHARE_TYPE_GROUP)
			->setSharedWith($shareModel->getSharedWith())// sharedWith existence was checked before
			->setPermissions($shareModel->getPermissions())
			->setSharedBy($shareModel->getOwner())// Override the initiator with the owner to avoid problems
			->setShareOwner($shareModel->getOwner());

		$this->shareManager->createShare($share);
	}

	/**
	 * @param Share $shareModel the model to be used in order to create the share
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in the share model.
	 */
	private function createLinkShareFromLinkShare(Share $shareModel) {
		// find the node id of the share
		$userId = $shareModel->getOwner();
		$userFolder = $this->rootFolder->getUserFolder($userId);
		// userFolder should exists because the user information should have been imported at this point
		$node = $userFolder->get($shareModel->getPath());  // this might throw an OCP\Files\NotFoundException

		$share = $this->shareManager->newShare();
		$share->setShouldHashPassword(false);
		$share->setNode($node)
			->setShareType(ShareConstants::SHARE_TYPE_LINK)
			->setPermissions($shareModel->getPermissions())
			->setSharedBy($shareModel->getOwner())// Override the initiator with the owner to avoid problems
			->setShareOwner($shareModel->getOwner())
			->setName($shareModel->getName())
			->setPassword($shareModel->getPassword());

		if ($shareModel->getExpirationDate()) {
			$datetime = new \DateTime();
			$datetime->setTimestamp($shareModel->getExpirationDate());
			$share->setExpirationDate($datetime);
		}

		$share = $this->shareManager->createShare($share);
		// CreateShare sets a new token, so update the share after creating.
		$share->setToken($shareModel->getToken());
		$this->shareManager->updateShare($share);
	}

	/**
	 * @param Share $shareModel the model to be used in order to create the share
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in the share model.
	 */
	private function createLocalUserShareFromRemoteUserShare(Share $shareModel) {
		// find the node id of the share
		$userId = $shareModel->getOwner();
		$userFolder = $this->rootFolder->getUserFolder($userId);
		// userFolder should exists because the user information should have been imported at this point
		$node = $userFolder->get($shareModel->getPath());  // this might throw an OCP\Files\NotFoundException

		$remoteUser = $shareModel->getSharedWith();
		$localUser = \implode('@', \explode('@', $remoteUser, -1));

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setShareType(ShareConstants::SHARE_TYPE_USER)
			->setSharedWith($localUser)
			->setPermissions($shareModel->getPermissions())
			->setSharedBy($shareModel->getOwner())// Override the initiator with the owner to avoid problems
			->setShareOwner($shareModel->getOwner());

		try {
			$this->shareManager->createShare($share);
		} catch (\Exception $e) {
			// FIXME: REALLY needs better detection, but core doesn't provide
			// anything better
			if ($e->getMessage() !== 'Path already shared with this user') {
				// rethrow the exception if creation didn't fail due to the share was created already
				throw $e;
			} else {
				$this->logger->debug('Ignoring shared path {path}', [
					'app' => 'data_exporter',
					'path' => $shareModel->getPath()
				]);
			}
		}
	}

	/**
	 * @param Share $shareModel the model to be used in order to create the share
	 * @throws \OCP\Files\NotFoundException if there is no node matching the path in the share model.
	 */
	private function createRemoteUserShareFromRemoteUserShare(Share $shareModel) {
		// find the node id of the share
		$userId = $shareModel->getOwner();
		$userFolder = $this->rootFolder->getUserFolder($userId);
		// userFolder should exists because the user information should have been imported at this point
		$node = $userFolder->get($shareModel->getPath());  // this might throw an OCP\Files\NotFoundException

		$share = $this->shareManager->newShare();
		$share->setNode($node)
			->setShareType(ShareConstants::SHARE_TYPE_REMOTE)
			->setSharedWith($shareModel->getSharedWith())
			->setPermissions($shareModel->getPermissions())
			->setSharedBy($shareModel->getOwner())// Override the initiator with the owner to avoid problems
			->setShareOwner($shareModel->getOwner());

		$this->shareManager->createShare($share);
	}
}
