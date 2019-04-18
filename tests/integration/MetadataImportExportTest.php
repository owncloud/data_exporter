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

use OCA\DataExporter\Extractor\MetadataExtractor;
use OCA\DataExporter\Importer\MetadataImporter;
use OCA\DataExporter\Importer\MetadataImporter\UserImporter;
use OCA\DataExporter\Serializer;
use OCP\IUser;
use OCP\IUserManager;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class MetadataImportExportTest extends \Test\TestCase {
	use UserTrait;

	/** @var IUser */
	private $testUser;

	public function setUp() {
		parent::setUp();
		/** @var IUserManager $userManager */
		$userManager = \OC::$server->getUserManager();
		$testId = \bin2hex(\random_bytes(4));

		$this->testUser = $userManager->createUser("importexportintest-$testId", '123');
		$this->testUser->setEMailAddress("$testId@example.com");

		$config = \OC::$server->getConfig();
		$config->setUserValue($this->testUser->getUID(), 'core', 'status', 'ok');
		$config->setUserValue($this->testUser->getUID(), 'core', 'value', 'key');

		// need to adjust the UserImporter to allow to create users in the Dummy backend
		$userImporter = \OC::$server->query(UserImporter::class);
		$userImporter->setAllowedBackends(['Database', 'Dummy']);
	}

	public function testExtractThenImportThenExtractGeneratesSameMetadata() {
		/** @var MetadataExtractor $extractor */
		$extractor = \OC::$server->query(MetadataExtractor::class);
		/** @var MetadataImporter $importer */
		$importer = \OC::$server->query(MetadataImporter::class);

		$export = $extractor->extract($this->testUser->getUID());
		// Don't test files for now
		$export->setFiles([]);

		$this->testUser->delete();

		$importer->import($export);

		$reExport = $extractor->extract($this->testUser->getUID());
		// Don't test files for now
		$reExport->setFiles([]);

		$date = new \DateTimeImmutable('now');

		// Set same date on both exports for testing
		$export->setDate($date);
		$reExport->setDate($date);

		$ser = new Serializer();
		$export = $ser->serialize($export);
		$reExport = $ser->serialize($reExport);

		$this->assertEquals(
			$export,
			$reExport,
			'Export metadata does not match after import/export cycle'
		);
	}

	public function tearDown() {
		$this->testUser->delete();
		return parent::tearDown();
	}
}
