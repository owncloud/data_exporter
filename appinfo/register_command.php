<?php

// @codeCoverageIgnoreStart
$config = \OC::$server->getConfig();
$ocVersion = $config->getSystemValue('version', '');

/**
 * @var Symfony\Component\Console\Application|null $application
 */
if (isset($application) && \version_compare($ocVersion, '10', '<')) {
	$exporter = \OC::$server->query(\OCA\DataExporter\Exporter::class);
	$platform = \OC::$server->query(\OCA\DataExporter\Exporter::class);
	$application->add(
		new \OCA\DataExporter\Command\ExportUser($exporter, $platform)
	);
}
// @codeCoverageIgnoreEnd
