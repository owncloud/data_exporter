<?php
/**
 * @author Michael Barz <mbarz@owncloud.com>
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

namespace OCA\DataExporter\Command;

use OCA\DataExporter\InstanceImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportInstance
 *
 * @package OCA\DataExporter\Command
 */
class ImportInstance extends Command {
	/**
	 * @var InstanceImporter
	 */
	private $instanceImporter;

	/**
	 * ImportInstance constructor.
	 *
	 * @param InstanceImporter $importer
	 */
	public function __construct(InstanceImporter $importer) {
		parent::__construct();
		$this->instanceImporter = $importer;
	}

	/**
	 * Command Config
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('instance:import')
			->setDescription('Imports global instance data')
			->addArgument('importDirectory', InputArgument::REQUIRED, 'Path to the directory to import data from');
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$this->instanceImporter->import(
				$input->getArgument('importDirectory')
			);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
