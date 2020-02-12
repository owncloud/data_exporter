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
namespace OCA\DataExporter\Importer\TrashBinImporter;

use OCP\IDBConnection;

/**
 * Wraps a database query to add an entry to files_trash. There
 * are currently no api`s for this in core.
 *
 * @package OCA\DataExporter\Extractor\MetadataExtractor
 * @codeCoverageIgnore
 */
class AddToTrashBinQuery {

	/** @var IDBConnection  */
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @param string $id
	 * @param string $user
	 * @param int $timestamp
	 * @param string $location
	 */
	public function execute($id, $user, $timestamp, $location) {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('files_trash')->values([
			'id' => $qb->createNamedParameter($id),
			'user' => $qb->createNamedParameter($user),
			'timestamp' => $qb->createNamedParameter($timestamp),
			'location' => $qb->createNamedParameter($location)
		]);
		$qb->execute();
	}
}
