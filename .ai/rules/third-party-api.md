---
paths:
    - 'app/Services/Ceskyflorbal/**'
    - 'app/Actions/Imports/**'
    - 'app/Jobs/**'
---

# Third-Party APIs

## Keep the status in the exception

Let a failed response throw with its status (`->throw()` raises `RequestException`, which keeps the response). Don't catch it to rethrow a plain `Exception` carrying only the body: the status is what decides whether another attempt can pass.

## Never map a provider's status to our HTTP exceptions

A 401, 403 or 404 from the provider means our URL, configuration or the provider is wrong, not that our user is unauthenticated or asked for something missing. Never turn it into `AuthenticationException`, `AuthorizationException` or a 404 of our own.

## Retry only what can pass on its own

A lost connection (`ConnectionException`), a 429 and a 5xx may pass on a later attempt; a 400, 401, 403 or 404 fails the same way however often it is sent. A queued job that calls a provider spaces its attempts with `#[Backoff]` and fails a permanent error at once with `FailOnException`, otherwise it burns the remaining tries and reports the same error on every attempt. A job that is not retried at all, such as `ImportFixtureList` with `#[Tries(1)]`, needs neither.

## Report what you swallow

A caller that must carry on past a failed call decides who needs to know. A failure that someone has to fix in our code or configuration goes to `report($e)`, because `Log::error()` keeps the exception out of Nightwatch's issues. A known degradation of the source that a later import recovers from on its own — a lost connection, one match detail page that does not download — is a `Log::warning()` with the team season's id, and the import records it as its status or reason where the administrator sees it.
