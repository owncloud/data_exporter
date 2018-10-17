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

use OCA\DataExporter\Model\UserMetadata\User\File;
use OCA\DataExporter\Importer\MetadataImporter\VersionImporter;
use OCA\DataExporter\Utilities\FSAccess\FSAccess;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;

class FilesImporter {

	/** @var IRootFolder  */
	private $rootFolder;
	/** @var VersionImporter */
	private $versionImporter;

	public function __construct(IRootFolder $rootFolder, VersionImporter $versionImporter) {
		$this->rootFolder = $rootFolder;
		$this->versionImporter = $versionImporter;
	}

	/**
	 * @param string $userId
	 * @param array $filesMetadata
	 * @param FSAccess $fsAccess
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	public function import(string $userId, array $filesMetadata, FSAccess $fsAccess) {
		// Trigger creation of user-folder
		$this->rootFolder->getUserFolder($userId);
		/** @var \OCP\Files\Folder $userFolder */
		$userFolder = $this->rootFolder->getUserFolder($userId)->getParent();

		/** @var File $fileMetadata */
		foreach ($filesMetadata as $fileMetadata) {
			$fileCachePath = $fileMetadata->getPath();
			$fileLocation = "/files{$fileCachePath}";

			if (!$fsAccess->fileExists($fileLocation)) {
				$fullFilePath = $fsAccess->getRoot() . "/files{$fileCachePath}";
				throw new ImportException("File '$fullFilePath' not found in export but exists in metadata.json");
			}

			if ($fileMetadata->getType() === File::TYPE_FILE) {
				$fileVersions = $fileMetadata->getVersions();
				// versions must have been sorted older to newer
				foreach ($fileVersions as $fileVersion) {
					// import the versions first
					$this->versionImporter->import($fileVersion, $fsAccess);
				}

				try {
					/** @var \OCP\Files\File $file */
					$file = $userFolder->get($fileCachePath);
				} catch (NotFoundException $e) {
					/** @var \OCP\Files\File $file */
					$file = $userFolder->newFile($fileCachePath);
				}

				// import the file over the versions
				$stream = $fsAccess->getStream($fileLocation);
				// assume $file will be always a file node
				$file->putContent($stream);
				if (\is_resource($stream)) {
					\fclose($stream);
				}

				$file->getStorage()->getCache()->update($file->getId(), [
					'etag' => $fileMetadata->getETag(),
					'permissions' => $fileMetadata->getPermissions()
				]);

				continue;
			}

			if ($fileMetadata->getType() === File::TYPE_FOLDER) {
				$folder = $userFolder->newFolder($fileCachePath);
				$folder->getStorage()->getCache()->update($folder->getId(), [
					'etag' => $fileMetadata->getETag(),
					'permissions' => $fileMetadata->getPermissions()
				]);
			}
		}
	}
}
