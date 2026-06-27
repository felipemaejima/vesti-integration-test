<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
  config()->set('integration.erp.xpto.source_path', base_path('tests/Fixtures/erp'));
  config()->set('integration.ecommerce.vesti.api_url', 'api.test');
  config()->set('integration.ecommerce.vesti.company_id', 'COMP1');
  config()->set('integration.ecommerce.vesti.api_token', 'TOKEN123');
});

it('executa o pipeline completo ERP -> e-commerce com sucesso', function () {
  Http::fake([
    '*' => Http::response(['result' => ['success' => true], 'statusCode' => 200], 200),
  ]);

  $this->artisan('sync xpto:vesti')
    ->expectsOutputToContain('Sincronizando produtos: xpto -> vesti')
    ->expectsOutputToContain('Sincronização concluída com sucesso.')
    ->assertExitCode(0);

  // 2 fixture products, default batch size -> a single request.
  Http::assertSentCount(1);
});

it('falha com mensagem amigável para um client desconhecido', function () {
  Http::fake();

  $this->artisan('sync xpto:unknown')
    ->expectsOutputToContain('E-commerce "unknown" desconhecido')
    ->assertExitCode(1);

  Http::assertNothingSent();
});

it('falha quando o formato da conexão é inválido', function () {
  Http::fake();

  $this->artisan('sync noseparator')
    ->expectsOutputToContain('inválida')
    ->assertExitCode(1);

  Http::assertNothingSent();
});

it('falha com mensagem amigável para um provider desconhecido', function () {
  Http::fake();

  $this->artisan('sync unknown:vesti')
    ->expectsOutputToContain('ERP "unknown" desconhecido')
    ->assertExitCode(1);

  Http::assertNothingSent();
});

it('reporta falha e retorna exit code 1 quando a sincronização falha', function () {
  Http::fake([
    '*' => Http::response(['result' => ['success' => false]], 422),
  ]);

  $this->artisan('sync xpto:vesti')
    ->expectsOutputToContain('Sincronizando produtos: xpto -> vesti')
    ->expectsOutputToContain('Sincronização concluída com falhas')
    ->assertExitCode(1);

  Http::assertSentCount(1);
});
