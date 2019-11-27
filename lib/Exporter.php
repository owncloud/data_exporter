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
namespace OCA\DataExporter;

use OCA\DataExporter\Extractor\FilesExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor;
use OCA\DataExporter\Utilities\Path;
use Symfony\Component\Filesystem\Filesystem;

class Exporter {

	/** @var Serializer  */
	private $serializer;
	/** @var MetadataExtractor  */
	private $metadataExtractor;
	/** @var FilesExtractor  */
	private $filesExtractor;
	/** @var Filesystem  */
	private $filesystem;

	public function __construct(Serializer $serializer, MetadataExtractor $metadataExtractor, FilesExtractor $filesExtractor, Filesystem $filesystem) {
		$this->serializer = $serializer;
		$this->metadataExtractor = $metadataExtractor;
		$this->filesExtractor = $filesExtractor;
		$this->filesystem = $filesystem;
	}

	/**
	 * @param string $uid
	 * @param string $exportDirectoryPath
	 * @param bool $exportFiles
	 *
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function export($uid, $exportDirectoryPath, $exportFiles = true, $exportFileIds = false) {
		$exportPath = Path::join($exportDirectoryPath, $uid);
		$metaData = $this->metadataExtractor->extract($uid, $exportPath, $exportFileIds);

		$this->filesystem->dumpFile(
			Path::join($exportPath, '/user.json'),
			$this->serializer->serialize($metaData)
		);

		if ($exportFiles) {
			$filesPath = Path::join($exportPath, 'files');
			$this->filesExtractor->export($uid, $filesPath);
		}
	}
}
