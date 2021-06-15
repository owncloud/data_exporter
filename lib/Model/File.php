<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 * @author Patrick Jahns <github@patrickjahns.de>
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
namespace OCA\DataExporter\Model;

/**
 * Represents the Files Metadata export format
 *
 * @package OCA\DataExporter\Model
 * @codeCoverageIgnore
 */
class File {
	public const TYPE_FOLDER = 'folder';
	public const TYPE_FILE = 'file';
	public const ROOT_FOLDER_PATH = '/files/';

	/** @var string */
	private $type;
	/** @var int */
	private $id;
	/** @var string */
	private $path;
	/** @var mixed */
	private $eTag;
	/** @var int */
	private $permissions;
	/** @var int */
	private $mtime;

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $type
	 * @return File
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 * @return File
	 */
	public function setId($id) {
		$this->id = (int) $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @param string $path
	 * @return File
	 */
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getETag() {
		return $this->eTag;
	}

	/**
	 * @param string $eTag
	 * @return File
	 */
	public function setETag($eTag) {
		$this->eTag = $eTag;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPermissions() {
		return $this->permissions;
	}

	/**
	 * @param int $permissions
	 * @return File
	 */
	public function setPermissions($permissions) {
		$this->permissions = $permissions;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getMtime() {
		return $this->mtime;
	}

	/**
	 * @param int $mtime
	 * @return File
	 */
	public function setMtime($mtime) {
		$this->mtime = $mtime;
		return $this;
	}
}
