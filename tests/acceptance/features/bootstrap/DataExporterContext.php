<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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
 * Context for data exporter specific steps
 */
class DataExporterContext implements Context {
	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * The relative path from the folder containing this Context PHP file to the test data
	 *
	 * @var string
	 */
	private $dataDir = __DIR__ . '/../../data/';

	private $outputDir = '/dataexp_acc_test/';

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
		$this->featureContext->mkDirOnServer($this->scenarioDir);
	}

	/**
	 * @AfterScenario
	 *
	 * @return void
	 */
	public function deleteLastExport() {
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
	public function exportUserUsingTheOccCommand($user, $path) {
		$internalPath = self::path("{$this->scenarioDir}/$path");
		$serverRoot = $this->featureContext->getServerRoot();
		$this->featureContext->mkDirOnServer($internalPath);
		$this->featureContext->runOcc(['instance:export:user', $user, self::path("$serverRoot/$internalPath")]);

		$this->lastExportBasePath = $internalPath;
		$this->lastExportPath = self::path("{$this->lastExportBasePath}/$user/");
		$this->lastExportUser = $user;
		$this->lastExportMetadataPath = "{$this->lastExportPath}metadata.json";
	}

	/**
	 * @Then the last export should contain file :path
	 *
	 * @param string $path
	 *
	 * Checks whether a given file is present physically
	 * and inside exporter metadata.
	 *
	 * @return void
	 */
	public function theLastExportShouldContainFile($path) {
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
	 * Checks whether a given file is present physically with a given content
	 * and also is present inside export metadata.
	 *
	 * @return void
	 */
	public function theLastExportShouldContainFileWithContent($path, $content) {
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
	public function importUserUsingTheOccCommand($path) {
		$importPath = self::path("$this->dataDir/$path");
		$this->featureContext->runOcc(['instance:import:user', $importPath]);
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 */
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

	/**
	 * @param string $filename
	 * @param string $message
	 *
	 * @return void
	 */
	private function assertFilePhysicallyExistInLastExport($filename, $message = '') {
		\PHPUnit_Framework_Assert::assertFileExists(
			self::path("{$this->lastExportPath}/files/$filename"),
			$message
		);
	}

	/**
	 * @param string $filename
	 * @param string $content
	 *
	 * @return void
	 */
	private function assertFilePhysicallyExistInLastExportWithContent($filename, $content) {
		$this->featureContext->theFileWithContentShouldExistInTheServerRoot(
			self::path("$this->lastExportPath/files/$filename"),
			$content
		);
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function readFileFromServerRoot($path) {
		$this->featureContext->readFileInServerRoot($path);
		PHPUnit_Framework_Assert::assertSame(
			200,
			$this->featureContext->getResponse()->getStatusCode(),
			"Failed to read the file {$path}"
		);

		$fileContent = \TestHelpers\HttpRequestHelper::getResponseXml($this->featureContext->getResponse());
		$fileContent = (string)$fileContent->data->element->contentUrlEncoded;
		return \urldecode($fileContent);
	}

	/**
	 * @param string $filename
	 *
	 * @return void
	 */
	private function assertFileExistsInLastExportMetadata($filename) {
		$metadata = \json_decode(
			$this->readFileFromServerRoot($this->lastExportMetadataPath),
			true
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
	 * @param string $path
	 *
	 * @return string
	 */
	private static function path($path) {
		return \preg_replace('#/+#', '/', $path);
	}
}
