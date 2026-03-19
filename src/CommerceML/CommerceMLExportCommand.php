<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

use CCatalog;
use CCatalogSku;
use RuntimeException;
use UralenergomashTestTask\Support\ExportLogger;

final class CommerceMLExportCommand
{
	private CommerceMLPackageBuilder $packageBuilder;
	private OneCExchangeClient $exchangeClient;
	private ExportLogger $logger;

	public function __construct(
		CommerceMLPackageBuilder $packageBuilder,
		OneCExchangeClient $exchangeClient,
		ExportLogger $logger
	)
	{
		$this->packageBuilder = $packageBuilder;
		$this->exchangeClient = $exchangeClient;
		$this->logger = $logger;
	}

	public function run(array $params): void
	{
		$iblockId = (int)($params['iblockId'] ?? 0);
		$rootSectionId = (int)($params['rootSectionId'] ?? 0);
		$targetUrl = (string)($params['targetUrl'] ?? '');
		$login = (string)($params['login'] ?? '');
		$password = (string)($params['password'] ?? '');
		$packetSize = (int)($params['packetSize'] ?? 2000);
		$dryRun = (($params['dryRun'] ?? 'N') === 'Y');

		if ($iblockId <= 0 || $rootSectionId <= 0)
		{
			throw new RuntimeException('Required params: --iblockId, --rootSectionId');
		}

		if (!$dryRun && ($targetUrl === '' || $login === '' || $password === ''))
		{
			throw new RuntimeException('Required params for live exchange: --targetUrl, --login, --password');
		}

		if ($packetSize <= 0)
		{
			throw new RuntimeException('packetSize must be greater than 0');
		}

		if (!CCatalog::GetByID($iblockId))
		{
			throw new RuntimeException('The iblock is not registered in catalog.');
		}

		$catalogInfo = CCatalogSku::GetInfoByIBlock($iblockId);
		if ($catalogInfo === false)
		{
			throw new RuntimeException('The iblock is not a catalog iblock.');
		}

		if ($catalogInfo['CATALOG_TYPE'] === CCatalogSku::TYPE_OFFERS)
		{
			throw new RuntimeException('Offers iblock was provided. Use the product iblock instead.');
		}

		$skuInfo = CCatalogSku::GetInfoByProductIBlock($iblockId) ?: null;

		$this->logger->info('CommerceML export started', [
			'iblockId' => $iblockId,
			'rootSectionId' => $rootSectionId,
			'packetSize' => $packetSize,
			'catalogType' => $catalogInfo['CATALOG_TYPE'],
			'hasSkuIblock' => $skuInfo !== null ? 'Y' : 'N',
			'dryRun' => $dryRun ? 'Y' : 'N',
		]);

		$buildResult = $this->packageBuilder->build([
			'iblockId' => $iblockId,
			'rootSectionId' => $rootSectionId,
			'packetSize' => $packetSize,
			'withImages' => (($params['withImages'] ?? 'N') === 'Y'),
			'workDir' => $params['workDir'] ?? null,
			'skuInfo' => $skuInfo,
		]);

		if ($dryRun)
		{
			$this->logger->info('Dry run completed', [
				'workDir' => $buildResult->getWorkDir(),
				'packagesCount' => count($buildResult->getPackages()),
				'productsCount' => $buildResult->getProductsCount(),
				'offersCount' => $buildResult->getOffersCount(),
			]);

			return;
		}

		$this->exchangeClient->sendPackages($targetUrl, $login, $password, $buildResult->getPackages());

		$this->logger->info('CommerceML export finished', [
			'packagesCount' => count($buildResult->getPackages()),
			'productsCount' => $buildResult->getProductsCount(),
			'offersCount' => $buildResult->getOffersCount(),
		]);
	}
}
