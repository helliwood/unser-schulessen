# Software List (Versions, Releases, Licenses)

This document provides a reproducible software inventory for **Unser Schulessen** covering:
- OS and container bases
- runtime environments
- frameworks/libraries
- database and data tooling
- proxy/routing and orchestration
- CI/CD
- backup and monitoring tooling

Scope: repository-declared software and deployment pipeline configuration.

## 1) Core Platform Software

| Layer | Software | Version / Release (as configured) | License Model | License (typical upstream) | Environments | Federal-State Scope | Evidence in Repository |
|---|---|---|---|---|---|---|---|
| Container base OS/runtime | `php:7.4-apache` image | Tag pinned to `7.4-apache` (digest not pinned) | OSS | PHP License + Apache-2.0; base OS packages are OSS (mixed licenses) | Dev, Test, Stage, Prod | Shared baseline for all state instances | `Dockerfile`, `Dockerfile_deploy`, `Dockerfile_test` |
| Container DB image | `mariadb:10.3` image | Tag pinned to `10.3` | OSS | GPL-2.0-or-later (MariaDB Server) | Dev, Test (local compose); Prod/Stage DBs managed externally by pipeline variables | Per-state DB in Stage/Prod | `docker-compose.yml` |
| Web server (inside app image) | Apache HTTP Server (from PHP Apache image) | Inherited from `php:7.4-apache` image release | OSS | Apache-2.0 | Dev, Test, Stage, Prod | One web runtime per state instance | `Dockerfile*` (`a2enmod rewrite`, vhost adjustments) |
| PHP runtime | PHP 7.4 | Runtime requirement `^7.4` and base image `php:7.4-apache` | OSS | PHP License | Dev, Test, Stage, Prod | Shared runtime baseline | `composer.json`, `Dockerfile*` |
| Node.js runtime | Node.js 12.x | Installed via NodeSource `setup_12.x` (floating within major) | OSS | MIT (Node.js project) | Dev, Test, Stage, Prod build steps | Shared build tooling | `Dockerfile*` |
| Yarn | Yarn classic (stable apt channel) | Unpinned (`stable`) | OSS | BSD-2-Clause | Dev, Test, Stage, Prod build steps | Shared build tooling | `Dockerfile*` |
| Composer | Composer (latest installer at build time) | Unpinned (installer script) | OSS | MIT | Dev, Test, Stage, Prod | Shared PHP dependency tooling | `Dockerfile*` |
| Docker engine/runtime | Docker | Version not pinned in repo (runner/host managed) | OSS / Commercial support optional | Apache-2.0 (engine) | CI build/deploy hosts, local dev | Shared deployment substrate | `.gitlab-ci.yml`, `docker-compose.yml` |
| Orchestration mode | Docker Compose + direct `docker run` in CI | Compose schema `version: "3"`; deployment via explicit `docker run` jobs | OSS | Apache-2.0 (Compose V2 plugin) | Dev (compose), Test/Stage/Prod (CI `docker run`) | Per-state deployment jobs in production | `docker-compose.yml`, `.gitlab-ci.yml` |
| CI/CD platform | GitLab CI/CD | GitLab pipeline YAML; platform version external | OSS/Commercial depends on CE/EE deployment | GitLab CE (MIT) or EE commercial terms | Test, Stage, Prod | Separate deploy jobs per state | `.gitlab-ci.yml` |
| Backup tooling | `mysqldump` | Version follows installed MariaDB/MySQL client on runner | OSS | GPL-family (MariaDB/MySQL client tooling) | Prod deploy jobs | One backup command per state deploy job | `.gitlab-ci.yml` |
| Logging/monitoring base | Monolog (Symfony bundle) | Bundle `^3.1`; app-level config by env | OSS | MIT | Dev, Test, Prod | Per-instance logs | `composer.json`, `config/packages/*/monolog.yaml` |

## 2) Application Framework and Key Libraries

