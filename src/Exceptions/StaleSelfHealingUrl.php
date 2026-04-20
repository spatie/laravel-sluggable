<?php

namespace Spatie\Sluggable\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Sluggable\SelfHealingManager;
use Symfony\Component\HttpFoundation\Response;

class StaleSelfHealingUrl extends Exception
{
    public function __construct(
        public readonly Model $model,
        public readonly string $staleRouteKey,
    ) {
        parent::__construct("The self-healing URL key `{$staleRouteKey}` is stale. Canonical is `{$model->getRouteKey()}`.");
    }

    public function render(Request $request): Response
    {
        return app(SelfHealingManager::class)->handleStaleUrl($this->model, $this->staleRouteKey, $request);
    }
}
