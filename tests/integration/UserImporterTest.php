<?php

namespace OCA\DataExporter\Tests\Integration;

use OCA\DataExporter\Importer\MetadataImporter\UserImporter;
use OCA\DataExporter\Model\User;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;

/**
 * @group DB
 */
class UserImporterTest extends \Test\TestCase {

	/** @var IUserManager */
	private $userManager;
	/** @var IGroupManager */
	private $groupManager;
	/** @var IDBConnection */
	private $db;

	private $testId;
	private $testUid;
	private $testDisplayName;
	private $testEmail;
	private $testGroups = ['group1', 'group2'];

	public function setUp() {
		parent::setUp();
		$this->userManager = \OC::$server->getUserManager();
		$this->groupManager = \OC::$server->getGroupManager();
		$this->db = \OC::$server->getDatabaseConnection();

		foreach ($this->testGroups as $group) {
			$this->groupManager->createGroup($group);
		}

		$this->testId = \bin2hex(\random_bytes(4));
		$this->testUid = "importTest-{$this->testId}";
		$this->testDisplayName = "disp-{$this->testDisplayName}";
		$this->testEmail = "mail-{$this->testId}@example.com";
	}

	public function testUserImporter() {
		$import = new User();

		$import->setUserId($this->testUid);
		$import->setDisplayName($this->testDisplayName);
		$import->setEmail($this->testEmail);
		$import->setBackend('Database');
		$import->setGroups($this->testGroups);

		$importer = new UserImporter(
			$this->userManager,
			$this->groupManager,
			new UserImporter\UpdatePasswordHashQuery($this->db)
		);

		$importer->import($import);

		$this->assertTrue($this->userManager->userExists($this->testUid));
		$user = $this->userManager->get($this->testUid);
		$this->assertEquals($this->testEmail, $user->getEMailAddress());
		$this->assertEquals($this->testDisplayName, $user->getDisplayName());

		foreach ($this->testGroups as $group) {
			$inGroup = $this->groupManager->isInGroup($this->testUid, $group);
			$this->assertTrue($inGroup);
		}
	}
}
