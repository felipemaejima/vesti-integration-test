<?php

declare(strict_types=1);

namespace App\Integration\Erp\Xpto;

use App\Integration\Contracts\ErpProviderInterface;
use App\Integration\Dtos\ProductDTO;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class XptoErpProvider implements ErpProviderInterface
{
  public function __construct(
    private readonly XptoErpResponseMapper $mapper,
  ) {
  }

  /**
   * @return list<ProductDTO>
   */
  public function getProducts(): iterable
  {
    $sourcePath = $this->sourcePath();

    Log::info('Xpto ERP read started.', ['source_path' => $sourcePath]);

    $products = $this->readJson($sourcePath . '/produtos.json');
    $variations = $this->readJson($sourcePath . '/variacoes.json');

    Log::info('Xpto ERP raw data loaded.', [
      'products' => count($products),
      'variations' => count($variations),
    ]);

    $variationsByCode = $this->indexVariationsByCode($variations);
    unset($variations);

    $productDTOs = [];

    foreach ($products as $product) {
      $code = (string) $product['code'];

      $productDTOs[] = $this->mapper->toProductDTO(
        $product,
        $variationsByCode[$code] ?? [],
      );
    }

    Log::info('Xpto ERP read finished.', ['products_normalized' => count($productDTOs)]);

    return $productDTOs;
  }

  private function indexVariationsByCode(array $variations): array
  {
    $index = [];

    foreach ($variations as $variation) {
      $sku = (string) $variation['sku'];
      $separatorPosition = strpos($sku, '_');

      $code = $separatorPosition === false
        ? $sku
        : substr($sku, 0, $separatorPosition);

      $index[$code][] = $variation;
    }

    return $index;
  }

  private function readJson(string $path): array
  {
    if (!is_file($path)) {
      throw new RuntimeException("Xpto ERP source file not found: {$path}");
    }

    $contents = file_get_contents($path);

    if ($contents === false) {
      throw new RuntimeException("Unable to read Xpto ERP source file: {$path}");
    }

    $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return $decoded;
  }

  private function sourcePath(): string
  {
    $configured = config('integration.erp.xpto.source_path');

    if (is_string($configured) && $configured !== '') {
      return rtrim($configured, '/');
    }

    return base_path('../erpXpto');
  }
}
