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
	use CommandLine;

	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;


	/**
	 * An array of rules that were already set when the scenario started
	 *
	 * @var string[]
	 */
	private $savedRules = [];

	/**
	 * The relative path from the core tests/acceptance folder to the test data
	 * folder.
	 *
	 * @var string
	 */
	private $relativePathToTestDataFolder
		= '../../apps/data_exporter/tests/acceptance/data/';

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
		$this->runOcc(['export:user', $user, $path]);
	}

	/**
	 * @When a user is imported from path :path using the occ command
	 * @Given user has been imported from path :path using the occ command
	 *
	 * @param string $path
	 *
	 * @return void
	 * @throws Exception
	 */
	public function importUserUsingTheCli($path) {
		$this->runOcc(['import:user', $path]);
	}
	/**
	 * @AfterScenario
	 *
	 * @return void
	 */
	public function afterScenario() {

	}
}
