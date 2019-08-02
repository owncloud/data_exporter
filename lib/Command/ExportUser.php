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
namespace OCA\DataExporter\Command;

use OCA\DataExporter\Exporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportUser extends Command {

	/** @var Exporter  */
	private $exporterFactory;

	public function __construct(Exporter\Factory $exporterFactory) {
		parent::__construct();
		$this->exporterFactory = $exporterFactory;
	}

	protected function configure() {
		$this->setName('instance:export:user')
			->setDescription('Exports data from a single owncloud user')
			->addArgument('exportDirectory', InputArgument::REQUIRED, 'Path to the directory to export data to')
			->addArgument('userId', InputArgument::OPTIONAL, 'Export a specific user only');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$params = new Exporter\Parameters();
		$params->setUserId($input->getArgument('userId'));
		$params->setExportDirectoryPath($input->getArgument('exportDirectory'));

		try {
			$this->exporterFactory->get($params)->export($params);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
