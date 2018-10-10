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
namespace OCA\DataExporter\Model\UserMetadata\User;

use OCA\DataExporter\Model\AbstractModel;

class File extends AbstractModel {
	const TYPE_FOLDER = 'folder';
	const TYPE_FILE = 'file';

	private $type;
	/** @var string */
	private $path;
	/** @var mixed */
	private $eTag;
	/** @var int */
	private $permissions;

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
	public function setType(string $type) : File {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @param string $path
	 * @return File
	 */
	public function setPath(string $path): File {
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
	public function setETag($eTag) : File {
		$this->eTag = $eTag;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPermissions(): int {
		return $this->permissions;
	}

	/**
	 * @param int $permissions
	 * @return File
	 */
	public function setPermissions(int $permissions): File {
		$this->permissions = $permissions;
		return $this;
	}
}
