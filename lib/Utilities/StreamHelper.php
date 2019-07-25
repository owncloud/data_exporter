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
namespace OCA\DataExporter\Utilities;

use OCA\DataExporter\Importer\ImportException;
use OCA\DataExporter\Model\File;
use OCA\DataExporter\Model\Share;
use OCA\DataExporter\Serializer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class StreamHelper {
	/**
	 * @var Filesystem
	 */
	private $filesystem;
	/**
	 * @var Serializer
	 */
	private $serializer;

	/**
	 * StreamHelper constructor.
	 *
	 * @param Filesystem $filesystem
	 * @param Serializer $serializer
	 */
	public function __construct(Filesystem $filesystem, Serializer $serializer) {
		$this->filesystem = $filesystem;
		$this->serializer = $serializer;
	}

	/**
	 * SetUp the Stream for the export
	 * Create empty file and open stream
	 *
	 * @param string $file
	 * @param string $mode
	 * @param bool $createFile
	 *
	 * @return resource
	 */
	public function initStream($file, $mode, $createFile = false) {
		if ($createFile) {
			$this->filesystem->dumpFile($file, '');
		}
		return \fopen($file, $mode);
	}

	/**
	 * close the Stream for the export
	 *
	 * @param resource $resource
	 *
	 * @return void
	 */
	public function closeStream($resource) {
		\fclose($resource);
	}

	/**
	 * Write line to the Stream for the export
	 * Create empty file and open stream
	 *
	 * @param resource $resource
	 * @param Share | File $entity
	 *
	 * @return void
	 */
	public function writelnToStream($resource, $entity) {
		$data = $this->serializer->serialize($entity);
		\fwrite($resource, $data . PHP_EOL);
	}

	/**
	 * Read single line from stream resource
	 *
	 * @param resource $resource
	 * @param string $type
	 *
	 * @return Share | File | object | bool
	 */
	public function readlnFromStream($resource, $type) {
		try {
			return $this->serializer->deserialize(
				\fgets($resource),
				$type
			);
		} catch (UnexpectedValueException $exception) {
			if (\feof($resource)) {
				return false;
			}
			throw new ImportException('Invalid Data.');
		}
	}

	/**
	 * @param resource $resource
	 *
	 * @return void
	 */
	public function checkEndOfStream($resource) {
		if (!\feof($resource)) {
			throw new ImportException("Error: Failure while reading Stream");
		}
	}
}
