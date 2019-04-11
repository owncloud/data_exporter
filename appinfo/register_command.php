<?php
/**
 * @var Symfony\Component\Console\Application $application
 */

// @codeCoverageIgnoreStart
$config = \OC::$server->getConfig();
$ocVersion = $config->getSystemValue('version', '');
if (isset($application) && \version_compare($ocVersion, '10', '<')) {
	$exporter = \OC::$server->query(\OCA\DataExporter\Exporter::class);
	$application->add(
		new \OCA\DataExporter\Command\ExportUser(
			$exporter
		)
	);
}
// @codeCoverageIgnoreEnd
