<?php
/**
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
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use TestHelpers\SetupHelper;

require_once 'bootstrap.php';

/**
 * Context for files classifier specific steps
 */
class DataExporterContext implements Context {
	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * The relative path from the core tests/acceptance folder to the test data
	 * folder.
	 *
	 * @var string
	 */
	private $dataDir = __DIR__ . '/../../data/';

	private $outputDir = __DIR__ . '/../../output/';

	/**
	 * This directory is created on each scenario run with a random name.
	 * Every output of the exporter is stored here. All step paths are relative
	 * to this directory.
	 *
	 * @var string
	 */
	private $scenarioDir;

	/**
	 * Base path of last export e.g /home/user/exports/
	 *
	 * @var string
	 */
	private $lastExportBasePath;

	/**
	 * Path to last export e.g /home/user/exports/user0
	 *
	 * @var string
	 */
	private $lastExportPath;

	/**
	 * Username of the last exported user e.g user0
	 *
	 * @var string
	 */
	private $lastExportUser;

	/**
	 * Path to metadata of last export e.g /home/user/exports/user0/metadata.json
	 *
	 * @var string
	 */
	private $lastExportMetadataPath;

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setUpScenario(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
		SetupHelper::init(
			$this->featureContext->getAdminUsername(),
			$this->featureContext->getAdminPassword(),
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getOcPath()
		);

		$scenarioId = \bin2hex(\random_bytes(6));
		$this->scenarioDir = self::path("{$this->outputDir}/$scenarioId");
		if (!\mkdir($this->scenarioDir) && !\is_dir($this->scenarioDir)) {
			throw new \RuntimeException(\sprintf('Scenario directory could not be created: %s', $this->scenarioDir));
		}
	}

	/**
	 * @AfterScenario
	 *
	 * @return void
	 */
	public function deleteLastExport() {
		if ($this->scenarioDir && \file_exists($this->scenarioDir)) {
			\system('rm -rf ' . \escapeshellarg($this->scenarioDir));
		}

		$this->scenarioDir = null;
		$this->lastExportBasePath = null;
		$this->lastExportPath = null;
		$this->lastExportUser = null;
		$this->lastExportMetadataPath = null;
	}

	/**
	 * @When user :user is exported to path :path using the occ command
	 * @Given user :user has been exported to path :path using the occ command
	 *
	 * @param string $user
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function exportUserUsingTheCli($user, $path) {
		$internalPath = self::path("{$this->scenarioDir}/$path");

		$this->featureContext->runOcc(['instance:export:user', $user, $internalPath]);

		$this->lastExportBasePath = $internalPath;
		$this->lastExportPath = self::path("{$this->lastExportBasePath}/$user/");
		$this->lastExportUser = $user;
		$this->lastExportMetadataPath = "{$this->lastExportPath}/metadata.json";
	}

	/**
	 * @Then the directory :path should contain an export
	 * @param string $path
	 *
	 * Checks whether a given file is present physically
	 * and inside metadata
	 */
	public function thenTheDirectoryShouldContainAnExport($path) {
		self::assertPathContainsExport(self::path("$this->scenarioDir/$path"));
	}

	/**
	 * @Then the last export should contain file :path
	 *
	 * @param string $path
	 *
	 * Checks whether a given file is present physically
	 * and inside metadata
	 *
	 */
	public function theLastExportContainsFile($path) {
		self::assertPathContainsExport($this->lastExportPath);
		$this->assertFilePhysicallyExistInLastExport($path, "File $path does not exist");
		$this->assertFileExistsInLastExportMetadata($path);
	}

	/**
	 * @Then the last export should contain file :path with content :content
	 *
	 * @param string $path
	 * @param string $content
	 *
	 * Checks whether a given file is present physically
	 * and inside metadata
	 *
	 */
	public function theLastExportContainsFileWithContent($path, $content) {
		self::assertPathContainsExport($this->lastExportPath);
		$this->assertFileExistsInLastExportMetadata($path);
		$this->assertFilePhysicallyExistInLastExportWithContent($path, $content);
	}

	/**
	 * @When a user is imported from path :path using the occ command
	 * @Given a user has been imported from path :path using the occ command
	 *
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function importUserUsingTheCli($path) {
		$importPath = self::path("$this->dataDir/$path");
		$this->featureContext->runOcc(['instance:import:user', $importPath]);
	}

	private static function assertPathContainsExport($path) {
		\PHPUnit_Framework_Assert::assertDirectoryExists(
			$path,
			"Export directory $path does not exist"
		);

		\PHPUnit_Framework_Assert::assertDirectoryExists(
			self::path("$path/files"),
			"No files directory found inside export $path"
		);

		\PHPUnit_Framework_Assert::assertFileExists(
			self::path("$path/metadata.json"),
			"No metadata.json found inside export $path"
		);
	}

	private function assertFilePhysicallyExistInLastExport($filename, $message = '') {
		\PHPUnit_Framework_Assert::assertFileExists(
			self::path("{$this->lastExportPath}/files/$filename"),
			$message
		);
	}

	private function assertFilePhysicallyExistInLastExportWithContent($filename, $content, $message = '') {
		$exportedFilePath = self::path("{$this->lastExportPath}/files/$filename");
		\PHPUnit_Framework_Assert::assertEquals(
			$content,
			\file_get_contents($exportedFilePath),
			$message
		);
	}

	private function assertFileExistsInLastExportMetadata($filename) {
		$metadata = \json_decode(
			\file_get_contents($this->lastExportMetadataPath), true
		);

		if (!isset($metadata['user']['files']) || empty($metadata['user']['files'])) {
			\PHPUnit_Framework_Assert::fail('File not found in metadata');
		}

		$isFileFoundInExport = false;
		foreach ($metadata['user']['files'] as $file) {
			if (isset($file['path']) && $file['path'] === self::path("/$filename")) {
				$isFileFoundInExport = true;
			}
		}

		\PHPUnit_Framework_Assert::assertTrue(
			$isFileFoundInExport,
			"File $filename not found in metadata"
		);
	}

	/**
	 * Removes duplicate slashes after joining a path
	 *
	 * @param $path
	 * @return string
	 */
	private static function path($path) {
		return \preg_replace('#/+#', '/', $path);
	}
}
