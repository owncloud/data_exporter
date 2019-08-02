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
namespace OCA\DataExporter\Exporter;

class Parameters {
	private $exportDirectoryPath;
	private $userId;
	private $all;

	/**
	 * @return mixed
	 */
	public function getExportDirectoryPath() {
		return $this->exportDirectoryPath;
	}

	/**
	 * @param Parameters $exportDirectoryPath
	 * @return Parameters
	 */
	public function setExportDirectoryPath($exportDirectoryPath) {
		$this->exportDirectoryPath = $exportDirectoryPath;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * @param mixed $userId
	 * @return Parameters
	 */
	public function setUserId($userId) {
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAll() {
		return $this->all;
	}

	/**
	 * @param mixed $all
	 * @return Parameters
	 */
	public function setAll($all) {
		$this->all = $all;
		return $this;
	}
}
