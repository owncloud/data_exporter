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

use OCA\DataExporter\Model\User\Preference;
use OCP\IAppConfig;
use OCP\IConfig;

class PreferencesExtractor {

	/** @var IConfig  */
	private $config;
	/** @var IAppConfig  */
	private $appConfig;

	public function __construct(IConfig $config, IAppConfig $appConfig) {
		$this->config = $config;
		$this->appConfig = $appConfig;
	}

	/**
	 * @param string $userId
	 * @return Preference[]
	 */
	public function extract($userId) {
		$appList = $this->appConfig->getApps();
		$preferences = [];
		foreach ($appList as $app) {
			$userKeys = $this->config->getUserKeys($userId, $app);
			foreach ($userKeys as $key) {
				$value = $this->config->getUserValue($userId, $app, $key);
				$preference = (new Preference())
					->setConfigKey($key)
					->setConfigValue($value)
					->setAppId($app);

				$preferences[] = $preference;
			}
		}

		return $preferences;
	}
}
