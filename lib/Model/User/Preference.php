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
namespace OCA\DataExporter\Model\User;

/**
 * Represents the User Preferences Export format
 *
 * @package OCA\DataExporter\Model\User
 * @codeCoverageIgnore
 */
class Preference {
	/** @var string */
	private $appId;
	/** @var string */
	private $configKey;
	/** @var string */
	private $configValue;

	/**
	 * @return string
	 */
	public function getAppId() {
		return $this->appId;
	}

	/**
	 * @param string $appId
	 * @return Preference
	 */
	public function setAppId($appId) {
		$this->appId = $appId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConfigKey() {
		return $this->configKey;
	}

	/**
	 * @param string $configKey
	 * @return Preference
	 */
	public function setConfigKey($configKey) {
		$this->configKey = $configKey;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConfigValue() {
		return $this->configValue;
	}

	/**
	 * @param string $configValue
	 * @return Preference
	 */
	public function setConfigValue($configValue) {
		$this->configValue = $configValue;
		return $this;
	}
}
