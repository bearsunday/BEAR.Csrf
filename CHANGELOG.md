# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `CsrfHiddenInput` — the hidden field as one escaped string, named from the
  bound `CsrfTokenField`. Template helpers can delegate to it so a renamed wire
  field cannot leave a form behind, and the token is issued only when the field
  is rendered.
- `HeaderRequestToken::HEADER_NAME` — the `X-CSRF-Token` header name as a public
  constant, for `<meta>` tags and JavaScript clients to reference.
- `SessionStartFailedException`, thrown by `SessionCsrfStore` when
  `session_start()` fails, on render and on verify alike. Before, the failure
  surfaced as a 403 on every later submission.

### Changed

- `CsrfTokenField` rejects `_charset_` in any letter case, which browsers
  overwrite with the form encoding, and a name ending in a newline.
- `CsrfTokenField` rejects names PHP would rewrite on submission (dots, spaces,
  brackets). Such a field rendered fine but never arrived under
  its own name.
- The README template example renders through `CsrfHiddenInput`. The previous
  example wrote the token out unescaped under a hard-coded field name.
- The README Resource example is no longer `final`: Ray.Di skips a `final`
  class when weaving, which leaves both gates off without any error.

### Removed

- `#[CsrfToken]` no longer takes a field name. The wire name has one source,
  `tokenField` in `CsrfModule`, so what a template renders and what the
  interceptor reads cannot differ. The attribute is a marker. **Breaking:** an
  argument left on an existing `#[CsrfToken('…')]` is accepted by PHP and
  ignored, so that method reads `tokenField` and rejects the old name with a 403.

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
- `CsrfTokenInterface` with `StoredCsrfToken`, which mints with `random_bytes()`
  and compares with `hash_equals()`, and nothing else.
- `CsrfStoreInterface` with `SessionCsrfStore` — where the token is kept, as a
  separate interface, so that a host needing somewhere else to put it does not
  have to reimplement the comparison to get there. Its session slot is injected
  as `CsrfSessionKey`, so an application sharing a session with an existing
  system can name the slot.
- `CsrfTokenField` — the wire field name, shared by the interceptor and the
  consumer's templates.

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

[Unreleased]: https://github.com/bearsunday/BEAR.Csrf/compare/0.1.0...HEAD
[0.1.0]: https://github.com/bearsunday/BEAR.Csrf/releases/tag/0.1.0
