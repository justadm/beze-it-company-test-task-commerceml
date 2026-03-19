<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Support;

final class UidGenerator
{
	public function sectionUid(int $id): string
	{
		return 'section_' . $id;
	}

	public function productUid(int $id): string
	{
		return 'product_' . $id;
	}

	public function offerUid(int $id): string
	{
		return 'offer_' . $id;
	}
}
