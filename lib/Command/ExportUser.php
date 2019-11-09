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
use OCA\DataExporter\Platform;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportUser extends Command {

	/** @var Exporter  */
	private $exporter;

	/** @var Platform  */
	private $platform;

	public function __construct(Exporter $exporter, Platform $platform) {
		parent::__construct();
		$this->exporter = $exporter;
		$this->platform = $platform;
	}

	protected function configure() {
		$this->setName('instance:export:user')
			->setDescription('Exports a single user')
			->addArgument('userId', InputArgument::REQUIRED, 'User to export')
			->addArgument('exportDirectory', InputArgument::REQUIRED, 'Path to the directory to export data to')
			->addOption('no-files', 'm', InputOption::VALUE_NONE, 'Skip exporting files (export metadata only)')
			->addOption('with-file-ids', 'i', InputOption::VALUE_NONE, 'Export file-ids in file-metadata');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$uid = $input->getArgument('userId');
			$exportDirectory = $input->getArgument('exportDirectory');
			$this->exporter->export($uid, $exportDirectory, [
				'exportFiles' => !$input->getOption('no-files'),
				'trashBinAvailable' => $this->platform->isAppEnabledForUser('files_trashbin', $uid),
				'exportFileIds' => $input->getOption('with-file-ids')
			]);
		} catch (\Exception $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
		}
	}
}
