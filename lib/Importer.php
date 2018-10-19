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
namespace OCA\DataExporter;

use OCA\DataExporter\Importer\ImportException;
use OCA\DataExporter\Importer\MetadataImporter;
use OCA\DataExporter\Model\UserMetadata;
use OCA\DataExporter\FSAccess\FSAccess;
use OCA\DataExporter\Importer\FilesImporter;
use OCA\DataExporter\Importer\MetadataImporter\ShareImporter;

class Importer {

	/** @var MetadataImporter  */
	private $metadataImporter;
	/** @var Serializer  */
	private $serializer;
	/** @var FilesImporter  */
	private $filesImporter;
	/** @var ShareImporter */
	private $shareImporter;

	public function __construct(
		Serializer $serializer,
		MetadataImporter $metadataImporter,
		FilesImporter $filesImporter,
		ShareImporter $shareImporter
	) {
		$this->metadataImporter = $metadataImporter;
		$this->serializer = $serializer;
		$this->filesImporter = $filesImporter;
		$this->shareImporter = $shareImporter;
	}

	/**
	 * @param FSAccess $fsAccess
	 * @param string|null $alias
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function import(FSAccess $fsAccess, $alias = null) {
		if (!$fsAccess->fileExists('/metadata.json')) {
			throw new ImportException("metadata.json not found in '{$fsAccess->getRoot()}'");
		}

		/** @var UserMetadata $metadata */
		$metadata = $this->serializer->deserialize(
			$fsAccess->getContentFromPath('/metadata.json'),
			UserMetadata::class
		);

		if ($alias) {
			$metadata->getUser()->setUserId($alias);
		}

		$this->metadataImporter->import($metadata);

		\OC_Util::setupFS($metadata->getUser()->getUserId());

		$this->filesImporter->import(
			$metadata->getUser()->getUserId(),
			$metadata->getUser()->getFiles(),
			$fsAccess
		);
		$this->shareImporter->import(
			$metadata->getUser()->getUserId(),
			$metadata->getUser()->getShares(),
			$metadata->getOriginServer()
		);
	}
}
