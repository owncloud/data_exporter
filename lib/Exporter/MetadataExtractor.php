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
namespace OCA\DataExporter\Exporter;

use OCA\DataExporter\Exporter\MetadataExtractor\FilesExtractor;
use OCA\DataExporter\Exporter\MetadataExtractor\PreferencesExtractor;
use OCA\DataExporter\Exporter\MetadataExtractor\UserExtractor;
use OCA\DataExporter\Exporter\MetadataExtractor\SharesExtractor;
use OCA\DataExporter\Model\UserMetadata;
use OCA\DataExporter\Utilities\FSAccess\FSAccess;
use OCP\IURLGenerator;

/**
 * Responsible for assembling all model objects which are required
 * for building metadata.json
 *
 * Each model object is read by its own specialized extractor
 *
 * @package OCA\DataExporter\Exporter
 */
class MetadataExtractor {

	/** @var UserExtractor  */
	private $userExtractor;
	/** @var PreferencesExtractor  */
	private $preferencesExtractor;
	/** @var FilesExtractor $filesExtractor */
	private $filesExtractor;
	/** @var SharesExtractor */
	private $sharesExtractor;
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param UserExtractor $userExtractor
	 * @param PreferencesExtractor $preferencesExtractor
	 * @param FilesExtractor $filesExtractor
	 * @param SharesExtractor $sharesExtractor
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		UserExtractor $userExtractor,
		PreferencesExtractor $preferencesExtractor,
		FilesExtractor $filesExtractor,
		SharesExtractor $sharesExtractor,
		IURLGenerator $urlGenerator
	) {
		$this->userExtractor = $userExtractor;
		$this->preferencesExtractor = $preferencesExtractor;
		$this->filesExtractor = $filesExtractor;
		$this->sharesExtractor = $sharesExtractor;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Extract all metadata required for export in to the database
	 *
	 * @param string $uid
	 * @return UserMetadata
	 * @throws \Exception
	 * @throws \RuntimeException if user can not be read
	 */
	public function extract(string $uid, FSAccess $fsAccess) : UserMetadata {
		$user = $this->userExtractor->extract($uid);
		$user->setPreferences(
			$this->preferencesExtractor->extract($uid)
		)->setFiles(
			$this->filesExtractor->extract($uid, $fsAccess)
		)->setShares(
			$this->sharesExtractor->extract($uid)
		);

		$metadata = new UserMetadata();
		$metadata->setDate(new \DateTimeImmutable())
			->setUser($user)
			->setOriginServer($this->urlGenerator->getAbsoluteURL('/'));

		return $metadata;
	}
}
