<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Tests;

use UralenergomashTestTask\Support\UidGenerator;

final class UidGeneratorTest extends TestCase
{
	public function testGeneratesStablePrefixes(): void
	{
		$generator = new UidGenerator();

		$this->assertSame('section_5', $generator->sectionUid(5));
		$this->assertSame('product_12', $generator->productUid(12));
		$this->assertSame('offer_99', $generator->offerUid(99));
	}
}
