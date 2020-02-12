<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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
namespace OCA\DataExporter\Model;

/**
 * Represents a File in trashbin in the export format
 *
 * @package OCA\DataExporter\Model
 * @codeCoverageIgnore
 */
class TrashBinFile extends File {
	/** @var string|null */
	private $originalName;
	/** @var int|null */
	private $deletionTimestamp;
	/** @var string|null */
	private $originalLocation;

	/**
	 * @return int
	 */
	public function getDeletionTimestamp() {
		return $this->deletionTimestamp;
	}

	/**
	 * @param int|null $deletionTimestamp
	 * @return TrashBinFile
	 */
	public function setDeletionTimestamp($deletionTimestamp) {
		$this->deletionTimestamp = $deletionTimestamp;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOriginalLocation() {
		return $this->originalLocation;
	}

	/**
	 * @param string $originalLocation
	 * @return TrashBinFile
	 */
	public function setOriginalLocation($originalLocation) {
		$this->originalLocation = $originalLocation;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOriginalName() {
		return $this->originalName;
	}

	/**
	 * @param string $originalName
	 * @return TrashBinFile
	 */
	public function setOriginalName($originalName) {
		$this->originalName = $originalName;
		return $this;
	}
}
