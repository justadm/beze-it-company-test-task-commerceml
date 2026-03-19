<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

use CIBlockSection;
use RuntimeException;
use UralenergomashTestTask\Support\ExportLogger;

final class CommerceMLPackageBuilder
{
	private ExportLogger $logger;

	public function __construct(ExportLogger $logger)
	{
		$this->logger = $logger;
	}

	public function build(array $params): CommerceMLBuildResult
	{
		$iblockId = (int)$params['iblockId'];
		$rootSectionId = (int)$params['rootSectionId'];
		$packetSize = (int)$params['packetSize'];
		$withImages = (bool)$params['withImages'];
		$skuInfo = $params['skuInfo'];
		$workDir = $this->resolveWorkDir($params['workDir'] ?? null);

		$rootSection = CIBlockSection::GetList(
			[],
			[
				'IBLOCK_ID' => $iblockId,
				'ID' => $rootSectionId,
			],
			false,
			['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL', 'NAME']
		)->Fetch();

		if (!$rootSection)
		{
			throw new RuntimeException('Root section not found.');
		}

		$packages = [];
		$totalProducts = 0;
		$totalOffers = 0;
		$lastProductId = 0;
		$packageIndex = 1;

		while (true)
		{
			$productFile = sprintf('%s/import_%03d.xml', $workDir, $packageIndex);

			$productExport = new ScopedCommerceMLExport();
			$productExport->setRootSectionBounds(
				$rootSectionId,
				(int)$rootSection['LEFT_MARGIN'],
				(int)$rootSection['RIGHT_MARGIN'],
				(int)$rootSection['DEPTH_LEVEL']
			);
			$productExport->setWithImages($withImages);

			$fp = fopen($productFile, 'wb');
			if ($fp === false)
			{
				throw new RuntimeException('Failed to open file for writing: ' . $productFile);
			}

			try
			{
				if (!$productExport->Init($fp, $iblockId, ['LAST_ID' => $lastProductId], false, false, false, false))
				{
					throw new RuntimeException('Failed to initialize CommerceML export for products.');
				}

				$productExport->NotCatalog();
				$productExport->ExportFileAsURL();
				if (!$withImages)
				{
					$productExport->DoNotDownloadCloudFiles();
				}

				$sectionMap = [];
				$propertyMap = [];

				$productExport->StartExport();
				$productExport->StartExportMetadata();
				$productExport->ExportProperties($propertyMap);
				$productExport->ExportScopedSections($sectionMap);
				$productExport->EndExportMetadata();
				$productExport->StartExportCatalog();

				$productCount = $productExport->ExportScopedElements(
					$propertyMap,
					$sectionMap,
					0,
					0,
					$packetSize,
					[
						'IBLOCK_ID' => $iblockId,
						'ACTIVE' => 'Y',
						'SECTION_ID' => $rootSectionId,
						'INCLUDE_SUBSECTIONS' => 'Y',
						'>ID' => $lastProductId,
					]
				);

				$productExport->EndExportCatalog();
				$productExport->EndExport();
			}
			finally
			{
				fclose($fp);
			}

			if ($productCount === 0)
			{
				@unlink($productFile);
				break;
			}

			$lastProductId = (int)($productExport->next_step['LAST_ID'] ?? 0);
			$productIds = $productExport->getExportedElementIds();
			$totalProducts += $productCount;

			$offersFile = null;
			$offersCount = 0;

			if ($skuInfo !== null && !empty($productIds))
			{
				$offersFile = sprintf('%s/offers_%03d.xml', $workDir, $packageIndex);
				$offersExport = new ScopedCommerceMLExport();
				$offersExport->setWithImages($withImages);

				$fp = fopen($offersFile, 'wb');
				if ($fp === false)
				{
					throw new RuntimeException('Failed to open file for writing: ' . $offersFile);
				}

				try
				{
					if (!$offersExport->Init(
						$fp,
						(int)$skuInfo['IBLOCK_ID'],
						['LAST_ID' => 0],
						false,
						false,
						false,
						false,
						$iblockId
					))
					{
						throw new RuntimeException('Failed to initialize CommerceML export for offers.');
					}

					$propertyMap = [];
					$sectionMap = [];

					$offersExport->ExportFileAsURL();
					if (!$withImages)
					{
						$offersExport->DoNotDownloadCloudFiles();
					}

					$offersExport->StartExport();
					$offersExport->StartExportMetadata();
					$offersExport->ExportProperties($propertyMap);
					$offersExport->EndExportMetadata();
					$offersExport->StartExportCatalog();
					$offersCount = $offersExport->ExportScopedElements(
						$propertyMap,
						$sectionMap,
						0,
						0,
						0,
						[
							'IBLOCK_ID' => (int)$skuInfo['IBLOCK_ID'],
							'ACTIVE' => 'Y',
							'PROPERTY_' . (int)$skuInfo['SKU_PROPERTY_ID'] => $productIds,
							'>ID' => 0,
						]
					);
					$offersExport->EndExportCatalog();
					$offersExport->EndExport();
				}
				finally
				{
					fclose($fp);
				}

				if ($offersCount === 0)
				{
					@unlink($offersFile);
					$offersFile = null;
				}
			}

			$totalOffers += $offersCount;

			$packages[] = new CommerceMLPackage($packageIndex, $productFile, $offersFile, $productCount, $offersCount);

			$this->logger->info('CommerceML package built', [
				'package' => $packageIndex,
				'productFile' => $productFile,
				'offersFile' => $offersFile,
				'products' => $productCount,
				'offers' => $offersCount,
			]);

			$packageIndex++;
		}

		if (empty($packages))
		{
			throw new RuntimeException('No products found in the selected section subtree.');
		}

		return new CommerceMLBuildResult($packages, $totalProducts, $totalOffers, $workDir);
	}

	private function resolveWorkDir(?string $workDir): string
	{
		if ($workDir === null || $workDir === '')
		{
			$workDir = sys_get_temp_dir() . '/uralenergomash-commerceml-' . date('Ymd-His');
		}

		if (!is_dir($workDir) && !mkdir($workDir, 0775, true) && !is_dir($workDir))
		{
			throw new RuntimeException('Failed to create workDir: ' . $workDir);
		}

		return $workDir;
	}
}
