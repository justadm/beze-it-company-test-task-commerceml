<?php

declare(strict_types=1);

namespace UralenergomashTestTask\CommerceML;

final class CommerceMLBuildResult
{
	/** @var CommerceMLPackage[] */
	private array $packages;
	private int $productsCount;
	private int $offersCount;
	private string $workDir;

	/**
	 * @param CommerceMLPackage[] $packages
	 */
	public function __construct(array $packages, int $productsCount, int $offersCount, string $workDir)
	{
		$this->packages = $packages;
		$this->productsCount = $productsCount;
		$this->offersCount = $offersCount;
		$this->workDir = $workDir;
	}

	/**
	 * @return CommerceMLPackage[]
	 */
	public function getPackages(): array
	{
		return $this->packages;
	}

	public function getProductsCount(): int
	{
		return $this->productsCount;
	}

	public function getOffersCount(): int
	{
		return $this->offersCount;
	}

	public function getWorkDir(): string
	{
		return $this->workDir;
	}
}
