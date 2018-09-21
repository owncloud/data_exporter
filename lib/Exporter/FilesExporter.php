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

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Symfony\Component\Filesystem\Filesystem;

class FilesExporter {

	/** @var IRootFolder  */
	private $rootFolder;
	/** @var Filesystem */
	private $filesystem;

	public function __construct(IRootFolder $rootFolder, Filesystem $filesystem) {
		$this->rootFolder =  $rootFolder;
		$this->filesystem = $filesystem;
	}

	/**
	 * @param string $userId
	 * @param string $exportPath
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function export(string $userId, string $exportPath) {
		/** @var Folder $userFolder */
		$userFolder = $this->rootFolder->getUserFolder($userId)->getParent();

		/** @var \OCP\Files\Node $node */
		foreach (new UserStorageIterator($userFolder) as $node) {
			$nodePath = $node->getPath();
			$relativeFileCachePath = $nodePath;
			if (\strpos($nodePath, "/$userId/") === 0) {
				$relativeFileCachePath = \substr($nodePath, \strlen("/$userId/"));
			}

			$path = "$exportPath/$relativeFileCachePath";

			if ($node instanceof File) {
				$this->filesystem->touch($path);
				$source = $node->fopen('rb');
				$target = \fopen($path, 'wb');
				\stream_copy_to_stream($source, $target);
				\fclose($source);
				\fclose($target);

				continue;
			}

			if ($node instanceof Folder) {
				$this->filesystem->mkdir($path);
			}
		}
	}
}
