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
namespace OCA\DataExporter\Importer;

use OCA\DataExporter\Importer\MetadataImporter\PreferencesImporter;
use OCA\DataExporter\Importer\MetadataImporter\UserImporter;
use OCA\DataExporter\Model\UserMetadata;

class MetadataImporter {

	/** @var UserImporter  */
	private $userImporter;
	/** @var PreferencesImporter  */
	private $preferencesImporter;

	public function __construct(UserImporter $userImporter, PreferencesImporter $preferencesImporter) {
		$this->userImporter = $userImporter;
		$this->preferencesImporter = $preferencesImporter;
	}

	/**
	 * @param UserMetadata $metadata
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function import(UserMetadata $metadata) {
		$user = $metadata->getUser();
		$this->userImporter->import($user);
		$this->preferencesImporter->import(
			$user->getUserId(),
			$user->getPreferences()
		);
	}
}
