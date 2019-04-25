<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

class Instance {
	/**
	 * @var \DateTimeImmutable
	 */
	private $date;

	/**
	 * @var string
	 */
	private $originServer;

	/**
	 * @var string
	 */
	private $instanceId;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var string
	 */
	private $passwordSalt;

	/**
	 * @var array
	 */
	private $groups = [];

	/**
	 * @return \DateTimeImmutable
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @param \DateTimeImmutable $date
	 *
	 * @return Instance
	 */
	public function setDate(\DateTimeImmutable $date) {
		$this->date = $date;
		return $this;
	}

	/**
	 * @return string the exporting server address with port
	 */
	public function getOriginServer() {
		return $this->originServer;
	}

	/**
	 * @param string $originServer the address of the exporting server,
	 * including port, like "10.10.10.10:8080" or "my.server:443"
	 *
	 * @return Instance
	 */
	public function setOriginServer($originServer) {
		$this->originServer = $originServer;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getInstanceId() {
		return $this->instanceId;
	}

	/**
	 * @param string $id
	 *
	 * @return Instance
	 */
	public function setInstanceId($id) {
		$this->instanceId = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSecret() {
		return $this->secret;
	}

	/**
	 * @param string $secret
	 *
	 * @return Instance
	 */
	public function setSecret($secret) {
		$this->secret = $secret;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPasswordSalt() {
		return $this->passwordSalt;
	}

	/**
	 * @param string $passwordSalt
	 *
	 * @return Instance
	 */
	public function setPasswordSalt($passwordSalt) {
		$this->passwordSalt = $passwordSalt;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getGroups() {
		return $this->groups;
	}

	/**
	 * @param array $groups
	 *
	 * @return Instance
	 */
	public function setGroups($groups) {
		$this->groups = $groups;
		return $this;
	}
}
