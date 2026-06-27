<?php

declare(strict_types=1);

use App\Integration\Dtos\OrderColorDTO;
use App\Integration\Dtos\ProductDTO;
use App\Integration\Dtos\VariationDTO;
use App\Integration\Ecommerce\Vesti\VestiPayloadMapper;

beforeEach(function () {
  $this->mapper = new VestiPayloadMapper;
});

function sampleProduct(): ProductDTO
{
  return new ProductDTO(
    code: '100',
    name: 'CAMISETA',
    description: 'Algodão',
    price: 109.9,
    pricePromotional: 66.0,
    composition: '100% Algodão',
    brand: 'Marca X',
    variations: [
      new VariationDTO('100_P_AZUL', 'P', 'AZUL', 10, 'UN', 1),
    ],
    orderColors: [
      new OrderColorDTO('AZUL', 1),
    ],
  );
}

it('mapeia um ProductDTO para o payload Vesti usando ambos os campos de code', function () {
  $payload = $this->mapper->toPayload(sampleProduct());

  expect($payload['integration_id'])->toBe('100')
    ->and($payload['code'])->toBe('100')
    ->and($payload['name'])->toBe('CAMISETA')
    ->and($payload['description'])->toBe('Algodão')
    ->and($payload['composition'])->toBe('100% Algodão')
    ->and($payload['brand'])->toBe('Marca X')
    ->and($payload['price'])->toBe(109.9)
    ->and($payload['price_promotional'])->toBe(66.0);
});

it('mapeia variações com order a partir de ordering e unit_type a partir de unit_measurement', function () {
  $payload = $this->mapper->toPayload(sampleProduct());

  expect($payload['variations'])->toHaveCount(1);

  $variation = $payload['variations'][0];

  expect($variation)->toBe([
    'sku' => '100_P_AZUL',
    'size' => 'P',
    'color' => 'AZUL',
    'quantity' => 10,
    'order' => 1,
    'unit_type' => 'UN',
  ]);
});

it('mapeia as order colors', function () {
  $payload = $this->mapper->toPayload(sampleProduct());

  expect($payload['order_colors'])->toBe([
    ['color' => 'AZUL', 'order' => 1],
  ]);
});

it('não inclui campos ausentes nos mocks', function () {
  $payload = $this->mapper->toPayload(sampleProduct());

  expect($payload)->not->toHaveKeys(['categories', 'weight', 'height', 'width', 'length', 'barcode', 'active', 'release_at']);
});

it('agrupa múltiplos produtos sob a chave products', function () {
  $body = $this->mapper->toRequestBody([sampleProduct(), sampleProduct()]);

  expect($body)->toHaveKey('products')
    ->and($body['products'])->toHaveCount(2)
    ->and($body['products'][0]['code'])->toBe('100');
});
