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
namespace OCA\DataExporter\Exporter;

use OCP\Files\FileInfo;
use OC\Files\Storage\FailedStorage;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\StorageAdapter;

/**
 * Iterates over all given filesystem nodes recursively skipping ext. storages, shares and
 * failed storages. Also ignores paths starting with $ignorePaths
 *
 * Use RecursiveNodeIterator::create() to correctly instantiate the iterator.
 *
 * @package OCA\DataExporter\Exporter
 */
class RecursiveNodeIterator implements \RecursiveIterator {

	/** @var array  */
	private $rootNodes;
	/** @var int  */
	private $nodeCount;
	/** @var int  */
	private $currentIndex;
	/** @var string[]  */
	private $ignorePaths = [
		'cache',
		'thumbnails',
		'uploads'
	];

	/**
	 * @param \OCP\Files\Node[] $nodes
	 */
	public function __construct(array $nodes) {
		$this->rootNodes = \array_values(
			\array_filter($nodes, function (\OCP\Files\Node $node) {
				return !$this->isIgnoredPath($node) && $this->isUserStorage($node->getStorage());
			})
		);

		$this->nodeCount = \count($this->rootNodes);
		$this->currentIndex = 0;
	}

	/**
	 * @param \OCP\Files\Folder $folder
	 * @return \RecursiveIteratorIterator
	 * @throws \OCP\Files\NotFoundException
	 */
	public static function create(\OCP\Files\Folder $folder) : \RecursiveIteratorIterator {
		return new \RecursiveIteratorIterator(
			new RecursiveNodeIterator($folder->getDirectoryListing()),
			\RecursiveIteratorIterator::SELF_FIRST
		);
	}

	/**
	 * @param \OCP\Files\Node $node
	 * @return bool
	 */
	private function isIgnoredPath(\OCP\Files\Node $node) : bool {
		$path = $node->getPath();
		$relativePath = $path;
		$uidOwner = $node->getOwner()->getUID();

		if (\strpos($path, "/$uidOwner/") === 0) {
			$relativePath = \substr($path, \strlen("/$uidOwner/"));
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

	public function hasChildren() : bool {
		return ($this->rootNodes[$this->currentIndex]->getType() === FileInfo::TYPE_FOLDER) &&
			(\count($this->rootNodes[$this->currentIndex]->getDirectoryListing()) > 0);
	}

	public function getChildren() {
		return new RecursiveNodeIterator($this->rootNodes[$this->currentIndex]->getDirectoryListing());
	}

	public function current() {
		return $this->rootNodes[$this->currentIndex];
	}

	public function next() {
		$this->currentIndex++;
	}

	public function key() {
		return $this->currentIndex;
	}

	public function valid() {
		return $this->currentIndex < $this->nodeCount;
	}

	public function rewind() {
		$this->currentIndex = 0;
	}
}
