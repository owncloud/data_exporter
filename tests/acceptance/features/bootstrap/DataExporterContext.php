<?php declare(strict_types=1);
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
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use TestHelpers\SetupHelper;
use TestHelpers\HttpRequestHelper;
use PHPUnit\Framework\Assert;
use TestHelpers\UserHelper;

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
	 * @var TrashbinContext
	 */
	private $trashBinContext;
	/**
	 * @var OccContext
	 */
	private $occContext;

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
	 * Path to last export e.g /home/user/exports/Alice
	 *
	 * @var string
	 */
	private $lastExportPath;

	/**
	 * Username of the last exported user e.g Alice
	 *
	 * @var string
	 */
	private $lastExportUser;

	private $importedUsers = [];

	/**
	 * Path to metadata of last export e.g /home/user/exports/Alice/metadata.json
	 *
	 * @var string
	 */
	private $lastExportMetadataPath;

	/**
	 * Path to metadata of last export trashbin  e.g /home/user/exports/Alice/trashbinMetadata.json
	 *
	 * @var string
	 */
	private $lastExportTrashMetadataPath;

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setUpScenario(BeforeScenarioScope $scope):void {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
		$this->occContext = $environment->getContext('OccContext');
		$this->trashBinContext = $environment->getContext('TrashbinContext');
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
	 *
	 * @throws Exception
	 */
	public function afterScenario():void {
		$this->scenarioDir = null;
		$this->lastExportBasePath = null;
		$this->lastExportPath = null;
		$this->lastExportUser = null;
		$this->lastExportMetadataPath = null;
		$this->lastExportTrashMetadataPath = null;

		foreach ($this->importedUsers as $uid) {
			UserHelper::deleteUser(
				$this->featureContext->getBaseUrl(),
				$uid,
				$this->featureContext->getAdminUsername(),
				$this->featureContext->getAdminPassword()
			);
		}
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
	public function exportUserUsingTheOccCommand(
		string $user,
		string $path
	):void {
		$internalPath = self::path("{$this->scenarioDir}/$path");
		$serverRoot = $this->featureContext->getServerRoot();
		$this->featureContext->mkDirOnServer($internalPath);
		$this->featureContext->setOccLastCode(
			$this->featureContext->runOcc(['instance:export:user', $user, self::path("$serverRoot/$internalPath")])
		);

		$this->lastExportBasePath = $internalPath;
		$this->lastExportPath = self::path("{$this->lastExportBasePath}/$user/");
		$this->lastExportUser = $user;
		$this->lastExportMetadataPath = "{$this->lastExportPath}files.jsonl";
		$this->lastExportTrashMetadataPath = "{$this->lastExportPath}trashbin.jsonl";
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
	public function theLastExportShouldContainFile(string $path):void {
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
	public function theLastExportShouldContainFileWithContent(
		string $path,
		string $content
	):void {
		$this->assertFileExistsInLastExportMetadata($path);
		$this->assertFilePhysicallyExistInLastExportWithContent($path, $content);
	}

	/**
	 * @Then the last export should contain file :path with content :content in trashbin
	 *
	 * @param string $path
	 * @param string $content
	 *
	 * @return void
	 */
	public function theLastExportShouldContainFileWithContentInTrashbin(
		string $path,
		string $content
	):void {
		$this->assertFileExistsInLastExportMetadata($path, true);
		$this->assertFilePhysicallyExistInLastExportWithContent($path, $content, true);
	}

	/**
	 * @Given a user has been imported from path :path using the occ command
	 *
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function importedUserUsingTheOccCommand(string $path):void {
		$this->importUserUsingTheOccCommand($path);
		$this->occContext->theCommandShouldHaveBeenSuccessful();
	}

	/**
	 * @When a user is imported from path :path using the occ command
	 *
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function importUserUsingTheOccCommand(string $path):void {
		$importPath = self::path("$this->dataDir/$path");
		$this->featureContext->setOccLastCode(
			$this->featureContext->runOcc(['instance:import:user', $importPath])
		);
		if ($this->occContext->theOccCommandExitStatusWasSuccess()) {
			$meta = \json_decode(\file_get_contents("$importPath/user.json"), true);
			if (isset($meta['user'], $meta['user']['userId'])) {
				$this->importedUsers[] = $meta['user']['userId'];
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	private static function assertPathContainsExport(string $path):void {
		\PHPUnit\Framework\Assert::assertDirectoryExists(
			$path,
			"Export directory $path does not exist"
		);

		\PHPUnit\Framework\Assert::assertDirectoryExists(
			self::path("$path/files"),
			"No files directory found inside export $path"
		);

		\PHPUnit\Framework\Assert::assertFileExists(
			self::path("$path/user.json"),
			"No user.json found inside export $path"
		);

		\PHPUnit\Framework\Assert::assertFileExists(
			self::path("$path/files.jsonl"),
			"No files.jsonl found inside export $path"
		);
	}

	/**
	 * @param string $filename
	 * @param string $message
	 *
	 * @return void
	 */
	private function assertFilePhysicallyExistInLastExport(
		string $filename,
		string $message = ''
	):void {
		\PHPUnit\Framework\Assert::assertFileExists(
			self::path("{$this->lastExportPath}/files/$filename"),
			$message
		);
	}

	/**
	 * @param string $filename
	 * @param string $content
	 * @param bool $trash
	 *
	 * @return void
	 */
	private function assertFilePhysicallyExistInLastExportWithContent(
		string $filename,
		string $content,
		bool $trash = false
	):void {
		if ($trash) {
			$this->featureContext->listTrashbinFileInServerRoot(self::path("$this->lastExportPath/files_trashbin"));
			$matches = [];
			foreach (HttpRequestHelper::getResponseXml($this->featureContext->getResponse())->data->element as $item) {
				$file = (string)$item;
				if (\strpos($file, $filename) === 0) {
					$matches[] = $file;
				}
			}
			$fileFoundWithMatchingContent = false;
			foreach ($matches as $match) {
				$path = "$this->lastExportPath/files_trashbin/$match";
				$this->featureContext->readFileInServerRootForCore($path);
				Assert::assertSame(
					200,
					$this->featureContext->getResponse()->getStatusCode(),
					"Failed to read the file {$path}"
				);
				$fileContent = HttpRequestHelper::getResponseXml($this->featureContext->getResponse());
				$fileContent = (string)$fileContent->data->element->contentUrlEncoded;
				$fileContent = \urldecode($fileContent);

				if ($fileContent === $content) {
					$fileFoundWithMatchingContent = true;
					break;
				}
			}
			if (!$fileFoundWithMatchingContent) {
				Assert::fail("The content of the file does not match with '{$content}'");
			}
		} else {
			$this->featureContext->theFileWithContentShouldExistInTheServerRoot(
				self::path("$this->lastExportPath/files/$filename"),
				$content
			);
		}
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function readFileFromServerRoot(string $path):string {
		$this->featureContext->readFileInServerRootForCore($path);
		PHPUnit\Framework\Assert::assertSame(
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
	 * @param bool $trash
	 *
	 * @return void
	 */
	private function assertFileExistsInLastExportMetadata(
		string $filename,
		bool $trash = false
	):void {
		if ($trash) {
			$fileContent = $this->readFileFromServerRoot($this->lastExportTrashMetadataPath);
		} else {
			$fileContent = $this->readFileFromServerRoot($this->lastExportMetadataPath);
		}
		$fileContents = \explode(PHP_EOL, $fileContent);
		if (!isset($fileContents) || empty($fileContents)) {
			\PHPUnit\Framework\Assert::fail('Not a valid metadata file');
		}

		$isFileFoundInExport = false;
		foreach ($fileContents as $file) {
			if (!empty($file)) {
				$fileMetadata = \json_decode(
					$file,
					true
				);
				if ($trash) {
					$fileMetadata['path'] = \preg_replace('/..[^d][0-9]{9}/', "", $fileMetadata['path']);
				}
				if (isset($fileMetadata['path']) && $fileMetadata['path'] === self::path("/$filename")) {
					$isFileFoundInExport = true;
				}
			}
		}
		\PHPUnit\Framework\Assert::assertTrue(
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
	private static function path(string $path):string {
		return \preg_replace('#/+#', '/', $path);
	}

	/**
	 * @Then /^the command output should contain the text "([^"]*)" for path "([^"]*)"$/
	 *
	 * @param string $text
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function theCommandOutputContainsTheTextWithPath(string $text, string $path):void {
		$fullPath = getcwd() . '/tests/acceptance/features/bootstrap/../../data/' . $path;
		$expectedOutput = str_replace("%path%", $fullPath, $text);
		$commandOutput = $this->featureContext->getStdOutOfOccCommand();
		$lines = SetupHelper::findLines(
			$commandOutput,
			$expectedOutput
		);
		Assert::assertGreaterThanOrEqual(
			1,
			\count($lines),
			"The command output did not contain the expected text on stdout '$expectedOutput'\n" .
			"The command output on stdout was:\n" .
			$commandOutput
		);
	}
}
