<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

use CIBlockSection;
use CIBlockCMLExport;

final class ScopedCommerceMLExport extends CIBlockCMLExport
{
	private int $rootSectionId = 0;
	private int $leftMargin = 0;
	private int $rightMargin = 0;
	private int $rootDepthLevel = 1;
	private bool $withImages = false;
	private array $exportedElementIds = [];

	public function setRootSectionBounds(int $rootSectionId, int $leftMargin, int $rightMargin, int $rootDepthLevel): void
	{
		$this->rootSectionId = $rootSectionId;
		$this->leftMargin = $leftMargin;
		$this->rightMargin = $rightMargin;
		$this->rootDepthLevel = $rootDepthLevel;
	}

	public function setWithImages(bool $withImages): void
	{
		$this->withImages = $withImages;
	}

	public function getExportedElementIds(): array
	{
		return $this->exportedElementIds;
	}

	public function ExportScopedSections(array &$sectionMap): int
	{
		$counter = 0;
		$sectionMap = [];

		fwrite($this->fp, "\t\t<" . GetMessage('IBLOCK_XML2_GROUPS') . ">\n");

		$rsSections = CIBlockSection::GetList(
			['LEFT_MARGIN' => 'ASC'],
			[
				'IBLOCK_ID' => $this->arIBlock['ID'],
				'>=LEFT_MARGIN' => $this->leftMargin,
				'<=RIGHT_MARGIN' => $this->rightMargin,
				'CHECK_PERMISSIONS' => 'N',
			],
			false
		);

		$currentDepth = 0;
		while ($arSection = $rsSections->Fetch())
		{
			$relativeDepth = ((int)$arSection['DEPTH_LEVEL'] - $this->rootDepthLevel) + 1;

			while ($currentDepth >= $relativeDepth)
			{
				fwrite($this->fp, str_repeat("\t\t", $currentDepth) . "\t\t</" . GetMessage('IBLOCK_XML2_GROUPS') . ">\n");
				fwrite($this->fp, str_repeat("\t\t", $currentDepth - 1) . "\t\t\t</" . GetMessage('IBLOCK_XML2_GROUP') . ">\n");
				$currentDepth--;
			}

			$whiteSpace = str_repeat("\t\t", $relativeDepth);
			$level = ($relativeDepth + 1) * 2;
			$xmlId = $this->GetSectionXML_ID($this->arIBlock['ID'], (int)$arSection['ID'], $arSection['XML_ID']);
			$sectionMap[(int)$arSection['ID']] = $xmlId;

			fwrite(
				$this->fp,
				$whiteSpace . "\t<" . GetMessage('IBLOCK_XML2_GROUP') . ">\n"
				. $this->formatXMLNode($level, GetMessage('IBLOCK_XML2_ID'), $xmlId)
				. $this->formatXMLNode($level, GetMessage('IBLOCK_XML2_NAME'), $arSection['NAME'])
			);
			if ($arSection['DESCRIPTION'] !== '')
			{
				fwrite(
					$this->fp,
					$whiteSpace . "\t\t<" . GetMessage('IBLOCK_XML2_DESCRIPTION') . ">"
					. htmlspecialcharsbx(FormatText($arSection['DESCRIPTION'], $arSection['DESCRIPTION_TYPE']))
					. "</" . GetMessage('IBLOCK_XML2_DESCRIPTION') . ">\n"
				);
			}

			if ($this->bExtended)
			{
				fwrite(
					$this->fp,
					$this->formatXMLNode($level, GetMessage('IBLOCK_XML2_BX_ACTIVE'), ($arSection['ACTIVE'] === 'Y' ? 'true' : 'false'))
					. $this->formatXMLNode($level, GetMessage('IBLOCK_XML2_BX_SORT'), (int)$arSection['SORT'])
					. $this->formatXMLNode($level, GetMessage('IBLOCK_XML2_BX_CODE'), $arSection['CODE'])
				);
			}

			fwrite($this->fp, $whiteSpace . "\t\t<" . GetMessage('IBLOCK_XML2_GROUPS') . ">\n");
			$currentDepth = $relativeDepth;
			$counter++;
		}

		while ($currentDepth > 0)
		{
			fwrite($this->fp, str_repeat("\t\t", $currentDepth) . "\t\t</" . GetMessage('IBLOCK_XML2_GROUPS') . ">\n");
			fwrite($this->fp, str_repeat("\t\t", $currentDepth - 1) . "\t\t\t</" . GetMessage('IBLOCK_XML2_GROUP') . ">\n");
			$currentDepth--;
		}

		fwrite($this->fp, "\t\t</" . GetMessage('IBLOCK_XML2_GROUPS') . ">\n");

		return $counter;
	}

	public function ExportScopedElements(array $propertyMap, array $sectionMap, int $startTime, int $interval, int $counterLimit, array $filter): int
	{
		$this->exportedElementIds = [];

		return $this->ExportElements($propertyMap, $sectionMap, $startTime, $interval, $counterLimit, $filter);
	}

	public function exportElement($arElement, $SECTION_MAP, $PROPERTY_MAP)
	{
		$this->exportedElementIds[] = (int)$arElement['ID'];
		parent::exportElement($arElement, $SECTION_MAP, $PROPERTY_MAP);
	}
}
