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
namespace OCA\DataExporter\Exporter\MetadataExtractor;

use OCA\DataExporter\Model\UserMetadata\User;
use OCP\IGroupManager;
use OCP\IUserManager;

class UserExtractor {

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager  */
	private $groupManager;

	public function __construct(IUserManager $userManager, IGroupManager $groupManager) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param string $userId
	 * @return User
	 *
	 * @throws \RuntimeException
	 */
	public function extract(string $userId) : User {
		$userData = $this->userManager->get($userId);

		if (!$userData) {
			throw new \RuntimeException("Could not extract user metadata for user '$userId'");
		}

		$user = new User();

		$user->setUserId($userData->getUID());
		$user->setEmail($userData->getEMailAddress());
		$user->setQuota($userData->getQuota());
		$user->setBackend($userData->getBackendClassName());
		$user->setDisplayName($userData->getDisplayName());
		$user->setEnabled($userData->isEnabled());
		$user->setGroups($this->groupManager->getUserGroupIds($userData));

		return $user;
	}
}
