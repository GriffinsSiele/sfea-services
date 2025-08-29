<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();


        Gate::define('update-source-account', function (User $user, $accessToUpdate) {
            return $user->AccessArea > 3;
        });

        Gate::define('update-message', function (User $user, $accessToUpdate) {
            return $user->AccessArea > 3;
        });

        Gate::define('update-access', function (User $user, $accessToUpdate) {
            // todo: ...
            return $user->AccessArea > 3;
        });

        Gate::define('update-client', function (User $user, $clientToUpdate) {
            // todo: ...
            return $user->AccessArea >= 2;
        });

        Gate::define('update-user', function (User $user, $userToUpdate) {
            // todo: ...
            return $user->AccessArea >= 2 || $userToUpdate->Id == $user->Id || ($userToUpdate->MasterUserId == $user->Id && $user->AccessArea > 0);
        });

        /*
         * AccessArea:
            0 - свои
            1 - свои и дочерних пользователей (SystemUsers.MasterUserId)
            2 - 2 уровня пользователей
            3 - все запросы
        */
        Gate::define('use-function', function (User $user, $type) {

            $aaRights = [];

            switch ($user->AccessArea) {

                case 4 :
                    $aaRights[] = 'manage_system';
                    $aaRights[] = 'access_levels_all';
                    $aaRights[] = 'clients_all';
                    $aaRights[] = 'users_all';
                    $aaRights[] = 'messages';
                    $aaRights[] = 'source_account';

                case 3 :
                    $aaRights[] = 'access_levels_own';
                    $aaRights[] = 'clients_own';
                    $aaRights[] = 'users_clients'; // all clients

                case 2 :
                    $aaRights[] = 'users_client'; // single client

                case 1 :
                    $aaRights[] = 'users_own';

                case 0 :
                    $aaRights[] = 'users_self';
            }

            if(in_array($type, $aaRights))
                return true;

            return $user->access && (isset($user->access->$type) && $user->access->$type);
        });
    }
}
