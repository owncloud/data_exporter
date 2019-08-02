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

use OCA\DataExporter\Exporter;
use OCA\DataExporter\Exporter\Strategy\SingleUser;
use OCP\AppFramework\IAppContainer;

class Factory {

	/** @var IAppContainer  */
	private $di;

	public function __construct(IAppContainer $di) {
		$this->di = $di;
	}

	public function get(Parameters $params) {
		if ($params->getUserId() !== null) {
			return new Exporter($this->di->query(SingleUser::class));
		}

		if ($params->getAll() === true) {
			return new Exporter($this->di->query(Exporter\Strategy\Everything::class));
		}

		throw new \RuntimeException('Unknown export strategy for given parameters');
	}
}
