<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestCase.php';

$testFiles = glob(__DIR__ . '/*Test.php');
sort($testFiles);

$failures = [];
$testsRun = 0;

foreach ($testFiles as $testFile)
{
	require_once $testFile;
	$className = 'UralenergomashTestTask\\Tests\\' . basename($testFile, '.php');
	$test = new $className();

	foreach (get_class_methods($test) as $method)
	{
		if (strpos($method, 'test') !== 0)
		{
			continue;
		}

		$testsRun++;

		try
		{
			$test->{$method}();
			echo "[OK] {$className}::{$method}\n";
		}
		catch (Throwable $e)
		{
			$failures[] = "[FAIL] {$className}::{$method}: " . $e->getMessage();
		}
	}
}

if ($failures !== [])
{
	foreach ($failures as $failure)
	{
		fwrite(STDERR, $failure . "\n");
	}

	fwrite(STDERR, sprintf("Tests failed: %d of %d\n", count($failures), $testsRun));
	exit(1);
}

echo sprintf("All tests passed: %d\n", $testsRun);
