<?php

namespace App\Providers;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Policies removed as they do not exist
    ];



    public function boot()
    {
        $this->registerPolicies();

        Gate::define('is-admin', fn(User $user) => $user->hasRole('admin'));
        Gate::define('is-client', fn(User $user) => $user->hasRole('client'));
        Gate::define('has-permission', fn(User $user, string $perm) => $user->hasPermission($perm));
        Gate::define('can-access-bank-operations', fn(User $u) => $u->hasRole('admin') || $u->hasRole('client'));
    }
}
