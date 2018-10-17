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
namespace OCA\DataExporter\Importer\MetadataImporter;

use OCA\DataExporter\Model\UserMetadata\User;
use OCA\DataExporter\Model\UserMetadata\User\File;
use OCA\DataExporter\Model\UserMetadata\User\File\Version;
use OCA\DataExporter\Utilities\FSAccess\FSAccess;
use OCA\DataExporter\Importer\ImportException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;

class VersionImporter {
	private $rootFolder;

	public function __construct(IRootFolder $rootFolder) {
		$this->rootFolder = $rootFolder;
	}

	public function import(Version $versionModel, FSAccess $fsAccess) {
		$fileLocation = "/files{$versionModel->getPath()}";

		$parentFileModel = $versionModel->getParent();
		if (!$parentFileModel instanceof File) {
			throw new ImportException('current version model isn\'t associated with a file model as parent');
		}
		$filePath = $parentFileModel->getPath();

		$parentUserModel = $parentFileModel->getParent();
		if (!$parentUserModel instanceof User) {
			throw new ImportException('file model associated to the model doesn\'t have a user model as parent');
		}
		$userId = $parentUserModel->getUserId();

		$ocTargetNodePath = "{$userId}{$filePath}";

		try {
			$node = $this->rootFolder->get($ocTargetNodePath);
		} catch (NotFoundException $e) {
			$node = $this->rootFolder->newFile($ocTargetNodePath);
		}

		// write the version content in the node
		$stream = $fsAccess->getStream($fileLocation);
		if ($stream !== false) {
			// @phan-suppress-next-line PhanTypeMismatchArgument
			$node->putContent($stream);
			if (\is_resource($stream)) {
				\fclose($stream);
			}
		}
	}
}
