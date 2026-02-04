# Unser Schulessen

Ein umfassendes Qualitätsmanagement-System für Schulverpflegung, entwickelt mit Symfony 5.4. Die Anwendung unterstützt Schulen in allen deutschen Bundesländern bei der Verwaltung und Bewertung ihrer Essensqualität durch strukturierte Fragebögen, Qualitätschecks und Berichtsfunktionen.

## Features

- **Multi-Mandanten-Architektur** - Unterstützung aller 16 deutschen Bundesländer mit landesspezifischen Fragebögen
- **Qualitäts-Check-System** - Strukturierte Fragebögen zur Bewertung der Schulverpflegung
- **Mini-Check** - Öffentlich zugängliche Schnellbewertung für Schulen
- **Dynamisches Flag-System** - Flexible Kategorisierung von Fragen (Nachhaltigkeit, DGE-Richtlinien, etc.)
- **PDF-Generierung** - Automatische Erstellung von Berichten und Auswertungen
- **Benutzerverwaltung** - Rollenbasierte Zugriffskontrolle (Admin, Berater, Schule)
- **Stammdaten-Management** - Verwaltung von Schulen, Kontakten und Personen
- **Speiseumfragen** - Zusätzliche Umfragefunktionen für Schulessen

## Technologie-Stack

### Backend
- **Framework:** Symfony 5.4 (PHP 7.4+)
- **Datenbank:** MariaDB 10.3
- **ORM:** Doctrine 2.7
- **PDF:** DomPDF
- **E-Mail:** Sendinblue

### Frontend
- **JavaScript:** Vue.js 2.6
- **CSS:** Bootstrap 4.3 / SCSS
- **Build:** Webpack Encore
- **Charts:** ApexCharts

### Infrastruktur
- **Container:** Docker & Docker Compose
- **CI/CD:** GitLab CI/CD

## Installation

### Voraussetzungen
- Docker & Docker Compose
- Node.js & npm
- PHP 7.4+
- Composer

### Setup

1. **Repository klonen**
   ```bash
   git clone git@git.helliwood.de:helliwood/unser-schulessen.git
   cd unser-schulessen
   ```

2. **Umgebungsvariablen konfigurieren**
   ```bash
   cp .env.dist .env
   # .env Datei bearbeiten und Datenbankverbindung konfigurieren
   ```

3. **Docker-Container starten**
   ```bash
   docker-compose up -d
   ```

4. **Dependencies installieren**
   ```bash
   composer install
   npm install
   ```

5. **Datenbank migrieren**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Frontend bauen**
   ```bash
   npm run build    # Production
   npm run watch    # Development mit Hot-Reload
   ```

## Testing

### PHPUnit Tests

Die Tests verwenden eine separate Test-Datenbank, die über Docker-Compose bereitgestellt wird.

**Test-Datenbank vorbereiten:**
```bash
# Alles in einem Befehl:
composer run rebuild-testdb

# Oder einzeln:
php bin/console doctrine:schema:drop --full-database --env=test --force
php bin/console doctrine:migrations:migrate --env=test -n
php bin/console doctrine:fixtures:load --env=test --append
```

**Tests ausführen:**
```bash
bin/phpunit
```

## Dokumentation

Weiterführende technische Dokumentation:

- [**Technische Dokumentation**](README/TECHNICAL_DOCUMENTATION.md) - Vollständige Architekturbeschreibung, Module und API-Referenz
- [**Datenbank ERD**](README/DATABASE_ERD.md) - Entity-Relationship-Diagramme aller Datenbankentitäten
- [**Flag-Filter Implementierung**](README/FLAG_FILTERING_IMPLEMENTATION.md) - Details zum dynamischen Flag-System

## Projektstruktur

```
├── assets/                 # Frontend Assets (Vue.js, SCSS)
├── config/                 # Symfony Konfiguration
├── migrations/             # Doctrine Migrationen
├── public/                 # Web Root (index.php, Assets)
├── src/
│   ├── Controller/         # HTTP Controller
│   ├── Entity/             # Doctrine Entities
│   ├── Form/               # Symfony Forms
│   ├── Repository/         # Datenbank-Repositories
│   ├── Service/            # Business Logic
│   └── Security/           # Authentifizierung
├── templates/              # Twig Templates
├── tests/                  # PHPUnit Tests
└── translations/           # Übersetzungsdateien (de/en)
```

