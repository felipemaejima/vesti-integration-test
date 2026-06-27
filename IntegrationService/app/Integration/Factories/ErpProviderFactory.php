<?php

declare(strict_types=1);

namespace App\Integration\Factories;

use App\Integration\Contracts\ErpProviderInterface;
use InvalidArgumentException;

final class ErpProviderFactory
{
  /**
   * Resolve as dependencias dos providers de ERPs dinamicamente com base nas informações do arquivo config/integration.php
   *
   * @throws InvalidArgumentException When the name is unknown or its class
   *                                  does not implement the expected contract.
   */
  public function make(string $name): ErpProviderInterface
  {
    /** @var array<string, class-string> $providers */
    $providers = config('integration.providers', []);

    if (!array_key_exists($name, $providers)) {
      throw new InvalidArgumentException(sprintf(
        'Unknown ERP provider "%s". Available providers: %s.',
        $name,
        implode(', ', array_keys($providers)) ?: '(none configured)',
      ));
    }

    $class = $providers[$name];
    $provider = app($class);

    if (!$provider instanceof ErpProviderInterface) {
      throw new InvalidArgumentException(sprintf(
        'Provider "%s" (%s) must implement %s.',
        $name,
        $class,
        ErpProviderInterface::class,
      ));
    }

    return $provider;
  }
}
