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
namespace OCA\DataExporter\Model;

use OCA\DataExporter\Model\AbstractModel;
use OCA\DataExporter\Model\UserMetadata\User;

class UserMetadata extends AbstractModel {

	/** @var \DateTimeImmutable */
	private $date;
	/** @var string */
	private $originServer;
	/** @var User */
	private $user;

	/**
	 * @return \DateTimeImmutable
	 */
	public function getDate(): \DateTimeImmutable {
		return $this->date;
	}

	/**
	 * @param \DateTimeImmutable $date
	 * @return UserMetadata
	 */
	public function setDate(\DateTimeImmutable $date): UserMetadata {
		$this->date = $date;
		return $this;
	}

	/**
	 * @return string the exporting server address with port
	 */
	public function getOriginServer(): string {
		return $this->originServer;
	}

	/**
	 * @param string $originServer the address of the exporting server,
	 * including port, like "10.10.10.10:8080" or "my.server:443"
	 * @return UserMetadata
	 */
	public function setOriginServer(string $originServer): UserMetadata {
		$this->originServer = $originServer;
		return $this;
	}

	/**
	 * @return User
	 */
	public function getUser(): User {
		return $this->user;
	}

	/**
	 * @param User $user
	 * @return UserMetadata
	 */
	public function setUser(User $user): UserMetadata {
		$user->setParent($this);
		$this->user = $user;
		return $this;
	}
}
