---
title: Site Resolution
---

# Site Resolution

Several ArtisanPack UI packages scope their records per site — analytics keeps
one set of statistics per site, bookings keeps one calendar per site, and so on.
Before Core 1.3 each package answered "which site is this request for?" for
itself, in a shape of its own, from configuration of its own. An application
installing two of them configured tenancy twice, in shapes that could not share
a source of truth, so a single request could resolve to site 2 for one package
while resolving to site 1 for another — silently.

Core 1.3 lifts that question into one contract, one resolver binding, and one
configuration block. Everything else is an implementation detail behind it.

## The contract

```php
namespace ArtisanPackUI\Core\Contracts;

interface SiteResolver
{
	public function currentSiteId(): int|string|null;
}
```

Two things about that signature are deliberate:

- **It is keyed on the identifier, not a model.** Core cannot depend on any
  package's `Site` model, and packages must not depend on each other's. The
  identifier is the only thing all of them can share; a package that needs the
  full record looks it up.
- **There is no `Request` parameter.** A resolver that requires a request is
  unusable from a console command or queue worker, which is exactly where
  per-site iteration happens.

`null` means "no site in context", not "site not found". Consumers read it as
"do not scope", which is what lets a single-tenant application install these
packages and never configure anything.

## Asking which site you are on

Consumers do not talk to a resolver directly — they ask the shared
`SiteContext`, which is the one place a site becomes known:

```php
use ArtisanPackUI\Core\Facades\ArtisanPackSite;
use ArtisanPackUI\Core\MultiTenancy\SiteContext;

// Facade
$siteId = ArtisanPackSite::currentSiteId();

// Helper
$siteId = apCurrentSiteId();

// Injection — preferred inside a service
public function __construct( protected SiteContext $siteContext ) {}
```

Going through the context rather than the resolver is what keeps packages in
agreement: one context is shared for the whole request or job, it applies the
`enabled` flag, and it honours any site pinned by the surrounding code.

Nothing is memoised. The resolver is asked on every call, because the site in
context can change within a single process.

## Pinning a site

A console command or queue worker iterating over sites pins each one for the
duration of its work. The previous context is restored afterwards, including
when the callback throws, and calls nest safely:

```php
foreach ( $siteIds as $siteId ) {
	ArtisanPackSite::forSite( $siteId, function () {
		// Every package scopes to $siteId in here.
	} );
}

// Administrative work that deliberately spans every site.
ArtisanPackSite::withoutSite( fn () => Booking::query()->count() );
```

A pinned site wins over the resolver, and it wins even when site scoping is
switched off in configuration: the `enabled` flag governs *automatic*
resolution, whereas a `forSite()` call is an unambiguous instruction from the
calling code.

`ArtisanPackSite::setSiteId()` pins without a scope, and
`ArtisanPackSite::forget()` releases the pin. Prefer `forSite()` — a pin left
behind by an early return or a swallowed exception is a cross-site read waiting
to happen, and `forSite()` cannot leave one.

### Telling "no site" apart from "no pin"

`currentSiteId()` answers `null` in two different situations: nothing is pinned
and the resolver found nothing, or something pinned "no site" on purpose —
`withoutSite()`, `setSiteId( null )`, or `forSite( null, … )`, which all set the
same pinned state. Most consumers can treat both situations the same way and
leave their queries unscoped.

A package that falls back to a default site of its own cannot. `null` meaning
"nothing in context" is its cue to apply that default, while `null` meaning "no
site, deliberately" is an instruction from another package to stay unscoped —
and applying the default there scopes work that was meant to span every site.
`isPinned()` (since 1.3.1) separates them:

```php
if ( ArtisanPackSite::currentSiteId() === null && ! ArtisanPackSite::isPinned() ) {
    // Nothing in context and nobody asked for that, so a default is safe here.
}
```

`SiteContext` and the resolver are bound with `scoped()`, not `singleton()`, so
Laravel forgets them between Octane requests and between queue jobs. A pin that
outlives its work therefore dies at the request or job boundary rather than
scoping everything the worker does next. `flush()` is still available for
resetting deliberately mid-process.

## Configuration

One block, in `config/artisanpack.php`, is authoritative for the whole
ecosystem:

```php
'core' => [
	'multi_tenant' => [
		'enabled'   => env( 'ARTISANPACK_MULTI_TENANT_ENABLED', false ),
		'resolvers' => [
			ArtisanPackUI\Core\MultiTenancy\HookSiteResolver::class,
		],
	],
],
```

