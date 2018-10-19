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
namespace OCA\DataExporter\Tests\Unit\Exporter\MetadataExtractor;

use OCA\DataExporter\Exporter\MetadataExtractor\PreferencesExtractor;
use OCA\DataExporter\Model\UserMetadata\User\Preference;
use OCP\IAppConfig;
use OCP\IConfig;
use Test\TestCase;

class PreferencesExtractorTest extends TestCase {

	/** @var PreferencesExtractor */
	private $preferencesExtractor;

	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IAppConfig */
	private $appConfig;

	private $mockPreferences = [
		'core' => [
			'coreKey' => 'someValue',
			'anotherKey' => 'anotherValue'
		],
		'app1' => [
			'app1Key' => 'someValue',
			'anotherKey' => 'anotherValue'
		],
		'app2' => [
			'app2Key' => 'someValue',
			'anotherKey' => 'anotherValue'
		]
	];

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->appConfig->method('getApps')
			->willReturn(\array_keys($this->mockPreferences));

		$this->config->method('getUserKeys')->willReturnCallback(function ($userId, $app) {
			return \array_keys($this->mockPreferences[$app]);
		});

		$this->config->method('getUserValue')->willReturnCallback(function ($userId, $app, $key) {
			return $this->mockPreferences[$app][$key];
		});

		$this->preferencesExtractor = new PreferencesExtractor($this->config, $this->appConfig);
	}

	public function testPreferencesAreReadCorrectly() {
		$preferences =  $this->preferencesExtractor->extract('someuser');
		$this->assertCount(6, $preferences);

		$result = [];

		foreach ($preferences as $preference) {
			$this->assertInstanceOf(Preference::class, $preference);
			$result[$preference->getAppId()][$preference->getConfigKey()] = $preference->getConfigValue();
		}

		// Trick for deep array equals: https://stackoverflow.com/a/6703718
		$this->assertEquals(\serialize($this->mockPreferences), \serialize($result));
	}
}
