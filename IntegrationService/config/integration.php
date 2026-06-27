<?php

declare(strict_types=1);
use App\Integration\Ecommerce\Vesti\VestiEcommerceClient;
use App\Integration\Erp\Xpto\XptoErpProvider;

return [

  /*
  |--------------------------------------------------------------------------
  | Batch Size
  |--------------------------------------------------------------------------
  |
  | Number of products sent to the ecommerce platform per request.
  |
  */

  'batch_size' => (int) env('INTEGRATION_BATCH_SIZE', 500),

  /*
  |--------------------------------------------------------------------------
  | ERP Providers
  |--------------------------------------------------------------------------
  |
  | Per-provider source settings used by the ERP providers. The Xpto mock
  | files live outside the Laravel application, at the repository root, so
  | the path is resolved relative to base_path() by default.
  |
  */

  'erp' => [

    'xpto' => [
      'source_path' => env('XPTO_SOURCE_PATH', base_path('../erpXpto')),
    ],

  ],

  /*
  |--------------------------------------------------------------------------
  | Ecommerce Clients
  |--------------------------------------------------------------------------
  |
  | Per-platform connection settings used by the ecommerce clients.
  |
  */

  'ecommerce' => [

    'vesti' => [
      'api_url' => env('VESTI_API_URL', 'integracao.meuvesti.com'),
      'company_id' => env('VESTI_COMPANY_ID'),
      'api_token' => env('VESTI_API_TOKEN'),
    ],

  ],

  /*
  |--------------------------------------------------------------------------
  | Provider & Client Maps
  |--------------------------------------------------------------------------
  |
  | Map of connection names to their concrete implementation classes. The
  | factories resolve these through the container, so the integration service
  | and command stay decoupled from any concrete ERP or ecommerce class.
  |
  */

  'providers' => [
    'xpto' => XptoErpProvider::class,
  ],

  'clients' => [
    'vesti' => VestiEcommerceClient::class,
  ],

];
