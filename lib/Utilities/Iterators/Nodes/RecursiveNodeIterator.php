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

use OCP\Files\Folder;
use OCP\Files\Node;
use OCA\DataExporter\Utilities\Iterators\Nodes\ISkipNodeCondition;

class RecursiveNodeIterator implements \RecursiveIterator {
	/** @var Folder */
	private $folder;

	/** @var Node[]|null */
	private $folderNodes = null;

	/** @var int|null */
	private $nodeCount = null;

	/** @var int */
	private $currentIndex = 0;

	/** @var ISkipNodeCondition[] */
	private $skipConditions = [];

	public function __construct(Folder $folder) {
		$this->folder = $folder;
	}

	public function rewind() {
		$this->folderNodes = $this->folder->getDirectoryListing();
		$this->nodeCount = \count($this->folderNodes);
		$this->currentIndex = 0;
		$this->findNextValid();
	}

	public function valid(): bool {
		return $this->currentIndex < $this->nodeCount;
	}

	public function next() {
		$this->currentIndex++;
		$this->findNextValid();
	}

	public function key() {
		return $this->folderNodes[$this->currentIndex]->getPath();
	}

	public function current(): Node {
		return $this->folderNodes[$this->currentIndex];
	}

	public function hasChildren(): bool {
		return $this->folderNodes[$this->currentIndex] instanceof Folder;
	}

	public function getChildren(): RecursiveNodeIterator {
		/** @var Folder $childFolder */
		$childFolder = $this->folderNodes[$this->currentIndex];
		/**
		 * $childFolder should always be a \OCP\Files\Folder instance because
		 * the `hasChildren` method checking if the current node is a Folder
		 * should has been called before. If `hasChildren` has returned false
		 * this method shouldn't have been called (the \RecursiveIterator interface,
		 * doesn't specify a way to deal with this)
		 */
		// @phan-suppress-next-line PhanTypeMismatchArgument
		$childIterator = new RecursiveNodeIterator($childFolder);

		foreach ($this->getSkipConditions() as $skipCondition) {
			$childIterator->addSkipCondition($skipCondition);
		}
		return $childIterator;
	}

	/**
	 * Find the next valid node, according to the conditions set
	 */
	private function findNextValid() {
		while ($this->valid() && $this->shouldSkipNode($this->current())) {
			$this->currentIndex++;
		}
	}

	/**
	 * Check if the target node should be skipped according to the conditions
	 * @param Node $currentNode the node to be checked
	 * @return bool true if it should be skipped, false otherwise
	 */
	private function shouldSkipNode(Node $currentNode): bool {
		foreach ($this->skipConditions as $skipCondition) {
			if ($skipCondition->shouldSkipNode($currentNode)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add a new skip condition. Every skip condition added will be used to check
	 * if the current node will be skipped automatically, so that node won't be
	 * available out of the iterator.
	 * Note that if a folder is skipped, that folder won't be scanned inside.
	 *
	 * Use this method BEFORE start iterating. Using it during the iteration might
	 * return confusing results.
	 * @param ISkipNodeCondition $condition the skip condition
	 */
	public function addSkipCondition(ISkipNodeCondition $condition) {
		$this->skipConditions[] = $condition;
	}

	/**
	 * Get the current set of ISkipNodeCondition. It will return an empty
	 * array if no condition is set
	 * @return ISkipNodeCondition[] the list of conditions currently set
	 */
	public function getSkipConditions(): array {
		return $this->skipConditions;
	}

	/**
	 * Clear the set of conditions currently set
	 */
	public function clearSkipConditions() {
		$this->skipConditions = [];
	}
}
