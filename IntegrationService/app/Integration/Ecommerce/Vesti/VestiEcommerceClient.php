<?php

declare(strict_types=1);

namespace App\Integration\Ecommerce\Vesti;

use App\Integration\Contracts\EcommerceClientInterface;
use App\Integration\Dtos\EcommerceSyncResult;
use App\Integration\Dtos\ProductDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class VestiEcommerceClient implements EcommerceClientInterface
{
  public function __construct(
    private readonly VestiPayloadMapper $mapper,
  ) {
  }

  public function syncProducts(iterable $products): EcommerceSyncResult
  {
    $collection = Collection::make($products);

    $batchSize = (int) config('integration.batch_size', 500);
    $totalProducts = $collection->count();

    Log::info('Vesti sync started.', [
      'total_products' => $totalProducts,
      'batch_size' => $batchSize,
    ]);

    $batches = [];
    $allSucceeded = true;
    $batchNumber = 0;

    foreach ($collection->chunk($batchSize) as $chunk) {
      $batchNumber++;
      $outcome = $this->sendBatch($chunk->values(), $batchNumber);

      $batches[] = $outcome;
      $allSucceeded = $allSucceeded && $outcome['success'];
    }

    Log::info('Vesti sync finished.', [
      'total_products' => $totalProducts,
      'batches_sent' => $batchNumber,
      'success' => $allSucceeded,
    ]);

    return new EcommerceSyncResult(
      success: $allSucceeded,
      totalProducts: $totalProducts,
      batchesSent: $batchNumber,
      batches: $batches,
    );
  }

  private function sendBatch(Collection $products, int $batchNumber): array
  {
    $body = $this->mapper->toRequestBody($products);

    Log::info('Sending Vesti batch.', [
      'batch' => $batchNumber,
      'products' => $products->count(),
    ]);

    try {
      $response = Http::withHeaders([
        'apikey' => (string) config('integration.ecommerce.vesti.api_token'),
      ])
        ->acceptJson()
        ->asJson()
        ->post($this->endpoint(), $body);

      $succeeded = $response->successful();

      Log::log($succeeded ? 'info' : 'error', 'Vesti batch response received.', [
        'batch' => $batchNumber,
        'status' => $response->status(),
        'success' => $succeeded,
      ]);

      return [
        'batch' => $batchNumber,
        'success' => $succeeded,
        'status' => $response->status(),
        'body' => $response->json() ?? $response->body(),
      ];
    } catch (\Throwable $exception) {
      Log::error('Vesti batch request failed.', [
        'batch' => $batchNumber,
        'exception' => $exception->getMessage(),
      ]);

      return [
        'batch' => $batchNumber,
        'success' => false,
        'status' => null,
        'body' => $exception->getMessage(),
      ];
    }
  }

  private function endpoint(): string
  {
    $apiUrl = trim((string) config('integration.ecommerce.vesti.api_url'), '/');
    $companyId = (string) config('integration.ecommerce.vesti.company_id');

    return "https://{$apiUrl}/v1/products/company/{$companyId}";
  }
}
