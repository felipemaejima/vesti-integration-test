<?php

declare(strict_types=1);

namespace App\Integration\Erp\Xpto;

use App\Integration\Dtos\OrderColorDTO;
use App\Integration\Dtos\ProductDTO;
use App\Integration\Dtos\VariationDTO;

final class XptoErpResponseMapper
{
  public function toProductDTO(array $product, array $variations): ProductDTO
  {
    $variationDTOs = array_map(
      $this->toVariationDTO(...),
      array_values($variations),
    );

    return new ProductDTO(
      code: (string) $product['code'],
      name: (string) $product['name'],
      description: $product['description'] ?? null,
      price: $this->normalizePrice($product['price']),
      pricePromotional: $this->normalizeOptionalPrice($product['price_promotional'] ?? null),
      composition: $product['composition'] ?? null,
      brand: $product['brand'] ?? null,
      variations: $variationDTOs,
      orderColors: $this->deriveOrderColors($variationDTOs),
    );
  }

  public function toVariationDTO(array $variation): VariationDTO
  {
    return new VariationDTO(
      sku: (string) $variation['sku'],
      size: (string) $variation['size'],
      color: (string) $variation['color'],
      quantity: (int) $variation['quantity'],
      unitMeasurement: (string) $variation['unit_measurement'],
      ordering: (int) $variation['ordering'],
    );
  }

  public function normalizePrice(int|float|string $price): float
  {
    if (\is_int($price) || \is_float($price)) {
      return (float) $price;
    }

    $normalized = str_replace(['.', ','], ['', '.'], $price);

    return (float) $normalized;
  }

  public function normalizeOptionalPrice(int|float|string|null $price): ?float
  {
    if ($price === null || $price === '') {
      return null;
    }

    return $this->normalizePrice($price);
  }

  private function deriveOrderColors(array $variations): array
  {
    $orderingByColor = [];

    foreach ($variations as $variation) {
      $color = $variation->color;

      if (!isset($orderingByColor[$color]) || $variation->ordering < $orderingByColor[$color]) {
        $orderingByColor[$color] = $variation->ordering;
      }
    }

    asort($orderingByColor);

    $orderColors = [];

    foreach ($orderingByColor as $color => $ordering) {
      $orderColors[] = new OrderColorDTO(
        color: (string) $color,
        order: $ordering,
      );
    }

    return $orderColors;
  }
}
