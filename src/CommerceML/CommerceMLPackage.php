<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

final class CommerceMLPackage
{
	private int $index;
	private string $importFile;
	private ?string $offersFile;
	private int $productsCount;
	private int $offersCount;

	public function __construct(int $index, string $importFile, ?string $offersFile, int $productsCount, int $offersCount)
	{
		$this->index = $index;
		$this->importFile = $importFile;
		$this->offersFile = $offersFile;
		$this->productsCount = $productsCount;
		$this->offersCount = $offersCount;
	}

	public function getIndex(): int
	{
		return $this->index;
	}

	public function getImportFile(): string
	{
		return $this->importFile;
	}

	public function getOffersFile(): ?string
	{
		return $this->offersFile;
	}

	public function getProductsCount(): int
	{
		return $this->productsCount;
	}

	public function getOffersCount(): int
	{
		return $this->offersCount;
	}
}
