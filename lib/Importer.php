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
use OCA\DataExporter\Model\Metadata;
use Symfony\Component\Filesystem\Filesystem;
use OCA\DataExporter\Importer\FilesImporter;
use OCA\DataExporter\Importer\MetadataImporter\ShareImporter;

class Importer {

	/** @var MetadataImporter  */
	private $metadataImporter;
	/** @var Serializer  */
	private $serializer;
	/** @var Filesystem  */
	private $filesystem;
	/** @var FilesImporter  */
	private $filesImporter;
	/** @var ShareImporter */
	private $shareImporter;

	public function __construct(
		Serializer $serializer,
		MetadataImporter $metadataImporter,
		FilesImporter $filesImporter,
		ShareImporter $shareImporter,
		Filesystem $filesystem
	) {
		$this->metadataImporter = $metadataImporter;
		$this->filesystem = $filesystem;
		$this->serializer = $serializer;
		$this->filesImporter = $filesImporter;
		$this->shareImporter = $shareImporter;
	}

	/**
	 * @param string $pathToExportDir
	 * @param string|null $alias
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function import($pathToExportDir, $alias = null) {
		$metaDataPath = "$pathToExportDir/metadata.json";

		if (!$this->filesystem->exists($metaDataPath)) {
			throw new ImportException("metadata.json not found in '$metaDataPath'");
		}

		/** @var Metadata $metadata */
		$metadata = $this->serializer->deserialize(
			\file_get_contents($metaDataPath),
			Metadata::class
		);

		if ($alias) {
			$metadata->getUser()->setUserId($alias);
		}

		$this->metadataImporter->import($metadata);
		$this->filesImporter->import(
			$metadata->getUser()->getUserId(),
			$metadata->getUser()->getFiles(),
			"$pathToExportDir/files"
		);
		$this->shareImporter->import(
			$metadata->getUser()->getUserId(),
			$metadata->getUser()->getShares(),
			$metadata->getOriginServer()
		);
	}
}
