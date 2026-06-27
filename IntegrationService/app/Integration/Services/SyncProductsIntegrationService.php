<?php

declare(strict_types=1);

namespace App\Integration\Services;

use App\Integration\Contracts\EcommerceClientInterface;
use App\Integration\Contracts\ErpProviderInterface;
use App\Integration\Dtos\EcommerceSyncResult;
use Illuminate\Support\Facades\Log;

final class SyncProductsIntegrationService
{
    public function sync(ErpProviderInterface $provider, EcommerceClientInterface $client): EcommerceSyncResult
    {
        Log::info('Sincronização de produtos iniciada.');

        $products = [...$provider->getProducts()];

        Log::info('Produtos obtidos do provider de ERP.', [
            'total_products' => \count($products),
        ]);

        $result = $client->syncProducts($products);

        Log::info('Sincronização de produtos finalizada.', [
            'success' => $result->success,
            'total_products' => $result->totalProducts,
            'batches_sent' => $result->batchesSent,
        ]);

        return $result;
    }
}
