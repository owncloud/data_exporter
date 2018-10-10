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
use OCP\Files\IRootFolder;
use Symfony\Component\Filesystem\Filesystem;

class FilesImporter {

	/** @var Filesystem  */
	private $filesystem;
	/** @var IRootFolder  */
	private $rootFolder;

	public function __construct(Filesystem $filesystem, IRootFolder $rootFolder) {
		$this->filesystem = $filesystem;
		$this->rootFolder = $rootFolder;
	}

	/**
	 * @param string $userId
	 * @param array $filesMetadata
	 * @param string $exportRootFilesPath
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	public function import(string $userId, array $filesMetadata, string $exportRootFilesPath) {
		// Trigger creation of user-folder
		$this->rootFolder->getUserFolder($userId);
		/** @var \OCP\Files\Folder $userFolder */
		$userFolder = $this->rootFolder->getUserFolder($userId)->getParent();

		/** @var File $fileMetadata */
		foreach ($filesMetadata as $fileMetadata) {
			$fileCachePath = $fileMetadata->getPath();
			$pathToFileInExport = "$exportRootFilesPath/$fileCachePath";

			if (!$this->filesystem->exists($pathToFileInExport)) {
				throw new ImportException("File '$pathToFileInExport' not found in export but exists in metadata.json");
			}

			if ($fileMetadata->getType() === File::TYPE_FILE) {
				$file = $userFolder->newFile($fileCachePath);
				$file->putContent(\file_get_contents($pathToFileInExport));
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
