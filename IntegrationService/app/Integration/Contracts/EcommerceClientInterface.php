<?php

declare(strict_types=1);

namespace App\Integration\Contracts;

use App\Integration\Dtos\EcommerceSyncResult;
use App\Integration\Dtos\ProductDTO;

interface EcommerceClientInterface
{
  /**
   * @param  iterable<ProductDTO>  $products
   */
  public function syncProducts(iterable $products): EcommerceSyncResult;
}
