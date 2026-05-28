<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleStaticRequests
{
    protected $staticPaths = [
        // Favicons y iconos
        'favicon.ico',
        'favicon.png',
        'apple-touch-icon',
        'apple-touch-icon-precomposed',
        'apple-touch-icon-120x120',
        'apple-touch-icon-152x152',
        'apple-touch-icon-180x180',
        'mstile-150x150.png',

        // Manifest y configs
        'site.webmanifest',
        'browserconfig.xml',

        // SEO
        'robots.txt',
        'sitemap',
        'sitemap.xml',
        'sitemap_index.xml',
        'ads.txt',

        // Verificaciones
        'google',
        'BingSiteAuth.xml',

        // Humanos
        'humans.txt',
        'crossdomain.xml',

        // .well-known (completo)
        '.well-known',
    ];
    protected $patterns = [
        '/^apple-touch-icon/i',
        '/^sitemap/i',
        '/^google.*\.html$/i',
    ];
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        foreach ($this->staticPaths as $staticPath) {
            if ($path === $staticPath || str_starts_with($path, $staticPath . '/')) {
                return response()->noContent();
            }
        }
        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return response()->noContent();
            }
        }

        return $next($request);
    }
}
