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
namespace OCA\DataExporter\Utilities\Iterators\Nodes;

use OCP\Files\IRootFolder;

class RecursiveNodeIteratorFactory {
	/** @var IRootFolder */
	private $rootFolder;

	public function __construct(IRootFolder $rootFolder) {
		$this->rootFolder = $rootFolder;
	}

	/**
	 * Returns an array containing a recursive iterator to iterate over the files of the user as the first
	 * element of the array, and the base Folder node used in the iterator as the second element. Something like:
	 * [RecursiveIteratorIterator, Folder]
	 * It will use a RecursiveIteratorIterator class wrapping a RecursiveNodeIterator class.
	 * This RecursiveNodeIterator will return \OCP\Files\Node elements
	 *
	 * Note that a SkipNodeConditionDifferentStorage is already set in the iterator in order to traverse
	 * only the primary storage
	 * @param string $userId the id of the user
	 * @param int $mode one of the \RecursiveIteratorIterator constants
	 * @return array a RecursiveIteratorIterator wrapping a RecursiveNodeIterator and the base Folder node
	 * @throws \OC\User\NoUserException (unhandled exception)
	 */
	public function getUserFolderRecursiveIterator(string $userId, $mode = \RecursiveIteratorIterator::SELF_FIRST) {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$nodeIterator = new RecursiveNodeIterator($userFolder);
		$conditionDifferentStorage = new SkipNodeConditionDifferentStorage($userFolder->getStorage()->getId());
		$nodeIterator->addSkipCondition($conditionDifferentStorage);
		return [new \RecursiveIteratorIterator($nodeIterator, $mode), $userFolder];
	}

	/**
	 * Returns an array containing a recursive iterator to iterate over the files of the user as the first
	 * element of the array, and the base Folder node used in the iterator as the second element. Something like:
	 * [RecursiveIteratorIterator, Folder]
	 * If the getUserFolderRecursiveIterator method will return an iterator over the files
	 * of the user (/<user>/files/), this iterator will iterate over that parent folder
	 * (/<user>/) so you could get access to trashbin and versions and maybe other directories
	 * related the to user.
	 * It will use a RecursiveIteratorIterator class wrapping a RecursiveNodeIterator class.
	 * This RecursiveNodeIterator will return \OCP\Files\Node elements
	 *
	 * Note that a SkipNodeConditionDifferentStorage is already set in the iterator in order to traverse
	 * only the primary storage, and also a SkipNodeConditionIgnorePath to skip some folders containing
	 * temporary information
	 * @param string $userId the id of the user
	 * @param int $mode one of the \RecursiveIteratorIterator constants
	 * @return array a RecursiveIteratorIterator wrapping a RecursiveNodeIterator and the base Folder node
	 * @throws \OC\User\NoUserException (unhandled exception)
	 */
	public function getUserFolderParentRecursiveIterator(string $userId, $mode = \RecursiveIteratorIterator::SELF_FIRST) {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$parentFolder = $userFolder->getParent();
		$nodeIterator = new RecursiveNodeIterator($parentFolder);
		$conditionDifferentStorage = new SkipNodeConditionDifferentStorage($parentFolder->getStorage()->getId());
		$conditionIgnorePaths = new SkipNodeConditionIgnorePath($parentFolder, ['/cache', '/thumbnails', '/uploads']);
		$nodeIterator->addSkipCondition($conditionDifferentStorage);
		$nodeIterator->addSkipCondition($conditionIgnorePaths);
		return [new \RecursiveIteratorIterator($nodeIterator, $mode), $parentFolder];
	}
}
