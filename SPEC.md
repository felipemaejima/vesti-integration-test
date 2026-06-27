# Implementação do hub de integração ERP - Ecommerce

Este será um serviço responsável por sincronizar produtos entre o(s) serviços ERPs com a plataforma de Ecommerce (Vesti). 
Sua função será implementar essa integração seguindo os passos definidos atribuindo sub-agentes de forma isolada para cada passo.

# Instruções
 - Não faça qualquer implementação ou ajuste que não foram explicitamente solicitados;
 - Não fuja das convenções definidas na arquitetura inicial (os arquivos previamente criados);
 - A utilização do serviço deverá ser escálavel, isto é, futuramente deve funcionar via API (Inicialmente, apenas via Command);
 - Não deverá ter nenhum acoplamento de serviço, tanto dos Providers ERP, Quanto dos Clients Ecommerce;
 - Em caso de conflito, furo no planejamento, ou qualquer outra coisa que impeça de seguir o desenvolvimento, especifique o que é no arquivo RESPONSE.md na raiz do projeto (se não houver, crie) e pare a execução explicando os pontos necessários.
 - Cada execução deverá registrar logs com Logging do Laravel

 # Passos de implementação

 1. Camada Ecommerce: Comece criando a integração com o Ecommerce da Vesti. Os dados da API (request e response) estão na raiz do projeto como payload.txt e response.txt.
  - Definir as constantes necessárias para ApiUrl, CompanyId, ApiToken, BatchSize = 500 (Tamanho do lote de envio para Ecommerce)(.env/config)
  - Criar o productDTO: Crie uma estrutura genérica, pensando na reutilização em providers e clients.
  - VestiPayloadMapper: Adicione o arquivo de normalização DTO -> Payload. Inicialmente, considere apenas para payload o que estiver os campos que estiverem nos arquivos produtos.json e variacoes,json.
  - VestiEcommerceClient: Implementação do serviço de cadastro via API, recebe o Payload normalizado, faz a requisição, e retorna a resposta.

2. Camada ERP: Implemente o serviço de consumo de dados no ERP. Deve retornar ProductDTO.
  - Lê os mocks na pasta erpXpto, produtos.json e variacoes.json, normaliza seguindo ProductDTO, e retorna os dados tratados.
  - O serviço deve normalizar os campos em tipos padrão de comunicação atraves do Mapper.

3. Implemente o serviço de integração: Este deverá receber o ERP (Provider) e o Ecommerce (Cliente) de forma limpa, utilizando apenas as abstrações e retornando a resposta padronizada. 

4. Implemente SyncProducttsCommand: Apenas chamará o serviço de integração passando os nomes dos serviços. Deve receber uma string no padrão "{Provider}:{Client}" através do comando "sync" e resolver as dependencias de forma dinâmica