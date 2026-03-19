<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Command;

use CCatalog;
use CCatalogSku;
use RuntimeException;
use UralenergomashTestTask\Service\OfferCollector;
use UralenergomashTestTask\Service\PacketBuilder;
use UralenergomashTestTask\Service\PacketSender;
use UralenergomashTestTask\Service\ProductCollector;
use UralenergomashTestTask\Service\SectionTreeBuilder;
use UralenergomashTestTask\Support\ExportLogger;

final class CatalogExportCommand
{
	private SectionTreeBuilder $sectionTreeBuilder;
	private ProductCollector $productCollector;
	private OfferCollector $offerCollector;
	private PacketBuilder $packetBuilder;
	private PacketSender $packetSender;
	private ExportLogger $logger;

	public function __construct(
		SectionTreeBuilder $sectionTreeBuilder,
		ProductCollector $productCollector,
		OfferCollector $offerCollector,
		PacketBuilder $packetBuilder,
		PacketSender $packetSender,
		ExportLogger $logger
	)
	{
		$this->sectionTreeBuilder = $sectionTreeBuilder;
		$this->productCollector = $productCollector;
		$this->offerCollector = $offerCollector;
		$this->packetBuilder = $packetBuilder;
		$this->packetSender = $packetSender;
		$this->logger = $logger;
	}

	public function run(array $params): void
	{
		$iblockId = (int)($params['iblockId'] ?? 0);
		$rootSectionId = (int)($params['rootSectionId'] ?? 0);
		$targetUrl = (string)($params['targetUrl'] ?? '');
		$packetSize = (int)($params['packetSize'] ?? 2000);

		if ($iblockId <= 0 || $rootSectionId <= 0 || $targetUrl === '')
		{
			throw new RuntimeException('Required params: --iblockId, --rootSectionId, --targetUrl');
		}

		$catalogRow = CCatalog::GetByID($iblockId);
		if (empty($catalogRow))
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

		$skuInfo = CCatalogSku::GetInfoByProductIBlock($iblockId);

		$this->logger->info('Export started', [
			'iblockId' => $iblockId,
			'rootSectionId' => $rootSectionId,
			'packetSize' => $packetSize,
			'catalogType' => $catalogInfo['CATALOG_TYPE'],
			'hasSkuIblock' => $skuInfo !== false ? 'Y' : 'N',
		]);

		$sectionTree = $this->sectionTreeBuilder->build($iblockId, $rootSectionId);
		$productResult = $this->productCollector->collect(
			$iblockId,
			$sectionTree->getSourceSectionIds(),
			$sectionTree->getSectionUidMap()
		);

		$offers = [];
		if ($skuInfo !== false)
		{
			$offers = $this->offerCollector->collect(
				$skuInfo,
				$productResult->getSourceProductIds(),
				$productResult->getProductUidMap()
			);
		}

		$packets = $this->packetBuilder->build(
			$sectionTree->getSections(),
			$productResult->getProducts(),
			$productResult->getBindings(),
			$offers,
			[
				'iblockId' => $iblockId,
				'catalogType' => $catalogInfo['CATALOG_TYPE'],
				'rootSectionUid' => $sectionTree->getRootSectionUid(),
				'packetSize' => $packetSize,
			]
		);

		foreach ($packets as $packet)
		{
			$response = $this->packetSender->send($targetUrl, $packet);
			$this->logger->logPacketResult($packet, $response);
		}

		$this->logger->info('Export finished', [
			'packetsCount' => count($packets),
			'productsCount' => count($productResult->getProducts()),
			'offersCount' => count($offers),
		]);
	}
}
