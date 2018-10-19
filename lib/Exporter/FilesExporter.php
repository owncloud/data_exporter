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

use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCA\DataExporter\FSAccess\FSAccess;
use OCP\Files\File;
use OCP\Files\Folder;

class FilesExporter {
	/** @var RecursiveNodeIteratorFactory  */
	private $iteratorFactory;

	public function __construct(RecursiveNodeIteratorFactory $iteratorFactory) {
		$this->iteratorFactory = $iteratorFactory;
	}

	/**
	 * @param string $userId
	 * @param FSAccess $fsAccess
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function export(string $userId, FSAccess $fsAccess) {
		list($iterator, $baseFolder) = $this->iteratorFactory->getUserFolderParentRecursiveIterator($userId);
		/** @var \OCP\Files\Node $node */
		foreach ($iterator as $node) {
			$nodePath = $node->getPath();
			$relativeFileCachePath = $baseFolder->getRelativePath($nodePath);
			// $relativeFileCachePath is expected to have a leading slash always
			$path = "/files${relativeFileCachePath}";

			if ($node instanceof File) {
				$stream = $node->fopen('rb');
				$fsAccess->copyStreamToPath($stream, $path);
				\fclose($stream);
				continue;
			}

			if ($node instanceof Folder) {
				$fsAccess->mkdir($path);
			}
		}
	}
}
