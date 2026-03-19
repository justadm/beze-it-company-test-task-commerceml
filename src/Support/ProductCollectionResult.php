<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Support;

final class ProductCollectionResult
{
	private array $products;
	private array $bindings;
	private array $productUidMap;
	private array $sourceProductIds;

	public function __construct(array $products, array $bindings, array $productUidMap, array $sourceProductIds)
	{
		$this->products = $products;
		$this->bindings = $bindings;
		$this->productUidMap = $productUidMap;
		$this->sourceProductIds = $sourceProductIds;
	}

	public function getProducts(): array
	{
		return $this->products;
	}

	public function getBindings(): array
	{
		return $this->bindings;
	}

	public function getProductUidMap(): array
	{
		return $this->productUidMap;
	}

	public function getSourceProductIds(): array
	{
		return $this->sourceProductIds;
	}
}
