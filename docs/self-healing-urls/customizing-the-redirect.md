---
title: Customizing the redirect
weight: 2
---

When an incoming URL's slug is stale, the package throws a `Spatie\Sluggable\Exceptions\StaleSelfHealingUrl` exception. Its `render()` method delegates to the `SelfHealingManager`, which by default returns a `301` redirect to the canonical URL.

## Replacing the default behavior

Register a closure through the `Sluggable` facade in a service provider's `boot()` method. The closure receives the resolved model, the stale route key, and the incoming request, and returns whatever response you want.

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Sluggable\Facades\Sluggable;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Sluggable::onStaleSelfHealingUrl(function (Model $model, string $staleRouteKey, Request $request) {
            return redirect()->route('posts.show', $model, status: 302);
        });
    }
}
```

Use cases include:

- Returning a `302` redirect instead of `301`.
- Rendering an "old link" notification before redirecting.
- Logging the stale access for analytics.
- Refusing to redirect based on request state.
