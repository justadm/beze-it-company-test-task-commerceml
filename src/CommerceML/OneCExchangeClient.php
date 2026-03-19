<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

use Bitrix\Main\Web\HttpClient;
use RuntimeException;
use UralenergomashTestTask\Support\ExportLogger;

final class OneCExchangeClient
{
	private ExportLogger $logger;

	public function __construct(ExportLogger $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @param CommerceMLPackage[] $packages
	 */
	public function sendPackages(string $targetUrl, string $login, string $password, array $packages): void
	{
		$client = new HttpClient([
			'socketTimeout' => 60,
			'streamTimeout' => 300,
		]);
		$client->setAuthorization($login, $password);

		$auth = $this->checkAuth($client, $targetUrl, $login, $password);
		$client->setCookies([$auth['sessionName'] => $auth['sessionId']]);

		$init = $this->initExchange($client, $targetUrl, $auth['sessid']);
		$fileLimit = $init['fileLimit'];

		foreach ($packages as $package)
		{
			$this->logger->info('Sending CommerceML package', [
				'package' => $package->getIndex(),
				'products' => $package->getProductsCount(),
				'offers' => $package->getOffersCount(),
			]);

			$this->uploadAndImport($client, $targetUrl, $auth['sessid'], $package->getImportFile(), $fileLimit);

			if ($package->getOffersFile() !== null)
			{
				$this->uploadAndImport($client, $targetUrl, $auth['sessid'], $package->getOffersFile(), $fileLimit);
			}
		}

		$this->completeExchange($client, $targetUrl, $auth['sessid']);
	}

	private function checkAuth(HttpClient $client, string $targetUrl, string $login, string $password): array
	{
		$url = $this->buildUrl($targetUrl, [
			'type' => 'catalog',
			'mode' => 'checkauth',
			'USER_LOGIN' => $login,
			'USER_PASSWORD' => $password,
			'AUTH_FORM' => 'Y',
			'TYPE' => 'AUTH',
		]);

		$response = $client->get($url);
		$lines = $this->parseLines($response);

		if (($lines[0] ?? '') !== 'success')
		{
			throw new RuntimeException('1C exchange checkauth failed: ' . implode(' | ', $lines));
		}

		if (count($lines) < 4)
		{
			throw new RuntimeException('Unexpected checkauth response: ' . implode(' | ', $lines));
		}

		$this->logger->info('1C exchange authenticated');

		return [
			'sessionName' => $lines[1],
			'sessionId' => $lines[2],
			'sessid' => $lines[3],
		];
	}

	private function initExchange(HttpClient $client, string $targetUrl, string $sessid): array
	{
		$url = $this->buildUrl($targetUrl, [
			'type' => 'catalog',
			'mode' => 'init',
			'sessid' => $sessid,
		]);

		$response = $client->get($url);
		$lines = $this->parseLines($response);

		if (empty($lines))
		{
			throw new RuntimeException('Empty init response from 1C exchange.');
		}

		if (($lines[0] ?? '') === 'failure')
		{
			throw new RuntimeException('1C exchange init failed: ' . implode(' | ', $lines));
		}

		$fileLimit = 1024 * 1024;
		foreach ($lines as $line)
		{
			if (strpos($line, 'file_limit=') === 0)
			{
				$fileLimit = (int)substr($line, strlen('file_limit='));
				break;
			}
		}

		if ($fileLimit <= 0)
		{
			$fileLimit = 1024 * 1024;
		}

		$this->logger->info('1C exchange initialized', [
			'fileLimit' => $fileLimit,
		]);

		return [
			'fileLimit' => $fileLimit,
		];
	}

	private function uploadAndImport(HttpClient $client, string $targetUrl, string $sessid, string $filePath, int $fileLimit): void
	{
		$filename = basename($filePath);
		$handle = fopen($filePath, 'rb');
		if ($handle === false)
		{
			throw new RuntimeException('Failed to open file for upload: ' . $filePath);
		}

		try
		{
			while (!feof($handle))
			{
				$chunk = fread($handle, $fileLimit);
				if ($chunk === false)
				{
					throw new RuntimeException('Failed to read file chunk: ' . $filePath);
				}

				if ($chunk === '')
				{
					break;
				}

				$url = $this->buildUrl($targetUrl, [
					'type' => 'catalog',
					'mode' => 'file',
					'filename' => $filename,
					'sessid' => $sessid,
				]);

				$response = $client->post($url, $chunk);
				$lines = $this->parseLines($response);
				if (($lines[0] ?? '') !== 'success')
				{
					throw new RuntimeException('1C exchange file upload failed for ' . $filename . ': ' . implode(' | ', $lines));
				}
			}
		}
		finally
		{
			fclose($handle);
		}

		$this->logger->info('CommerceML file uploaded', [
			'filename' => $filename,
		]);

		$this->importFile($client, $targetUrl, $sessid, $filename);
	}

	private function importFile(HttpClient $client, string $targetUrl, string $sessid, string $filename): void
	{
		$maxAttempts = 1000;
		for ($attempt = 1; $attempt <= $maxAttempts; $attempt++)
		{
			$url = $this->buildUrl($targetUrl, [
				'type' => 'catalog',
				'mode' => 'import',
				'filename' => $filename,
				'sessid' => $sessid,
			]);

			$response = $client->get($url);
			$lines = $this->parseLines($response);
			$status = $lines[0] ?? '';

			if ($status === 'success')
			{
				$this->logger->info('CommerceML file imported', [
					'filename' => $filename,
					'attempts' => $attempt,
				]);

				return;
			}

			if ($status === 'progress')
			{
				$this->logger->info('CommerceML import progress', [
					'filename' => $filename,
					'attempt' => $attempt,
					'message' => $lines[1] ?? '',
				]);

				continue;
			}

			throw new RuntimeException('1C exchange import failed for ' . $filename . ': ' . implode(' | ', $lines));
		}

		throw new RuntimeException('1C exchange import exceeded max attempts for ' . $filename);
	}

	private function completeExchange(HttpClient $client, string $targetUrl, string $sessid): void
	{
		$url = $this->buildUrl($targetUrl, [
			'type' => 'catalog',
			'mode' => 'complete',
			'sessid' => $sessid,
		]);

		$response = $client->get($url);
		$lines = $this->parseLines($response);

		if (($lines[0] ?? '') !== 'success')
		{
			throw new RuntimeException('1C exchange complete failed: ' . implode(' | ', $lines));
		}

		$this->logger->info('1C exchange completed');
	}

	private function buildUrl(string $baseUrl, array $query): string
	{
		$separator = strpos($baseUrl, '?') === false ? '?' : '&';
		return $baseUrl . $separator . http_build_query($query);
	}

	private function parseLines($response): array
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
}
