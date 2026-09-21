# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-09-21

First release. CSRF defence for BEAR.Sunday, applied by attribute and bound by
AOP so that a Resource declares protection without carrying a token through its
method signature.

### Added

- `#[CsrfToken]` — synchroniser-token gate. The submitted token is read from the
  `X-CSRF-Token` header, then `ResourceObject->uri->query`, then `$_POST`, so it
  never appears as a Resource method argument.
- `#[SameOrigin]` — browser-origin gate over `Sec-Fetch-Site`, `Origin` and
  `Referer`, fail-closed when every signal is absent.
- `CsrfModule` — one-stop DI wiring. `withSameOriginCheck()` and
  `withoutSameOriginCheck()` choose which gates run; the constructor is private,
  so a deployment cannot end up unprotected by leaving an argument out.
- `CsrfTokenInterface` with `SessionCsrfToken`, whose session slot is injected
  as `CsrfSessionKey` — an application sharing a session with an existing system
  can name the slot instead of reimplementing the interface.
- `CsrfTokenField` — the wire field name, shared by the interceptor and the
  consumer's templates.

### Known limitations

- `SessionCsrfToken` requires a host where a request owns its process (PHP-FPM,
  mod_php, the built-in server, CLI). On any host serving several requests from
  one persistent worker, `$_SESSION` outlives the request and every visitor of
  that worker shares one token — CSRF protection removed rather than weakened,
  and invisible from the outside because every request still succeeds.
  Reproduced on Swoole 6.2 with `enable_coroutine` off: three cookie-less
  clients received the same token.

  The store throws `CoroutineUnsafeStoreException` when it finds itself inside a
  coroutine, which covers coroutine hosts. It cannot detect the non-coroutine
  case — no coroutine id is reported and the extension exposes no way to ask
  whether a server is running — so absence of the exception is not evidence of
  safety. Such a host needs `CsrfTokenInterface` bound to a request-scoped
  store; the package does not yet ship one
  ([#7](https://github.com/bearsunday/BEAR.Csrf/issues/7)).

[0.1.0]: https://github.com/bearsunday/BEAR.Csrf/releases/tag/0.1.0
