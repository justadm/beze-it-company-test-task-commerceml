<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Service;

use CPrice;
use CCatalogProduct;
use CIBlockElement;

final class OfferCollector
{
	private EntityNormalizer $normalizer;

	public function __construct(EntityNormalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	public function collect(array $skuInfo, array $productIds, array $productUidMap): array
	{
		$offers = [];
		$offersIblockId = (int)$skuInfo['IBLOCK_ID'];
		$skuPropertyId = (int)$skuInfo['SKU_PROPERTY_ID'];
		$propertyField = 'PROPERTY_' . $skuPropertyId;

		if (empty($productIds))
		{
			return $offers;
		}

		$result = CIBlockElement::GetList(
			['ID' => 'ASC'],
			[
				'IBLOCK_ID' => $offersIblockId,
				$propertyField => $productIds,
			],
			false,
			false,
			['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'SORT', 'ACTIVE', 'CODE', 'XML_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', $propertyField]
		);

		while ($row = $result->GetNext())
		{
			$offerId = (int)$row['ID'];
			$parentProductId = (int)$row[$propertyField . '_VALUE'];
			if (!isset($productUidMap[$parentProductId]))
			{
				continue;
			}

			$catalogData = CCatalogProduct::GetByID($offerId) ?: [];
			$priceData = CPrice::GetBasePrice($offerId) ?: [];
			$offers[] = $this->normalizer->normalizeOffer($row, $catalogData, $priceData, $productUidMap[$parentProductId]);
		}

		return $offers;
	}
}
