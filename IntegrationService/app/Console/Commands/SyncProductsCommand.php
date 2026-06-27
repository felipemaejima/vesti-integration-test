<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hello {nome=Mundo}')]
#[Description('Comando de exemplo que imprime uma saudação')]
class HelloWorld extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nome = $this->argument('nome');

        $this->info("Hello, {$nome}!");

        return self::SUCCESS;
    }
}
