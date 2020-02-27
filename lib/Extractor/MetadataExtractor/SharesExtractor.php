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

namespace OCA\DataExporter\Extractor\MetadataExtractor;

use OCA\DataExporter\Model\Share;
use OCA\DataExporter\Platform;
use OCA\DataExporter\Utilities\StreamHelper;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\Share as ShareConstants;
use OCP\Share\IManager;

class SharesExtractor {
	const FILE_NAME = 'shares.jsonl';
	/**
	 * @var IManager
	 */
	private $manager;
	/**
	 * @var IRootFolder
	 */
	private $rootFolder;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var StreamHelper
	 */
	private $streamHelper;
	/**
	 * @var resource $streamFile
	 */
	private $streamFile;

	/**
	 * @var Platform
	 */
	private $platform;

	/**
	 * SharesExtractor constructor.
	 *
	 * @param IManager $manager
	 * @param IRootFolder $rootFolder
	 * @param IConfig $config
	 * @param StreamHelper $streamHelper
	 * @param Platform $platform
	 */
	public function __construct(
		IManager $manager,
		IRootFolder $rootFolder,
		IConfig $config,
		StreamHelper $streamHelper,
		Platform $platform
	) {
		$this->manager = $manager;
		$this->rootFolder = $rootFolder;
		$this->config = $config;
		$this->streamHelper = $streamHelper;
		$this->platform = $platform;
	}

	/**
	 * @param string $userId the id of the user to extract the info from
	 * @param string $exportPath
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	public function extract($userId, $exportPath) {
		$ocVersion = $this->config->getSystemValue('version', '');
		$filename = $exportPath . '/' . $this::FILE_NAME;
		$this->streamFile = $this->streamHelper
			->initStream($filename, 'ab', true);

		$this->getUserShares($userId);
		$this->getGroupShares($userId);
		if (\version_compare($ocVersion, '10', '<') || $this->platform->getVendor() === 'nextcloud') {
			$this->getLinkShares9($userId);
		} else {
			$this->getLinkShares($userId);
		}
		$this->getRemoteShares($userId);
		$this->streamHelper->closeStream($this->streamFile);
	}

	/**
	 * @param string $userId the user id to get the shares from
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	private function getUserShares($userId) {
		$limit = 50;
		$offset = 0;
		$userFolder = $this->rootFolder->getUserFolder($userId);

		do {
			$shares = $this->manager->getSharesBy(
				$userId,
				ShareConstants::SHARE_TYPE_USER,
				null,
				true,
				$limit,
				$offset
			);
			$offset += $limit;

			foreach ($shares as $share) {
				$shareModel = new Share();
				$shareModel->setPath($userFolder->getRelativePath($share->getNode()->getPath()))
					->setShareType(Share::SHARETYPE_USER)
					->setType($share->getNodeType())
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setSharedWith($share->getSharedWith())
					->setPermissions($share->getPermissions());
				// the rest of the model attributes doesn't make sense with local shares
				$this->streamHelper->writelnToStream(
					$this->streamFile,
					$shareModel
				);
			}
		} while (\count($shares) >= $limit);
	}

	/**
	 * @param string $userId the user id to get the shares from
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	private function getGroupShares($userId) {
		$limit = 50;
		$offset = 0;
		$userFolder = $this->rootFolder->getUserFolder($userId);

		do {
			$shares = $this->manager->getSharesBy(
				$userId,
				ShareConstants::SHARE_TYPE_GROUP,
				null,
				true,
				$limit,
				$offset
			);
			$offset += $limit;

			foreach ($shares as $share) {
				$shareModel = new Share();
				$shareModel->setPath($userFolder->getRelativePath($share->getNode()->getPath()))
					->setShareType(Share::SHARETYPE_GROUP)
					->setType($share->getNodeType())
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setSharedWith($share->getSharedWith())
					->setPermissions($share->getPermissions());
				// the rest of the model attributes doesn't make sense with local shares
				$this->streamHelper->writelnToStream(
					$this->streamFile,
					$shareModel
				);
			}
		} while (\count($shares) >= $limit);
	}

	/**
	 * @param string $userId the user id to get the shares from
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	private function getLinkShares9($userId) {
		$limit = 50;
		$offset = 0;
		$userFolder = $this->rootFolder->getUserFolder($userId);

		do {
			$shares = $this->manager->getSharesBy(
				$userId,
				ShareConstants::SHARE_TYPE_LINK,
				null,
				true,
				$limit,
				$offset
			);
			$offset += $limit;

			foreach ($shares as $share) {
				$shareModel = new Share();
				$shareModel->setPath($userFolder->getRelativePath($share->getNode()->getPath()))
					->setShareType(Share::SHARETYPE_LINK)
					->setType($share->getNodeType())
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setPermissions($share->getPermissions())
					->setName('')
					->setToken($share->getToken());

				$expiration = $share->getExpirationDate();
				if ($expiration) {
					$shareModel->setExpirationDate($expiration->getTimestamp());
				}

				$password = $share->getPassword();  // the retrieved password is expected to be hashed
				if (\is_string($password)) {
					$shareModel->setPassword($password);
				}
				// the rest of the model attributes doesn't make sense with link shares
				$this->streamHelper->writelnToStream(
					$this->streamFile,
					$shareModel
				);
			}
		} while (\count($shares) >= $limit);
	}

	/**
	 * @param string $userId the user id to get the shares from
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	private function getLinkShares($userId) {
		$limit = 50;
		$offset = 0;
		$userFolder = $this->rootFolder->getUserFolder($userId);

		do {
			$shares = $this->manager->getSharesBy(
				$userId,
				ShareConstants::SHARE_TYPE_LINK,
				null,
				true,
				$limit,
				$offset
			);
			$offset += $limit;

			foreach ($shares as $share) {
				$shareModel = new Share();
				$shareModel->setPath($userFolder->getRelativePath($share->getNode()->getPath()))
					->setShareType(Share::SHARETYPE_LINK)
					->setType($share->getNodeType())
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setPermissions($share->getPermissions())
					->setName($share->getName())
					->setToken($share->getToken());

				$expiration = $share->getExpirationDate();
				if ($expiration) {
					$shareModel->setExpirationDate($expiration->getTimestamp());
				}

				$password = $share->getPassword();  // the retrieved password is expected to be hashed
				if (\is_string($password)) {
					$shareModel->setPassword($password);
				}
				// the rest of the model attributes doesn't make sense with link shares
				$this->streamHelper->writelnToStream(
					$this->streamFile,
					$shareModel
				);
			}
		} while (\count($shares) >= $limit);
	}

	/**
	 * @param string $userId the user id to get the shares from
	 *
	 * @throws NotFoundException
	 *
	 * @return void
	 */
	private function getRemoteShares($userId) {
		$limit = 50;
		$offset = 0;
		$userFolder = $this->rootFolder->getUserFolder($userId);

		do {
			$shares = $this->manager->getSharesBy(
				$userId,
				ShareConstants::SHARE_TYPE_REMOTE,
				null,
				true,
				$limit,
				$offset
			);

			$offset += $limit;

			foreach ($shares as $share) {
				$shareModel = new Share();
				$shareModel->setPath($userFolder->getRelativePath($share->getNode()->getPath()))
					->setShareType(Share::SHARETYPE_REMOTE)
					->setType($share->getNodeType())
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setSharedWith($share->getSharedWith())
					->setPermissions($share->getPermissions());
				// the rest of the model attributes doesn't make sense with remote shares
				$this->streamHelper->writelnToStream(
					$this->streamFile,
					$shareModel
				);
			}
		} while (\count($shares) >= $limit);
	}
}
