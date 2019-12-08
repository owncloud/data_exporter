<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
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

namespace OCA\DataExporter\Tests\Unit\Exporter\MetadataExtractor;

use OC\Group\Database;
use OCA\DataExporter\Exporter\InstanceExtractor;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use Test\TestCase;

class InstanceExtractorTest extends TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	/**
	 * @var InstanceExtractor
	 */
	private $extractor;

	public function setUp(): void {
		$this->groupManager = $this->getMockBuilder(IGroupManager::class)->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->extractor = new InstanceExtractor(
			$this->groupManager,
			$this->config,
			$this->urlGenerator
		);
		return parent::setUp();
	}

	public function testExtract() {
		$group = $this->getMockBuilder(IGroup::class)->getMock();
		$group->method('getGID')->willReturn('aGroup');
		$group->method('getBackend')->willReturn(
			$this->getMockBuilder(Database::class)->getMock()
		);

		$this->urlGenerator->method('getAbsoluteUrl')->willReturn('https://myinstance.com/');
		$this->groupManager->method('search')->willReturn([$group]);
		$this->config->method('getSystemValue')->will($this->returnValueMap([
				['instanceid', '',  'someinstanceid'],
				['secret', '', 'somesecret'],
				['passwordsalt', '', 'somepasswordsalt']
			]
		));

		$result = $this->extractor->extract();

		$this->assertEquals('https://myinstance.com/', $result->getOriginServer());
		$this->assertEquals('someinstanceid', $result->getInstanceId());
		$this->assertEquals('somesecret', $result->getSecret());
		$this->assertEquals('somepasswordsalt', $result->getPasswordSalt());
		$this->assertEquals(['aGroup'], $result->getGroups());
	}
}
