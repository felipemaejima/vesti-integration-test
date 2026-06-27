<?php

declare(strict_types=1);

namespace App\Integration\Factories;

use App\Integration\Contracts\EcommerceClientInterface;
use InvalidArgumentException;

final class EcommerceClientFactory
{
    /**
     * Resolve as dependencias do serviço de Ecommerce com base nas configurações do arquivo config/integration.php
     *
     * @throws InvalidArgumentException When the name is unknown or its class
     *                                  does not implement the expected contract.
     */
    public function make(string $name): EcommerceClientInterface
    {
        /** @var array<string, class-string> $clients */
        $clients = config('integration.clients', []);

        if (! array_key_exists($name, $clients)) {
            throw new InvalidArgumentException(sprintf(
                'E-commerce "%s" desconhecido. Clients disponíveis: %s.',
                $name,
                implode(', ', array_keys($clients)) ?: '(nenhum configurado)',
            ));
        }

        $class = $clients[$name];
        $client = app($class);

        if (! $client instanceof EcommerceClientInterface) {
            throw new InvalidArgumentException(sprintf(
                'O client "%s" (%s) deve implementar %s.',
                $name,
                $class,
                EcommerceClientInterface::class,
            ));
        }

        return $client;
    }
}
