<?php

declare(strict_types=1);

namespace App\Integration\Dtos;

final readonly class EcommerceSyncResult
{
  public function __construct(
    public bool $success,
    public int $totalProducts,
    public int $batchesSent,
    public array $batches = [],
  ) {
  }
}
