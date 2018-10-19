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
use OCA\DataExporter\FSAccess\FSAccessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportUser extends Command {

	/** @var Importer */
	private $importer;

	/** @var FSAccessFactory */
	private $fsAccessFactory;

	public function __construct(Importer $importer, FSAccessFactory $fsAccessFactory) {
		parent::__construct();
		$this->importer = $importer;
		$this->fsAccessFactory = $fsAccessFactory;
	}

	protected function configure() {
		$this->setName('import:user')
			->setDescription('Imports a single user')
			->addArgument('exportDirectory', InputArgument::REQUIRED, 'Path to export directory')
			->addOption('as', 'a', InputOption::VALUE_REQUIRED, 'Import the user under a different user id');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$targetDir = $input->getArgument('exportDirectory');
		$fsAccess = $this->fsAccessFactory->getFSAccess($targetDir);
		try {
			$this->importer->import($fsAccess, $input->getOption('as'));
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
