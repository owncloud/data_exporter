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

namespace OCA\DataExporter\Exporter;

use OC\Group\Database;
use OCA\DataExporter\Model\Instance;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;

/**
 * Class InstanceExtractor
 *
 * @package OCA\DataExporter\Exporter
 */
class InstanceExtractor {
	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * InstanceExtractor constructor.
	 *
	 * @param IGroupManager $groupManager
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IGroupManager $groupManager,
		IConfig $config,
		IURLGenerator $urlGenerator
	) {
		$this->groupManager = $groupManager;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Extract all instance data
	 *
	 * @return Instance
	 *
	 * @throws \Exception
	 * @throws \RuntimeException if user can not be read
	 */
	public function extract() {
		$groups = $this->groupManager->search('');
		$groupIds = [];
		// Only extract DB groups
		foreach ($groups as $group) {
			if ($group->getBackend() instanceof Database) {
				$groupIds[] = $group->getGID();
			}
		}

		$instance = new Instance();
		$instance->setDate(new \DateTimeImmutable())
			->setOriginServer($this->urlGenerator->getAbsoluteURL('/'))
			->setInstanceId($this->config->getSystemValue('instanceid', ''))
			->setSecret($this->config->getSystemValue('secret', ''))
			->setPasswordSalt($this->config->getSystemValue('passwordsalt', ''))
			->setGroups($groupIds);

		return $instance;
	}
}
