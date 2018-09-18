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

use OC\Files\Storage\FailedStorage;
use OCP\Files\Folder;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\StorageAdapter;

/**
 * Iterates through nodes while ignoring shares, blacklisted paths and
 * ext. storages.
 *
 * TODO: Nodes are still internally traversed even if the parent is excluded
 *
 * @package OCA\DataExporter\Exporter
 */
class UserStorageIterator extends \FilterIterator {

	/** @var string[]  */
	private $ignorePaths = [
		'cache',
		'thumbnails',
		'uploads'
	];

	/** @var string  */
	private $uidOwner;

	public function __construct(Folder $folder) {
		$this->uidOwner = $folder->getOwner()->getUID();

		parent::__construct(
			new \RecursiveIteratorIterator(
				new RecursiveNodeIterator($folder->getDirectoryListing()),
				\RecursiveIteratorIterator::SELF_FIRST
			)
		);
	}

	/**
	 * Check whether the current element of the iterator is acceptable
	 *
	 * @link http://php.net/manual/en/filteriterator.accept.php
	 * @return bool true if the current element is acceptable, otherwise false.
	 * @since 5.1.0
	 * @throws \OCP\Files\NotFoundException
	 */
	public function accept() : bool {
		/** @var \OCP\Files\Node */
		$currentNode = $this->current();

		return !$this->isIgnoredPath($currentNode->getPath()) &&
				$this->isUserStorage($currentNode->getStorage());
	}

	private function isIgnoredPath(string $path) : bool {
		$relativePath = $path;
		if (\strpos($path, "/{$this->uidOwner}/") === 0) {
			$relativePath = \substr($path, \strlen("/{$this->uidOwner}/"));
		}
		foreach ($this->ignorePaths as $ignorePath) {
			if (\substr($relativePath, 0, \strlen($ignorePath)) === $ignorePath) {
				return true;
			}
		}

		return false;
	}

	private function isUserStorage(IStorage $storage) : bool {
		$isSharedStorage = false;

		// sharing app could be disabled
		if (\interface_exists("OCA\Files_Sharing\ISharedStorage")) {
			$isSharedStorage = $storage
				/* @phan-suppress-next-line PhanUndeclaredClassConstant */
				->instanceOfStorage(\OCA\Files_Sharing\ISharedStorage::class);
		}

		return !$isSharedStorage &&
			   !$storage->instanceOfStorage(StorageAdapter::class) &&
			   !$storage->instanceOfStorage(FailedStorage::class);
	}
}
