<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Tests;

use UralenergomashTestTask\Service\PacketBuilder;

final class PacketBuilderTest extends TestCase
{
	public function testBuildSplitsProductsAndKeepsRelatedOffersInSamePacket(): void
	{
		$builder = new PacketBuilder();

		$packets = $builder->build(
			[['uid' => 'section_1']],
			[
				['uid' => 'product_1', 'has_offers' => 'N'],
				['uid' => 'product_2', 'has_offers' => 'N'],
				['uid' => 'product_3', 'has_offers' => 'N'],
			],
			[
				['product_uid' => 'product_1', 'section_uids' => ['section_1']],
				['product_uid' => 'product_2', 'section_uids' => ['section_1']],
				['product_uid' => 'product_3', 'section_uids' => ['section_1']],
			],
			[
				['uid' => 'offer_1', 'parent_product_uid' => 'product_1'],
				['uid' => 'offer_2', 'parent_product_uid' => 'product_3'],
			],
			[
				'packetSize' => 2,
				'iblockId' => 10,
				'catalogType' => 'D',
				'rootSectionUid' => 'section_1',
			]
		);

		$this->assertCount(2, $packets);
		$this->assertSame(2, $packets[0]['meta']['packet_total']);
		$this->assertSame(2, $packets[0]['meta']['products_in_packet']);
		$this->assertSame(['offer_1'], array_column($packets[0]['offers'], 'uid'));
		$this->assertSame(['offer_2'], array_column($packets[1]['offers'], 'uid'));
		$this->assertSame('Y', $packets[0]['products'][0]['has_offers']);
		$this->assertSame('N', $packets[0]['products'][1]['has_offers']);
		$this->assertSame('Y', $packets[1]['products'][0]['has_offers']);
	}
}
