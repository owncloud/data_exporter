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
namespace OCA\DataExporter\Tests\Integration;

use OCA\DataExporter\Extractor\FilesExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor;
use OCA\DataExporter\Importer\FilesImporter;
use OCA\DataExporter\Importer\MetadataImporter;
use OCA\DataExporter\Importer\MetadataImporter\UserImporter;
use OCA\DataExporter\Importer\TrashBinImporter;
use OCA\DataExporter\Model\File;
use OCA\DataExporter\Model\Metadata;
use OCA\DataExporter\Model\TrashBinFile;
use OCA\DataExporter\Serializer;
use OCA\DataExporter\Utilities\Iterators\Nodes\RecursiveNodeIteratorFactory;
use OCP\IGroup;
use OCP\IGroupManager;
use org\bovigo\vfs\vfsStream;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class MetadataImportExportTest extends \Test\TestCase {
	use UserTrait;

	/** @var IGroup $testGroup */
	private $testGroup;
	public const FILES_CONTENT = <<< JSONL
{"type":"folder","path":"\/files","eTag":"5d481eb6273f6","permissions":31}
{"type":"file","path":"\/files\/someFile.txt","eTag":"0f7c0b5154f85e7aa74a54d361feb7d5","permissions":27}
{"type":"folder","path":"\/files\/someFolder","eTag":"5d481eb6273f6","permissions":31}

JSONL;
	public const SHARES_CONTENT = <<< JSONL
{"path":"\/someFile.txt","shareType":"remote","type":"file","owner":"testuser","sharedBy":"testuser","sharedWith":"testuser2@http://localhost","permissions":19,"expirationDate":null,"password":null,"name":null,"token":null}
{"path":"\/someFolder","shareType":"group","type":"folder","owner":"testuser","sharedBy":"testuser","sharedWith":"people","permissions":31,"expirationDate":null,"password":null,"name":null,"token":null}

JSONL;
	public const USER_CONTENT = <<< JSONL
{"date":"2019-08-05T12:21:14+00:00","originServer":"http:\/\/localhost\/","user":{"userId":"testuser","displayName":"someUser","email":"test@owncloud.com","quota":"default","backend":"Database","enabled":true,"groups":["admin","people"],"preferences":[{"appId":"core","configKey":"lang","configValue":"de"},{"appId":"core","configKey":"timezone","configValue":"Europe\/Berlin"}]}}
JSONL;
	public const TRASH_BIN_CONTENT = <<< JSONL
{"deletionTimestamp":1573301236,"originalLocation":"someFolder","originalName":"user_ldap-0.13.0.tar.gz","type":"file","path":"\/user_ldap-0.13.0.tar.gz.d1573301236","eTag":"66a2151e70d144e9b0acdc37b71c3ff2","permissions":27,"mtime":1570180669}
JSONL;

	public function setUp(): void {
		parent::setUp();
		/** @var IGroupManager $groupManager */
		$groupManager = \OC::$server->getGroupManager();

		$this->testGroup = $groupManager->createGroup('people');

		// need to adjust the UserImporter to allow to create users in the Dummy backend
		$userImporter = \OC::$server->query(UserImporter::class);
		$userImporter->setAllowedBackends(['Database', 'Dummy']);
	}

	public function testExtractThenImportThenExtractGeneratesSameMetadata() {
		/** @var MetadataExtractor $metadataExtractor */
		$metadataExtractor = \OC::$server->query(MetadataExtractor::class);
		/** @var MetadataImporter $metadataImporter */
		$metadataImporter = \OC::$server->query(MetadataImporter::class);
		/** @var FilesExtractor $filesExtractor */
		$filesExtractor = \OC::$server->query(FilesExtractor::class);
		/** @var MetadataExtractor\FilesMetadataExtractor $filesMetadataExtractor */
		$filesMetadataExtractor = \OC::$server->query(MetadataExtractor\FilesMetadataExtractor::class);
		/** @var FilesImporter $filesImporter*/
		$filesImporter = \OC::$server->query(FilesImporter::class);
		/** @var Serializer $serializer */
		$serializer = \OC::$server->query(Serializer::class);
		/** @var RecursiveNodeIteratorFactory $iteratorFactory */
		$iteratorFactory = \OC::$server->query(RecursiveNodeIteratorFactory::class);
		/** @var MetadataExtractor\TrashBinExtractor $trashBinMetaDataExtractor */
		$trashBinMetaDataExtractor = \OC::$server->query(MetadataExtractor\TrashBinExtractor::class);
		/** @var TrashBinImporter $trashBinImporter */
		$trashBinImporter = \OC::$server->query(TrashBinImporter::class);

		$directoryTree = [
			'testimport' => [
				'testuser' => [
					'shares.jsonl' => $this::SHARES_CONTENT,
					'files.jsonl' => $this::FILES_CONTENT,
					'user.json' => $this::USER_CONTENT,
					'trashbin.jsonl' => $this::TRASH_BIN_CONTENT,
					'files' => [
						'files' => [
							'someFile.txt' => 'This is a test!',
							'someFolder' => []
						]
					],
					'files_trashbin' => [
						'user_ldap-0.13.0.tar.gz.d1573301236' => '',

					]
				]
			],
			'testexport' => [
				'testuser' => []
			]
		];
		$fileSystem = vfsStream::setup('testroot', '644', $directoryTree);
		$date = new \DateTimeImmutable('now');
		/** @var Metadata $metadata */
		$metadata = $serializer->deserialize(
			\file_get_contents($fileSystem->url() . '/testimport/testuser/user.json'),
			Metadata::class
		);
		$metadata->setDate($date);
		$metadataImporter->import($metadata);
		$filesImporter->import('testuser', $fileSystem->url() . '/testimport/testuser');
		// @todo Shares do not import / export cleanly because of conversion
		// to federated
		$trashBinImporter->import('testuser', $fileSystem->url() . '/testimport/testuser');

		$metadataExported = $metadataExtractor->extract('testuser', $fileSystem->url() . '/testexport', ['trashBinAvailable' => true]);
		$metadataExported->setDate($date);
		$metadataExported->getUser()->setBackend('Database');
		$user = $metadataExported->getUser();
		$groups = $user->getGroups();
		\sort($groups);
		$metadataExported->getUser()->setGroups($groups);
		$this->assertEquals(
			$metadata,
			$metadataExported,
			'Export metadata does not match after import/export cycle'
		);
		$filesMetadataExtractor->extract('testuser', $fileSystem->url() . '/testexport/testuser');
		list($iter, $baseFolder) = $iteratorFactory->getUserFolderRecursiveIterator('testuser');
		$filesExtractor->export($iter, $baseFolder, $fileSystem->url() . '/testexport/testuser');
		$filesMetadata = \explode(
			PHP_EOL,
			\file_get_contents($fileSystem->url() . '/testexport/testuser/files.jsonl')
		);

		$expectedFilesMetadata = \explode(
			PHP_EOL,
			\file_get_contents($fileSystem->url() . '/testexport/testuser/files.jsonl')
		);
		foreach ($expectedFilesMetadata as $key => $expected) {
			if (!empty($expected)) {
				$expectedObject = $serializer->deserialize($expected, File::class);
				$actualObject = $serializer->deserialize($filesMetadata[$key], File::class);
				// @todo Etag of parent doesn't match
				$actualObject->setEtag($expectedObject->getEtag());
				$this->assertEquals($expectedObject, $actualObject);
			}
		}

		$trashBinMetaDataExtractor->extract('testuser', $fileSystem->url() . '/testexport/testuser');

		$actualTrashBinMetadata = \explode(
			PHP_EOL,
			\file_get_contents($fileSystem->url() . '/testexport/testuser/trashbin.jsonl')
		);

		$expectedTrashBinMetadata = \explode(
			PHP_EOL,
			\file_get_contents($fileSystem->url() . '/testimport/testuser/trashbin.jsonl')
		);
		foreach ($expectedTrashBinMetadata as $key => $expected) {
			if (!empty($expected)) {
				$expectedObject = $serializer->deserialize($expected, TrashBinFile::class);
				$actualObject = $serializer->deserialize($actualTrashBinMetadata[$key], TrashBinFile::class);
				// @todo Etag of parent doesn't match
				$actualObject->setMtime($expectedObject->getMtime());
				$actualObject->setEtag($expectedObject->getEtag());
				$this->assertEquals($expectedObject, $actualObject);
			}
		}
	}

	public function tearDown(): void {
		$this->testGroup->delete();
		parent::tearDown();
	}
}
