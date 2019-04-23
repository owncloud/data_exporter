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

use OCA\DataExporter\Model\User\Share;
use OCP\IConfig;
use OCP\Share\IManager;
use OCP\Share as ShareConstants;
use OCP\Files\IRootFolder;

class SharesExtractor {
	/** @var IManager */
	private $manager;
	/** @var IRootFolder */
	private $rootFolder;
	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct(IManager $manager, IRootFolder $rootFolder, IConfig $config) {
		$this->manager = $manager;
		$this->rootFolder = $rootFolder;
		$this->config = $config;
	}

	/**
	 * @param string $userId the id of the user to extract the info from
	 * @return Share[]
	 */
	public function extract($userId) {
		$ocVersion = $this->config->getSystemValue('version', '');
		if (\version_compare($ocVersion, '10', '<')) {
			return \array_merge(
				$this->getUserShares($userId),
				$this->getGroupShares($userId),
				$this->getLinkShares9($userId),
				$this->getRemoteShares($userId)
			);
		}
		return \array_merge(
			$this->getUserShares($userId),
			$this->getGroupShares($userId),
			$this->getLinkShares($userId),
			$this->getRemoteShares($userId)
		);
	}

	/**
	 * @param string $userId the user id to get the shares from
	 * @return Share[] the list of matching share models
	 */
	private function getUserShares($userId) {
		$shareModels = [];
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
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setSharedWith($share->getSharedWith())
					->setPermissions($share->getPermissions());
				// the rest of the model attributes doesn't make sense with local shares
				$shareModels[] = $shareModel;
			}
		} while (\count($shares) >= $limit);

		return $shareModels;
	}

	/**
	 * @param string $userId the user id to get the shares from
	 * @return Share[] the list of matching share models
	 */
	private function getGroupShares($userId) {
		$shareModels = [];
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
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setSharedWith($share->getSharedWith())
					->setPermissions($share->getPermissions());
				// the rest of the model attributes doesn't make sense with local shares
				$shareModels[] = $shareModel;
			}
		} while (\count($shares) >= $limit);

		return $shareModels;
	}

	/**
	 * @param string $userId the user id to get the shares from
	 * @return Share[] the list of matching share models
	 */
	private function getLinkShares($userId) {
		$shareModels = [];
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
				$shareModels[] = $shareModel;
			}
		} while (\count($shares) >= $limit);

		return $shareModels;
	}

	/**
	 * @param string $userId the user id to get the shares from
	 * @return Share[] the list of matching share models
	 */
	private function getLinkShares9($userId) {
		$shareModels = [];
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
				$shareModels[] = $shareModel;
			}
		} while (\count($shares) >= $limit);

		return $shareModels;
	}

	/**
	 * @param string $userId the user id to get the shares from
	 * @return Share[] the list of matching share models
	 */
	private function getRemoteShares($userId) {
		$shareModels = [];
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
					->setOwner($share->getShareOwner())
					->setSharedBy($share->getSharedBy())
					->setSharedWith($share->getSharedWith())
					->setPermissions($share->getPermissions());
				// the rest of the model attributes doesn't make sense with remote shares
				$shareModels[] = $shareModel;
			}
		} while (\count($shares) >= $limit);

		return $shareModels;
	}
}
