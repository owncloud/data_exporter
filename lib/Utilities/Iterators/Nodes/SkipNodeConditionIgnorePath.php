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
 * Class representing a skip condition for the iterator: if the path of the node
 * is in the list of the paths to be ignored, the node will be skipped.
 * The list of ignored paths are relative to the base folder set in the constructor
 */
class SkipNodeConditionIgnorePath implements ISkipNodeCondition {
	/** @var Folder */
	private $baseFolder;

	/** @var string[] */
	private $ignoredPaths;

	public function __construct(Folder $baseFolder, array $ignoredPaths) {
		$this->baseFolder = $baseFolder;
		$this->ignoredPaths = $ignoredPaths;
	}

	public function shouldSkipNode(Node $node) {
		$nodeRelativePath = $this->baseFolder->getRelativePath($node->getPath());
		if ($nodeRelativePath === null) {
			return false;  // the node isn't inside the folder whose paths we want to ignore
		}

		foreach ($this->ignoredPaths as $ignoredPath) {
			if ($ignoredPath === $nodeRelativePath) {
				return true;
			}
		}
		return false;
	}
}
