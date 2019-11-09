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
namespace OCA\DataExporter\Extractor;

use OC\Files\Node\Node;
use OCA\DataExporter\Utilities\Path;
use OCP\Files\File;
use OCP\Files\Folder;
use Symfony\Component\Filesystem\Filesystem;

class FilesExtractor {
	/** @var Filesystem */
	private $filesystem;

	public function __construct(Filesystem $filesystem) {
		$this->filesystem = $filesystem;
	}

	/**
	 * @param \Traversable $iterator
	 * @param Folder $baseFolder
	 * @param string $exportPath
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function export(\Traversable $iterator, Folder $baseFolder, $exportPath) {
		/** @var \OCP\Files\Node $node */
		foreach ($iterator as $node) {
			$nodePath = $node->getPath();
			$relativeFileCachePath = $baseFolder->getRelativePath($nodePath);
			// $relativeFileCachePath is expected to have a leading slash always
			$path = Path::join("${exportPath}${relativeFileCachePath}");

			if ($node instanceof File) {
				@\mkdir(\pathinfo($path, PATHINFO_DIRNAME), 0777, true);

				$src = $node->fopen('rb');
				if (!\is_resource($src)) {
					throw new \RuntimeException("Couldn't read $nodePath from owncloud");
				}

				$dst = \fopen($path, 'wb+');
				if (!\is_resource($dst)) {
					\fclose($src);
					throw new \RuntimeException("Couldn't create $path in export");
				}

				\stream_copy_to_stream($src, $dst);

				\fclose($src);
				\fclose($dst);

				continue;
			}

			if ($node instanceof Folder) {
				$this->filesystem->mkdir($path);
			}
		}
	}
}
