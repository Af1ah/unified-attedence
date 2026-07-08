<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Organisation;

class InitializeTenancyForLivewire
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('livewire/*') && $request->headers->has('referer')) {
            $referer = $request->headers->get('referer');
            $path = parse_url($referer, PHP_URL_PATH);
            
            if ($path) {
                $parts = explode('/', trim($path, '/'));
                if (count($parts) > 0) {
                    $shortname = $parts[0];
                    if ($shortname !== 'master') {
                        $tenant = Organisation::where('shortname', $shortname)->orWhere('id', $shortname)->first();
                        if ($tenant) {
                            tenancy()->initialize($tenant);
                            \Illuminate\Support\Facades\URL::defaults(['tenant' => $tenant->shortname ?? $tenant->id]);
                        }
                    }
                }
            }
        }

        return $next($request);
    }
}
