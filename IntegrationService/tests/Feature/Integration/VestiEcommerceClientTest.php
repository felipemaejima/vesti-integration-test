<?php

declare(strict_types=1);

use App\Integration\Dtos\EcommerceSyncResult;
use App\Integration\Dtos\ProductDTO;
use App\Integration\Dtos\VariationDTO;
use App\Integration\Ecommerce\Vesti\VestiEcommerceClient;
use App\Integration\Ecommerce\Vesti\VestiPayloadMapper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
  config()->set('integration.batch_size', 2);
  config()->set('integration.ecommerce.vesti.api_url', 'api.test');
  config()->set('integration.ecommerce.vesti.company_id', 'COMP1');
  config()->set('integration.ecommerce.vesti.api_token', 'TOKEN123');

  $this->client = new VestiEcommerceClient(new VestiPayloadMapper);
});

function makeProduct(string $code): ProductDTO
{
  return new ProductDTO(
    code: $code,
    name: "Produto {$code}",
    description: null,
    price: 10.0,
    pricePromotional: null,
    composition: null,
    brand: null,
    variations: [new VariationDTO("{$code}_P_AZUL", 'P', 'AZUL', 1, 'UN', 1)],
    orderColors: [],
  );
}

it('divide os produtos em lotes do tamanho configurado', function () {
  Http::fake([
    '*' => Http::response(['result' => ['success' => true], 'statusCode' => 200], 200),
  ]);

  $result = $this->client->syncProducts([makeProduct('1'), makeProduct('2'), makeProduct('3')]);

  // 3 products, batch size 2 -> 2 requests.
  Http::assertSentCount(2);

  expect($result)->toBeInstanceOf(EcommerceSyncResult::class)
    ->and($result->success)->toBeTrue()
    ->and($result->totalProducts)->toBe(3)
    ->and($result->batchesSent)->toBe(2);
});

it('faz POST no endpoint resolvido com o header apikey e um corpo products', function () {
  Http::fake([
    '*' => Http::response(['result' => ['success' => true], 'statusCode' => 200], 200),
  ]);

  $this->client->syncProducts([makeProduct('1')]);

  Http::assertSent(function (Request $request) {
    return $request->url() === 'https://api.test/v1/products/company/COMP1'
      && $request->hasHeader('apikey', 'TOKEN123')
      && $request->method() === 'POST'
      && count($request->data()['products']) === 1
      && $request->data()['products'][0]['code'] === '1';
  });
});

it('reporta falha quando a API responde com status de erro', function () {
  Http::fake([
    '*' => Http::response(['result' => ['success' => false]], 422),
  ]);

  $result = $this->client->syncProducts([makeProduct('1')]);

  expect($result->success)->toBeFalse()
    ->and($result->batchesSent)->toBe(1);
});

it('reporta falha sem status quando a requisição lança exceção', function () {
  Http::fake(function () {
    throw new ConnectionException('connection refused');
  });

  $result = $this->client->syncProducts([makeProduct('1')]);

  expect($result->success)->toBeFalse()
    ->and($result->batchesSent)->toBe(1)
    ->and($result->batches[0]['status'])->toBeNull()
    ->and($result->batches[0]['success'])->toBeFalse();
});

it('reporta falha global quando apenas um dos lotes falha', function () {
  // batch_size = 2; 3 produtos -> lote 1 (ok), lote 2 (falha).
  $responses = [
    Http::response(['result' => ['success' => true]], 200),
    Http::response(['result' => ['success' => false]], 500),
  ];
  Http::fake(['*' => Http::sequence($responses)]);

  $result = $this->client->syncProducts([makeProduct('1'), makeProduct('2'), makeProduct('3')]);

  expect($result->success)->toBeFalse()
    ->and($result->totalProducts)->toBe(3)
    ->and($result->batchesSent)->toBe(2)
    ->and($result->batches[0]['success'])->toBeTrue()
    ->and($result->batches[1]['success'])->toBeFalse();
});

it('retorna um resultado vazio de sucesso quando não há produtos', function () {
  Http::fake();

  $result = $this->client->syncProducts([]);

  Http::assertNothingSent();

  expect($result->success)->toBeTrue()
    ->and($result->totalProducts)->toBe(0)
    ->and($result->batchesSent)->toBe(0);
});
