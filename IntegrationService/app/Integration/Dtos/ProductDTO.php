<?php

declare(strict_types=1);

namespace App\Integration\Dtos;

final readonly class ProductDTO
{
  /**
   * @param  list<VariationDTO>  $variations
   * @param  list<OrderColorDTO>  $orderColors
   */
  public function __construct(
    public string $code,
    public string $name,
    public ?string $description,
    public float $price,
    public ?float $pricePromotional,
    public ?string $composition,
    public ?string $brand,
    public array $variations = [],
    public array $orderColors = [],
  ) {
  }
}
