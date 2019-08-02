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
namespace OCA\DataExporter\Exporter\Strategy;

use OC\User\Account;
use OC\User\AccountMapper;
use OCA\DataExporter\Exporter\Parameters;

class Everything implements ExportStrategyInterface {

	/** @var Instance  */
	private $instanceExporter;
	/** @var SingleUser  */
	private $singleUserExporter;
	/** @var AccountMapper  */
	private $accountMapper;

	public function __construct(Instance $instanceExporter, SingleUser $singleUserExporter, AccountMapper $am) {
		$this->instanceExporter = $instanceExporter;
		$this->singleUserExporter = $singleUserExporter;
		$this->accountMapper = $am;
	}

	public function export(Parameters $params) {
		$this->instanceExporter->export($params);

		$this->accountMapper->callForAllUsers(function (Account $acc) use ($params) {
			$uid = $acc->getUserId();
			$p = new Parameters();
			$p->setUserId($uid);
			$p->setExportDirectoryPath($params->getExportDirectoryPath());

			$this->singleUserExporter->export($p);
		}, null, false);
	}
}
