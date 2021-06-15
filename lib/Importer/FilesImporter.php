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

use OCA\DataExporter\Model\File;
use OCA\DataExporter\Utilities\StreamHelper;
use OCP\Files\IRootFolder;
use Symfony\Component\Filesystem\Filesystem;
use OCA\DataExporter\Utilities\Path;

class FilesImporter {
	public const FILE_NAME = 'files.jsonl';
	/** @var Filesystem */
	private $filesystem;
	/** @var IRootFolder */
	private $rootFolder;
	/**
	 * @var StreamHelper
	 */
	private $streamHelper;
	/**
	 * @var resource
	 */
	private $streamFile;

	/**
	 * @var int
	 */
	private $currentLine;

	public function __construct(Filesystem $filesystem, IRootFolder $rootFolder, StreamHelper $streamHelper) {
		$this->filesystem = $filesystem;
		$this->rootFolder = $rootFolder;
		$this->streamHelper = $streamHelper;
	}

	/**
	 * @param string $userId
	 * @param string $exportPath
	 *
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	public function import($userId, $exportPath) {
		// Trigger creation of user-folder
		$this->rootFolder->getUserFolder($userId);
		$filename = Path::join($exportPath, $this::FILE_NAME);
		$exportRootFilesPath = Path::join($exportPath, '/files');

		/**
		 * @var \OCP\Files\Folder $userFolder
		 */
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$this->streamFile = $this
			->streamHelper
			->initStream($filename, 'rb');
		$this->currentLine = 1;

		try {
			while ((
				/**
				 * @var File $fileMetadata
				 */
				$fileMetadata = $this->streamHelper->readlnFromStream(
					$this->streamFile,
					File::class
				)
			)
				!== false
			) {
				$fileCachePath = $fileMetadata->getPath();
				$pathToFileInExport = Path::join($exportRootFilesPath, $fileCachePath);

				if (!$this->filesystem->exists($pathToFileInExport)) {
					throw new ImportException("File '$pathToFileInExport' not found in export but exists in metadata.json");
				}

				if ($fileMetadata->getType() === File::TYPE_FILE) {
					$file = $userFolder->newFile($fileCachePath);
					$src = \fopen($pathToFileInExport, "rb");
					if (!\is_resource($src)) {
						throw new \RuntimeException("Couldn't read file in export $pathToFileInExport");
					}

					$dst = $file->fopen("wb+");
					if (!\is_resource($dst)) {
						\fclose($src);
						throw new \RuntimeException("Couldn't open node for writing for file $fileCachePath");
					}

					\stream_copy_to_stream($src, $dst);
					\fclose($src);
					\fclose($dst);

					$file->getStorage()->getCache()->update($file->getId(), [
						'etag' => $fileMetadata->getETag(),
						'permissions' => $fileMetadata->getPermissions()
					]);

					continue;
				}

				if ($fileMetadata->getType() === File::TYPE_FOLDER) {
					if ($fileMetadata->getPath() == '/') {
						$folder = $userFolder;
					} else {
						$folder = $userFolder->newFolder($fileCachePath);
					}
					$folder->getStorage()->getCache()->update($folder->getId(), [
						'etag' => $fileMetadata->getETag(),
						'permissions' => $fileMetadata->getPermissions()
					]);
				}
				$this->currentLine++;
			}
		} catch (ImportException $exception) {
			$message = $exception->getMessage();
			$line = $this->currentLine + 1;
			throw new ImportException("Import failed on $filename on line $line: $message", 0, $exception);
		}
		$this->streamHelper->checkEndOfStream($this->streamFile);
		$this->streamHelper->closeStream($this->streamFile);
	}
}
