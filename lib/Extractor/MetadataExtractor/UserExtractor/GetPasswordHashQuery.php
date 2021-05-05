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
namespace OCA\DataExporter\Extractor\MetadataExtractor\UserExtractor;

use OCP\IDBConnection;

/**
 * Wraps a database query to extract a users hashed password since there
 * is currently no api in ownCloud to do this and query builder is hard to mock.
 *
 * @package OCA\DataExporter\Extractor\MetadataExtractor
 * @codeCoverageIgnore
 */
class GetPasswordHashQuery {

	/** @var IDBConnection  */
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @param string $uid
	 * @return bool|string|null
	 */
	public function execute($uid) {
		$qb = $this->db->getQueryBuilder();
		/* @phan-suppress-next-line PhanDeprecatedFunction */
		$password = $qb->from('users')
			->select('password')
			->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid)))
			->execute()->fetchColumn();

		if ($password === false || empty(\trim($password))) {
			return null;
		}

		return $password;
	}
}
