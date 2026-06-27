<?php

namespace App\Console\Commands;

use App\Integration\Factories\EcommerceClientFactory;
use App\Integration\Factories\ErpProviderFactory;
use App\Integration\Services\SyncProductsIntegrationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('sync {connection : Conexão no formato provider:client, ex.: xpto:vesti}')]
#[Description('Sincroniza produtos de um ERP para uma plataforma de e-commerce')]
class SyncProductsCommand extends Command
{
    public function handle(
        ErpProviderFactory $providerFactory,
        EcommerceClientFactory $clientFactory,
        SyncProductsIntegrationService $service,
    ): int {
        $connection = (string) $this->argument('connection');
        $parts = explode(':', $connection);

        if (\count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            $this->error("Conexão \"{$connection}\" inválida. Formato esperado: provider:client (ex.: xpto:vesti).");

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

        $this->info("Sincronizando produtos: {$providerName} -> {$clientName}...");

        $result = $service->sync($provider, $client);

        $this->table(
            ['Sucesso', 'Total de produtos', 'Lotes enviados'],
            [
                [
                    $result->success ? 'sim' : 'não',
                    $result->totalProducts,
                    $result->batchesSent,
                ],
            ],
        );

        if (! $result->success) {
            $this->error('Sincronização concluída com falhas. Verifique os logs para detalhes.');

            return self::FAILURE;
        }

        $this->info('Sincronização concluída com sucesso.');

        return self::SUCCESS;
    }
}
