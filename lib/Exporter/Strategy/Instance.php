<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
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

namespace OCA\DataExporter\Exporter\Strategy;

use OCA\DataExporter\Exporter\InstanceExtractor;
use OCA\DataExporter\Exporter\Parameters;
use OCA\DataExporter\Serializer;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class InstanceExporter
 *
 * @package OCA\DataExporter
 */
class Instance implements ExportStrategyInterface {
	/**
	 * @var Serializer
	 */
	private $serializer;

	/**
	 * @var InstanceExtractor
	 */
	private $instanceExtractor;

	/**
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * InstanceExporter constructor.
	 *
	 * @param Serializer $serializer
	 * @param InstanceExtractor $instanceExtractor
	 * @param Filesystem $filesystem
	 */
	public function __construct(Serializer $serializer, InstanceExtractor $instanceExtractor, Filesystem $filesystem) {
		$this->serializer = $serializer;
		$this->instanceExtractor = $instanceExtractor;
		$this->filesystem = $filesystem;
	}

	/**
	 * @param string $exportDirectoryPath
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function export(Parameters $params) {
		$instanceData = $this->instanceExtractor->extract();
		$this->filesystem->dumpFile(
			"{$params->getExportDirectoryPath()}/instancedata.json",
			$this->serializer->serialize($instanceData)
		);
	}
}
