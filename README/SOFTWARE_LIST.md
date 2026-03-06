# Software List (Versions, Releases, Licenses)

Current software inventory for **Unser Schulessen**.

Scope:
- OS/container bases
- runtime environments
- frameworks/libraries
- database and tooling
- CI/CD and operations

## 1) Core Platform Software

| Layer | Software | Version / Release (as configured) | License Model | License (typical upstream) | Environments | Evidence |
|---|---|---|---|---|---|---|
| Container base runtime | `php:8.4-apache` | tag `8.4-apache` | OSS | PHP License + Apache-2.0 | Dev, Test, Stage, Prod | `Dockerfile*` |
| DB image (local/test) | `mariadb:11.8` | tag `11.8` | OSS | GPL-2.0-or-later | Dev, Test | `docker-compose.yml` |
| Web server | Apache HTTP Server (from php image) | inherited from base image | OSS | Apache-2.0 | Dev, Test, Stage, Prod | `Dockerfile*` |
| PHP runtime | PHP 8.4 (container runtime) | image-based | OSS | PHP License | Dev, Test, Stage, Prod | `Dockerfile*` |
| Node.js runtime | Node.js 20.x | NodeSource `setup_20.x` | OSS | MIT | Dev/Test build, CI build | `Dockerfile*` |
| Yarn | Yarn 1.x via corepack | project package manager `yarn@1.22.22` | OSS | BSD-2-Clause | Dev/Test/CI | `package.json`, `Dockerfile*` |
| Composer | Composer (installer in image build) | unpinned installer | OSS | MIT | Dev, Test, Stage, Prod | `Dockerfile*` |
| Docker runtime | Docker Engine | host-managed | OSS/commercial support optional | Apache-2.0 (engine) | Dev, CI, Deploy hosts | `.gitlab-ci.yml`, `docker-compose.yml` |
| CI/CD platform | GitLab CI/CD | pipeline YAML-based | OSS/commercial (platform dependent) | CE: MIT / EE: commercial | Test, Stage, Prod | `.gitlab-ci.yml` |
| Backup tool | `mysqldump` | host/client version dependent | OSS | GPL-family | Prod deploy jobs | `.gitlab-ci.yml` |
| Logging | Monolog bundle | `symfony/monolog-bundle ^3.1` | OSS | MIT | Dev, Test, Prod | `composer.json`, `config/packages/*/monolog.yaml` |

## 2) Backend Framework and Libraries

| Category | Software | Version / Release | License Model | License (typical upstream) | Evidence |
|---|---|---|---|---|---|
| Backend framework | Symfony | `6.4.*` | OSS | MIT | `composer.json` |
| ORM | Doctrine ORM | `^2.20` | OSS | MIT | `composer.json` |
| DBAL | Doctrine DBAL | `^3.10` | OSS | MIT | `composer.json` |
| Migrations | Doctrine Migrations | `^3.0` | OSS | MIT | `composer.json` |
| PDF | DomPDF | `^3.1` | OSS | LGPL-2.1-or-later | `composer.json` |
| Mail bridge | Symfony Brevo Mailer | `6.4.*` | OSS integration + commercial provider option | MIT (bridge) | `composer.json` |
| QR code | Endroid QR Code Bundle | `^4.1` | OSS | MIT | `composer.json` |
| Testing | PHPUnit | `^9.6` | OSS | BSD-3-Clause | `composer.json` |

## 3) Frontend Stack

| Category | Software | Version / Release | License Model | License (typical upstream) | Evidence |
|---|---|---|---|---|---|
| Framework | Vue | `^3.5.13` | OSS | MIT | `package.json` |
| Compat bridge | `@vue/compat` | `^3.5.13` | OSS | MIT | `package.json`, `assets/js/app.js` |
| SFC compiler | `@vue/compiler-sfc` | `^3.5.13` | OSS | MIT | `package.json` |
| UI framework | Bootstrap | `^4.6.2` | OSS | MIT | `package.json` |
| Vue UI integration | Bootstrap-Vue | `^2.23.1` | OSS | MIT | `package.json` |
| Build tooling | Webpack Encore | `^2.0.0` | OSS | MIT | `package.json`, `webpack.config.js` |
| Charts | ApexCharts / vue-apexcharts | `^3.35.0` / `^1.4.0` | OSS | MIT | `package.json` |
| Icons | Font Awesome Pro | `^6.3.0` | Commercial | Commercial license required | `package.json` |

## 4) Domain Extensions

| Extension | Components | Evidence |
|---|---|---|
| School Authority Dashboard (Schulträger) | `SchoolAuthority`, authority controllers, authority surveys, participation mapping | `src/Entity/SchoolAuthority.php`, `src/Controller/SchoolAuthority/*`, `src/Entity/Survey/SurveySchoolParticipation.php`, migrations from 2025-06 onward |

## 5) Reproducibility Status

Build/deploy are reproducible from repository sources:
- Dockerfiles define runtime/build base
- composer/package manifests define dependencies
- CI pipeline defines build/test/deploy sequence

Current hardening gaps:
- image digests are not pinned
- lockfiles are currently not committed (`composer.lock`, `yarn.lock`)

## 6) Licensing Notes

- Project license metadata:
  - `composer.json`: `proprietary`
  - `package.json`: `UNLICENSED`
- OSS/commercial classification is documented above.
- `@fortawesome/fontawesome-pro` requires valid commercial licensing.
