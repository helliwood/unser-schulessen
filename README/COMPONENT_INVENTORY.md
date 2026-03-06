# Component Inventory

Current component directory for **Unser Schulessen** after migration to Symfony 6.4 / PHP 8.4 and introduction of the School Authority Dashboard.

## Component Directory

| Component | Category | Purpose | Main Interfaces | Key Dependencies | Environments | Federal-State Scope | Owner | Operational Responsibility |
|---|---|---|---|---|---|---|---|---|
| State Domain Routing (`<state>.unser-schulessen.de`) | Proxy/Routing | Route requests to state-specific runtime instances. | HTTPS hostnames per state subdomain | DNS/TLS setup, host routing | Stage, Prod | `bb, be, bw, by, hb, he, hh, mv, ni, nw, rp, sh, sl, sn, st, th` | Platform/Infra | domain/TLS/routing integrity |
| Runtime App Container (`web`) | Backend Runtime | Execute web requests and business logic. | HTTP routes, Twig views, JSON endpoints | Apache, PHP 8.4, Symfony 6.4 | Dev, Test, Stage, Prod | One runtime instance per state | Application Team | runtime stability, secure config |
| Symfony Backend Core | Backend | Domain logic, auth, validation, workflows. | Controllers, services, forms, repositories | Symfony 6.4, Doctrine ORM 2.20 | Dev, Test, Stage, Prod | Shared code, isolated state data | Application Team | feature correctness, maintenance |
| School Authority Dashboard | Backend Domain | School authority workflow: school oversight, authority surveys/templates, profile/admin views. | `/schultraeger/*`, admin school authority routes | `SchoolAuthority` domain entities/repositories, survey participation model | Dev, Test, Stage, Prod | Active in state runtime, data scoped by state and authority relation | Product + Application Team | authorization and reporting correctness |
| Twig UI Layer | Frontend SSR | Render server-side pages for authenticated/public flows. | Twig templates under `/templates` | Symfony view layer, translations | Dev, Test, Stage, Prod | State-aware rendering (`APP_STATE_COUNTRY`) | Application Team | template quality and consistency |
| Vue Frontend Layer | Frontend Client | Interactive components/tables/forms/charts. | Webpack bundles in browser | Vue 3.5 (`@vue/compat`), Bootstrap-Vue, ApexCharts | Dev, Test, Stage, Prod | Shared components, state-specific data | Application Team | client behavior and regressions |
| Authentication & Authorization | Security | Login/session/password flows + role-based access control. | `/login`, `/reset`, invitation/change-password flows | Symfony Security 6.4, `User`, `UserHasSchool` | Dev, Test, Stage, Prod | Data scoped per state DB | Application Team | access policy, auth hardening |
| Master Data Module | Backend Domain | Multi-step school master data workflow per school year. | `/master_data/*` | `MasterDataService`, Doctrine | Dev, Test, Stage, Prod | State-aware forms/rules | Product + Application Team | data validity and lifecycle |
| Quality Check Module | Backend Domain | Quality check execution, scoring, finalization, exports. | `/quality_check/*` | `QualityCheckService`, Doctrine, PDF/email integrations | Dev, Test, Stage, Prod | State-aware questionnaire behavior | Product + Application Team | workflow integrity |
| Survey Module | Backend Domain | School and authority surveys, templates, public participation. | `/survey/*`, `/Umfrage/*`, authority survey routes | Doctrine, survey entities, participation model | Dev, Test, Stage, Prod | State-isolated, school/authority scoped | Product + Application Team | publication/results correctness |
| Food Survey Module | Backend Domain | Spot-based food survey creation and response workflows. | `/food-survey/*`, `/Essensumfrage/*` | Doctrine, image/document storage | Dev, Test, Stage, Prod | State-isolated | Product + Application Team | data accuracy |
| Mini-Check Public Module | Backend/Public | Public short assessment + result generation. | `/Mini-Check/*` | `MiniCheckService`, PDF + mail flow | Dev, Test, Stage, Prod | State-specific questionnaire context | Product + Application Team | public flow reliability |
| Database (Primary) | Database | Persistent storage for all domain data. | `DATABASE_URL` (MySQL protocol) | MariaDB 11 target, Doctrine migrations | Dev, Stage, Prod | Dedicated DB/schema per state | DBA/Platform | uptime, migration, backup, restore |
| Database (Test) | Database | Isolated test execution database. | `TEST_DATABASE_URL` | MariaDB 11.8 test image, fixtures | Dev, Test | No cross-state production data | Application + CI Team | test isolation |
| File Storage (`/var/www/var/data`) | Storage | Documents, uploads, generated files. | Mounted filesystem | Docker volume mounts | Stage, Prod (dev local bind) | Dedicated state mount in deploy jobs | Platform/Infra | storage, retention, restore |
| Mail Transport (`MAILER_DSN`) | External Integration | Transactional emails and notifications. | Symfony Mailer DSN | Symfony Mailer + Brevo-compatible provider | Dev, Stage, Prod (`test`: null transport) | State context in sender/workflow logic | Application + Platform | mail deliverability/configuration |
| CI/CD Pipeline | CI/CD | Build, test, and deploy all state instances. | GitLab stages/jobs | Docker, composer/yarn steps, migration steps | Test, Stage, Prod | Separate deploy jobs per state | DevOps/Release | reliable release orchestration |
| Backup Step (`mysqldump`) | Operations/Backup | Pre-deploy DB snapshots per state deployment job. | `mysqldump` in deploy scripts | DB credentials + runner storage | Prod | One backup stream per deployed state | DBA/DevOps | backup execution and retention |
| Logging (Monolog) | Monitoring/Logging | Application logs per environment. | `%kernel.logs_dir%/*.log` | Symfony Monolog bundle | Dev, Test, Prod | Per-instance log streams | Application + Ops | troubleshooting/incident support |

## Notes

- Owner labels are role-based and should be mapped to concrete teams.
- School Authority functionality introduces a second high-level governance domain next to school-level workflows.
