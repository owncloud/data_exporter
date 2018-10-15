<?php

require __DIR__ . '/../../../../../../lib/base.php';
require __DIR__ . '/../../../../../../lib/composer/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4(
	"", __DIR__ . "/../../../../../../tests/acceptance/features/bootstrap", true
);
$classLoader->addPsr4(
	"TestHelpers\\", __DIR__ . "/../../../../../../tests/TestHelpers", true
);

$classLoader->register();
