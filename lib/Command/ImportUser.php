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

use OCA\DataExporter\Importer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportUser extends Command {

	/** @var Importer */
	private $importer;

	public function __construct(Importer $importer) {
		parent::__construct();
		$this->importer = $importer;
	}

	protected function configure() {
		$this->setName('instance:import:user')
			->setDescription('Imports a single user')
			->addArgument('importDirectory', InputArgument::REQUIRED, 'Path to the directory to import data from')
			->addOption('as', 'a', InputOption::VALUE_REQUIRED, 'Import the user under a different user id');
	}

	/**
	 * Executes the current command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->importer->import(
				$input->getArgument('importDirectory'),
				$input->getOption('as')
			);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return 1;
		}
		return 0;
	}
}
