<?php

declare(strict_types=1);

namespace App\Integration\Dtos;

final readonly class OrderColorDTO
{
  public function __construct(
    public string $color,
    public int $order,
  ) {
  }
}
