<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Support;

final class SectionTreeResult
{
	private array $sections;
	private array $sectionUidMap;
	private array $sourceSectionIds;
	private string $rootSectionUid;

	public function __construct(array $sections, array $sectionUidMap, array $sourceSectionIds, string $rootSectionUid)
	{
		$this->sections = $sections;
		$this->sectionUidMap = $sectionUidMap;
		$this->sourceSectionIds = $sourceSectionIds;
		$this->rootSectionUid = $rootSectionUid;
	}

	public function getSections(): array
	{
		return $this->sections;
	}

	public function getSectionUidMap(): array
	{
		return $this->sectionUidMap;
	}

	public function getSourceSectionIds(): array
	{
		return $this->sourceSectionIds;
	}

	public function getRootSectionUid(): string
	{
		return $this->rootSectionUid;
	}
}