| Category | Software | Version / Release (as configured) | License Model | License (typical upstream) | Evidence in Repository |
|---|---|---|---|---|---|
| Backend framework | Symfony | `5.4.*` line (core + multiple bundles) | OSS | MIT | `composer.json` (`extra.symfony.require`, package constraints) |
| ORM | Doctrine ORM | `^2.7` | OSS | MIT | `composer.json` |
| DB migrations | Doctrine Migrations | `^3.0` | OSS | MIT | `composer.json`, `/migrations` |
| DB abstraction | Doctrine DBAL | `^2` | OSS | MIT | `composer.json` |
| PDF rendering | DomPDF | `0.8.5` (pinned) | OSS | LGPL-2.1-or-later | `composer.json` |
| Mail integration | Symfony Sendinblue Mailer bridge | `5.4.*` | OSS integration + commercial SaaS possible | MIT (bridge); provider service terms for Sendinblue/Brevo account | `composer.json`, `config/packages/mailer.yaml` |
| Frontend framework | Vue.js | `^2.6.10` | OSS | MIT | `package.json` |
| CSS/UI | Bootstrap | `^4.3.1` | OSS | MIT | `package.json` |
| Vue UI integration | Bootstrap-Vue | `^2.0.1` | OSS | MIT | `package.json` |
| Charts | ApexCharts + vue-apexcharts | `^3.35.0` / `^1.4.0` | OSS | MIT | `package.json` |
| Build system | Webpack Encore | `^2.0.0` | OSS | MIT | `package.json`, `webpack.config.js` |
| Frontend utility | jQuery | `^3.6.0` | OSS | MIT | `package.json` |
| Date/time libs | Moment, Luxon | `2.29.3`, `^1.17.2` | OSS | MIT | `package.json` |
| Icons | Font Awesome Pro | `^6.3.0` | Commercial | Commercial license required | `package.json` (`@fortawesome/fontawesome-pro`) |
| QR code bundle | Endroid QR Code Bundle | `^4.1` | OSS | MIT | `composer.json` |
| Static analysis/test | PHPUnit | `9.5.26` | OSS | BSD-3-Clause | `composer.json` |

## 3) Environment and Federal-State Assignment

| Environment | Main Runtime Components | Notes |
|---|---|---|
| Dev | `web` (`php:7.4-apache`), `db` (`mariadb:10.3`), optional `testdb` | Local setup via `docker-compose`. |
| Test (CI) | `Dockerfile_test` container + test DB URL + PHPUnit/CS checks | Created in pipeline jobs (`test_phpunit`, `test_schema`, `test_code`). |
| Stage | One deployed state container (configured with `APP_STATE_COUNTRY=rp`, URL currently `bb-stage.unser-schulessen.de`) | Uses deployment image and stage DB URL variable. |
| Prod | 16 state-specific containers and DB connections | Separate job per state in `.gitlab-ci.yml`. States: `bb, be, bw, by, hb, he, hh, mv, ni, nw, rp, sh, sl, sn, st, th`. |

## 4) Reproducibility Evidence (Build and Deploy)

The software list above is reproducible from repository configuration:

1. Build definitions are explicit in:
- `Dockerfile`
- `Dockerfile_deploy`
- `Dockerfile_test`

2. Dependency manifests are explicit in:
- `composer.json`
- `package.json`

3. Deployment procedure is explicit in:
- `.gitlab-ci.yml` (`build`, `test`, `deploy` stages)

4. Local reproducibility entrypoint:
```bash
cp .env.dist .env
docker-compose up -d
composer install
npm install
npm run build
php bin/console doctrine:migrations:migrate
```

5. CI reproducibility entrypoint (as implemented):
- Build deployment image (`docker build -f Dockerfile_deploy ...`)
- Install dependencies (`composer install`, `yarn install`, `yarn encore production`)
- Run checks/tests
- Deploy per-state runtime containers with state-specific `APP_STATE_COUNTRY` + `DATABASE_URL`

## 5) OSS License Documentation Status

OSS licensing is documented at repository level and component level:
- Project metadata:
  - PHP project: `composer.json` -> `"license": "proprietary"`
  - Node project: `package.json` -> `"license": "UNLICENSED"`
- Third-party components listed above include their common upstream licenses.

To generate a fully auditable third-party license report during CI, add:
```bash
composer licenses --format=json > build/composer-licenses.json
npm ls --json > build/npm-dependency-tree.json
```

Recommended hardening for strict reproducibility:
- Pin Docker image digests (instead of tags only).
- Commit lockfiles (`composer.lock`, `yarn.lock` or `package-lock.json`).
- Store generated license artifacts as CI artifacts per release.

## 6) Compliance Notes

- `@fortawesome/fontawesome-pro` is commercial and requires a valid paid license.
- GitLab platform licensing depends on your installation (CE vs EE/SaaS tier) and is outside this repository.
- Some tool versions are intentionally floating in current Dockerfiles (Composer installer, Yarn stable, Node 12.x line); these should be pinned if deterministic rebuilds are mandatory.
