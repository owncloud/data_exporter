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
namespace OCA\DataExporter\Tests\Unit\Command;

use Symfony\Component\Console\Tester\CommandTester;
use OCA\DataExporter\Command\MigrateShare;
use OCA\DataExporter\Utilities\ShareConverter;
use OCP\IUserManager;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use Test\TestCase;

class MigrateShareTest extends TestCase {
	/** @var ShareConverter */
	private $shareConverter;
	/** @var IClientService*/
	private $clientService;
	/** @var IUserManager */
	private $userManager;
	/** @var CommandTester */
	private $commandTester;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->shareConverter = $this->createMock(ShareConverter::class);

		$command = new MigrateShare($this->userManager, $this->clientService, $this->shareConverter);
		$this->commandTester = new CommandTester($command);
	}

	public function testExecuteMissingUser() {
		$this->userManager->method('userExists')->willReturn(false);

		$this->shareConverter->expects($this->never())
			->method('convertLocalUserShareToRemoteUserShare');

		$input = ['userId' => 'missing', 'remoteServer' => 'https://server.host/oc'];
		$commandOutput = $this->commandTester->execute($input);

		$this->assertEquals(MigrateShare::ERROR_MISSING_USER, $commandOutput);
	}

	public function testExecuteClientRequestFails() {
		$this->userManager->method('userExists')->willReturn(true);

		$client = $this->createMock(IClient::class);
		$client->method('get')->will($this->throwException(new \Exception('Faking exception')));

		$this->clientService->method('newClient')->willReturn($client);

		$this->shareConverter->expects($this->never())
			->method('convertLocalUserShareToRemoteUserShare');

		$input = ['userId' => 'usertest', 'remoteServer' => 'https://server.host/oc'];
		$commandOutput = $this->commandTester->execute($input);

		$this->assertEquals(MigrateShare::ERROR_BAD_REQUEST, $commandOutput);
	}

	public function testExecuteUnparseableJson() {
		$this->userManager->method('userExists')->willReturn(true);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn('this is NOT JSON');

		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturn($response);

		$this->clientService->method('newClient')->willReturn($client);

		$this->shareConverter->expects($this->never())
			->method('convertLocalUserShareToRemoteUserShare');

		$input = ['userId' => 'usertest', 'remoteServer' => 'https://server.host/oc'];
		$commandOutput = $this->commandTester->execute($input);

		$this->assertEquals(MigrateShare::ERROR_WRONG_JSON, $commandOutput);
	}

	public function testExecuteNotValidJson() {
		$this->userManager->method('userExists')->willReturn(true);

		$response = $this->createMock(IResponse::class);
		// just check for installed and maintenance keys
		$response->method('getBody')->willReturn('{"validkey": true, "validvalue": false}');

		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturn($response);

		$this->clientService->method('newClient')->willReturn($client);

		$this->shareConverter->expects($this->never())
			->method('convertLocalUserShareToRemoteUserShare');

		$input = ['userId' => 'usertest', 'remoteServer' => 'https://server.host/oc'];
		$commandOutput = $this->commandTester->execute($input);

		$this->assertEquals(MigrateShare::ERROR_WRONG_JSON, $commandOutput);
	}

	public function testExecuteConverterException() {
		$this->userManager->method('userExists')->willReturn(true);

		$response = $this->createMock(IResponse::class);
		// just check for installed and maintenance keys
		$response->method('getBody')->willReturn('{"installed": true, "maintenance": false}');

		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturn($response);

		$this->clientService->method('newClient')->willReturn($client);

		$this->shareConverter->method('convertLocalUserShareToRemoteUserShare')
			->will($this->throwException(new \Exception('conversion failed badly')));

		$this->shareConverter->expects($this->once())
			->method('convertLocalUserShareToRemoteUserShare');

		$input = ['userId' => 'usertest', 'remoteServer' => 'https://server.host/oc'];
		$commandOutput = $this->commandTester->execute($input);

		$this->assertEquals(MigrateShare::ERROR_CONVERSION_EXCEPTION, $commandOutput);
	}

	public function testExecuteConverter() {
		$this->userManager->method('userExists')->willReturn(true);

		$response = $this->createMock(IResponse::class);
		// just check for installed and maintenance keys
		$response->method('getBody')->willReturn('{"installed": true, "maintenance": false}');

		$client = $this->createMock(IClient::class);
		$client->method('get')->willReturn($response);

		$this->clientService->method('newClient')->willReturn($client);

		$this->shareConverter->expects($this->once())
			->method('convertLocalUserShareToRemoteUserShare')
			->with($this->equalTo('usertest'), $this->equalTo('https://server.host/oc'));

		$input = ['userId' => 'usertest', 'remoteServer' => 'https://server.host/oc'];
		$commandOutput = $this->commandTester->execute($input);

		$this->assertEquals(0, $commandOutput);
	}
}
