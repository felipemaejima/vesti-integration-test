<?php

namespace App\Console\Commands;

use App\Integration\Factories\EcommerceClientFactory;
use App\Integration\Factories\ErpProviderFactory;
use App\Integration\Services\SyncProductsIntegrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('sync {connection : Connection in the provider:client format, e.g. xpto:vesti}')]
#[Description('Synchronizes products from an ERP provider into an ecommerce client')]
class SyncProductsCommand extends Command
{

  public function handle(
    ErpProviderFactory $providerFactory,
    EcommerceClientFactory $clientFactory,
    SyncProductsIntegrationService $service,
  ): int {
    $connection = (string) $this->argument('connection');
    $parts = explode(':', $connection);

    if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
      $this->error("Invalid connection \"{$connection}\". Expected format: {provider}:{client} (e.g. xpto:vesti).");

      return self::FAILURE;
    }

    [$providerName, $clientName] = [trim($parts[0]), trim($parts[1])];

    try {

      /**
       * Resolve as dependencias
       */
      $provider = $providerFactory->make($providerName);
      $client = $clientFactory->make($clientName);

    } catch (InvalidArgumentException $exception) {

      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    $this->info("Synchronizing products: {$providerName} -> {$clientName}...");

    $result = $service->sync($provider, $client);

    $this->table(
      ['Success', 'Total products', 'Batches sent'],
      [
        [
          $result->success ? 'yes' : 'no',
          $result->totalProducts,
          $result->batchesSent,
        ]
      ],
    );

    if (!$result->success) {
      $this->error('Synchronization completed with failures. Check the logs for details.');

      return self::FAILURE;
    }

    $this->info('Synchronization completed successfully.');

    return self::SUCCESS;
  }
}
