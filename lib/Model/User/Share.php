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
namespace OCA\DataExporter\Model\User;

class Share {
	const SHARETYPE_USER = 'user';
	const SHARETYPE_GROUP = 'group';
	const SHARETYPE_LINK = 'link';
	const SHARETYPE_REMOTE = 'remote';

	/** @var string */
	private $path;
	/** @var string */
	private $shareType;
	/** @var string */
	private $owner;
	/** @var string */
	private $initiator;
	/** @var string|null */
	private $sharedWith;
	/** @var int */
	private $permissions;
	/** @var int|null */
	private $expirationDate;
	/** @var string|null */
	private $password;
	/** @var string|null */
	private $name;

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @param string $path the path of the shared file
	 * @return Share
	 */
	public function setPath(string $path): Share {
		$this->path = $path;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getShareType(): string {
		return $this->shareType;
	}

	/**
	 * @param string $shareType SHARETYPE_* constants
	 * @return Share
	 */
	public function setShareType(string $shareType): Share {
		$this->shareType = $shareType;
		return $this;
	}

	/**
	 * @return string the owner of the share
	 */
	public function getOwner(): string {
		return $this->owner;
	}

	/**
	 * @param string $owner the owner of the share
	 * @return Share
	 */
	public function setOwner(string $owner): Share {
		$this->owner = $owner;
		return $this;
	}
	/**
	 * @return string the initiator (not the owner) of the share
	 */
	public function getSharedBy(): string {
		return $this->initiator;
	}

	/**
	 * @param string $initiator the initiator (not the owner) of the share
	 * @return Share
	 */
	public function setSharedBy(string $initiator): Share {
		$this->initiator = $initiator;
		return $this;
	}

	/**
	 * @return string|null share by link might not set this and return null
	 */
	public function getSharedWith() {
		return $this->sharedWith;
	}

	/**
	 * @param string|null $sharedWith
	 * @return Share
	 */
	public function setSharedWith($sharedWith): Share {
		$this->sharedWith = $sharedWith;
		return $this;
	}

	/**
	 * @return int the permissions code number
	 */
	public function getPermissions(): int {
		return $this->permissions;
	}

	/**
	 * @param int $permissions the permissions code number
	 * @return Share
	 */
	public function setPermissions(int $permissions): Share {
		$this->permissions = $permissions;
		return $this;
	}

	/**
	 * @return int|null the expiration time of the link or null if the share isn't
	 * a share link type or the link doesn't have an expiration date
	 */
	public function getExpirationDate() {
		return $this->expirationDate;
	}

	/**
	 * @param int|null $expirationDate the expiration time for the share link
	 * @return Share
	 */
	public function setExpirationDate($expirationDate): Share {
		$this->expirationDate = $expirationDate;
		return $this;
	}

	/**
	 * @return string|null the hashed password for the share link or null if the
	 * share isn't a share link of the link doesn't have a password
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * @param string|null $password the (hashed) password for the link
	 */
	public function setPassword($password): Share {
		$this->password = $password;
		return $this;
	}

	/**
	 * @return string|null the name of the share link or null if the share isn't
	 * a share link
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string|null $name the name of the share link
	 * @return Share
	 */
	public function setName($name): Share {
		$this->name = $name;
		return $this;
	}
}
