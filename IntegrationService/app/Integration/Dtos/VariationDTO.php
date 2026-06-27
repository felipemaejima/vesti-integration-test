<?php

declare(strict_types=1);

namespace App\Integration\Dtos;

final readonly class VariationDTO
{
  public function __construct(
    public string $sku,
    public string $size,
    public string $color,
    public int $quantity,
    public string $unitMeasurement,
    public int $ordering,
  ) {
  }
}
