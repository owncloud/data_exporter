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
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCA\DataExporter\Utilities\Path;
use OCP\Files\NotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Exporter {

	/** @var Serializer  */
	private $serializer;
	/** @var MetadataExtractor  */
	private $metadataExtractor;
	/** @var FilesExtractor  */
	private $filesExtractor;
	/** @var Filesystem  */
	private $filesystem;
	/** @var RecursiveNodeIteratorFactory  */
	private $iteratorFactory;
	/** @var OptionsResolver  */
	private $optionsResolver;

	public function __construct(Serializer $serializer, MetadataExtractor $metadataExtractor, FilesExtractor $filesExtractor, Filesystem $filesystem, RecursiveNodeIteratorFactory $iteratorFactory) {
		$this->serializer = $serializer;
		$this->metadataExtractor = $metadataExtractor;
		$this->filesExtractor = $filesExtractor;
		$this->filesystem = $filesystem;
		$this->iteratorFactory = $iteratorFactory;

		$optionsResolver = new OptionsResolver();
		$this->configureOptions($optionsResolver);
		$this->optionsResolver = $optionsResolver;
	}

	private function configureOptions(OptionsResolver $resolver) {
		$resolver
			->setDefaults(['exportFiles' => true, 'exportFileIds' => false])
			->setRequired("trashBinAvailable");
	}

	/**
	 * @param string $uid
	 * @param string $exportDirectoryPath
	 * @param array $options
	 * @return void
	 *
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OC\User\NoUserException
	 */
	public function export($uid, $exportDirectoryPath, $options = []) {
		$options = $this->optionsResolver->resolve($options);
		$exportPath = Path::join($exportDirectoryPath, $uid);
		$metaData = $this->metadataExtractor->extract(
			$uid,
			$exportPath,
			[
			'trashBinAvailable' => $options['trashBinAvailable'],
			'exportFileIds' => $options['exportFileIds']
			]
		);

		$this->filesystem->dumpFile(
			Path::join($exportPath, '/user.json'),
			$this->serializer->serialize($metaData)
		);

		if ($options['exportFiles']) {
			list($iterator, $baseFolder) = $this->iteratorFactory->getUserFolderRecursiveIterator($uid);
			$this->filesExtractor->export($iterator, $baseFolder, Path::join($exportPath, 'files'));

			if ($options['trashBinAvailable']) {
				try {
					list($iterator, $baseFolder) = $this->iteratorFactory->getTrashBinRecursiveIterator($uid);
				} catch (NotFoundException $e) {
					// If the user has never deleted anything, then the trashbin folder will not exist.
					// In this case, there is no need to export the trashbin.
					return;
				}
				$this->filesExtractor->export($iterator, $baseFolder, Path::join($exportPath, 'files_trashbin'));
			}
		}
	}
}
