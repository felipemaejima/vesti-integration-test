# Hub de Integração ERP - Vesti

Serviço que sincroniza produtos de sistemas ERP para a plataforma de e-commerce
Vesti. O hub consome os dados de um ERP (leitura), normaliza essas informações
em uma estrutura interna comum e as cadastra na Vesti através da API de produtos.

O desenho privilegia o requisito central do desafio: suportar N entradas
(diferentes ERPs, com formatos distintos) que resultam em uma mesma saída (o
payload da Vesti), sem acoplamento entre as pontas.

## Sumário

- [Visão geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Requisitos](#requisitos)
- [Instalação e execução com Docker](#instalação-e-execução-com-docker)
- [Instalação e execução local (sem Docker)](#instalação-e-execução-local-sem-docker)
- [Uso](#uso)
- [Configuração](#configuração)
- [Testes](#testes)
- [Como adicionar um novo ERP ou e-commerce](#como-adicionar-um-novo-erp-ou-e-commerce)
- [Fluxo de desenvolvimento e uso de IA](#fluxo-de-desenvolvimento-e-uso-de-ia)

## Visão geral

O fluxo de uma execução é:

```
ERP (produtos.json + variacoes.json)
        |
        v
  ErpProvider  ----->  ProductDTO[]  (estrutura interna normalizada)
        |
        v
SyncProductsIntegrationService  (orquestra, depende só de abstrações)
        |
        v
  EcommerceClient  ----->  POST /v1/products/company/{company_id}  (Vesti)
```

O ERP de exemplo ("Xpto") expõe dois conjuntos de dados:

- `erpXpto/produtos.json`: produtos pai (code, name, description, price,
  price_promotional, composition, brand).
- `erpXpto/variacoes.json`: variações (sku, size, color, quantity,
  unit_measurement, ordering).

As variações se ligam ao produto pai pelo prefixo do `sku`, no formato
`{code}_{size}_{color}`. O provider agrupa as variações por `code`, normaliza os
tipos e devolve uma coleção de `ProductDTO`. O envio para a Vesti é feito em
lotes para suportar o volume (na base de exemplo são 4.972 produtos e 23.896
variações).

## Arquitetura

O código de integração vive sob `app/Integration`, organizado por
responsabilidade. O serviço de integração e o comando dependem apenas de
interfaces; as implementações concretas são resolvidas dinamicamente por
fábricas a partir de um mapa em configuração.

```
app/
├── Console/Commands/
│   └── SyncProductsCommand.php         Gatilho CLI: sync {provider}:{client}
└── Integration/
    ├── Contracts/
    │   ├── ErpProviderInterface.php     Contrato das N entradas (ERPs)
    │   └── EcommerceClientInterface.php Contrato da saída (e-commerce)
    ├── Dtos/
    │   ├── ProductDTO.php               Estrutura interna normalizada
    │   ├── VariationDTO.php
    │   ├── OrderColorDTO.php
    │   └── EcommerceSyncResult.php       Resultado padronizado da sincronização
    ├── Erp/Xpto/
    │   ├── XptoErpProvider.php           Lê os mocks e agrupa as variações
    │   └── XptoErpResponseMapper.php     Traduz dados crus do ERP -> ProductDTO
    ├── Ecommerce/Vesti/
    │   ├── VestiEcommerceClient.php      Envia em lotes via API e retorna o resultado
    │   └── VestiPayloadMapper.php        Traduz ProductDTO -> payload da Vesti
    ├── Factories/
    │   ├── ErpProviderFactory.php        Resolve o nome do ERP -> implementação
    │   └── EcommerceClientFactory.php    Resolve o nome do e-commerce -> implementação
    └── Services/
        └── SyncProductsIntegrationService.php  Orquestra provider -> client
```

Princípios aplicados:

- Inversão de dependência: o serviço de integração conhece somente
  `ErpProviderInterface` e `EcommerceClientInterface`. Não conhece Xpto, Vesti,
  fábricas nem o container.
- Responsabilidade única: leitura (provider) é separada da tradução (mapper); o
  envio (client) é separado da montagem do payload (mapper).
- Performance: o agrupamento de variações é feito em uma única passada,
  indexando por `code`, evitando busca quadrática entre produtos e variações.

## Requisitos

Com Docker (recomendado):

- Docker e Docker Compose.

Sem Docker (execução local):

- PHP 8.3 ou superior.
- Composer 2.
- Extensões PHP: pdo_sqlite, mbstring.

## Instalação e execução com Docker

A imagem é um único container PHP CLI. Não há servidor web nem banco de dados,
porque a aplicação é executada por linha de comando e não depende de
persistência em runtime.

A partir da raiz do repositório:

```bash
# 1. Construir a imagem
docker compose build

# 2. Rodar a suíte de testes
docker compose run --rm app php artisan test

# 3. Executar a sincronização
docker compose run --rm app php artisan sync xpto:vesti

# 4. Listar os comandos disponíveis
docker compose run --rm app php artisan list
```

O entrypoint do container cria automaticamente um `.env` a partir de
`.env.example` e gera a `APP_KEY` na primeira execução.

Os dados do ERP de exemplo (`erpXpto/`) são copiados para dentro da imagem como
diretório irmão da aplicação, de modo que o caminho padrão de configuração
(`base_path('../erpXpto')`) funciona sem ajustes.

## Instalação e execução local (sem Docker)

A aplicação Laravel fica em `IntegrationService/`.

```bash
cd IntegrationService

# 1. Instalar dependências
composer install

# 2. Preparar o ambiente
cp .env.example .env
php artisan key:generate

# 3. Rodar a suíte de testes
php artisan test

# 4. Executar a sincronização
php artisan sync xpto:vesti
```

Executando localmente, o caminho padrão dos mocks (`base_path('../erpXpto')`)
aponta para a pasta `erpXpto/` na raiz do repositório, irmã de
`IntegrationService/`.

## Uso

O comando recebe a "conexão" no formato `{provider}:{client}`:

```bash
php artisan sync xpto:vesti
```

- `xpto`: nome do ERP de origem (mapeado em `config/integration.php`).
- `vesti`: nome do e-commerce de destino (mapeado em `config/integration.php`).

A saída traz um resumo da execução (sucesso, total de produtos e lotes
enviados). Cada execução também registra logs através do sistema de Logging do
Laravel.

Tratamento de erros do comando:

- Formato inválido (sem `:`) retorna mensagem de erro e código de saída 1.
- Nome de ERP ou e-commerce inexistente retorna mensagem descritiva listando os
  nomes disponíveis e código de saída 1.

## Configuração

As configurações ficam em `IntegrationService/config/integration.php` e podem
ser sobrescritas por variáveis de ambiente:

| Variável             | Padrão                     | Descrição                                  |
| -------------------- | -------------------------- | ------------------------------------------ |
| `INTEGRATION_BATCH_SIZE` | `500`                  | Tamanho do lote de produtos enviado por requisição |
| `VESTI_API_URL`      | `integracao.meuvesti.com`  | Host da API da Vesti                       |
| `VESTI_COMPANY_ID`   | (vazio)                    | Identificador da empresa na Vesti          |
| `VESTI_API_TOKEN`    | (vazio)                    | Token de autenticação (header `apikey`)    |
| `XPTO_SOURCE_PATH`   | `base_path('../erpXpto')`  | Caminho da pasta com os mocks do ERP Xpto  |

O arquivo de configuração também mantém os mapas de resolução dinâmica:

```php
'providers' => ['xpto' => XptoErpProvider::class],
'clients'   => ['vesti' => VestiEcommerceClient::class],
```

## Testes

A suíte usa Pest e cobre as peças com lógica relevante: normalização de preço
(incluindo o caso brasileiro com vírgula decimal), agrupamento de variações,
derivação de cores, montagem do payload, envio em lotes com HTTP simulado,
resolução das fábricas e o comando ponta a ponta.

```bash
# Local
php artisan test

# Docker
docker compose run --rm app php artisan test
```

Os testes que exercitam a leitura do ERP usam fixtures pequenos e
determinísticos em `tests/Fixtures/erp/`, e os testes do client usam HTTP
simulado (`Http::fake`), sem chamadas reais à API.

## Como adicionar um novo ERP ou e-commerce

O ponto central do projeto é a extensibilidade. Para suportar um novo ERP com
um formato de dados diferente:

1. Criar uma classe em `app/Integration/Erp/{Nome}/` que implemente
   `ErpProviderInterface` (retornando `ProductDTO[]`), normalmente apoiada por um
   mapper próprio.
2. Registrar uma linha no mapa `providers` de `config/integration.php`.

Nenhuma alteração é necessária no serviço de integração, no comando ou na camada
da Vesti. A mesma lógica vale para adicionar um novo e-commerce de destino,
implementando `EcommerceClientInterface` e registrando em `clients`.

## Fluxo de desenvolvimento e uso de IA

O projeto foi construído com o Claude Code (CLI) seguindo uma abordagem de
desenvolvimento orientado a especificação (Spec-Driven Development). A IA foi
usada não como um gerador de código pontual, mas como um time de agentes
coordenados, com um agente orquestrador conduzindo o trabalho e delegando cada
etapa a subagentes isolados.

### Etapa 1 - Entendimento e alinhamento

Antes de qualquer código, a IA foi usada para interpretar o desafio, mapear os
dados reais (descobrindo, por exemplo, a chave de ligação entre produtos e
variações pelo prefixo do `sku`) e alinhar o escopo: confirmar que se trata de
um serviço de integração no modelo "pull" (o hub consome o ERP), definir o
gatilho como um comando Artisan e desenhar a arquitetura modular.

### Etapa 2 - Especificação (SPEC.md)

O entendimento foi consolidado em um arquivo `SPEC.md`, revisado em várias
iterações com a IA. Cada revisão fechou lacunas que tornariam a execução
ambígua: o mecanismo de resolução dinâmica (fábrica mais mapa em configuração),
as regras de normalização (incluindo a armadilha do preço com vírgula decimal,
que um cast ingênuo trunca) e a decisão de considerar inicialmente apenas os
campos presentes nos mocks. O SPEC virou a fonte de verdade da implementação.

### Etapa 3 - Implementação por subagentes isolados

A implementação foi quebrada em quatro passos, e cada passo foi entregue a um
subagente isolado, com contexto limpo e escopo restrito. Cada subagente recebeu
a instrução de ler os arquivos de contexto (o desafio, o roadmap e o SPEC) para
ter a visão geral antes de codar:

1. Camada de e-commerce: `ProductDTO` e demais DTOs, `VestiPayloadMapper`,
   `VestiEcommerceClient` e as constantes de configuração.
2. Camada de ERP: `ErpProviderInterface`, `XptoErpResponseMapper` e
   `XptoErpProvider`, com atenção à performance do agrupamento.
3. Serviço de integração: `SyncProductsIntegrationService`, dependente apenas de
   abstrações.
4. Comando e resolução de dependências: `SyncProductsCommand`, as fábricas e os
   mapas em configuração.

O agente orquestrador validou a saída de cada subagente antes de iniciar o
próximo, garantindo as dependências entre os passos (por exemplo, o `ProductDTO`
do passo 1 sendo consumido no passo 2) e mantendo a coerência arquitetural.

### Etapa 4 - Agente dedicado à construção dos testes

Concluída a implementação, um agente adicional foi dedicado exclusivamente à
construção da suíte de testes automatizados com Pest. Esse agente analisou cada
peça com lógica de negócio e produziu testes unitários e de integração para:
a normalização de tipos no mapper do ERP, a montagem do payload da Vesti, o
agrupamento de variações no provider, o envio em lotes do client (com HTTP
simulado), a resolução das fábricas e o comando ponta a ponta. Os testes do ERP
usam fixtures determinísticos, isolando a suíte dos arquivos grandes de mock.

### Etapa 5 - Validação e correções

A validação ponta a ponta executada pelo orquestrador encontrou um defeito sutil
que os testes isolados não pegaram: a descrição do argumento do comando continha
chaves (`{provider}:{client}`), e o interpretador de assinatura de comandos do
Laravel as tratava como um segundo argumento obrigatório. O problema foi
corrigido e revalidado. Diagnósticos de qualidade apontados pelas ferramentas de
análise (chamadas de funções no namespace global, sintaxe de callables) também
foram ajustados, e o código foi formatado com o Laravel Pint.

### Resumo do uso de IA

- Interpretação do desafio e descoberta das regras a partir dos dados reais.
- Especificação iterativa em `SPEC.md` como fonte de verdade.
- Implementação delegada a subagentes isolados, um por etapa.
- Um agente dedicado à construção dos testes.
- Orquestração, validação cruzada entre etapas e correção de defeitos.
