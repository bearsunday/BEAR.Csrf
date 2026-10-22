# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.0] - 2026-10-22

### Added

- `SameOriginModule($origin)` — the arming module itself, shipped so a context
  module rebinds `AllowedOrigin` by installing a package class instead of
  writing its own. A malformed origin throws `InvalidArgumentException` at
  construction rather than on the first `#[SameOrigin]` request. (#11)

### Changed

- Rebinding `AllowedOrigin` over `withoutSameOriginCheck()` is now a documented
  contract for context modules, pinned by a test. The README had said
  `#[SameOrigin]` runs only under `withSameOriginCheck()`, which left a
  consumer composing contexts over a neutral application module — the first
  one did, be-framework/BeMart#160 — depending on an implementation detail the
  documentation allowed to change.

## [0.1.0] - 2026-10-20

First release. CSRF defence for BEAR.Sunday, applied by attribute and bound by
AOP so that a Resource declares protection without carrying a token through its
method signature.

### Added

- `#[CsrfToken]` — synchroniser-token gate. The submitted token is read from the
  `X-CSRF-Token` header, then `ResourceObject->uri->query`, then `$_POST`, so it
  never appears as a Resource method argument. The attribute is a marker: the
  wire name has one source, `tokenField` in `CsrfModule`, so what a template
  renders and what the interceptor reads cannot differ.
- `#[SameOrigin]` — browser-origin gate over `Sec-Fetch-Site`, `Origin` and
  `Referer`, fail-closed when every signal is absent.
- `CsrfModule` — one-stop DI wiring. `withSameOriginCheck()` and
  `withoutSameOriginCheck()` choose which gates run; the constructor is private,
  so a deployment cannot end up unprotected by leaving an argument out.
- `CsrfTokenInterface` with `StoredCsrfToken`, which mints with `random_bytes()`
  and compares with `hash_equals()`, and nothing else.
- `CsrfStoreInterface` with `SessionCsrfStore` — where the token is kept, as a
  separate interface, so that a host needing somewhere else to put it does not
  have to reimplement the comparison to get there. Its session slot is injected
  as `CsrfSessionKey`, so an application sharing a session with an existing
  system can name the slot. A failed `session_start()` throws
  `SessionStartFailedException` on render and on verify alike, rather than
  surfacing as a 403 on every later submission.
- `CsrfTokenField` — the wire field name, shared by the interceptor and the
  consumer's templates. Rejects names PHP would rewrite on submission (dots,
  spaces, brackets), `_charset_` in any letter case, which browsers overwrite
  with the form encoding, and a name ending in a newline — each of which
  renders fine and never arrives under its own name.
- `CsrfHiddenInput` — the hidden field as one escaped string, named from the
  bound `CsrfTokenField`. Template helpers delegate to it so a renamed wire
  field cannot leave a form behind, and the token is issued only when the field
  is rendered.
- `HeaderRequestToken::HEADER_NAME` — the `X-CSRF-Token` header name as a public
  constant, for `<meta>` tags and JavaScript clients to reference.

### Known limitations

- `SessionCsrfStore` requires a host where a request owns its process (PHP-FPM,
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
  safety. Such a host needs `CsrfStoreInterface` implemented against a
  request-scoped backing; the package does not yet ship one
  ([#7](https://github.com/bearsunday/BEAR.Csrf/issues/7)).
- A protected Resource must not be `final`. Ray.Di weaves by subclassing and
  skips a `final` class without any error, which leaves both gates off.

[Unreleased]: https://github.com/bearsunday/BEAR.Csrf/compare/0.2.0...HEAD
[0.2.0]: https://github.com/bearsunday/BEAR.Csrf/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/bearsunday/BEAR.Csrf/releases/tag/0.1.0
