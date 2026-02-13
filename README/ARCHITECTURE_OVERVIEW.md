# Architecture Overview (One-Page)

This page provides a one-page architecture view of **Unser Schulessen** with:
- system boundaries,
- major components,
- main data paths,
- deployment topology,
- and federal-state separation.

## 1) System + Deployment View

```mermaid
flowchart LR
    %% External actors
    su["School users<br/>(roles: admin, consultant, school staff)"]
    pu["Public users<br/>(Mini-Check, public surveys)"]
    smtp["Email provider<br/>(Sendinblue / SMTP DSN)"]

    dns["State domain routing<br/>(&lt;state&gt;.unser-schulessen.de)"]

    su --> dns
    pu --> dns

    %% Delivery and runtime boundary
    subgraph runtime["Unser Schulessen Runtime Boundary"]
        direction LR

        subgraph stateA["State Instance A (example: BB)"]
            direction TB
            appA["Symfony 5.4 App Container<br/>(Apache + PHP, Twig + Vue)"]
            modA["Core modules<br/>Quality Check, Master Data, Surveys, Mini-Check, Auth"]
            pdfA["PDF generation<br/>(DomPDF)"]
            dbA["MariaDB schema/database<br/>state-specific"]
            fsA["State data storage<br/>/var/www/var/data"]

            appA --> modA
            modA --> dbA
            modA --> fsA
            modA --> pdfA
            modA --> smtp
        end

        subgraph stateB["State Instance B (example: RP)"]
            direction TB
            appB["Symfony 5.4 App Container<br/>(Apache + PHP, Twig + Vue)"]
            dbB["MariaDB schema/database<br/>state-specific"]
            fsB["State data storage<br/>/var/www/var/data"]

            appB --> dbB
            appB --> fsB
            appB --> smtp
        end

        more["... 14 more state instances<br/>(BE, BW, BY, HB, HE, HH, MV, NI, NW, SH, SL, SN, ST, TH)"]
    end

    dns --> appA
    dns --> appB
    dns --> more

    %% CI/CD and deployment boundary
    subgraph cicd["Build & Deployment Boundary (GitLab CI + Docker)"]
        direction TB
        ci["GitLab pipeline<br/>(build, test, deploy)"]
        img["Deployment image<br/>Dockerfile_deploy"]
        host["Docker host<br/>runs per-state containers"]

        ci --> img --> host
    end

    host -. deploy/update .-> appA
    host -. deploy/update .-> appB
```

## 2) Federal-State Separation Model

```mermaid
flowchart TB
    subgraph separation["Bundesland Separation (Tenant Isolation)"]
        direction LR
        cfg["Per instance config<br/>APP_STATE_COUNTRY=&lt;state&gt;"]
        code["State-aware logic<br/>services, templates, translations"]
        data["Dedicated DB per state<br/>DATABASE_URL -> state DB"]
        files["Dedicated mounted storage per state<br/>/datastore/&lt;state&gt;/ -> /var/www/var/data"]

        cfg --> code
        cfg --> data
        cfg --> files
    end

    note["Result: functional and data isolation per Bundesland<br/>(deployment-level isolation + state-specific runtime behavior)"]
    separation --> note
```

## 3) Main Data Paths (Condensed)

1. User request enters via state domain (`<state>.unser-schulessen.de`) and reaches the matching state container.
2. Symfony controllers/services process module logic (Quality Check, Master Data, Surveys, Mini-Check, Auth).
3. Persistent data is read/written only to that state’s MariaDB database.
4. Generated artifacts (documents, uploads, exports) are stored in that state’s mounted data directory.
5. Outbound notifications are sent via configured mail provider (`MAILER_DSN`).
6. CI/CD builds once, then deploys multiple state-specific runtime instances with separate env vars and storage/database bindings.

## 4) Source Basis

This diagram is derived from:
- `/README/TECHNICAL_DOCUMENTATION.md`
- `/README/DATABASE_ERD.md`
- `/.gitlab-ci.yml`
- `/docker-compose.yml`
- `/config/services.yaml`
- `/src/Kernel.php`
- `/.env.dist`
