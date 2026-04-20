---
title: Scoped uniqueness (trait only)
weight: 5
---

When slugs should be unique within a group of rows, such as per tenant or per locale, add an `extraScope` closure. The closure receives the uniqueness query and can constrain it however you want.

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('slug')
        ->extraScope(fn ($query) => $query->where('tenant_id', $this->tenant_id));
}
```

With this configuration, two records with different `tenant_id` values can share the same slug without triggering a uniqueness suffix.

Scopes involve closures, which cannot live in attribute arguments. Use the trait when you need scoped uniqueness.
