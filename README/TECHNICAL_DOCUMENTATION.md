# Technical Documentation - Unser Schulessen

## 1. Project Overview

**Unser Schulessen** is a multi-tenant school meal quality platform for all 16 German federal states.

It provides:
- role-based school workflows (master data, quality checks, quality circle)
- survey workflows (school surveys, public surveys, food surveys)
- public mini-check workflow
- administrative workflows
- **School Authority Dashboard** (Schulträger) for authority-level oversight and survey orchestration

Current baseline (March 2026):
- Symfony 6.4
- PHP 8.4 (container runtime)
- MariaDB 11.x (11.8 in local dev/test images)
- Vue 3.5 via compat mode
- Node.js 20.x

## 2. Architecture Summary

### 2.1 Runtime model

- One state-specific runtime instance per federal state in stage/prod.
- `APP_STATE_COUNTRY` controls state-aware behavior (translations, templates, rule branches).
- Each state runtime uses a dedicated DB binding and storage mount.

### 2.2 Main layers

1. **Presentation**: Twig templates + Vue components
2. **Application**: Symfony controllers/services/forms
3. **Persistence**: Doctrine ORM/DBAL with migrations
4. **Integration**: Mail transport (`MAILER_DSN`), PDF generation
5. **Operations**: Docker + GitLab CI/CD multi-job deployment

### 2.3 Security model

- Symfony Security 6.4 firewall with form login and remember-me.
- Role hierarchy in `config/packages/security.yaml`.
- Public endpoints use `PUBLIC_ACCESS`.
- Domain-specific checks implemented in controllers/services/listeners.

## 3. Core Modules

## 3.1 School-level modules

- **Master Data**: multi-step yearly data capture/finalization
- **Quality Check**: questionnaire-based evaluation and result exports
- **Quality Circle**: action and follow-up management
- **Survey**: school-managed surveys with templates and public participation
- **Food Survey**: spot-based food survey workflows
- **Mini-Check**: public lightweight quality assessment

## 3.2 School Authority Dashboard (new)

The School Authority extension introduces authority-level governance workflows.

Primary capabilities:
- authority profile management
- overview of assigned schools
- school-level KPI aggregation (quality check, survey, food survey status)
- authority survey/template management
- participation distribution to schools via `SurveySchoolParticipation`
- authority-specific result and detail views

Primary technical artifacts:
- controllers under `src/Controller/SchoolAuthority/`
- admin controllers for school authority management
- entities: `SchoolAuthority`, `SurveySchoolParticipation`
- role: `ROLE_SCHOOL_AUTHORITY`

## 4. Data Model Summary

Core domains include:
- identity and access (`User`, `UserHasSchool`, roles)
- school domain (`School`, `MasterData`, `MasterDataEntry`, contacts/media)
- quality domain (`Questionnaire`, `Result`, `Answer`, quality circle entities)
- survey domain (`Survey`, `SurveyQuestion*`, vouchers, participations)
- school authority domain (`SchoolAuthority`, authority-school links, authority-survey participation)

For relationship-level detail, see `README/DATABASE_ERD.md`.

## 5. Build and Deployment

## 5.1 Local development

- Docker images based on `php:8.4-apache`
- Node.js 20 installed in image
- MariaDB 11.8 images for local dev/test databases

## 5.2 CI/CD

Pipeline stages:
1. build image
2. install dependencies/build frontend assets
3. code/schema/tests
4. state-specific deployments

Deploy model:
- one job per federal-state instance
- per-job `APP_STATE_COUNTRY` and DB URL injection
- pre-migration DB backup (`mysqldump`) in deploy scripts

## 6. Configuration and Environment

Key environment variables:
- `APP_ENV`
- `APP_SECRET`
- `APP_STATE_COUNTRY`
- `DATABASE_URL`
- `TEST_DATABASE_URL`
- `MAILER_DSN`

Routing uses Symfony attribute routing (`config/routes/annotations.yaml` with `type: attribute`).

## 7. Frontend Notes

- Vue 3 with `@vue/compat` is currently used to keep existing Vue 2-style components operational.
- Webpack Encore 2.x bundles assets.
- Existing Bootstrap-Vue usage remains and should be treated as a migration bridge component.

## 8. Operational Notes

- State isolation is enforced by deployment topology and environment binding.
- Logs are configured by Monolog per environment.
- Backups are created during production deployment jobs.
- Documentation and software inventory were aligned to current stack in March 2026.

## 9. Source References

- `README.md`
- `README/ARCHITECTURE_OVERVIEW.md`
- `README/COMPONENT_INVENTORY.md`
- `README/SOFTWARE_LIST.md`
- `README/USER_JOURNEYS.md`
- `.gitlab-ci.yml`
- `docker-compose.yml`
- `Dockerfile`, `Dockerfile_deploy`, `Dockerfile_test`
