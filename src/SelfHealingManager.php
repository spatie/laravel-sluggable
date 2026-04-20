<?php

namespace Spatie\Sluggable;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SelfHealingManager
{
    protected ?Closure $staleUrlHandler = null;

    public function onStaleSelfHealingUrl(Closure $handler): self
    {
        $this->staleUrlHandler = $handler;

        return $this;
    }

    public function handleStaleUrl(Model $model, string $staleRouteKey, Request $request): Response
    {
        if ($this->staleUrlHandler !== null) {
            return ($this->staleUrlHandler)($model, $staleRouteKey, $request);
        }

        return $this->redirectToCanonicalUrl($model, $staleRouteKey, $request);
    }

    protected function redirectToCanonicalUrl(Model $model, string $staleRouteKey, Request $request): Response
    {
        $canonicalRouteKey = (string) $model->getRouteKey();

        $canonicalUrl = str_replace(
            [rawurlencode($staleRouteKey), $staleRouteKey],
            [rawurlencode($canonicalRouteKey), $canonicalRouteKey],
            $request->fullUrl(),
        );

        return redirect($canonicalUrl, 301);
    }
}
