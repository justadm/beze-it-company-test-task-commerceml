<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Service;

use CFile;
use UralenergomashTestTask\Support\UidGenerator;

final class EntityNormalizer
{
	private UidGenerator $uidGenerator;
	private bool $withImages;

	public function __construct(UidGenerator $uidGenerator, array $params)
	{
		$this->uidGenerator = $uidGenerator;
		$this->withImages = (($params['withImages'] ?? 'N') === 'Y');
	}

	public function normalizeSection(array $row): array
	{
		$sectionId = (int)$row['ID'];

		return [
			'uid' => $this->uidGenerator->sectionUid($sectionId),
			'parent_uid' => (int)$row['IBLOCK_SECTION_ID'] > 0
				? $this->uidGenerator->sectionUid((int)$row['IBLOCK_SECTION_ID'])
				: null,
			'xml_id' => (string)$row['XML_ID'],
			'code' => (string)$row['CODE'],
			'name' => (string)$row['NAME'],
			'description' => (string)$row['DESCRIPTION'],
			'sort' => (int)$row['SORT'],
			'active' => (string)$row['ACTIVE'],
			'picture' => $this->normalizeFile((int)$row['PICTURE']),
		];
	}

	public function normalizeProduct(array $row, array $catalogData, array $priceData): array
	{
		$productId = (int)$row['ID'];

		return [
			'uid' => $this->uidGenerator->productUid($productId),
			'xml_id' => (string)$row['XML_ID'],
			'code' => (string)$row['CODE'],
			'name' => (string)$row['NAME'],
			'preview_text' => (string)$row['PREVIEW_TEXT'],
			'detail_text' => (string)$row['DETAIL_TEXT'],
			'sort' => (int)$row['SORT'],
			'active' => (string)$row['ACTIVE'],
			'iblock_section_main_uid' => (int)$row['IBLOCK_SECTION_ID'] > 0
				? $this->uidGenerator->sectionUid((int)$row['IBLOCK_SECTION_ID'])
				: null,
			'preview_picture' => $this->normalizeFile((int)$row['PREVIEW_PICTURE']),
			'detail_picture' => $this->normalizeFile((int)$row['DETAIL_PICTURE']),
			'price' => [
				'base' => isset($priceData['PRICE']) ? (float)$priceData['PRICE'] : null,
				'currency' => $priceData['CURRENCY'] ?? null,
			],
			'quantity' => isset($catalogData['QUANTITY']) ? (float)$catalogData['QUANTITY'] : null,
			'available' => $catalogData['AVAILABLE'] ?? null,
			'has_offers' => 'N',
		];
	}

	public function normalizeOffer(array $row, array $catalogData, array $priceData, string $parentProductUid): array
	{
		$offerId = (int)$row['ID'];

		return [
			'uid' => $this->uidGenerator->offerUid($offerId),
			'parent_product_uid' => $parentProductUid,
			'xml_id' => (string)$row['XML_ID'],
			'code' => (string)$row['CODE'],
			'name' => (string)$row['NAME'],
			'preview_text' => (string)$row['PREVIEW_TEXT'],
			'detail_text' => (string)$row['DETAIL_TEXT'],
			'sort' => (int)$row['SORT'],
			'active' => (string)$row['ACTIVE'],
			'preview_picture' => $this->normalizeFile((int)$row['PREVIEW_PICTURE']),
			'detail_picture' => $this->normalizeFile((int)$row['DETAIL_PICTURE']),
			'price' => [
				'base' => isset($priceData['PRICE']) ? (float)$priceData['PRICE'] : null,
				'currency' => $priceData['CURRENCY'] ?? null,
			],
			'quantity' => isset($catalogData['QUANTITY']) ? (float)$catalogData['QUANTITY'] : null,
			'available' => $catalogData['AVAILABLE'] ?? null,
			'attributes' => [],
		];
	}

	private function normalizeFile(int $fileId): ?array
	{
		if (!$this->withImages || $fileId <= 0)
		{
			return null;
		}

		$file = CFile::GetFileArray($fileId);
		if (!$file)
		{
			return null;
		}

		return [
			'id' => (int)$file['ID'],
			'subdir' => (string)$file['SUBDIR'],
			'file_name' => (string)$file['FILE_NAME'],
			'content_type' => (string)$file['CONTENT_TYPE'],
			'file_size' => isset($file['FILE_SIZE']) ? (int)$file['FILE_SIZE'] : null,
			'src' => (string)$file['SRC'],
		];
	}
}
