<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Tests;

use RuntimeException;
use UralenergomashTestTask\CommerceML\OneCExchangeProtocol;

final class OneCExchangeProtocolTest extends TestCase
{
	public function testBuildUrlAddsQueryToBaseUrlWithoutQuery(): void
	{
		$url = OneCExchangeProtocol::buildUrl('https://example.com/bitrix/admin/1c_exchange.php', [
			'type' => 'catalog',
			'mode' => 'init',
		]);

		$this->assertSame('https://example.com/bitrix/admin/1c_exchange.php?type=catalog&mode=init', $url);
	}

	public function testBuildUrlAddsQueryToBaseUrlWithExistingQuery(): void
	{
		$url = OneCExchangeProtocol::buildUrl('https://example.com/bitrix/admin/1c_exchange.php?foo=bar', [
			'mode' => 'complete',
		]);

		$this->assertSame('https://example.com/bitrix/admin/1c_exchange.php?foo=bar&mode=complete', $url);
	}

	public function testParseLinesSkipsEmptyLines(): void
	{
		$lines = OneCExchangeProtocol::parseLines("success\r\n\r\nPHPSESSID\r\n123\r\n");

		$this->assertSame(['success', 'PHPSESSID', '123'], $lines);
	}

	public function testParseCheckAuthResponseReturnsParsedValues(): void
	{
		$result = OneCExchangeProtocol::parseCheckAuthResponse([
			'success',
			'PHPSESSID',
			'abcdef',
			'sessid-token',
		]);

		$this->assertSame('PHPSESSID', $result['sessionName']);
		$this->assertSame('abcdef', $result['sessionId']);
		$this->assertSame('sessid-token', $result['sessid']);
	}

	public function testParseCheckAuthResponseThrowsOnFailure(): void
	{
		$this->expectException(static function (): void {
			OneCExchangeProtocol::parseCheckAuthResponse(['failure', 'Access denied']);
		}, RuntimeException::class);
	}

	public function testExtractFileLimitReturnsConfiguredValue(): void
	{
		$fileLimit = OneCExchangeProtocol::extractFileLimit([
			'zip=yes',
			'file_limit=204800',
		]);

		$this->assertSame(204800, $fileLimit);
	}

	public function testExtractFileLimitFallsBackToDefault(): void
	{
		$fileLimit = OneCExchangeProtocol::extractFileLimit([
			'success',
			'zip=yes',
		], 777);

		$this->assertSame(777, $fileLimit);
	}

	public function testExtractFileLimitThrowsOnFailure(): void
	{
		$this->expectException(static function (): void {
			OneCExchangeProtocol::extractFileLimit(['failure', 'wrong auth']);
		}, RuntimeException::class);
	}
}
