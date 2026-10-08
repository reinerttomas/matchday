# 01: Send email through Resend in production

**What to build:** Production sends every email (our import emails and Fortify's password reset and email verification) through Resend instead of SMTP. Laravel 13's built-in `resend` mailer is already configured in `config/mail.php` and `config/services.php` (`RESEND_API_KEY`); only Resend's PHP SDK is missing. Use the SDK (`resend/resend-php`, approved), not the `resend/resend-laravel` package. Local development keeps sending to Herd's mail catcher over SMTP.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `resend/resend-php` is a composer dependency and `MAIL_MAILER=resend` builds the transport without errors
- [ ] `.env.example` lists an empty `RESEND_API_KEY`; its `MAIL_MAILER` stays `log`
- [ ] The production env template (`.env.dokploy`) uses `MAIL_MAILER=resend` and `RESEND_API_KEY`, drops the SMTP host/port/username/password/scheme lines, and keeps `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`
- [ ] The local `.env` is untouched
- [ ] `composer ci:check` passes
