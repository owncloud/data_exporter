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
namespace OCA\DataExporter\Command;

use OCA\DataExporter\Utilities\ShareConverter;
use OCP\IUserManager;
use OCP\Http\Client\IClientService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateShare extends Command {
	const ERROR_MISSING_USER = 1;
	const ERROR_BAD_REQUEST = 2;
	const ERROR_WRONG_JSON = 3;
	const ERROR_CONVERSION_EXCEPTION = 4;
	/** @var ShareConverter */
	private $shareConverter;
	/** @var IClientService*/
	private $clientService;
	/** @var IUserManager */
	private $userManager;

	public function __construct(IUserManager $userManager, IClientService $clientService, ShareConverter $shareConverter) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->clientService = $clientService;
		$this->shareConverter = $shareConverter;
	}

	protected function configure() {
		$this->setName('export:migrate:share')
			->setDescription('Converts the local shares pointing to the given user to federated shares pointing at the remote instance. An important prerequisite is that the user and his shares have already been imported on that remote instance')
			->addArgument('userId', InputArgument::REQUIRED, 'The exported userId whose shares we want to migrate')
			->addArgument('remoteServer', InputArgument::REQUIRED, 'The remote ownCloud server where the exported user is now, for example "https://myown.server:8080/owncloud"');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$userId = $input->getArgument('userId');
		if (!$this->userManager->userExists($userId)) {
			$output->writeln("<error>$userId doesn't exists</error>");
			return self::ERROR_MISSING_USER;
		}

		$remoteServer = $input->getArgument('remoteServer');
		$remoteServerStatusPage = \rtrim($remoteServer, '/') . '/status.php';

		$client = $this->clientService->newClient();
		try {
			$clientResponse = $client->get($remoteServerStatusPage);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return self::ERROR_BAD_REQUEST;
		}
		$remoteServerStatusString = $clientResponse->getBody();
		$remoteServerStatusData = \json_decode($remoteServerStatusString, true);
		if (\json_last_error() !== JSON_ERROR_NONE) {
			$errorMessage = 'Cannot decode remote server status: ' . \json_last_error_msg();
			$output->writeln("<error>$errorMessage</error>");
			return self::ERROR_WRONG_JSON;
		} else {
			if (!isset($remoteServerStatusData['installed']) || !isset($remoteServerStatusData['maintenance'])) {
				// this doesn't seem an ownCloud server
				$output->writeln("<error>$remoteServer doesn't seem to be a valid server</error>");
				return self::ERROR_WRONG_JSON;
			}
		}

		try {
			$this->shareConverter->convertLocalUserShareToRemoteUserShare($userId, $remoteServer);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return self::ERROR_CONVERSION_EXCEPTION;
		}
	}
}
