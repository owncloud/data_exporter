<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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

namespace OCA\DataExporter\Tests\Unit\Extractor;

use OCA\DataExporter\Extractor\MetadataExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor\FilesMetadataExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor\PreferencesExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor\SharesExtractor;
use OCA\DataExporter\Extractor\MetadataExtractor\UserExtractor;
use OCA\DataExporter\Model\User;
use OCA\DataExporter\Model\User\Preference;
use OCA\DataExporter\Model\User\Share;
use OCP\IURLGenerator;
use Test\TestCase;

class MetadataExtractorTest extends TestCase {
	/** @var UserExtractor | \PHPUnit_Framework_MockObject_MockObject */
	private $userExtractor;
	/** @var PreferencesExtractor | \PHPUnit_Framework_MockObject_MockObject */
	private $preferencesExtractor;
	/** @var FilesMetadataExtractor | \PHPUnit_Framework_MockObject_MockObject */
	private $filesMetadataExtractor;
	/** @var SharesExtractor | \PHPUnit_Framework_MockObject_MockObject */
	private $sharesExtractor;
	/** @var IURLGenerator | \PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var MetadataExtractor */
	private $metadataExtractor;

	public function setUp() {
		parent::setUp();
		$this->userExtractor = $this->createMock(UserExtractor::class);
		$this->preferencesExtractor = $this->createMock(PreferencesExtractor::class);
		$this->filesMetadataExtractor = $this->createMock(FilesMetadataExtractor::class);
		$this->sharesExtractor = $this->createMock(SharesExtractor::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->metadataExtractor = new MetadataExtractor(
			$this->userExtractor,
			$this->preferencesExtractor,
			$this->filesMetadataExtractor,
			$this->sharesExtractor,
			$this->urlGenerator
		);
	}

	/**
	 * Test basic Extractor input and output
	 *
	 * @throws \Exception
	 */
	public function testMetadataExtractor() {
		$user = new User();
		$user->setUserId('testuser')
			->setEmail('mail@example.com')
			->setEnabled(true);
		$this->userExtractor
			->method('extract')
			->with('testuser')
			->willReturn($user);
		$preference = new Preference();
		$preference
			->setAppId('core')
			->setConfigValue('testvalue')
			->setConfigKey('testkey');
		$this->preferencesExtractor
			->method('extract')
			->with('testuser')
			->willReturn([$preference]);
		$share = new Share();
		$share
			->setShareType(Share::SHARETYPE_LINK)
			->setPath('path/to/file')
			->setName('test')
			->setOwner('testuser')
			->setSharedBy('initiator')
			->setPermissions(31)
			->setToken('token')
			->setPassword('password')
			->setExpirationDate(1556150400);
		$this->sharesExtractor
			->method('extract')
			->with('testuser')
			->willReturn([$share]);
		$this->filesMetadataExtractor
			->method('extract')
			->with('testuser')
			->willReturn([]);

		$metadata = $this->metadataExtractor->extract('testuser');
		self::assertEquals($user->isEnabled(), $metadata->getUser()->isEnabled());
		self::assertEquals($user->getUserId(), $metadata->getUser()->getUserId());
		self::assertEquals($user->getEmail(), $metadata->getUser()->getEmail());
		self::assertEquals($preference, $metadata->getUser()->getPreferences()[0]);
		self::assertEquals($share, $metadata->getUser()->getShares()[0]);
		self::assertEquals([], $metadata->getFiles());
	}
}
