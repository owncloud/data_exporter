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

/**
 * Class representing a skip condition for the iterator: if the storage id of
 * the node is different than the one set, the node will be skipped.
 * This means that the iterator will traverse only the nodes inside that storage
 */
class SkipNodeConditionDifferentStorage implements ISkipNodeCondition {
	/** @var string */
	private $storageId;

	public function __construct(string $storageId) {
		$this->storageId = $storageId;
	}

	public function shouldSkipNode(Node $node): bool {
		if ($node instanceof Folder) {
			$nodeStorageId = $node->getStorage()->getId();
			if ($this->storageId !== $nodeStorageId) {
				return true;
			}
		}
		return false;
	}
}
