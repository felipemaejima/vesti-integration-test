<?php

declare(strict_types=1);

namespace App\Integration\Services;

use App\Integration\Contracts\EcommerceClientInterface;
use App\Integration\Contracts\ErpProviderInterface;
use App\Integration\Dtos\EcommerceSyncResult;
use App\Integration\Dtos\ProductDTO;
use Illuminate\Support\Facades\Log;

final class SyncProductsIntegrationService
{

  public function sync(ErpProviderInterface $provider, EcommerceClientInterface $client): EcommerceSyncResult
  {
    Log::info('Product synchronization started.');

    $products = [...$provider->getProducts()];

    Log::info('Products fetched from ERP provider.', [
      'total_products' => count($products),
    ]);

    $result = $client->syncProducts($products);

    Log::info('Product synchronization finished.', [
      'success' => $result->success,
      'total_products' => $result->totalProducts,
      'batches_sent' => $result->batchesSent,
    ]);

    return $result;
  }
}
