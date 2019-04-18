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

use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use Symfony\Component\Filesystem\Filesystem;

class FilesExtractor {
	/** @var RecursiveNodeIteratorFactory  */
	private $iteratorFactory;

	/** @var Filesystem */
	private $filesystem;

	public function __construct(RecursiveNodeIteratorFactory $iteratorFactory, Filesystem $filesystem) {
		$this->iteratorFactory = $iteratorFactory;
		$this->filesystem = $filesystem;
	}

	/**
	 * @param string $userId
	 * @param string $exportPath
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function export($userId, $exportPath) {
		list($iterator, $baseFolder) = $this->iteratorFactory->getUserFolderParentRecursiveIterator($userId);
		/** @var \OCP\Files\Node $node */
		foreach ($iterator as $node) {
			$nodePath = $node->getPath();
			$relativeFileCachePath = $baseFolder->getRelativePath($nodePath);
			// $relativeFileCachePath is expected to have a leading slash always
			$path = "${exportPath}${relativeFileCachePath}";

			if ($node instanceof File) {
				$this->filesystem->dumpFile($path, $node->getContent());
				continue;
			}

			if ($node instanceof Folder) {
				$this->filesystem->mkdir($path);
			}
		}
	}
}
