<?php

declare(strict_types=1);

use UralenergomashTestTask\CommerceML\CommerceMLExportCommand;
use UralenergomashTestTask\CommerceML\CommerceMLPackageBuilder;
use UralenergomashTestTask\CommerceML\OneCExchangeClient;
use UralenergomashTestTask\Support\ExportLogger;

require_once __DIR__ . '/../../../bootstrap.php';

$params = [];
foreach (array_slice($argv, 1) as $arg)
{
	if (strpos($arg, '--') !== 0)
	{
		continue;
	}

	[$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, 'Y');
	$params[$key] = $value;
}

$logger = new ExportLogger($params['logFile'] ?? null);

$command = new CommerceMLExportCommand(
	new CommerceMLPackageBuilder($logger),
	new OneCExchangeClient($logger),
	$logger
);

try
{
	$command->run($params);
}
catch (Throwable $e)
{
	$logger->error('CommerceML export failed', [
		'message' => $e->getMessage(),
		'file' => $e->getFile(),
		'line' => $e->getLine(),
	]);

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);
}
