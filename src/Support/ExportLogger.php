<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Support;

final class ExportLogger
{
	private ?string $logFile;

	public function __construct(?string $logFile = null)
	{
		$this->logFile = $logFile;
	}

	public function info(string $message, array $context = []): void
	{
		$this->write('INFO', $message, $context);
	}

	public function error(string $message, array $context = []): void
	{
		$this->write('ERROR', $message, $context);
	}

	public function logPacketResult(array $packet, array $response): void
	{
		$level = ($response['status'] >= 200 && $response['status'] < 300) ? 'INFO' : 'ERROR';
		$this->write($level, 'Packet sent', [
			'packet_index' => $packet['meta']['packet_index'],
			'packet_total' => $packet['meta']['packet_total'],
			'products_in_packet' => $packet['meta']['products_in_packet'],
			'status' => $response['status'],
			'error' => $response['error'],
		]);
	}

	private function write(string $level, string $message, array $context): void
	{
		$line = sprintf(
			"[%s] %s %s %s\n",
			date('c'),
			$level,
			$message,
			$context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
		);

		echo $line;

		if ($this->logFile !== null && $this->logFile !== '')
		{
			file_put_contents($this->logFile, $line, FILE_APPEND);
		}
	}
}
