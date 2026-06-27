<?php

declare(strict_types=1);

use App\Integration\Dtos\OrderColorDTO;
use App\Integration\Dtos\ProductDTO;
use App\Integration\Dtos\VariationDTO;
use App\Integration\Erp\Xpto\XptoErpResponseMapper;

beforeEach(function () {
  $this->mapper = new XptoErpResponseMapper;
});

describe('normalizePrice', function () {
  it('converte string decimal brasileira em float', function () {
    expect($this->mapper->normalizePrice('109,90'))->toBe(109.9);
  });

  it('remove o separador de milhar antes do cast', function () {
    expect($this->mapper->normalizePrice('1.099,90'))->toBe(1099.9);
  });

  it('não trunca na vírgula (o bug do cast ingênuo)', function () {
    expect($this->mapper->normalizePrice('109,90'))->not->toBe(109.0);
  });

  it('repassa valores numéricos diretamente', function () {
    expect($this->mapper->normalizePrice(66))->toBe(66.0)
      ->and($this->mapper->normalizePrice(12.5))->toBe(12.5);
  });
});

describe('normalizeOptionalPrice', function () {
  it('retorna null para null ou vazio', function () {
    expect($this->mapper->normalizeOptionalPrice(null))->toBeNull()
      ->and($this->mapper->normalizeOptionalPrice(''))->toBeNull();
  });

  it('normaliza um valor presente', function () {
    expect($this->mapper->normalizeOptionalPrice('50,00'))->toBe(50.0);
  });
});

describe('toVariationDTO', function () {
  it('mapeia os campos e converte size inteiro em string', function () {
    $dto = $this->mapper->toVariationDTO([
      'sku' => '200_40_PRETA',
      'size' => 40,
      'color' => 'PRETA',
      'quantity' => 3,
      'unit_measurement' => 'UN',
      'ordering' => 1,
    ]);

    expect($dto)->toBeInstanceOf(VariationDTO::class)
      ->and($dto->sku)->toBe('200_40_PRETA')
      ->and($dto->size)->toBe('40')
      ->and($dto->color)->toBe('PRETA')
      ->and($dto->quantity)->toBe(3)
      ->and($dto->unitMeasurement)->toBe('UN')
      ->and($dto->ordering)->toBe(1);
  });
});

describe('toProductDTO', function () {
  $rawProduct = [
    'code' => 100,
    'name' => 'CAMISETA',
    'description' => null,
    'price' => '1.099,90',
    'price_promotional' => 66,
    'composition' => '100% Algodão',
    'brand' => 'Marca X',
  ];

  $rawVariations = [
    ['sku' => '100_P_AZUL', 'size' => 'P', 'color' => 'AZUL', 'quantity' => 10, 'unit_measurement' => 'UN', 'ordering' => 2],
    ['sku' => '100_M_AZUL', 'size' => 'M', 'color' => 'AZUL', 'quantity' => 5, 'unit_measurement' => 'UN', 'ordering' => 1],
    ['sku' => '100_P_BRANCO', 'size' => 'P', 'color' => 'BRANCO', 'quantity' => 7, 'unit_measurement' => 'UN', 'ordering' => 3],
  ];

  it('converte o code inteiro em string e normaliza o preço', function () use ($rawProduct, $rawVariations) {
    $dto = $this->mapper->toProductDTO($rawProduct, $rawVariations);

    expect($dto)->toBeInstanceOf(ProductDTO::class)
      ->and($dto->code)->toBe('100')
      ->and($dto->price)->toBe(1099.9)
      ->and($dto->pricePromotional)->toBe(66.0);
  });

  it('cria um VariationDTO por variação bruta', function () use ($rawProduct, $rawVariations) {
    $dto = $this->mapper->toProductDTO($rawProduct, $rawVariations);

    expect($dto->variations)->toHaveCount(3)
      ->and($dto->variations)->each->toBeInstanceOf(VariationDTO::class);
  });

  it('deriva order colors distintas usando o menor ordering, ordenadas', function () use ($rawProduct, $rawVariations) {
    $dto = $this->mapper->toProductDTO($rawProduct, $rawVariations);

    expect($dto->orderColors)->toHaveCount(2)
      ->and($dto->orderColors)->each->toBeInstanceOf(OrderColorDTO::class);

    // AZUL appears with ordering 2 and 1 -> smallest is 1; BRANCO -> 3.
    // Result is sorted by ordering: AZUL(1), BRANCO(3).
    expect($dto->orderColors[0]->color)->toBe('AZUL')
      ->and($dto->orderColors[0]->order)->toBe(1)
      ->and($dto->orderColors[1]->color)->toBe('BRANCO')
      ->and($dto->orderColors[1]->order)->toBe(3);
  });

  it('lida com um produto sem variações', function () use ($rawProduct) {
    $dto = $this->mapper->toProductDTO($rawProduct, []);

    expect($dto->variations)->toBe([])
      ->and($dto->orderColors)->toBe([]);
  });
});
