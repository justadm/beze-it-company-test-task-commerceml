<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Service;

use Bitrix\Main\Web\HttpClient;
use RuntimeException;

final class PacketSender
{
	public function send(string $url, array $packet): array
	{
		$body = json_encode($packet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($body === false)
		{
			throw new RuntimeException('Failed to encode packet to JSON.');
		}

		$client = new HttpClient([
			'socketTimeout' => 60,
			'streamTimeout' => 60,
		]);
		$client->setHeader('Content-Type', 'application/json; charset=UTF-8', true);
		$responseBody = $client->post($url, $body);

		return [
			'status' => (int)$client->getStatus(),
			'body' => $responseBody,
			'error' => $responseBody === false ? $client->getError() : null,
		];
	}
}
