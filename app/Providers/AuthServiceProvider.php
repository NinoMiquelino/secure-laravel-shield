<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate para verificar se usuário pode acessar logs de segurança
        Gate::define('view-security-logs', function ($user) {
            // Em produção, implementar lógica de permissões
            return true; // Temporariamente permitido para todos
        });

        // Gate para verificar se a atividade é suspeita
        Gate::define('perform-secure-action', function ($user, $action) {
            return $this->isActionAllowed($user, $action);
        });
    }

    /**
     * Check if action is allowed for user based on security rules
     */
    private function isActionAllowed($user, $action)
    {
        $suspiciousActions = ['login_from_new_device', 'multiple_failed_attempts'];
        
        return !in_array($action, $suspiciousActions);
    }
}