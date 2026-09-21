# BEAR.Csrf

CSRF protection for BEAR.Sunday.

The gates are bound by AOP onto `ResourceObject` methods, and a rejection is a
`BEAR\Resource` 4xx exception, so the framework's own error pipeline serves the
response. That coupling is deliberate, and it is why this is a `bear/` package.

`bear/csrf` keeps CSRF outside the Resource semantic contract: Resource method
parameters do not need a CSRF token, and request schemas / API documentation do
not need to expose it. Browser-facing unsafe methods are protected by Ray AOP
interceptors.

## Installation

```bash
composer require bear/csrf
```

## Module

Install `CsrfModule` in your application module. Which gates run is chosen by
name, so a deployment cannot end up unprotected by leaving an argument out:

```php
use BEAR\Csrf\CsrfModule;

$this->install(CsrfModule::withSameOriginCheck('https://example.com'));
```

For a host with no browser origin to compare against — a CLI or a
machine-to-machine API — the origin gate would reject every request rather
than protect it. Say so explicitly; the token gate stays on either way:

```php
$this->install(CsrfModule::withoutSameOriginCheck());
```

`tokenField` defaults to `_csrf_token` and `sessionKey` to `bear_csrf_token`.
Override either when an existing wire name or session layout requires it:

```php
$this->install(CsrfModule::withSameOriginCheck(
    allowedOrigin: 'https://example.com',
    tokenField: 'csrfToken',
    sessionKey: 'cms_csrf_token',
));
```

## Resource attributes

```php
use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\Attribute\SameOrigin;

final class Article extends ResourceObject
{
    #[SameOrigin]
    #[CsrfToken]
    public function onPost(string $title, string $body): static
    {
        // No csrfToken parameter: CSRF is a transport concern, not a
        // Resource semantic argument.
        return $this;
    }
}
```

`#[SameOrigin]` validates browser origin signals (`Sec-Fetch-Site`, `Origin`,
`Referer`), and runs only under `withSameOriginCheck()`.

When `Sec-Fetch-Site` is present (all modern browsers send it), the request is
judged by that browser-computed signal alone and the allowed origin is not
compared. It is consulted only for the `Origin` / `Referer` fallback used by
older or non-browser clients.

`#[CsrfToken]` validates a synchroniser token. The two gates are independent
defences: `withoutSameOriginCheck()` does not affect this one.

A missing token is submitted to `CsrfTokenInterface::verify()` as `''` rather
than rejected by the interceptor, so the bound implementation is the only
authority on what is acceptable. A test whose subject is not CSRF binds a
permissive implementation; it does not have to replace the interceptor.

## Token sources

The submitted token is read in this order:

1. `X-CSRF-Token` header
2. `ResourceObject->uri->query[$field]`
3. `$_POST[$field]`

This keeps the token out of Resource method arguments while still supporting
BEAR.Resource requests, HTML forms, and JavaScript submissions.

The header name `X-CSRF-Token` is fixed and is **not** affected by `tokenField`;
only the query and `$_POST` sources use the configured `tokenField` name.

Avoid the query-string source for browser-facing requests: a token placed in the
URL can leak through server access logs, the `Referer` header, browser history,
and shared or bookmarked links. Prefer the `X-CSRF-Token` header or the `$_POST`
body for browser submissions, and reserve `uri->query` for server-side
BEAR.Resource requests whose URI is never exposed to a browser.

## Template example

Issue a token through `CsrfTokenInterface` in your renderer or template helper
and render it as a hidden field:

```html
<input type="hidden" name="_csrf_token" value="<?= $csrfToken ?>">
```

The Resource does not need to know that this field exists.

## Token lifecycle

The synchroniser token is stored per session and stays stable across requests,
so it does not need to be reissued for every form.

Rotate it whenever the authentication state changes, to mitigate login CSRF and
session fixation. On login and logout, regenerate the session id and clear the
token so the next `issue()` mints a fresh one bound to the new session:

```php
// $csrf is an injected CsrfTokenInterface
session_regenerate_id(true);
$csrf->clear();   // drop the old token
$csrf->issue();   // mint a fresh token for the new session
```

## Supported hosts

The bundled `SessionCsrfToken` keeps the token in PHP's `$_SESSION`, which
belongs to the process. It is correct only where a request owns its process —
PHP-FPM, mod_php, the built-in server, CLI.

**On any host that serves several requests from one persistent worker, do not
use it.** `$_SESSION` outlives the request there, so every visitor of that
worker shares one token and a forged request carries a token the server
accepts. This does not weaken CSRF protection, it removes it, and nothing is
visible from the outside because every request still succeeds. Measured on a
Swoole 6.2 HTTP server with `enable_coroutine` off: three clients with no
cookies all received the same token.

`SessionCsrfToken` throws `CoroutineUnsafeStoreException` when it finds itself
inside a coroutine, which covers coroutine hosts. **It cannot detect the
non-coroutine case** — a Swoole worker with `enable_coroutine` off reports no
coroutine id, and the extension exposes no way to ask whether a server is
running. Treat the guard as catching one shape of the mistake, not as
permission to deploy the bundled store on a persistent-worker host.

On such a host, bind `CsrfTokenInterface` to a store scoped to the request —
coroutine context, or a shared backend keyed by the session id. Everything else
in this package is stateless and unaffected.
