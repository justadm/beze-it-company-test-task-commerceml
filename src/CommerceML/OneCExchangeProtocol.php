<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

use RuntimeException;

final class OneCExchangeProtocol
{
	public static function buildUrl(string $baseUrl, array $query): string
	{
		$separator = strpos($baseUrl, '?') === false ? '?' : '&';

		return $baseUrl . $separator . http_build_query($query);
	}

	public static function parseLines($response): array
	{
		if ($response === false || $response === null)
		{
			return [];
		}

		$response = trim((string)$response);
		if ($response === '')
		{
			return [];
		}

		return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $response) ?: []), static function (string $line): bool {
			return $line !== '';
		}));
	}

	public static function parseCheckAuthResponse(array $lines): array
	{
		if (($lines[0] ?? '') !== 'success')
		{
			throw new RuntimeException('1C exchange checkauth failed: ' . implode(' | ', $lines));
		}

		if (count($lines) < 4)
		{
			throw new RuntimeException('Unexpected checkauth response: ' . implode(' | ', $lines));
		}

		return [
			'sessionName' => $lines[1],
			'sessionId' => $lines[2],
			'sessid' => $lines[3],
		];
	}

	public static function extractFileLimit(array $lines, int $defaultLimit = 1048576): int
	{
		if (empty($lines))
		{
			throw new RuntimeException('Empty init response from 1C exchange.');
		}

		if (($lines[0] ?? '') === 'failure')
		{
			throw new RuntimeException('1C exchange init failed: ' . implode(' | ', $lines));
		}

		foreach ($lines as $line)
		{
			if (strpos($line, 'file_limit=') === 0)
			{
				$fileLimit = (int)substr($line, strlen('file_limit='));

				return $fileLimit > 0 ? $fileLimit : $defaultLimit;
			}
		}

		return $defaultLimit;
	}
}
