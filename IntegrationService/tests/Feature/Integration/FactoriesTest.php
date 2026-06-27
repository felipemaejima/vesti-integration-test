<?php

declare(strict_types=1);

use App\Integration\Contracts\EcommerceClientInterface;
use App\Integration\Contracts\ErpProviderInterface;
use App\Integration\Ecommerce\Vesti\VestiEcommerceClient;
use App\Integration\Erp\Xpto\XptoErpProvider;
use App\Integration\Factories\EcommerceClientFactory;
use App\Integration\Factories\ErpProviderFactory;

describe('ErpProviderFactory', function () {
  it('resolve um nome de provider conhecido para sua implementação concreta', function () {
    $provider = (new ErpProviderFactory)->make('xpto');

    expect($provider)->toBeInstanceOf(ErpProviderInterface::class)
      ->toBeInstanceOf(XptoErpProvider::class);
  });

  it('lança uma exceção descritiva para um provider desconhecido', function () {
    (new ErpProviderFactory)->make('nope');
  })->throws(InvalidArgumentException::class, 'ERP "nope" desconhecido');
});

describe('EcommerceClientFactory', function () {
  it('resolve um nome de client conhecido para sua implementação concreta', function () {
    $client = (new EcommerceClientFactory)->make('vesti');

    expect($client)->toBeInstanceOf(EcommerceClientInterface::class)
      ->toBeInstanceOf(VestiEcommerceClient::class);
  });

  it('lança uma exceção descritiva para um client desconhecido', function () {
    (new EcommerceClientFactory)->make('nope');
  })->throws(InvalidArgumentException::class, 'E-commerce "nope" desconhecido');
});
