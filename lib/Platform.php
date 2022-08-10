<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
namespace OCA\DataExporter;

use OCP\App\IAppManager;

/**
 * Query various parameters about the platform the data-exporter is running on.
 *
 * @package OCA\DataExporter
 */
class Platform {

	/** @var IAppManager  */
	private $appManager;

	/** @var array  */
	private $version;

	public function __construct(IAppManager $appManager, $versionFile = __DIR__ . '/../../../version.php') {
		$this->loadVersion($versionFile);
		$this->appManager = $appManager;
	}

	private function loadVersion($versionFile) {
		$OC_Version = '';
		$OC_VersionString = '';
		$OC_Build = '';
		$OC_Channel = '';
		$vendor = '';
		require $versionFile;
		$this->version =  [
			'OC_Version' => $OC_Version,
			'OC_VersionString' => $OC_VersionString,
			'OC_Build' => $OC_Build,
			'OC_Channel' => $OC_Channel,
			'vendor' => $vendor
		];
	}

	public function getVendor() {
		return $this->version['vendor'];
	}

	public function getVersionString() {
		return $this->version['OC_VersionString'];
	}

	/**
	 * Check if an app is enabled for user
	 *
	 * @param string $appId
	 * @param \OCP\IUser $user
	 * @return bool
	 */
	public function isAppEnabledForUser($appId, $user) {
		return $this->appManager->isEnabledForUser($appId, $user);
	}
}
