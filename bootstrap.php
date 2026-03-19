<?php

declare(strict_types=1);

use Bitrix\Main\Loader;

if (PHP_SAPI !== 'cli')
{
	die("This script must be run from CLI.\n");
}

$documentRoot = realpath(__DIR__ . '/../../..');
if ($documentRoot === false)
{
	die("Unable to detect document root.\n");
}

$_SERVER['DOCUMENT_ROOT'] = $documentRoot;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

spl_autoload_register(static function (string $class): void {
	$prefix = 'UralenergomashTestTask\\';
	if (strpos($class, $prefix) !== 0)
	{
		return;
	}

	$relativeClass = substr($class, strlen($prefix));
	$file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

	if (is_file($file))
	{
		require_once $file;
	}
});

if (!Loader::includeModule('iblock'))
{
	die("Failed to load iblock module.\n");
}

if (!Loader::includeModule('catalog'))
{
	die("Failed to load catalog module.\n");
}
