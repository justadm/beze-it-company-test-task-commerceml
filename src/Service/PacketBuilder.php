<?php

declare(strict_types=1);

namespace UralenergomashTestTask\Service;

final class PacketBuilder
{
	public function build(array $sections, array $products, array $bindings, array $offers, array $meta): array
	{
		$packetSize = (int)$meta['packetSize'];
		$productChunks = array_chunk($products, $packetSize);
		$bindingsMap = [];
		$offersMap = [];
		$packets = [];

		foreach ($bindings as $binding)
		{
			$bindingsMap[$binding['product_uid']] = $binding;
		}

		foreach ($offers as $offer)
		{
			$offersMap[$offer['parent_product_uid']][] = $offer;
		}

		$packetTotal = count($productChunks);

		foreach ($productChunks as $index => $productChunk)
		{
			$productUids = array_column($productChunk, 'uid');
			$packetBindings = [];
			$packetOffers = [];

			foreach ($productUids as $productUid)
			{
				if (isset($bindingsMap[$productUid]))
				{
					$packetBindings[] = $bindingsMap[$productUid];
				}

				if (isset($offersMap[$productUid]))
				{
					foreach ($offersMap[$productUid] as $offer)
					{
						$packetOffers[] = $offer;
					}
				}
			}

			$packets[] = [
				'meta' => [
					'protocol_version' => 1,
					'source' => [
						'iblock_id' => $meta['iblockId'],
						'catalog_type' => $meta['catalogType'],
					],
					'root_section_uid' => $meta['rootSectionUid'],
					'packet_index' => $index + 1,
					'packet_total' => $packetTotal,
					'products_in_packet' => count($productChunk),
					'generated_at' => date(DATE_ATOM),
				],
				'sections' => $sections,
				'products' => $this->markProductsWithOffers($productChunk, $offersMap),
				'offers' => $packetOffers,
				'section_product_bindings' => $packetBindings,
			];
		}

		return $packets;
	}

	private function markProductsWithOffers(array $products, array $offersMap): array
	{
		foreach ($products as &$product)
		{
			$product['has_offers'] = isset($offersMap[$product['uid']]) ? 'Y' : 'N';
		}
		unset($product);

		return $products;
	}
}
