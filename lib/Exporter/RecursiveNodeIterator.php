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

use OC\Files\Node\File;
use OC\Files\Node\Folder;
use OCP\Files\FileInfo;

class RecursiveNodeIterator implements \RecursiveIterator {

	/** @var Folder[]|File[] $rootNodes  */
	private $rootNodes;
	/** @var int  */
	private $nodeCount;
	/** @var int  */
	private $currentIndex;

	public function __construct(array $nodes) {
		$this->rootNodes = $nodes;
		$this->nodeCount = \count($nodes);
		$this->currentIndex = 0;
	}
	public function hasChildren() {
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
