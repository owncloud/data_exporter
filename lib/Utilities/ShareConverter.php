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
namespace OCA\DataExporter\Utilities;

use OCP\Share\IManager;
use OCP\Share as ShareConstants;

class ShareConverter {
	/** @var IManager */
	private $shareManager;

	public function __construct(IManager $shareManager) {
		$this->shareManager = $shareManager;
	}

	/**
	 * Convert the remote shares targeting the $userId in the $targetRemoteHost
	 * into local shares targeting $userId.
	 * This function will only create new local shares, but it won't delete any share
	 *
	 * Note that we have to check if the $userId exists in the local server
	 * before calling this method because such check won't be performed here.
	 * Share creation might crash badly if the $userId doesn't exist locally
	 *
	 * @param string $userId the local user id
	 * @param string $targetRemoteHost the remote ownCloud server (https://my.server:8888/owncloud)
	 */
	public function convertRemoteUserShareToLocalUserShare($userId, $targetRemoteHost) {
		$remoteUser = \rtrim("{$userId}@{$targetRemoteHost}", '/');  // the remote user isn't stored with the last slash
		$limit = 50;
		$offset = 0;

		do {
			//get the remote shares targeting the remote user
			$shares = $this->shareManager->getSharedWith(
				$remoteUser,
				ShareConstants::SHARE_TYPE_REMOTE,
				null,
				$limit,
				$offset
			);
			$offset += $limit;

			foreach ($shares as $share) {
				// foreach of the found shares, compare with the matching models
				// and delete the share if needed
				$newShare = $this->shareManager->newShare();
				$newShare->setNode($share->getNode())
					->setShareType(ShareConstants::SHARE_TYPE_USER)
					->setSharedWith($userId)  // sharedWith existence was checked before
					->setPermissions($share->getPermissions())
					->setSharedBy($share->getSharedBy())
					->setShareOwner($share->getShareOwner());

				$this->shareManager->createShare($newShare);
			}
		} while (\count($shares) >= $limit);
	}

	/**
	 * Convert files shared with $userId in the local server to remote shares
	 * shared with the $userId in the remote server defined by $targetRemoteHost
	 * This function will NOT remove any local share
	 *
	 * Note that you have to check if the $userId exists in the local server
	 * before calling this method because such check won't be performed here.
	 * Share creation might crash badly if the $userId doesn't exist locally
	 *
	 * @param string $userId the local user id
	 * @param string $targetRemoteHost the remote ownCloud server (https://my.server:8888/owncloud)
	 */
	public function convertLocalUserShareToRemoteUserShare($userId, $targetRemoteHost) {
		$remoteUser = \rtrim("{$userId}@{$targetRemoteHost}", '/');  // the remote user isn't stored with the last slash
		$limit = 50;
		$offset = 0;

		do {
			//get the remote shares targetting the remote user
			$shares = $this->shareManager->getSharedWith(
				$userId,
				ShareConstants::SHARE_TYPE_USER,
				null,
				$limit,
				$offset
			);
			$offset += $limit;

			foreach ($shares as $share) {
				// foreach of the found shares, compare with the matching models
				// and delete the share if needed
				$newShare = $this->shareManager->newShare();
				$newShare->setNode($share->getNode())
					->setShareType(ShareConstants::SHARE_TYPE_REMOTE)
					->setSharedWith($remoteUser)  // sharedWith existence was checked before
					->setPermissions($share->getPermissions())
					->setSharedBy($share->getSharedBy())
					->setShareOwner($share->getShareOwner());

				$this->shareManager->createShare($newShare);
			}
		} while (\count($shares) >= $limit);
	}
}
