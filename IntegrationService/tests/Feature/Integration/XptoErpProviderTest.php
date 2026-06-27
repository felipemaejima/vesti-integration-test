<?php

declare(strict_types=1);

use App\Integration\Dtos\ProductDTO;
use App\Integration\Erp\Xpto\XptoErpProvider;
use App\Integration\Erp\Xpto\XptoErpResponseMapper;

beforeEach(function () {
  config()->set('integration.erp.xpto.source_path', base_path('tests/Fixtures/erp'));
  $this->provider = new XptoErpProvider(new XptoErpResponseMapper);
});

it('lê os mocks e retorna um ProductDTO por produto pai', function () {
  $products = $this->provider->getProducts();

  expect($products)->toHaveCount(2)
    ->and($products)->each->toBeInstanceOf(ProductDTO::class);
});

it('agrupa variações sob o pai pelo prefixo de code do sku', function () {
  $products = collect($this->provider->getProducts())->keyBy('code');

  expect($products['100']->variations)->toHaveCount(3)
    ->and($products['200']->variations)->toHaveCount(1);
});

it('normaliza preços e codes vindos dos mocks', function () {
  $products = collect($this->provider->getProducts())->keyBy('code');

  expect($products['100']->code)->toBe('100')
    ->and($products['100']->price)->toBe(1099.9)
    ->and($products['100']->pricePromotional)->toBe(66.0)
    ->and($products['200']->price)->toBe(50.0)
    ->and($products['200']->pricePromotional)->toBeNull();
});

it('lança exceção quando um arquivo de origem está ausente', function () {
  config()->set('integration.erp.xpto.source_path', base_path('tests/Fixtures/does-not-exist'));

  (new XptoErpProvider(new XptoErpResponseMapper))->getProducts();
})->throws(RuntimeException::class, 'não encontrado');

it('lança exceção quando o JSON de origem é inválido', function () {
  $dir = base_path('tests/Fixtures/erp-invalid');
  mkdir($dir, 0777, true);
  file_put_contents($dir . '/produtos.json', '{ invalid json');
  file_put_contents($dir . '/variacoes.json', '[]');

  config()->set('integration.erp.xpto.source_path', $dir);

  try {
    (new XptoErpProvider(new XptoErpResponseMapper))->getProducts();
  } finally {
    unlink($dir . '/produtos.json');
    unlink($dir . '/variacoes.json');
    rmdir($dir);
  }
})->throws(JsonException::class);

it('usa o sku inteiro como code quando não há separador', function () {
  $dir = base_path('tests/Fixtures/erp-nosep');
  mkdir($dir, 0777, true);
  file_put_contents($dir . '/produtos.json', json_encode([
    ['code' => 'ABC', 'name' => 'Sem separador', 'price' => '10,00'],
  ]));
  file_put_contents($dir . '/variacoes.json', json_encode([
    ['sku' => 'ABC', 'size' => 'U', 'color' => 'AZUL', 'quantity' => 1, 'unit_measurement' => 'UN', 'ordering' => 1],
  ]));

  config()->set('integration.erp.xpto.source_path', $dir);

  try {
    $products = collect((new XptoErpProvider(new XptoErpResponseMapper))->getProducts())->keyBy('code');

    expect($products['ABC']->variations)->toHaveCount(1)
      ->and($products['ABC']->variations[0]->sku)->toBe('ABC');
  } finally {
    unlink($dir . '/produtos.json');
    unlink($dir . '/variacoes.json');
    rmdir($dir);
  }
});
