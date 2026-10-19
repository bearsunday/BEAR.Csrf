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

class Article extends ResourceObject
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

The Resource must not be `final`. Ray.Aop applies both gates by subclassing,
and Ray.Di silently skips a `final` class, so its methods run unprotected.

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

The header name is fixed and exposed as `HeaderRequestToken::HEADER_NAME`; it is **not**
affected by `tokenField`, which only the query and `$_POST` sources use.

Avoid the query-string source for browser-facing requests: a token placed in the
URL can leak through server access logs, the `Referer` header, browser history,
and shared or bookmarked links. Prefer the `X-CSRF-Token` header or the `$_POST`
body for browser submissions, and reserve `uri->query` for server-side
BEAR.Resource requests whose URI is never exposed to a browser.

## Template example

`CsrfHiddenInput` renders the hidden field as one escaped string, with the name
taken from the bound `CsrfTokenField`. Inject it into your template helper and
call it inside every form that submits to a protected method:

```php
// Qiq: a Helpers subclass bound in place of HtmlHelpers
public function csrfField(): string
{
    return ($this->csrfHiddenInput)();
}
```

```html
<form method="post" action="/admin/article">
  {{= csrfField() }}
```

```php
// Twig: the function returns markup, so declare it safe
new TwigFunction('csrf_field', $csrfHiddenInput, ['is_safe' => ['html']]);
```

The output is `<input type="hidden" name="_csrf_token" value="…">` with both
attributes already escaped, so emit it raw; escaping it again breaks the markup.
The Resource does not need to know that this field exists.

Resolving the helper issues nothing; the token is issued, and the session
started, when the field is rendered. A rendered page carries the token, and the package
sets no cache headers of its own: do not put a resource whose body carries the
token behind `#[Cacheable]` or a CDN, and send such pages as
`Cache-Control: private, no-store`.

For JavaScript, expose the same token once in a `<meta>` tag and send it back in
the `X-CSRF-Token` header (`HeaderRequestToken::HEADER_NAME`), attaching it to same-origin
requests only. `$csrf` is the injected `CsrfTokenInterface`; the same cache rule
applies to any markup carrying the token. In plain PHP:

```php
<meta name="csrf-token" content="<?= htmlspecialchars($csrf->issue(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') ?>">
```

Qiq accepts the `<?= ?>` above as is. Twig does not: expose `$csrf->issue()`
through a function and let Twig escape it in the attribute.

## Token lifecycle

The synchroniser token is stored per session and stays stable across requests,
so it does not need to be reissued for every form.

Rotate it whenever the authentication state changes, to mitigate login CSRF and
session fixation. On login and logout, clear the token and regenerate the
session id, so the next `issue()` mints a fresh one bound to the new session.
`clear()` starts the session when none is active yet, so in this order
`session_regenerate_id()` always has a session to work on:

```php
// $csrf is an injected CsrfTokenInterface
$csrf->clear();   // drop the old token
session_regenerate_id(true);
$csrf->issue();   // mint a fresh token for the new session
```

## Supported hosts

The bundled `SessionCsrfStore` keeps the token in PHP's `$_SESSION`, which
belongs to the process. It is correct only where a request owns its process —
PHP-FPM, mod_php, the built-in server, CLI.

The session cookie belongs to the application. Set `HttpOnly`, `SameSite`,
`Secure` and `use_strict_mode` before the store is first reached, by rendering
the field or by verifying a submission: the store calls plain `session_start()`,
and PHP ignores cookie parameters set after a session is active.

**On any host that serves several requests from one persistent worker, do not
use it.** `$_SESSION` outlives the request there, so every visitor of that
worker shares one token and a forged request carries a token the server
accepts. This does not weaken CSRF protection, it removes it, and nothing is
visible from the outside because every request still succeeds. Measured on a
Swoole 6.2 HTTP server with `enable_coroutine` off: three clients with no
cookies all received the same token.

When `session_start()` fails — headers already sent, an unwritable save path, a
session backend that is down — `SessionCsrfStore` throws
`SessionStartFailedException`, whether the store is reached by rendering the
field or by verifying a submission. Silently continuing would store nothing and
turn every submission into a 403 that blames the token. In CLI, that means
nothing may be printed before the first `issue()` or `verify()`.

`SessionCsrfStore` throws `CoroutineUnsafeStoreException` when it finds itself
inside a coroutine, which covers coroutine hosts. **It cannot detect the
non-coroutine case** — a Swoole worker with `enable_coroutine` off reports no
coroutine id, and the extension exposes no way to ask whether a server is
running. Treat the guard as catching one shape of the mistake, not as
permission to deploy the bundled store on a persistent-worker host.

On such a host, implement `CsrfStoreInterface` against something scoped to the
request — coroutine context, or a shared backend keyed by the session id — and
bind it:

```php
$this->bind(CsrfStoreInterface::class)->to(MyRequestScopedStore::class);
```

Storage is a separate interface from `CsrfTokenInterface` on purpose. Minting
with `random_bytes()` and comparing with `hash_equals()` stay in
`StoredCsrfToken`, so moving the token somewhere else does not mean rewriting
the parts that have to be right. Everything else in this package is stateless
and unaffected.
