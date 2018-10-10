<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
namespace OCA\DataExporter\Model\UserMetadata;

use OCA\DataExporter\Model\AbstractModel;
use OCA\DataExporter\Model\UserMetadata\User\File;
use OCA\DataExporter\Model\UserMetadata\User\Preference;
use OCA\DataExporter\Model\UserMetadata\User\Share;

class User extends AbstractModel {

	/** @var string */
	private $userId;
	/** @var string */
	private $displayName;
	/** @var string|null */
	private $email;
	/** @var string */
	private $quota;
	/** @var string */
	private $backend;
	/** @var boolean */
	private $enabled;
	/** @var string[] */
	private $groups = [];
	/** @var Preference[] */
	private $preferences = [];
	/** @var File[] */
	private $files = [];
	/** @var Share[] */
	private $shares = [];

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * @param string $userId
	 * @return User
	 */
	public function setUserId(string $userId): User {
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}

	/**
	 * @param string|null $displayName
	 * @return User
	 */
	public function setDisplayName($displayName): User {
		$this->displayName = $displayName;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string $email
	 * @return User
	 */
	public function setEmail($email): User {
		$this->email = $email;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getQuota(): string {
		return $this->quota;
	}

	/**
	 * @param string|null $quota
	 * @return User
	 */
	public function setQuota($quota): User {
		$this->quota = $quota;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBackend(): string {
		return $this->backend;
	}

	/**
	 * @param string $backend
	 * @return User
	 */
	public function setBackend(string $backend): User {
		$this->backend = $backend;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isEnabled() : bool {
		return $this->enabled;
	}

	/**
	 * @param bool $enabled
	 * @return User
	 */
	public function setEnabled(bool $enabled): User {
		$this->enabled = $enabled;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getGroups(): array {
		return $this->groups;
	}

	/**
	 * @param string[] $groups
	 * @return User
	 */
	public function setGroups(array $groups): User {
		$this->groups = $groups;
		return $this;
	}

	/**
	 * @return Preference[]
	 */
	public function getPreferences(): array {
		return $this->preferences;
	}

	/**
	 * @param Preference[] $preferences
	 * @return User
	 */
	public function setPreferences(array $preferences): User {
		foreach ($preferences as $preference) {
			$preference->setParent($this);
		}
		$this->preferences = $preferences;
		return $this;
	}

	/**
	 * @return File[]
	 */
	public function getFiles(): array {
		return $this->files;
	}

	/**
	 * @param File[] $files
	 * @return User
	 */
	public function setFiles(array $files): User {
		foreach ($files as $file) {
			$file->setParent($this);
		}
		$this->files = $files;
		return $this;
	}

	/**
	 * @return Share[]
	 */
	public function getShares(): array {
		return $this->shares;
	}

	/**
	 * @param Share[] $shares
	 * @return User
	 */
	public function setShares(array $shares): User {
		foreach ($shares as $share) {
			$share->setParent($this);
		}
		$this->shares = $shares;
		return $this;
	}
}
