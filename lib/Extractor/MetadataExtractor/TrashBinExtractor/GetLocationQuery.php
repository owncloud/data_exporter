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
namespace OCA\DataExporter\Extractor\MetadataExtractor\TrashBinExtractor;

use OCP\IDBConnection;

/**
 * Wraps database query to extract original location from trashbin table.
 * TrashBin iteration api is buggy (does not return folders)
 *
 * @package OCA\DataExporter\Extractor\MetadataExtractor
 * @codeCoverageIgnore
 */
class GetLocationQuery {

	/** @var IDBConnection  */
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @param string $uid
	 * @param string $filename
	 * @param int $timestamp
	 * @return bool|string|null
	 */
	public function execute($uid, $filename, $timestamp) {
		$qb = $this->db->getQueryBuilder();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$location = $qb->from('files_trash')
			->select('location')
			->where($qb->expr()->eq('user', $qb->createNamedParameter($uid)))
			->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($filename)))
			->andWhere($qb->expr()->eq('timestamp', $qb->createNamedParameter($timestamp)))
			->execute()->fetchColumn();

		if ($location === false) {
			$location = null;
		}

		return $location;
	}
}
