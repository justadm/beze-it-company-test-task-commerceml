<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Service;

use CIBlockSection;
use RuntimeException;
use UralenergomashTestTask\Support\SectionTreeResult;
use UralenergomashTestTask\Support\UidGenerator;

final class SectionTreeBuilder
{
	private UidGenerator $uidGenerator;
	private EntityNormalizer $normalizer;

	public function __construct(UidGenerator $uidGenerator, EntityNormalizer $normalizer)
	{
		$this->uidGenerator = $uidGenerator;
		$this->normalizer = $normalizer;
	}

	public function build(int $iblockId, int $rootSectionId): SectionTreeResult
	{
		$root = CIBlockSection::GetList(
			[],
			[
				'IBLOCK_ID' => $iblockId,
				'ID' => $rootSectionId,
			],
			false,
			['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'NAME', 'DESCRIPTION', 'SORT', 'ACTIVE', 'CODE', 'XML_ID', 'PICTURE']
		)->Fetch();

		if (!$root)
		{
			throw new RuntimeException('Root section not found.');
		}

		$sections = [];
		$sectionUidMap = [];

		$result = CIBlockSection::GetList(
			['LEFT_MARGIN' => 'ASC'],
			[
				'IBLOCK_ID' => $iblockId,
				'>=LEFT_MARGIN' => (int)$root['LEFT_MARGIN'],
				'<=RIGHT_MARGIN' => (int)$root['RIGHT_MARGIN'],
			],
			false,
			['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'DESCRIPTION', 'SORT', 'ACTIVE', 'CODE', 'XML_ID', 'PICTURE']
		);

		while ($row = $result->Fetch())
		{
			$sectionId = (int)$row['ID'];
			$sectionUidMap[$sectionId] = $this->uidGenerator->sectionUid($sectionId);
			$sections[] = $this->normalizer->normalizeSection($row);
		}

		return new SectionTreeResult(
			$sections,
			$sectionUidMap,
			array_keys($sectionUidMap),
			$this->uidGenerator->sectionUid((int)$root['ID'])
		);
	}
}
