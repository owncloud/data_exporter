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
namespace OCA\DataExporter\Tests\Unit\Utilities\Iterators\Nodes;

use OCA\DataExporter\Utilities\Iterators\Nodes\SkipNodeConditionIgnorePath;
use OCP\Files\Folder;
use OCP\Files\Node;
use Test\TestCase;

class SkipNodeConditionIgnorePathTest extends TestCase {
	/** @var Folder */
	private $folder;

	private $paths = [];

	/** @var SkipNodeConditionIgnorePath */
	private $skipNodeCondition;

	protected function setUp(): void {
		$this->folder = $this->createMock(Folder::class);
		$this->paths = [];
	}

	public function testShouldSkipNodePathOutsideOfBase() {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn('/usertest/private');

		$this->folder->method('getRelativePath')->willReturn(null);

		$this->skipNodeCondition = new SkipNodeConditionIgnorePath($this->folder, $this->paths);
		$this->assertFalse($this->skipNodeCondition->shouldSkipNode($node));
	}

	public function testShouldSkipNodeNoIgnoredPaths() {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn('/usertest/private');

		$this->folder->method('getRelativePath')->willReturn('/private');

		$this->skipNodeCondition = new SkipNodeConditionIgnorePath($this->folder, $this->paths);
		$this->assertFalse($this->skipNodeCondition->shouldSkipNode($node));
	}

	public function testShouldSkipNode() {
		$this->paths = ['/private'];

		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn('/usertest/private');

		$this->folder->method('getRelativePath')->willReturn('/private');

		$this->skipNodeCondition = new SkipNodeConditionIgnorePath($this->folder, $this->paths);
		$this->assertTrue($this->skipNodeCondition->shouldSkipNode($node));
	}

	public function testShouldSkipNodeOnlySpecific() {
		$this->paths = ['/private/supersecret'];

		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn('/usertest/private');

		$node2 = $this->createMock(Node::class);
		$node2->method('getPath')->willReturn('/usertest/private/supersecret');

		$node3 = $this->createMock(Node::class);
		$node3->method('getPath')->willReturn('/usertest/private/supersecret/stuff');

		$this->folder->method('getRelativePath')
			->will($this->returnValueMap([
				['/usertest/private', '/private'],
				['/usertest/private/supersecret', '/private/supersecret'],
				['/usertest/private/supersecret/stuff', '/private/supersecret/stuff'],
			]));

		$this->skipNodeCondition = new SkipNodeConditionIgnorePath($this->folder, $this->paths);
		$this->assertFalse($this->skipNodeCondition->shouldSkipNode($node));
		$this->assertTrue($this->skipNodeCondition->shouldSkipNode($node2));
		$this->assertFalse($this->skipNodeCondition->shouldSkipNode($node3));
	}
}
