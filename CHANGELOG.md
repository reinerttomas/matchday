# Changelog

## [0.4.0](https://github.com/reinerttomas/matchday/compare/v0.3.0...v0.4.0) (2026-10-09)


### Features

* make calendar subscription work from chat apps ([#5](https://github.com/reinerttomas/matchday/issues/5)) ([663c842](https://github.com/reinerttomas/matchday/commit/663c8428419a3525afcdc84c30c7320a75bb4cc7))
* show fixture revisions in an expandable row ([d577e28](https://github.com/reinerttomas/matchday/commit/d577e28bff6116d3bd587b461cf18ed16c6af313))

## [0.3.0](https://github.com/reinerttomas/matchday/compare/v0.2.0...v0.3.0) (2026-10-09)


### Features

* send emails through resend ([5079cc9](https://github.com/reinerttomas/matchday/commit/5079cc9551dce36a6459a03dc624ea982ee281d4))

## [0.2.0](https://github.com/reinerttomas/matchday/compare/v0.1.0...v0.2.0) (2026-10-08)


### Features

* replace the welcome page with a redirect to login or the fixture list ([3144f60](https://github.com/reinerttomas/matchday/commit/3144f605107646d7f77a11641b86dd6c5657881b))

## 0.1.0 (2026-10-08)


### Features

* add admin shell and imports page ([296a687](https://github.com/reinerttomas/matchday/commit/296a6876a4cdcee21e01c44a4e618e0e9fda1cac))
* add changes page with unsent change summary count ([b14e8c7](https://github.com/reinerttomas/matchday/commit/b14e8c7d4df8e7ee7d35b9b61652798b2a3af4b9))
* add demo seeder ([beb2136](https://github.com/reinerttomas/matchday/commit/beb2136b2f2fcf3112913525ef28723da8340439))
* add fixture list page ([d4d119f](https://github.com/reinerttomas/matchday/commit/d4d119f503a296d05e9bd550421649f523974e40))
* add imports and revisions schema ([a4c8b71](https://github.com/reinerttomas/matchday/commit/a4c8b71b2586d887fc2b1c180efcb5e46eb114be))
* add laravel nightwatch monitoring ([2fddc87](https://github.com/reinerttomas/matchday/commit/2fddc87d7f0f1ed900871090dbb063170247e3ae))
* add manual import with polling to admin pages ([55d3dc5](https://github.com/reinerttomas/matchday/commit/55d3dc563abb9aa287cd3f57939a3038c10417b8))
* add production docker image with octane ([ef4559a](https://github.com/reinerttomas/matchday/commit/ef4559a4f5e45d8e8fd13a305a0cd3f299ff43dd))
* add public team page ([9b7b8d0](https://github.com/reinerttomas/matchday/commit/9b7b8d070c59ae79af671d24c5a9dd07baccb1d9))
* add seasons page ([1efbf37](https://github.com/reinerttomas/matchday/commit/1efbf378b52771e4e00dd6b3e0ca8e1a10bacc6d))
* add seasons, teams and team seasons schema ([212dc63](https://github.com/reinerttomas/matchday/commit/212dc637c8dfeffeda25eed164c72140f016cb94))
* add teams page with auto import toggle ([0dadcc0](https://github.com/reinerttomas/matchday/commit/0dadcc043cdc0358987dd9f0d8d86b8f557bcf02))
* add teams to a season ([fc62bb4](https://github.com/reinerttomas/matchday/commit/fc62bb4ba189ea64803215182616196f4fdcc503))
* add venues and fixtures schema ([26769a3](https://github.com/reinerttomas/matchday/commit/26769a39435890241fdcc68f60733e3b9360a830))
* add venues page ([f5e85cd](https://github.com/reinerttomas/matchday/commit/f5e85cd11ee9278546d029da109c8620379a0c5c))
* **auth:** add google login and disable public registration ([71b5ce0](https://github.com/reinerttomas/matchday/commit/71b5ce0f99f32b9d0deac244648a1e785fadd7e0))
* cancel fixtures missing from consecutive imports ([8979321](https://github.com/reinerttomas/matchday/commit/897932100f9065c65baeb569f8fe60987806d987))
* **console:** add user:create command ([c018f8b](https://github.com/reinerttomas/matchday/commit/c018f8bb08b10a20dec2c1e3739e794e9a920d7b))
* email a change summary after an import with revisions ([8b36d13](https://github.com/reinerttomas/matchday/commit/8b36d13de312ed9eb6abd43decc26f831f885508))
* fill in the venue of finished fixtures ([8b589c2](https://github.com/reinerttomas/matchday/commit/8b589c234cceda69cfc815df920b051caaac8533))
* handle failed and aborted imports ([df59d84](https://github.com/reinerttomas/matchday/commit/df59d843f51ad03a08e68ca0ad03daa44a80114d))
* import a fixture list from ceskyflorbal.cz ([a000ba4](https://github.com/reinerttomas/matchday/commit/a000ba4c34e3433c29d769cefb2225f6b52ca9dd))
* import fixture lists on a schedule and on demand ([5ce8ac4](https://github.com/reinerttomas/matchday/commit/5ce8ac423da038824012a8e102cf6454079bc424))
* list changes in the fixture list's row layout ([7fb6c4c](https://github.com/reinerttomas/matchday/commit/7fb6c4c241aeafe803a50fc6aa8781594a268579))
* list fixtures in the federation's row layout ([d0fb8c0](https://github.com/reinerttomas/matchday/commit/d0fb8c0a212a731c7778b8aa6eebad666819900f))
* list imports in the fixture list's row layout ([71acae2](https://github.com/reinerttomas/matchday/commit/71acae2afa96a43220ae72e53dafd4914b43c281))
* move czech texts into translation files ([0298e51](https://github.com/reinerttomas/matchday/commit/0298e51da6d51c33082b831ab146fd8de449a5ab))
* name teams and let the administrator rename them ([be7c9ee](https://github.com/reinerttomas/matchday/commit/be7c9eecc65bd9d0c06a1a711fc2f37d44d05f80))
* record revisions on re-import ([90a7a46](https://github.com/reinerttomas/matchday/commit/90a7a462b37aa1a2efdf288e7242c2f0459a1acf))
* redesign the public team page with match day cards ([3a3a471](https://github.com/reinerttomas/matchday/commit/3a3a471f22fd1af5901991467f32455b484ca0e5))
* resolve venues from match detail pages ([bc17282](https://github.com/reinerttomas/matchday/commit/bc1728276fa5f8400785118f2c169234f403ca2e))
* send change summaries to whatsapp from the changes page ([39c3697](https://github.com/reinerttomas/matchday/commit/39c369716227af76116fa0e8da01016bc38d0e2b))
* serve team calendar as ics feed ([376fbc8](https://github.com/reinerttomas/matchday/commit/376fbc83935bf15ed980c6f6d20ba300f6c13cf9))
* show fixture variants in calendar feed ([cfd2c30](https://github.com/reinerttomas/matchday/commit/cfd2c305a34f80caeaae910b95b99d0c06e5c1f9))
* subscribe to the calendar from the team page ([e70a77d](https://github.com/reinerttomas/matchday/commit/e70a77d46af3680a0946c3e9d3b327e27e1bf7d7))


### Bug Fixes

* list tbd fixtures last on team page ([219b5c2](https://github.com/reinerttomas/matchday/commit/219b5c2a585eb03f7682c03f40f132c45d230303))
