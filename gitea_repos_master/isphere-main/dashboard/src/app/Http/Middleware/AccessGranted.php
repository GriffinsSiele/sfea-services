<?php
 
namespace App\Http\Middleware;

use Illuminate\Support\Facades\Gate;

use Closure;
 
class AccessGranted
{
    public function handle($request, Closure $next, $type)
    {
        if (!Gate::check('use-function', $type)) {
            return redirect('/');
        }
 
        return $next($request);
    }
}