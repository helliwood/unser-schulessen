# Unser Schulessen

A comprehensive quality management system for school meals across all 16 German federal states.

The platform supports school-level quality workflows (master data, quality checks, surveys, food surveys, mini-check) and now also includes a **School Authority Dashboard** for school authority organizations (Schulträger).

## Features

- Multi-tenant architecture by federal state (`APP_STATE_COUNTRY`)
- Quality check workflow with scoring, exports, and follow-up actions
- Master data workflow per school year
- Public mini-check and public surveys
- Food survey workflows with visual spot-based evaluation
- School/member administration with role-based access control
- **School Authority Dashboard**:
  - school authority profile and assigned schools overview
  - authority-level surveys/templates and participation tracking
  - consolidated school insights (quality checks, surveys, food surveys)

## Technology Stack (Current)

### Backend
- **Framework:** Symfony 6.4
- **Runtime:** PHP 8.4 (Docker images)
- **Database:** MariaDB 11.8 (dev/test images; MariaDB 11 target)
- **ORM:** Doctrine ORM 2.20 / DBAL 3.10
- **PDF:** DomPDF 3.1
- **Email transport:** Symfony Mailer (`MAILER_DSN`, Brevo-compatible)

### Frontend
- **Framework:** Vue 3.5 (currently via compat mode)
- **Build:** Webpack Encore 2.x
- **Node.js:** 20.x
- **CSS/UI:** Bootstrap 4.6 + Bootstrap-Vue
- **Charts:** ApexCharts

### Infrastructure
- Docker / Docker Compose
- GitLab CI/CD with multi-state deployment jobs
- Per-state database and storage separation in production

## Installation

### Prerequisites
- Docker & Docker Compose
- Node.js 20+
- PHP 8.4+ (if running outside Docker)
- Composer

### Setup

1. **Clone repository**
   ```bash
   git clone https://github.com/helliwood/unser-schulessen.git
   cd unser-schulessen
   ```

2. **Configure environment**
   ```bash
   cp .env.dist .env
   # adjust DATABASE_URL / MAILER_DSN / APP_STATE_COUNTRY
   ```

3. **Start containers**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies**
   ```bash
   composer install
   yarn install
   ```

5. **Run migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Build frontend**
   ```bash
   yarn build
   # or: yarn watch
   ```

## Testing

```bash
composer run rebuild-testdb
bin/phpunit
```

## Documentation

- [**Technical Documentation**](README/TECHNICAL_DOCUMENTATION.md) - Current architecture, modules, deployment model, and operations
- [**Architecture Overview (One-Page)**](README/ARCHITECTURE_OVERVIEW.md) - System boundaries, components, data flows, deployment view
- [**Component Inventory**](README/COMPONENT_INVENTORY.md) - Component catalog with purpose, interfaces, dependencies, environments, ownership
- [**Software List (Versions & Licenses)**](README/SOFTWARE_LIST.md) - Current software stack, versions, and licensing
- [**User Journeys and Data Flows**](README/USER_JOURNEYS.md) - Role-based journeys and end-to-end data flows
- [**Database ERD**](README/DATABASE_ERD.md) - Core ERD plus current School Authority model extensions
- [**Flag Filtering Implementation**](README/FLAG_FILTERING_IMPLEMENTATION.md) - Dynamic flag system details

## Project Structure

```
├── assets/                 # Frontend assets (Vue, SCSS)
├── config/                 # Symfony configuration
├── migrations/             # Doctrine migrations
├── public/                 # Web root
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Form/
│   ├── Repository/
│   ├── Service/
│   └── Security/
├── templates/              # Twig templates
├── tests/                  # PHPUnit tests
└── translations/           # State/default translation files
```
