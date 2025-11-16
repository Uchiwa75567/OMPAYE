<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwaggerURLMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Si c'est une requête pour /api/documentation, remplacer l'URL
        if ($request->path() === 'api/documentation' && $response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();
            
            // Remplacer http://localhost/api-docs.json par /api-docs.json
            $content = str_replace(
                'url: "http://localhost/api-docs.json"',
                'url: "/api-docs.json"',
                $content
            );
            
            $response->setContent($content);
        }

        return $response;
    }
}