`enabled` defaults to false, so an application that installs these packages and
configures nothing behaves as a single-tenant application: no site is ever put
in context and every package leaves its queries alone.

These defaults are merged in by the service provider, so they apply whether or
not the application has run `vendor:publish --tag=artisanpack-config`. Setting
`ARTISANPACK_MULTI_TENANT_ENABLED=true` works in an unpublished application —
the alternative would let an operator switch tenancy on, see nothing happen, and
run a multi-site install with every query unscoped.

`resolvers` lists the strategies in order; the first to return an identifier
wins. The list is assembled into a `ChainSiteResolver` and bound as the single
shared `SiteResolver`. An empty list binds `NullSiteResolver` instead, so the
contract is always resolvable and consumers can type-hint it unconditionally.

A configured class that is missing or does not implement `SiteResolver` raises
`SiteResolutionException` at bind time rather than being dropped — a silently
dropped resolver produces an application that scopes nothing and looks like it
is working. The same applies to a `resolvers` value that is not a list at all
(`'resolvers' => HookSiteResolver::class`, the brackets forgotten): that is a
configuration fault, not an opt-out. Only an explicit `[]` switches resolution
off.

## Shipped resolvers

| Resolver | Behaviour |
| --- | --- |
| `HookSiteResolver` | Applies the `ap.cmsFramework.currentSite.resolve` filter. The default. |
| `NullSiteResolver` | Never puts a site in context. Bind this to switch tenancy off outright. |
| `ChainSiteResolver` | Asks each resolver in turn and takes the first non-null answer. |

### `HookSiteResolver` and the CMS framework

`HookSiteResolver` applies the `ap.cmsFramework.currentSite.resolve` filter with
a null default. The coupling flows one way: packages that scope by site listen,
and `artisanpack-ui/cms-framework` never needs to know they exist. Where nothing
answers the filter, the resolver returns null and the application behaves as
single-tenant.

Nothing *listening* is ordinary. `artisanpack-ui/hooks` not being *installed* is
different — there is then no filter to answer at all, so this resolver raises
`SiteResolutionException` rather than returning null and unscoping every query in
a deployment whose configuration says it is multi-site. That branch is
unreachable while `enabled` is false, since no resolver is consulted then, which
is what keeps `HookSiteResolver` safe as the shipped default for applications
that have neither package.

A listener may answer with an integer, or with a string identifier — a numeric
string is coerced to an integer, and any other string is trimmed and passed
through for applications keyed on UUIDs. Anything else, including a string that
is empty once trimmed, raises
`SiteResolutionException`. Coercing an unusable answer to null would silently
unscope every query the resolution feeds, which leaks one site's records into
another; failing loudly is the safer direction.

One asymmetry to know about: `"12"` comes back as `int(12)`, but `"012"` stays
`string("012")`, because `FILTER_VALIDATE_INT` rejects leading zeros. A listener
that zero-pads hands back a different PHP type from one that does not, which
matters to any consumer using the identifier as an array key, in a strict `===`
comparison, or in a cache key. Pick one format and keep to it.

## Writing your own resolver

Resolve from a subdomain, a header, a session, or anywhere else:

```php
namespace App\MultiTenancy;

use App\Models\Site;
use ArtisanPackUI\Core\Contracts\SiteResolver;
use Illuminate\Http\Request;

class SubdomainSiteResolver implements SiteResolver
{
	public function __construct( protected Request $request ) {}

	public function currentSiteId(): int|string|null
	{
		// Console commands and queue workers have no real request. Returning
		// null hands the decision to the next resolver, or to an explicitly
		// pinned site, rather than matching every one of them to whatever
		// domain the CLI happens to report.
		if ( ! app()->runningInConsole() ) {
			return Site::query()->where( 'domain', $this->request->getHost() )->value( 'id' );
		}

		return null;
	}
}
```

Register it ahead of the default:

```php
'resolvers' => [
	App\MultiTenancy\SubdomainSiteResolver::class,
	ArtisanPackUI\Core\MultiTenancy\HookSiteResolver::class,
],
```

Two rules for implementations:

- **Do not memoise.** Re-resolve on every call, or a worker looping over sites
  scopes its later iterations to the first site it saw.
- **Do not swallow failures.** Returning null on error unscopes every query the
  resolution feeds. Let the exception escape.
