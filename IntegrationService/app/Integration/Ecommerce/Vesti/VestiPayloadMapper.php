<?php

declare(strict_types=1);

namespace App\Integration\Ecommerce\Vesti;

use App\Integration\Dtos\OrderColorDTO;
use App\Integration\Dtos\ProductDTO;
use App\Integration\Dtos\VariationDTO;

final class VestiPayloadMapper
{

  public function toPayload(ProductDTO $product): array
  {
    return [
      'integration_id' => $product->code,
      'code' => $product->code,
      'name' => $product->name,
      'description' => $product->description,
      'composition' => $product->composition,
      'brand' => $product->brand,
      'price' => $product->price,
      'price_promotional' => $product->pricePromotional,
      'order_colors' => $this->mapOrderColors($product->orderColors),
      'variations' => $this->mapVariations($product->variations),
    ];
  }

  public function toRequestBody(iterable $products): array
  {
    $payloads = [];

    foreach ($products as $product) {
      $payloads[] = $this->toPayload($product);
    }

    return ['products' => $payloads];
  }

  private function mapOrderColors(array $orderColors): array
  {
    return array_map(
      static fn(OrderColorDTO $orderColor): array => [
        'color' => $orderColor->color,
        'order' => $orderColor->order,
      ],
      $orderColors,
    );
  }

  private function mapVariations(array $variations): array
  {
    return array_map(
      static fn(VariationDTO $variation): array => [
        'sku' => $variation->sku,
        'size' => $variation->size,
        'color' => $variation->color,
        'quantity' => $variation->quantity,
        'order' => $variation->ordering,
        'unit_type' => $variation->unitMeasurement,
      ],
      $variations,
    );
  }
}
