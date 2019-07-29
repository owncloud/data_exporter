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
namespace OCA\DataExporter\Importer\MetadataImporter;

use OCA\DataExporter\Importer\MetadataImporter\UserImporter\UpdatePasswordHashQuery;
use OCA\DataExporter\Model\User;
use OCA\DataExporter\Importer\ImportException;
use OCP\IGroupManager;
use OCP\IUserManager;

class UserImporter {

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager  */
	private $groupManager;

	/** @var UpdatePasswordHashQuery */
	private $updatePwHashQuery;

	private $allowedBackends = ['Database'];

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param UpdatePasswordHashQuery $q
	 */
	public function __construct(IUserManager $userManager, IGroupManager $groupManager, UpdatePasswordHashQuery $q) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->updatePwHashQuery = $q;
	}

	/**
	 * @param User $import
	 * @throws \Exception
	 */
	public function import(User $import) {
		$user = $this->userManager->get($import->getUserId());

		// create the new account if the model comes from the DB backend and the user doesn't exists
		// if the user exists, do nothing
		if (!$user) {
			if (\in_array($import->getBackend(), $this->getAllowedBackends(), true)) {
				$this->createNewUser($import);
			} else {
				throw new ImportException("User account creation in the {$import->getBackend()} backend is not supported");
			}
		} else {
			// check the backends to abort the process if the userid is from a different backend
			if ($import->getBackend() !== $user->getBackendClassName()) {
				throw new ImportException("Existing user account doesn't have the same backend as the importing user");
			}
		}
	}

	/**
	 * Normally only the Database backend will allow you to create new users during the import process
	 * Backends such as LDAP or SAML will reject to create new users (they're considered as read-only)
	 *
	 * This method is used mainly on testing to allow the creation of users in the Dummy backend. Note that
	 * this backend is just for tests, so unless explicitly set, it will also reject the creation of new users
	 *
	 * This method will overwrite any previously allowed backend. If you want to add new backends, get the list
	 * first, add the new backend to the list and finally set the new list.
	 * @param string[] $backendNames the name of the backends that will allow you to create
	 * a new user account during the import of the user.
	 * @return bool true if the list is set properly, false otherwise
	 */
	public function setAllowedBackends(array $backendNames) {
		foreach ($backendNames as $backendName) {
			// ensure the list contains just strings
			if (!\is_string($backendName)) {
				return false;
			}
		}
		$this->allowedBackends = $backendNames;
		return true;
	}

	/**
	 * Get the backends that will allow user creation during import. Normally only the Database
	 * backend will allow it. Use the `setAllowedBackends` method to overwrite
	 * @return string[] the backend names
	 */
	public function getAllowedBackends() {
		return $this->allowedBackends;
	}

	private function createNewUser(User $userModel) {
		$user = $this->userManager->createUser(
			$userModel->getUserId(),
			\bin2hex(\random_bytes(8)) //Temporary random password
		);

		$user->setDisplayName($userModel->getDisplayName());
		$user->setEMailAddress($userModel->getEmail());
		$user->setEnabled($userModel->isEnabled());
		$passwordHash = $userModel->getPasswordHash();

		if ($passwordHash !== null && !empty(\trim($passwordHash))) {
			$this->updatePwHashQuery->execute(
				$userModel->getUserId(),
				$userModel->getPasswordHash()
			);
		}

		$userGroups = $userModel->getGroups();

		foreach ($userGroups as $group) {
			if ($this->groupManager->groupExists($group)) {
				$group = $this->groupManager->get($group);
				$group->addUser($user);
			}
		}
		return $user;
	}
}
