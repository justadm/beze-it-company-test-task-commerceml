<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Service;

use CPrice;
use CCatalogProduct;
use CIBlockElement;
use UralenergomashTestTask\Support\ProductCollectionResult;

final class ProductCollector
{
	private EntityNormalizer $normalizer;

	public function __construct(EntityNormalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	public function collect(int $iblockId, array $sectionIds, array $sectionUidMap): ProductCollectionResult
	{
		$products = [];
		$bindings = [];
		$productUidMap = [];
		$sourceProductIds = [];

		$result = CIBlockElement::GetList(
			['ID' => 'ASC'],
			[
				'IBLOCK_ID' => $iblockId,
				'SECTION_ID' => $sectionIds,
				'INCLUDE_SUBSECTIONS' => 'Y',
				'ACTIVE' => 'Y',
			],
			false,
			false,
			['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'SORT', 'ACTIVE', 'CODE', 'XML_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
		);

		while ($row = $result->Fetch())
		{
			$productId = (int)$row['ID'];
			$catalogData = CCatalogProduct::GetByID($productId) ?: [];
			$priceData = CPrice::GetBasePrice($productId) ?: [];
			$groupIds = [];

			$groups = CIBlockElement::GetElementGroups($productId, true, ['ID']);
			while ($group = $groups->Fetch())
			{
				$groupId = (int)$group['ID'];
				if (isset($sectionUidMap[$groupId]))
				{
					$groupIds[] = $groupId;
				}
			}

			$normalizedProduct = $this->normalizer->normalizeProduct($row, $catalogData, $priceData);
			$productUid = $normalizedProduct['uid'];

			$products[] = $normalizedProduct;
			$bindings[] = [
				'product_uid' => $productUid,
				'section_uids' => array_values(array_map(static function (int $sectionId) use ($sectionUidMap): string {
					return $sectionUidMap[$sectionId];
				}, array_unique($groupIds))),
			];
			$productUidMap[$productId] = $productUid;
			$sourceProductIds[] = $productId;
		}

		return new ProductCollectionResult($products, $bindings, $productUidMap, $sourceProductIds);
	}
}
