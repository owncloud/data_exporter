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

namespace OCA\DataExporter\Importer;

use OCA\DataExporter\Model\Instance;
use OCP\IConfig;
use OCP\IGroupManager;

/**
 * Class InstanceImporter
 *
 * @package OCA\DataExporter\Importer
 */
class InstanceDataImporter {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * InstanceImporter constructor.
	 *
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 */
	public function __construct(IConfig $config, IGroupManager $groupManager) {
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * @param Instance $instanceData
	 *
	 * @return void
	 */
	public function import($instanceData) {
		$this->config->setSystemValues([
				'instanceid' => $instanceData->getInstanceId(),
				'passwordsalt' => $instanceData->getPasswordSalt(),
				'secret' => $instanceData->getSecret()
			]
		);

		$groups = $instanceData->getGroups();
		foreach ($groups as $group) {
			$this->groupManager->createGroup($group);
		}
	}
}
