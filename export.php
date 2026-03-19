<?php

declare(strict_types=1);

use UralenergomashTestTask\Command\CatalogExportCommand;
use UralenergomashTestTask\Service\EntityNormalizer;
use UralenergomashTestTask\Service\OfferCollector;
use UralenergomashTestTask\Service\PacketBuilder;
use UralenergomashTestTask\Service\PacketSender;
use UralenergomashTestTask\Service\ProductCollector;
use UralenergomashTestTask\Service\SectionTreeBuilder;
use UralenergomashTestTask\Support\ExportLogger;
use UralenergomashTestTask\Support\UidGenerator;

require_once __DIR__ . '/bootstrap.php';

$params = [];
foreach (array_slice($argv, 1) as $arg)
{
	if (strpos($arg, '--') !== 0)
	{
		continue;
	}

	$pair = explode('=', substr($arg, 2), 2);
	$key = $pair[0];
	$value = $pair[1] ?? 'Y';
	$params[$key] = $value;
}

$logger = new ExportLogger($params['logFile'] ?? null);
$uidGenerator = new UidGenerator();
$normalizer = new EntityNormalizer($uidGenerator, $params);

$command = new CatalogExportCommand(
	new SectionTreeBuilder($uidGenerator, $normalizer),
	new ProductCollector($normalizer),
	new OfferCollector($normalizer),
	new PacketBuilder(),
	new PacketSender(),
	$logger
);

try
{
	$command->run($params);
}
catch (Throwable $e)
{
	$logger->error('Export failed', [
		'message' => $e->getMessage(),
		'file' => $e->getFile(),
		'line' => $e->getLine(),
	]);

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);
}
