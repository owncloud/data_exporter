<?php
/**
 * @author Juan Pablo Villafáñez <jvillafanez@solidgeargroup.com>
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
namespace OCA\DataExporter\Tests\Unit\Utilities\FSAccess;

use org\bovigo\vfs\vfsStream;
use OCA\DataExporter\Utilities\FSAccess\FSAccessFactory;
use OCA\DataExporter\Utilities\FSAccess\FSAccess;
use Test\TestCase;

class FSAccessFactoryTest extends TestCase {
	private $fsAccessFactory;

	public function setUp() {
		$this->fsAccessFactory = new FSAccessFactory();
	}

	public function testGetFSAccess() {
		vfsStream::setup('root');
		$fsAccess = $this->fsAccessFactory->getFSAccess('vfs://root');
		$this->assertInstanceOf(FSAccess::class, $fsAccess);
		$this->assertEquals('vfs://root', $fsAccess->getRoot());
	}
}
