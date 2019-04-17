<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
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

namespace OCA\DataExporter;

use OCA\DataExporter\Importer\ImportException;
use OCA\DataExporter\Importer\InstanceDataImporter;
use OCA\DataExporter\Model\Instance;
use Symfony\Component\Filesystem\Filesystem;

class InstanceImporter {
	/**
	 * @var InstanceDataImporter
	 */
	private $instanceDataImporter;

	/**
	 * @var Serializer
	 */
	private $serializer;

	/**
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * InstanceImporter constructor.
	 *
	 * @param Serializer $serializer
	 * @param InstanceDataImporter $instanceDataImporter
	 * @param Filesystem $filesystem
	 */
	public function __construct(
		Serializer $serializer,
		InstanceDataImporter $instanceDataImporter,
		Filesystem $filesystem
	) {
		$this->serializer = $serializer;
		$this->instanceDataImporter = $instanceDataImporter;
		$this->filesystem = $filesystem;
	}

	/**
	 * @param string $pathToExportDir
	 *
	 * @return void
	 */
	public function import($pathToExportDir) {
		$instanceDataPath = "$pathToExportDir/instancedata.json";

		if (!$this->filesystem->exists($instanceDataPath)) {
			throw new ImportException("instancedata.json not found in '$instanceDataPath'");
		}

		/**
		 * @var Instance $instanceData
		 */
		$instanceData = $this->serializer->deserialize(
			\file_get_contents($instanceDataPath),
			Instance::class
		);

		$this->instanceDataImporter->import($instanceData);
	}
}
