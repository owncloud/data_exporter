<?php
/**
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
namespace OCA\DataExporter\Exporter\MetadataExtractor;

use OCA\DataExporter\Model\UserMetadata\User\File\Version;
use OCA\DataExporter\Utilities\FSAccess\FSAccess;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IVersionedStorage;
use OCP\Files\Storage\IStorage;

class VersionsExtractor {
	/** @var IRootFolder  */
	private $rootFolder;

	public function __construct(IRootFolder $rootFolder) {
		$this->rootFolder = $rootFolder;
	}

	public function extract(string $userId, string $path, FSAccess $fsAccess) {
		$fileNode = $this->rootFolder->get($path);
		if (!$fileNode instanceof File) {
			// only files will have versions
			return [];
		}

		$storage = $this->getStorage($fileNode);
		$internalPath = $fileNode->getInternalPath();
		$versionModels = [];

		if ($storage !== null) {
			$versions = $storage->getVersions($internalPath);
			// traverse the version list backwards so older versions are first
			for (\end($versions); \key($versions) !== null; \prev($versions)) {
				$fileVersion = \current($versions);
				if ($fileVersion['path'][0] !== '/') {
					$versionPath = "/versions/{$fileVersion['path']}.{$fileVersion['version']}";
				} else {
					$versionPath = "/versions{$fileVersion['path']}.{$fileVersion['version']}";
				}
				$versionModel = new Version();
				$versionModel->setPath($versionPath);

				// copy the version over
				$versionContentStream = $storage->getContentOfVersion($internalPath, $fileVersion['version']);
				$fsAccess->copyStreamToPath($versionContentStream, "/files${versionPath}");
				\fclose($versionContentStream);

				$versionModels[] = $versionModel;
			}
		}
		return $versionModels;
	}

	/**
	 * @param File $fileNode the target file node to fetch the storage from
	 * @return IStorage|IVersionedStorage|null the versioned storage or null if the storage is of
	 * a different type
	 */
	private function getStorage(File $fileNode) {
		/** @var IStorage|IVersionedStorage $storage */
		$storage = $fileNode->getStorage();
		if (!$storage->instanceOfStorage(IVersionedStorage::class)) {
			return null;
		}
		return $storage;
	}
}
