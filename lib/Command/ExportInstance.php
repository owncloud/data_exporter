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

use OCA\DataExporter\Exporter\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportInstance
 *
 * @package OCA\DataExporter\Command
 */
class ExportInstance extends Command {
	/**
	 * @var Instance
	 */
	private $instanceExporter;
	/**
	 * @var Factory
	 */
	private $exporterFactory;

	/**
	 * ExportInstance constructor.
	 *
	 * @param Instance $exporter
	 */
	public function __construct(Factory $exporterFactory) {
		parent::__construct();
		$this->exporterFactory = $exporterFactory;
	}

	/**
	 * Command Config
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('instance:export')
			->setDescription('Exports global instance data')
			->addArgument('exportDirectory', InputArgument::REQUIRED, 'Path to the directory to export data to');
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
			$this->instanceExporter->export(
				$input->getArgument('exportDirectory')
			);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
