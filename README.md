# Unser Schulessen

A comprehensive quality management system for school meals, built with Symfony 5.4. The application supports schools across all German federal states in managing and evaluating meal quality through structured questionnaires, quality checks, and reporting features.

## Features

- **Multi-tenant architecture** - Support for all 16 German federal states with state-specific questionnaires
- **Quality check system** - Structured questionnaires for evaluating school meal quality
- **Mini-Check** - Publicly accessible quick assessment for schools
- **Dynamic flag system** - Flexible categorization of questions (sustainability, DGE guidelines, etc.)
- **PDF generation** - Automated creation of reports and evaluations
- **User management** - Role-based access control (admin, consultant, school)
- **Master data management** - Management of schools, contacts, and persons
- **Meal surveys** - Additional survey features for school meals

## Technology Stack

### Backend
- **Framework:** Symfony 5.4 (PHP 7.4+)
- **Database:** MariaDB 10.3
- **ORM:** Doctrine 2.7
- **PDF:** DomPDF
- **Email:** Sendinblue

### Frontend
- **JavaScript:** Vue.js 2.6
- **CSS:** Bootstrap 4.3 / SCSS
- **Build:** Webpack Encore
- **Charts:** ApexCharts

### Infrastructure
- **Container:** Docker & Docker Compose
- **CI/CD:** GitLab CI/CD

## Installation

### Prerequisites
- Docker & Docker Compose
- Node.js & npm
- PHP 7.4+
- Composer

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/helliwood/unser-schulessen.git
   cd unser-schulessen
   ```

2. **Configure environment variables**
   ```bash
   cp .env.dist .env
   # Edit the .env file and configure the database connection
   ```

3. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

4. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

5. **Run database migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Build frontend assets**
   ```bash
   npm run build    # Production
   npm run watch    # Development with hot reload
   ```

## Testing

### PHPUnit Tests

The tests use a separate test database provided via Docker Compose.

**Prepare test database:**
```bash
# All in one command:
composer run rebuild-testdb

# Or step by step:
php bin/console doctrine:schema:drop --full-database --env=test --force
php bin/console doctrine:migrations:migrate --env=test -n
php bin/console doctrine:fixtures:load --env=test --append
```

**Run tests:**
```bash
bin/phpunit
```

## Documentation

Additional technical documentation:

- [**Technical Documentation**](README/TECHNICAL_DOCUMENTATION.md) - Complete architecture description, modules, and API reference
- [**Architecture Overview (One-Page)**](README/ARCHITECTURE_OVERVIEW.md) - System boundaries, main components, data flows, deployment view, and federal-state separation
- [**Component Inventory**](README/COMPONENT_INVENTORY.md) - Complete component directory with purpose, interfaces, owner, dependencies, environments, and state instances
- [**Software List (Versions & Licenses)**](README/SOFTWARE_LIST.md) - OS, images, runtimes, frameworks, DB, proxy/orchestration, CI/CD, backups, and license documentation status
- [**Database ERD**](README/DATABASE_ERD.md) - Entity relationship diagrams of all database entities
- [**Flag Filtering Implementation**](README/FLAG_FILTERING_IMPLEMENTATION.md) - Details of the dynamic flag system

## Project Structure

```
├── assets/                 # Frontend assets (Vue.js, SCSS)
├── config/                 # Symfony configuration
├── migrations/             # Doctrine migrations
├── public/                 # Web root (index.php, assets)
├── src/
│   ├── Controller/         # HTTP controllers
│   ├── Entity/             # Doctrine entities
│   ├── Form/               # Symfony forms
│   ├── Repository/         # Database repositories
│   ├── Service/            # Business logic
│   └── Security/           # Authentication
├── templates/              # Twig templates
├── tests/                  # PHPUnit tests
└── translations/           # Translation files (de/en)
```
