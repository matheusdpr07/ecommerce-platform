<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdminCommand extends Command
{
    protected $signature = 'admin:promote
                            {email : E-mail do usuario cadastrado}
                            {--force : Promove mesmo com e-mail nao verificado}';

    protected $description = 'Promove um usuario existente a administrador';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("Usuario com e-mail [{$email}] nao encontrado.");
            $this->line('Cadastre o usuario pela loja antes de promove-lo.');

            return self::FAILURE;
        }

        if ($user->isAdmin()) {
            $this->warn("O usuario [{$email}] ja e administrador.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && $user->email_verified_at === null) {
            $this->error('E-mail nao verificado. Verifique o e-mail ou use --force.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => UserRole::Admin])->save();

        $this->info("Usuario [{$email}] promovido a administrador com sucesso.");

        return self::SUCCESS;
    }
}
