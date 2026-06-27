<?php

declare(strict_types=1);

use App\Integration\Contracts\EcommerceClientInterface;
use App\Integration\Contracts\ErpProviderInterface;
use App\Integration\Dtos\EcommerceSyncResult;
use App\Integration\Dtos\ProductDTO;
use App\Integration\Services\SyncProductsIntegrationService;

function fakeProductDTO(string $code): ProductDTO
{
  return new ProductDTO(
    code: $code,
    name: "Produto {$code}",
    description: null,
    price: 1.0,
    pricePromotional: null,
    composition: null,
    brand: null,
  );
}

it('lê os produtos do provider e os repassa ao client', function () {
  $provider = new class implements ErpProviderInterface {
    public function getProducts(): iterable
    {
      return [fakeProductDTO('1'), fakeProductDTO('2')];
    }
  };

  $client = new class implements EcommerceClientInterface {
    public ?array $received = null;

    public function syncProducts(iterable $products): EcommerceSyncResult
    {
      $this->received = is_array($products) ? $products : iterator_to_array($products);

      return new EcommerceSyncResult(success: true, totalProducts: count($this->received), batchesSent: 1);
    }
  };

  $result = (new SyncProductsIntegrationService)->sync($provider, $client);

  expect($client->received)->toHaveCount(2)
    ->and($client->received[0]->code)->toBe('1')
    ->and($result)->toBeInstanceOf(EcommerceSyncResult::class)
    ->and($result->success)->toBeTrue()
    ->and($result->totalProducts)->toBe(2);
});

it('retorna o resultado do client sem alterações', function () {
  $provider = new class implements ErpProviderInterface {
    public function getProducts(): iterable
    {
      return [];
    }
  };

  $expected = new EcommerceSyncResult(success: false, totalProducts: 0, batchesSent: 0);

  $client = new class ($expected) implements EcommerceClientInterface {
    public function __construct(private EcommerceSyncResult $result)
    {}

    public function syncProducts(iterable $products): EcommerceSyncResult
    {
      return $this->result;
    }
  };

  expect((new SyncProductsIntegrationService)->sync($provider, $client))->toBe($expected);
});
