<?php

declare(strict_types=1);

namespace App\Integration\Contracts;

use App\Integration\Dtos\ProductDTO;

interface ErpProviderInterface
{
  /**
   * @return iterable<ProductDTO>
   */
  public function getProducts(): iterable;
}
