<?php
/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General,
 * Public License as published by the Free
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

namespace OCA\DataExporter\Tests\Unit;

use OCA\DataExporter\Exporter\InstanceExtractor;
use OCA\DataExporter\InstanceExporter;
use OCA\DataExporter\Model\Instance;
use OCA\DataExporter\Platform;
use OCA\DataExporter\Serializer;
use OCP\App\IAppManager;
use Symfony\Component\Filesystem\Filesystem;
use Test\TestCase;

class PlatformTest extends TestCase {
	/** @var Platform  */
	private $platform;
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	public function setUp() : void {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->platform = new Platform($this->appManager, __DIR__ . '/version.php');
	}

	public function testVersion() {
		$this->assertEquals($this->platform->getVendor(), 'owncloud');
		$this->assertEquals($this->platform->getVersionString(), '10.3.0');
	}

	public function testIsAppEnabledForUser() {
		$this->appManager->method('isEnabledForUser')
			->with('someApp', 'someUser')
			->willReturn(true);

		$isEnabled = $this->platform->isAppEnabledForUser('someApp', 'someUser');
		$this->assertTrue($isEnabled);
	}
}
