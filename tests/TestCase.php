<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Tests;

abstract class TestCase
{
	protected function assertSame($expected, $actual, string $message = ''): void
	{
		if ($expected !== $actual)
		{
			throw new \RuntimeException($message !== '' ? $message : 'Failed asserting that two values are identical.');
		}
	}

	protected function assertCount(int $expectedCount, array $items, string $message = ''): void
	{
		$this->assertSame($expectedCount, count($items), $message !== '' ? $message : 'Failed asserting array count.');
	}

	protected function assertTrue(bool $value, string $message = ''): void
	{
		$this->assertSame(true, $value, $message !== '' ? $message : 'Failed asserting that value is true.');
	}

	protected function expectException(callable $callback, string $exceptionClass): void
	{
		try
		{
			$callback();
		}
		catch (\Throwable $e)
		{
			if ($e instanceof $exceptionClass)
			{
				return;
			}

			throw new \RuntimeException('Unexpected exception class: ' . get_class($e));
		}

		throw new \RuntimeException('Expected exception was not thrown: ' . $exceptionClass);
	}
}
