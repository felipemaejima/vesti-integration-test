# Integração ERP - Ecommerce - Anotações Iniciais

# ERP
    Listar produtos pai (erp/produtos.json)
    Listar variações de produtos pai (erp/variacoes.json)

# Ecommerce 
 Cadastrar produtos (API Real Vesti - Cadastrar Produtos -> https://integracao.meuvesti.com/doc/api/index.html#api-Produtos-post_products)

# Intergração
 - Serviço que consome API do ERP e insere no Ecommerce

# Definições técnicas iniciais

 - Solid 
 - Arquitetura Modular
 - Specs
 - Serviço de consumo de ERP depender de abstração - ERPService implementa ERPInterface

# Stack base
 - PHP 8.5
 - laravel 13
 - docker (Laravel Sail)

# Ferramentas de apoio
 - Claude CLI (SDD)
  
# Informações Adicionais
 - Descrição de instalação e execução no arquivo README.md do projeto;
 - Descrição de como utilizou a IA a seu favor;
