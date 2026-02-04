# Technical Documentation - Unser Schulessen

## Project Overview

**Unser Schulessen** is a comprehensive school meal quality management system built with Symfony 5.4. The application helps schools across German federal states manage and evaluate their meal quality through structured questionnaires, quality checks, and reporting features.

### Key Features
- Multi-tenant architecture supporting all 16 German federal states
- Quality assessment system with questionnaires and evaluations
- Mini-Check functionality for public access
- User management with role-based access control
- PDF report generation
- Food survey management
- Master data management for schools

## Architecture

### Technology Stack

**Backend:**
- **Framework:** Symfony 5.4 (PHP 7.4+)
- **Database:** MariaDB 10.3
- **ORM:** Doctrine ORM 2.7
- **Authentication:** Symfony Security Bundle
- **PDF Generation:** DomPDF 0.8.5
- **Email:** Sendinblue Mailer

**Frontend:**
- **JavaScript Framework:** Vue.js 2.6
- **CSS Framework:** Bootstrap 4.3
- **Build Tool:** Webpack Encore
- **Charts:** ApexCharts
- **UI Components:** Bootstrap Vue
- **Flag System Components:** Custom Vue.js components for dynamic flag rendering

**Infrastructure:**
- **Containerization:** Docker & Docker Compose
- **Web Server:** Apache (in Docker)
- **CI/CD:** GitLab CI/CD
- **Deployment:** Multi-environment Docker deployment

### Project Structure

```
├── assets/                 # Frontend assets (JS, CSS, images)
├── bin/                   # Executable scripts
├── config/                # Symfony configuration
├── migrations/            # Database migrations
├── public/                # Web root directory
├── src/                   # PHP source code
│   ├── Controller/        # MVC Controllers
│   ├── Entity/           # Doctrine entities
│   ├── Form/             # Symfony forms
│   ├── Repository/       # Data repositories
│   ├── Service/          # Business logic services
│   └── Security/         # Authentication logic
├── templates/            # Twig templates
├── tests/               # PHPUnit tests
├── translations/        # Internationalization files
└── var/                # Cache, logs, data storage
```

## Core Modules

### 1. Dynamic Flag System

**Purpose:** Flexible question categorization and filtering system that allows multiple flags per question for enhanced organization and analysis.

**Key Components:**
- **Flag Definitions**: Centralized configuration in `QualityCheckService`
- **JSON Storage**: Flags stored in database JSON columns for flexibility
- **Vue.js Components**: Dynamic flag rendering across the application
- **Client-Side Filtering**: JavaScript-based filtering for real-time results

**Flag Types:**
- **sustainable**: Nachhaltige Ernährung (green leaf icon)
- **miniCheck**: Mini-Check Fragen (blue checkbox icon)
- **guidelineCheck**: DGE-Richtlinien (yellow clipboard icon)
- **wurst**: Wurstwaren (red drumstick icon)

**Implementation Features:**
- **Multiple Flags per Question**: Questions can have multiple flags simultaneously
- **Dynamic Form Generation**: Admin forms automatically generate flag checkboxes
- **Visual Indicators**: Color-coded icons with tooltips for easy identification
- **Filtering Capabilities**: Results can be filtered by specific flags
- **PDF Export Support**: Flags are properly rendered in PDF exports
- **Vue.js Integration**: Reusable `QuestionFlags` component for consistent rendering

**Technical Architecture:**
```php
// Flag definitions in QualityCheckService
private const FLAG_DEFINITIONS = [
    'sustainable' => [
        'icon' => 'fas fa-leaf',
        'description' => 'Nachhaltige Ernährung',
        'color' => '#28a745'
    ],
    // ... more flags
];

// JSON storage in Question entity
class Question
{
    private ?array $flags = null;
    
    public function getFlags(): ?array
    {
        return $this->flags;
    }
    
    public function setFlags(?array $flags): self
    {
        $this->flags = $flags;
        return $this;
    }
}
```

**Vue.js Component:**
```vue
<!-- assets/js/components/QuestionFlags.vue -->
<template>
  <span v-if="visibleFlags.length > 0">
    <i
      v-for="flag in visibleFlags"
      :key="flag.name"
      :class="flag.icon"
      :title="flag.description"
      :style="{ color: flag.color }"
      class="flag-icon ml-1"
    ></i>
  </span>
</template>

<script>
export default {
  name: 'QuestionFlags',
  props: {
    question: {
      type: Object,
      required: true
    }
  },
  computed: {
    visibleFlags() {
      if (!window.flagDefinitions || !this.question.flags) {
        return [];
      }
      
      return Object.entries(window.flagDefinitions)
        .filter(([flagName, definition]) => 
          this.question.flags[flagName] === true
        )
        .map(([flagName, definition]) => ({
          name: flagName,
          ...definition
        }));
    }
  }
};
</script>
```

**Usage Examples:**
- **Admin Question Creation**: Dynamic form with flag checkboxes
- **Quality Check Results**: Filterable results by flag type
- **Mini-Check Display**: Flag icons in public-facing views
- **PDF Reports**: Proper flag rendering in exported documents
- **Quality Circle Overview**: Flag-based question categorization

### 2. User Management & Authentication

**Entities:**
- `User` - Main user entity with role-based permissions
- `UserHasSchool` - Many-to-many relationship between users and schools

**User Roles:**
- `ROLE_ADMIN` - System administrators
- `ROLE_CONSULTANT` - Educational consultants
- `ROLE_HEADMASTER` - School principals
- `ROLE_FOOD_COMMISSIONER` - Food service coordinators
- `ROLE_MENSA_AG` - Cafeteria working groups
- `ROLE_SCHOOL_AUTHORITIES` - School district officials
- `ROLE_KITCHEN` - Kitchen staff
- `ROLE_GUEST` - Limited access users

**Features:**
- Email-based authentication
- Password reset functionality
- Token-based invitation system
- Temporary password management
- Multi-school access per user

### 2. School Management

**Entities:**
- `School` - School information and metadata
- `SchoolYear` - Academic year management
- `Address` - School address information
- `Person` - Contact persons
- `MasterData` - School-specific configuration data

**Features:**
- Multi-state support (16 German federal states)
- School registration and verification
- Master data management per school year
- Address and contact management

### 3. Quality Check System

**Main Components:**
- Quality assessments and evaluations
- Questionnaire management
- Progress tracking
- Comparative analysis with previous assessments
- **Dynamic Flag System** for question categorization and filtering

**Features:**
- Structured quality evaluation process
- Historical data comparison
- Finalization workflow
- State-specific quality criteria
- **Flexible question flagging system** with multiple flags per question
- **Client-side filtering** based on question flags
- **Vue.js component-based flag rendering** across the application
- **Flag-based statistics** and reporting capabilities

### 4. Mini-Check System

**Purpose:** Public-facing simplified quality assessment tool

**Features:**
- Anonymous school quality assessment
- State-specific question sets
- Instant evaluation results
- Lead generation for full registration
- PDF report generation
- Mobile-responsive interface

**Workflow:**
1. State selection
2. Basic school data entry
3. Simplified questionnaire
4. Instant evaluation with traffic light system
5. Optional contact information collection
6. PDF report download

### 5. Survey Management

**Components:**
- Food surveys and evaluations
- Survey templates and customization
- Response collection and analysis
- Reporting and visualization

### 6. Quality Circle Management

**Features:**
- Quality improvement initiatives
- Stakeholder collaboration
- Progress tracking
- Documentation management

## Database Architecture

The database architecture follows a multi-layered approach with clear separation of concerns across different functional domains. The system uses MariaDB 10.3 with Doctrine ORM for object-relational mapping.

### Database Design Principles

1. **Multi-tenancy**: Data isolation by federal state (APP_STATE_COUNTRY)
2. **Audit Trail**: Comprehensive tracking of data changes and user actions
3. **Referential Integrity**: Strong foreign key relationships with cascade rules
4. **Flexible Schema**: JSON fields for extensible metadata and flags
5. **Temporal Data**: Support for school years and time-based data management

### Core Database Schema

#### 1. User Management & Authentication Domain

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│      User       │    │  UserHasSchool   │    │     School      │
├─────────────────┤    ├──────────────────┤    ├─────────────────┤
│ id (PK)         │    │ user_id (PK,FK)  │    │ id (PK)         │
│ email (UNIQUE)  │◄───┤ school_id(PK,FK) ├───►│ school_number   │
│ password        │    │ person_type (FK) │    │ name            │
│ person_id (FK)  │    │ role             │    │ headmaster      │
│ state           │    │ state            │    │ address_id (FK) │
│ roles (JSON)    │    │ created_at       │    │ created_at      │
│ current_school  │    │ responded_at     │    │ mini_check      │
│ created_at      │    └──────────────────┘    │ flags (JSON)    │
│ current_login   │                            └─────────────────┘
│ last_login      │    
│ employee        │    ┌─────────────────┐
│ temp_password   │    │     Person      │
└─────────────────┘    ├─────────────────┤
         │             │ id (PK)         │
         └────────────►│ salutation      │
                       │ first_name      │
                       │ last_name       │
                       │ school_id (FK)  │
                       │ user_id (FK)    │
                       └─────────────────┘

┌─────────────────┐    ┌─────────────────┐
│   PersonType    │    │    Address      │
├─────────────────┤    ├─────────────────┤
│ name (PK)       │    │ id (PK)         │
│                 │    │ street          │
└─────────────────┘    │ postal_code     │
                       │ city            │
                       │ district        │
                       └─────────────────┘
```

**Key Relationships:**
- **User ↔ School**: Many-to-Many through UserHasSchool (junction table)
- **User → Person**: One-to-One relationship for personal information
- **School → Address**: One-to-One relationship for location data
- **UserHasSchool → PersonType**: Many-to-One for role categorization

**User States:**
- `0` - Not Activated
- `1` - Active  
- `2` - Blocked

**UserHasSchool States:**
- `0` - Requested
- `1` - Accepted
- `2` - Rejected
- `3` - Blocked
- `4` - Consultant (special state for Rheinland-Pfalz)

#### 2. School Management Domain

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     School      │    │   MasterData    │    │   SchoolYear    │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ id (PK)         │◄───┤ id (PK)         │    │ year (PK)       │
│ school_number   │    │ school_id (FK)  │    │ label           │
│ name            │    │ school_year(FK) ├───►│ period_begin    │
│ headmaster      │    │ finalised       │    │ period_end      │
│ phone_number    │    │ finalised_by    │    └─────────────────┘
│ email_address   │    │ created_at      │
│ webpage         │    │ finalised_at    │    ┌─────────────────┐
│ education_auth  │    └─────────────────┘    │ MasterDataEntry │
│ school_type     │             │             ├─────────────────┤
│ school_operator │             └────────────►│ id (PK)         │
│ particularity   │                           │ master_data(FK) │
│ address_id (FK) │                           │ step            │
│ created_at      │                           │ field           │
│ audit_end       │                           │ value           │
│ mini_check      │                           │ created_at      │
│ mini_check_name │                           └─────────────────┘
│ mini_check_email│
│ flags (JSON)    │
└─────────────────┘
```

**Master Data Management:**
- **Unique Constraint**: (school_id, school_year) ensures one master data set per school per year
- **Finalization Workflow**: Master data can be finalized to prevent further changes
- **Flexible Storage**: MasterDataEntry uses key-value pairs for different data types
- **Audit Trail**: Tracks who finalized data and when

#### 3. Quality Check System Domain

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Questionnaire  │    │    Category     │    │    Question     │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ id (PK)         │◄───┤ id (PK)         │◄───┤ id (PK)         │
│ name (UNIQUE)   │    │ questionnaire   │    │ category_id(FK) │
│ date            │    │ name            │    │ question        │
│ created_by (FK) │    │ order           │    │ sustainable     │
│ based_on (FK)   │    │ mini_check      │    │ mini_check      │
│ state           │    │ mini_check_info │    │ mini_check_info │
│ state_country   │    │ flags (JSON)    │    │ order           │
│ mini_check      │    └─────────────────┘    │ type            │
└─────────────────┘                           │ flags (JSON)    │
                                              └─────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     Result      │    │     Answer      │    │   MiniCheck     │
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ id (PK)         │◄───┤ id (PK)         │    │ MiniCheckResult │
│ school_id (FK)  │    │ question_id(FK) │    │ MiniCheckAnswer │
│ created_by (FK) │    │ result_id (FK)  │    │ (Separate       │
│ created_at      │    │ answer          │    │  entities for   │
│ last_edited_at  │    └─────────────────┘    │  public access) │
│ last_edited_by  │                           └─────────────────┘
│ questionnaire   │    ┌─────────────────┐
│ school_year     │    │    Ideabox      │
│ finalised       │    ├─────────────────┤
│ finalised_at    │    │ id (PK)         │
│ finalised_by    │    │ result_id (FK)  │
└─────────────────┘    │ question_id(FK) │
                       │ idea            │
                       │ icon_id (FK)    │
                       └─────────────────┘
```

**Quality Check Workflow:**
1. **Questionnaire Creation**: Admin creates questionnaires with categories and questions
2. **Question Flagging**: Questions can be assigned multiple flags (sustainable, miniCheck, guidelineCheck, etc.)
3. **School Assessment**: Schools answer questions creating Results with Answers
4. **Idea Collection**: Schools can submit improvement ideas via Ideabox
5. **Finalization**: Results can be finalized to prevent further changes
6. **Mini-Check**: Simplified public version for lead generation
7. **Flag-Based Filtering**: Results can be filtered by question flags for focused analysis

**Answer Values:**
- `"true"` - Trifft zu (Applies)
- `"partial"` - Trifft teilweise zu (Partially applies)  
- `"false"` - Trifft nicht zu (Does not apply)
- `null` - Nicht beantwortet (Not answered)

#### 4. Survey System Domain

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     Survey      │    │ SurveyQuestion  │    │SurveyQuestionChoice│
├─────────────────┤    ├─────────────────┤    ├─────────────────┤
│ id (PK)         │◄───┤ id (PK)         │◄───┤ id (PK)         │
│ school_id (FK)  │    │ survey_id (FK)  │    │ question_id(FK) │
│ name            │    │ question_id(FK) │    │ choice_text     │
│ description     │    │ order           │    │ order           │
│ created_at      │    │ required        │    └─────────────────┘
│ created_by (FK) │    └─────────────────┘
│ active          │             │
│ public          │             ▼
└─────────────────┘    ┌─────────────────┐    ┌─────────────────┐
                       │SurveyQuestionAnswer│  │SurveyQuestionChoiceAnswer│
                       ├─────────────────┤    ├─────────────────┤
                       │ id (PK)         │    │ id (PK)         │
                       │ question_id(FK) │    │ choice_id (FK)  │
                       │ answer_text     │    │ answer_id (FK)  │
                       │ created_at      │    └─────────────────┘
                       │ session_id      │
                       └─────────────────┘
```

#### 5. Media & File Management

```
┌─────────────────┐
│     Media       │
├─────────────────┤
│ id (PK)         │
│ school_id (FK)  │
│ description     │
│ file_name       │
│ mime_type       │
│ created_at      │
│ created_by (FK) │
└─────────────────┘
```

### Database Indexes and Performance

#### Primary Indexes
- All tables have auto-incrementing primary keys
- Composite primary keys for junction tables (UserHasSchool)
- String primary keys for reference data (SchoolYear, PersonType)

#### Unique Constraints
- `user.email` - Ensures unique user accounts
- `questionnaire.name` - Prevents duplicate questionnaire names
- `(user_id, school_id)` in UserHasSchool - One relationship per user-school pair
- `(school_id, school_year)` in MasterData - One master data set per school per year
- `(category_id, question)` in Question - Prevents duplicate questions in categories

#### Foreign Key Relationships

**Cascade Rules:**
- **CASCADE**: User deletion removes all UserHasSchool relationships
- **RESTRICT**: Prevents deletion of referenced entities (Users, Schools)
- **SET NULL**: Sets foreign key to null when referenced entity is deleted

#### JSON Fields for Flexibility

**User.roles**: `["ROLE_USER", "ROLE_HEADMASTER"]`
**School.flags**: `{"certified": true, "organic": false}`
**Question.flags**: `{"sustainable": true, "miniCheck": true, "guidelineCheck": false, "wurst": true}`
**Category.flags**: `{"sustainable": true, "miniCheck": false}`

### Database Security & Multi-Tenancy

#### State-Based Isolation
- Each federal state operates with separate database instances
- `APP_STATE_COUNTRY` environment variable controls state-specific logic
- Repository classes filter data based on state context

#### Data Protection
- Password hashing using Symfony's security component
- Sensitive data encryption for PII
- Audit trails for all data modifications
- Soft deletes for critical business data

### Migration Strategy

#### Version Control
- Doctrine Migrations for schema versioning
- Automated migration execution in CI/CD pipeline
- Rollback capabilities for schema changes

#### Recent Schema Changes (2025)
- **Version20250606104120**: Added `flags` JSON field to Question entity for dynamic flag system
- **Version20250526113547**: Added `district` field to Address entity  
- **Version20250513142712**: Added `mini_check_info` to Category entity
- **Flag System Implementation**: Complete dynamic flag system with Vue.js components and client-side filtering

### Performance Optimization

#### Query Optimization
- Eager loading for frequently accessed relationships
- Repository pattern for complex queries
- Database connection pooling
- Query result caching

#### Indexing Strategy
- Composite indexes on frequently queried combinations
- Full-text search indexes for content search
- Partial indexes for filtered queries

### Backup and Recovery

#### Automated Backups
- Daily database dumps before deployments
- State-specific backup retention policies
- Point-in-time recovery capabilities
- Cross-state backup replication for disaster recovery

### Complete Database Documentation

For detailed Entity Relationship Diagrams, data flow charts, and comprehensive database schema documentation, see: **[DATABASE_ERD.md](DATABASE_ERD.md)**

The separate database documentation includes:
- Complete ERD with all entity relationships
- Data flow diagrams for key business processes
- Database constraints and business rules
- Indexing strategy and performance optimization
- Query examples and optimization techniques
- Data archival and retention policies
- Database monitoring and maintenance procedures

## Advanced Architecture Patterns

### Repository Pattern Implementation

The application uses the Repository pattern to abstract database operations:

```php
// Example: SchoolRepository with state-specific filtering
class SchoolRepository extends ServiceEntityRepository
{
    private string $appStateCountry;
    
    public function findByStateCountry(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.stateCountry = :state')
            ->setParameter('state', $this->appStateCountry)
            ->getQuery()
            ->getResult();
    }
}
```

### Service Layer Architecture

Business logic is encapsulated in service classes:

```php
// Example: QualityCheckService with Flag System
class QualityCheckService
{
    private const FLAG_DEFINITIONS = [
        'sustainable' => [
            'icon' => 'fas fa-leaf',
            'description' => 'Nachhaltige Ernährung',
            'color' => '#28a745'
        ],
        'miniCheck' => [
            'icon' => 'fas fa-check-square',
            'description' => 'Mini-Check Frage',
            'color' => '#007bff'
        ],
        'guidelineCheck' => [
            'icon' => 'fas fa-clipboard-check',
            'description' => 'DGE-Richtlinien',
            'color' => '#ffc107'
        ],
        'wurst' => [
            'icon' => 'fas fa-drumstick-bite',
            'description' => 'Wurstwaren',
            'color' => '#dc3545'
        ]
    ];
    
    public function getFlagDefinitions(): array
    {
        return self::FLAG_DEFINITIONS;
    }
    
    public function questionHasFlag(Question $question, string $flag): bool
    {
        $flags = $question->getFlags() ?? [];
        return isset($flags[$flag]) && $flags[$flag] === true;
    }
    
    public function getQuestionFlagClasses(Question $question): string
    {
        $classes = [];
        $flags = $question->getFlags() ?? [];
        
        foreach ($flags as $flag => $value) {
            if ($value === true) {
                $classes[] = 'flag-' . $flag;
            }
        }
        
        return implode(' ', $classes);
    }
    
    public function createResult(School $school, Questionnaire $questionnaire): Result
    {
        // Business logic for creating quality check results
        // Validation, state management, audit trail
    }
    
    public function finalizeResult(Result $result, User $user): void
    {
        // Finalization workflow with business rules
    }
}
```

### Event-Driven Architecture

The system uses Symfony's Event Dispatcher for decoupled operations:

```php
// Example: User login event handling
class LoginListener
{
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        // Update last login timestamp
        // Log user activity
        // Initialize session data
    }
}
```

### Multi-State Data Isolation

State-specific data isolation is implemented through:

1. **Environment Configuration**: `APP_STATE_COUNTRY` parameter
2. **Repository Filtering**: Automatic state-based query filtering
3. **Service Layer Validation**: Business rules per federal state
4. **Deployment Separation**: Separate database instances per state

## Configuration

### Environment Configuration

**Key Environment Variables:**
```bash
APP_ENV=dev|prod|test
APP_SECRET=<application-secret>
APP_STATE_COUNTRY=<state-code>  # bb, rp, th, sn, etc.
DATABASE_URL=mysql://user:pass@host:port/database
TEST_DATABASE_URL=mysql://user:pass@host:port/test_database
MAILER_DSN=<sendinblue-configuration>
```

### Multi-State Configuration

The application supports deployment across all 16 German federal states:
- Brandenburg (bb) - Primary/Production
- Rheinland-Pfalz (rp)
- Thüringen (th)
- Sachsen (sn)
- Niedersachsen (ni)
- Hamburg (hh)
- Berlin (be)
- Sachsen-Anhalt (st)
- Baden-Württemberg (bw)
- Saarland (sl)
- Bayern (by)
- Bremen (hb)
- Hessen (he)
- Mecklenburg-Vorpommern (mv)
- Nordrhein-Westfalen (nw)
- Schleswig-Holstein (sh)

Each state has its own:
- Database instance
- Domain (e.g., bb.unser-schulessen.de)
- Docker container
- Configuration parameters

## Development Setup

### Prerequisites
- Docker & Docker Compose
- PHP 7.4+
- Composer
- Node.js & Yarn
- MariaDB 10.3+

### Local Development

1. **Clone and Setup:**
```bash
git clone <repository-url>
cd unser-schulessen
cp .env.dist .env
```

2. **Start Services:**
```bash
docker-compose up -d
```

3. **Install Dependencies:**
```bash
composer install
yarn install
```

4. **Database Setup:**
```bash
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

5. **Build Assets:**
```bash
yarn encore dev --watch
```

### Testing Strategy

The application employs a comprehensive multi-layered testing approach with automated testing integrated into the CI/CD pipeline, ensuring code quality, functionality, and reliability across all 16 federal state deployments.

#### Testing Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    Testing Pipeline Architecture                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────┐  │
│  │   Unit Tests    │    │ Integration     │    │   E2E Tests │  │
│  │                 │    │ Tests           │    │             │  │
│  │ • Entity Tests  │    │ • Controller    │    │ • User      │  │
│  │ • Service Tests │    │   Tests         │    │   Workflows │  │
│  │ • Repository    │    │ • Database      │    │ • Quality   │  │
│  │   Tests         │    │   Tests         │    │   Checks    │  │
│  │ • Form Tests    │    │ • API Tests     │    │ • Mini-Check│  │
│  └─────────────────┘    └─────────────────┘    └─────────────┘  │
│           │                       │                       │     │
│           └───────────────────────┼───────────────────────┘     │
│                                   │                             │
│  ┌────────────────────────────────┼────────────────────────┐    │
│  │                     Code Quality Testing                │    │
│  │                                │                        │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │    │
│  │  │   PHPStan   │  │   PHPCS     │  │  Security Scan  │  │    │
│  │  │ Static      │  │ Coding      │  │  Vulnerability  │  │    │
│  │  │ Analysis    │  │ Standards   │  │  Assessment     │  │    │
│  │  └─────────────┘  └─────────────┘  └─────────────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                   │                             │
│  ┌────────────────────────────────┼────────────────────────┐    │
│  │                        Database Testing                 │    │
│  │                                │                        │    │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐  │    │
│  │  │ Migration   │  │ Schema      │  │  Data Integrity │  │    │
│  │  │ Testing     │  │ Validation  │  │  Testing        │  │    │
│  │  └─────────────┘  └─────────────┘  └─────────────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

#### 1. Unit Testing with PHPUnit

**Configuration Files:**
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         executionOrder="depends,defects"
         forceCoversAnnotation="false"
         beStrictAboutCoversAnnotation="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         failOnRisky="true"
         failOnWarning="true"
         verbose="true">
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Functional">
            <directory>tests/Functional</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Migrations</directory>
            <file>src/Kernel.php</file>
        </exclude>
        <report>
            <html outputDirectory="build/coverage"/>
            <text outputFile="build/coverage.txt"/>
            <clover outputFile="build/logs/clover.xml"/>
        </report>
    </coverage>
</phpunit>
```

**Test Database Configuration:**
```bash
# .env.test
APP_ENV=test
DATABASE_URL=mysql://username:your-password@172.17.0.1:8887/mein-schulessen-test
TEST_DATABASE_URL=mysql://username:your-password@172.17.0.1:8887/mein-schulessen-test
MAILER_DSN=null://null
```

**Test Structure and Examples:**

```php
// tests/Unit/Service/QualityCheckServiceTest.php - Flag System Tests
<?php
namespace App\Tests\Unit\Service;

use App\Service\QualityCheckService;
use App\Entity\QualityCheck\Question;
use PHPUnit\Framework\TestCase;

class QualityCheckServiceTest extends TestCase
{
    private QualityCheckService $service;
    
    protected function setUp(): void
    {
        $this->service = new QualityCheckService();
    }
    
    public function testGetFlagDefinitions(): void
    {
        $definitions = $this->service->getFlagDefinitions();
        
        $this->assertArrayHasKey('sustainable', $definitions);
        $this->assertArrayHasKey('miniCheck', $definitions);
        $this->assertArrayHasKey('guidelineCheck', $definitions);
        $this->assertArrayHasKey('wurst', $definitions);
        
        $this->assertEquals('fas fa-leaf', $definitions['sustainable']['icon']);
        $this->assertEquals('#28a745', $definitions['sustainable']['color']);
    }
    
    public function testQuestionHasFlag(): void
    {
        $question = new Question();
        $question->setFlags(['sustainable' => true, 'miniCheck' => false]);
        
        $this->assertTrue($this->service->questionHasFlag($question, 'sustainable'));
        $this->assertFalse($this->service->questionHasFlag($question, 'miniCheck'));
        $this->assertFalse($this->service->questionHasFlag($question, 'nonexistent'));
    }
    
    public function testGetQuestionFlagClasses(): void
    {
        $question = new Question();
        $question->setFlags(['sustainable' => true, 'miniCheck' => true, 'guidelineCheck' => false]);
        
        $classes = $this->service->getQuestionFlagClasses($question);
        
        $this->assertStringContains('flag-sustainable', $classes);
        $this->assertStringContains('flag-miniCheck', $classes);
        $this->assertStringNotContains('flag-guidelineCheck', $classes);
    }
}

// tests/Unit/Entity/UserTest.php
<?php
namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\School;
use App\Entity\UserHasSchool;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');
        
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUsername());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }
    
    public function testUserSchoolRelationship(): void
    {
        $user = new User();
        $school = new School();
        $school->setName('Test School');
        
        $userHasSchool = new UserHasSchool();
        $userHasSchool->setUser($user);
        $userHasSchool->setSchool($school);
        $userHasSchool->setState(UserHasSchool::STATE_ACCEPTED);
        
        $this->assertTrue($user->hasSchool($school));
        $this->assertTrue($user->isAccepted($school));
    }
    
    /**
     * @dataProvider roleProvider
     */
    public function testUserRoles(string $role, bool $expected): void
    {
        $user = new User();
        $user->addRole($role);
        
        $this->assertEquals($expected, in_array($role, $user->getRoles()));
    }
    
    public function roleProvider(): array
    {
        return [
            [User::ROLE_ADMIN, true],
            [User::ROLE_HEADMASTER, true],
            [User::ROLE_CONSULTANT, true],
            ['INVALID_ROLE', false],
        ];
    }
}
```

```php
// tests/Unit/Service/QualityCheckServiceTest.php
<?php
namespace App\Tests\Unit\Service;

use App\Service\QualityCheckService;
use App\Entity\School;
use App\Entity\Questionnaire;
use App\Entity\QualityCheck\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class QualityCheckServiceTest extends TestCase
{
    private QualityCheckService $service;
    private EntityManagerInterface|MockObject $entityManager;
    
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new QualityCheckService($this->entityManager);
    }
    
    public function testCreateResult(): void
    {
        $school = new School();
        $school->setName('Test School');
        
        $questionnaire = new Questionnaire();
        $questionnaire->setName('Test Questionnaire');
        
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Result::class));
            
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        
        $result = $this->service->createResult($school, $questionnaire);
        
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals($school, $result->getSchool());
        $this->assertEquals($questionnaire, $result->getQuestionnaire());
    }
}
```

#### 2. Integration Testing

**Database Integration Tests:**
```php
// tests/Integration/Repository/SchoolRepositoryTest.php
<?php
namespace App\Tests\Integration\Repository;

use App\Entity\School;
use App\Entity\Address;
use App\Repository\SchoolRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class SchoolRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SchoolRepository $repository;
    
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->repository = $this->entityManager->getRepository(School::class);
    }
    
    public function testFindByStateCountry(): void
    {
        // Create test data
        $school = new School();
        $school->setName('Test School Brandenburg');
        $school->setSchoolNumber('12345');
        
        $address = new Address();
        $address->setCity('Berlin');
        $address->setState('Brandenburg');
        $school->setAddress($address);
        
        $this->entityManager->persist($address);
        $this->entityManager->persist($school);
        $this->entityManager->flush();
        
        // Test repository method
        $schools = $this->repository->findByStateCountry('bb');
        
        $this->assertCount(1, $schools);
        $this->assertEquals('Test School Brandenburg', $schools[0]->getName());
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
```

**Controller Integration Tests:**
```php
// tests/Integration/Controller/SecurityControllerTest.php
<?php
namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Anmelden');
        $this->assertSelectorExists('form[name="login"]');
    }
    
    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Anmelden')->form([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $client->submit($form);
        
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
    
    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        
        $form = $crawler->selectButton('Anmelden')->form([
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $client->submit($form);
        
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }
}
```

#### 3. Functional Testing

**Quality Check Workflow Tests:**
```php
// tests/Functional/QualityCheckWorkflowTest.php
<?php
namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\School;
use App\Entity\QualityCheck\Questionnaire;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class QualityCheckWorkflowTest extends WebTestCase
{
    private $client;
    private $entityManager;
    
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }
    
    public function testCompleteQualityCheckWorkflow(): void
    {
        // 1. Create test user and school
        $user = $this->createTestUser();
        $school = $this->createTestSchool();
        $questionnaire = $this->createTestQuestionnaire();
        
        // 2. Login user
        $this->loginUser($user);
        
        // 3. Start quality check
        $crawler = $this->client->request('GET', '/quality-check/start');
        $this->assertResponseIsSuccessful();
        
        // 4. Select questionnaire
        $form = $crawler->selectButton('Starten')->form();
        $form['questionnaire'] = $questionnaire->getId();
        $this->client->submit($form);
        
        // 5. Answer questions
        $this->client->followRedirect();
        $crawler = $this->client->getCrawler();
        
        $questionForm = $crawler->selectButton('Speichern')->form();
        $questionForm['answers[1]'] = 'true';
        $questionForm['answers[2]'] = 'partial';
        $this->client->submit($questionForm);
        
        // 6. Finalize assessment
        $this->client->followRedirect();
        $finalizeForm = $this->client->getCrawler()->selectButton('Finalisieren')->form();
        $this->client->submit($finalizeForm);
        
        // 7. Verify results
        $this->assertResponseRedirects('/quality-check/results');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Qualitätscheck erfolgreich abgeschlossen');
    }
    
    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('$2y$13$hashed_password');
        $user->setState(User::STATE_ACTIVE);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
    
    private function loginUser(User $user): void
    {
        $session = $this->client->getContainer()->get('session');
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $session->set('_security_main', serialize($token));
        $session->save();
        
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
```

#### 4. API Testing

**Mini-Check API Tests:**
```php
// tests/Functional/Api/MiniCheckApiTest.php
<?php
namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MiniCheckApiTest extends WebTestCase
{
    public function testMiniCheckStart(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/mini-check/start', [
            'state_country' => 'bb',
            'school_name' => 'Test School',
            'school_type' => 'Grundschule'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('session_id', $response);
        $this->assertArrayHasKey('questions', $response);
    }
    
    public function testMiniCheckSubmitAnswers(): void
    {
        $client = static::createClient();
        
        // Start mini-check first
        $client->request('POST', '/api/mini-check/start', [
            'state_country' => 'bb',
            'school_name' => 'Test School',
            'school_type' => 'Grundschule'
        ]);
        
        $startResponse = json_decode($client->getResponse()->getContent(), true);
        $sessionId = $startResponse['session_id'];
        
        // Submit answers
        $client->request('POST', '/api/mini-check/submit', [
            'session_id' => $sessionId,
            'answers' => [
                ['question_id' => 1, 'answer' => 'true'],
                ['question_id' => 2, 'answer' => 'partial'],
                ['question_id' => 3, 'answer' => 'false']
            ]
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('evaluation', $response);
        $this->assertArrayHasKey('score', $response['evaluation']);
    }
}
```

#### 5. Code Quality Testing

**PHP CodeSniffer Configuration:**
```xml
<!-- phpcs.xml -->
<?xml version="1.0"?>
<ruleset name="Unser Schulessen Coding Standard">
    <description>Coding standard for Unser Schulessen project</description>
    
    <file>src</file>
    <file>tests</file>
    
    <exclude-pattern>src/Migrations</exclude-pattern>
    <exclude-pattern>var</exclude-pattern>
    <exclude-pattern>vendor</exclude-pattern>
    
    <rule ref="PSR12"/>
    <rule ref="Slevomat.Coding.Standard">
        <exclude name="SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint"/>
        <exclude name="SlevomatCodingStandard.Classes.SuperfluousAbstractClassNaming"/>
    </rule>
    
    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="lineLimit" value="120"/>
            <property name="absoluteLineLimit" value="150"/>
        </properties>
    </rule>
</ruleset>
```

**Static Analysis with PHPStan:**
```neon
# phpstan.neon
parameters:
    level: 8
    paths:
        - src
        - tests
    excludePaths:
        - src/Migrations
    ignoreErrors:
        - '#Call to an undefined method Doctrine\\Common\\Persistence\\ObjectManager::#'
    symfony:
        container_xml_path: var/cache/dev/App_KernelDevDebugContainer.xml
    doctrine:
        objectManagerLoader: tests/object-manager.php
```

#### 6. Database Testing

**Migration Testing:**
```php
// tests/Integration/Database/MigrationTest.php
<?php
namespace App\Tests\Integration\Database;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrationTest extends KernelTestCase
{
    public function testMigrationsExecuteSuccessfully(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);
        
        // Test migration execution
        $command = $application->find('doctrine:migrations:migrate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--no-interaction' => true,
            '--env' => 'test'
        ]);
        
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
    
    public function testSchemaValidation(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);
        
        // Test schema validation
        $command = $application->find('doctrine:schema:validate');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--env' => 'test']);
        
        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContains('The mapping files are correct', $commandTester->getDisplay());
        $this->assertStringContains('The database schema is in sync', $commandTester->getDisplay());
    }
}
```

#### 7. Performance Testing

**Load Testing with PHPUnit:**
```php
// tests/Performance/LoadTest.php
<?php
namespace App\Tests\Performance;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoadTest extends WebTestCase
{
    /**
     * @group performance
     */
    public function testHomePagePerformance(): void
    {
        $client = static::createClient();
        
        $startTime = microtime(true);
        $client->request('GET', '/');
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(500, $responseTime, 'Home page should load in less than 500ms');
    }
    
    /**
     * @group performance
     */
    public function testDatabaseQueryPerformance(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        
        $startTime = microtime(true);
        
        // Execute complex query
        $schools = $entityManager->getRepository(School::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.address', 'a')
            ->leftJoin('s.userHasSchool', 'uhs')
            ->leftJoin('uhs.user', 'u')
            ->where('s.miniCheck = :miniCheck')
            ->setParameter('miniCheck', true)
            ->getQuery()
            ->getResult();
        
        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000;
        
        $this->assertLessThan(100, $queryTime, 'Complex query should execute in less than 100ms');
    }
}
```

#### 8. Test Commands and Automation

**Composer Scripts:**
```json
{
    "scripts": {
        "test": "bin/phpunit",
        "test-unit": "bin/phpunit --testsuite=Unit",
        "test-integration": "bin/phpunit --testsuite=Integration",
        "test-functional": "bin/phpunit --testsuite=Functional",
        "test-coverage": "bin/phpunit --coverage-html build/coverage",
        "test-performance": "bin/phpunit --group=performance",
        "cs-check": "phpcs -s -n --runtime-set ignore_warnings_on_exit true",
        "cs-fix": "phpcbf",
        "static-analysis": "phpstan analyse",
        "rebuild-testdb": [
            "php bin/console doctrine:schema:drop --full-database --env=test --force",
            "php bin/console doctrine:schema:update --force --env=test -n",
            "php bin/console doctrine:fixtures:load --env=test --append"
        ],
        "test-all": [
            "@cs-check",
            "@static-analysis", 
            "@rebuild-testdb",
            "@test-coverage"
        ]
    }
}
```

**GitLab CI Testing Configuration:**
```yaml
# Detailed CI testing stages
test_phpunit:
  stage: test
  except: [stage, production]
  script:
    # Build test container
    - docker build --rm -t unser-schulessen:test_build -f ./Dockerfile_test .
    - docker ps -q -a --filter name="unser_schulessen_testing" | xargs -r docker rm -f
    - docker run -d -p 127.0.0.1:8899:80 
        -v /home/gitlab-runner/composer_cache/:/root/.composer/cache/ 
        -v /home/gitlab-runner/yarn-cache/:/tmp/yarn/ 
        --name unser_schulessen_testing unser-schulessen:test_build
    
    # Configure test environment
    - docker exec -i unser_schulessen_testing cp .env.dist .env
    - docker exec -i unser_schulessen_testing sed -i -e 's!APP_ENV=dev!APP_ENV=test!g' .env
    - docker exec -i unser_schulessen_testing sed -i -e 's!^TEST_DATABASE_URL=[^\n]*!TEST_DATABASE_URL='"$TEST_DB_URL"'!g' .env
    
    # Install dependencies
    - docker exec -i unser_schulessen_testing composer install --no-dev --optimize-autoloader
    - docker exec -i unser_schulessen_testing yarn install --cache-folder /tmp/yarn --production
    - docker exec -i unser_schulessen_testing yarn encore production
    
    # Prepare test database
    - docker exec -i unser_schulessen_testing php bin/console cache:clear --env=test
    - docker exec -i unser_schulessen_testing composer run rebuild-testdb
    
    # Run comprehensive test suite
    - docker exec -i unser_schulessen_testing bin/phpunit --stop-on-failure --coverage-text --colors=never --log-junit build/junit.xml
    
    # Run static analysis
    - docker exec -i unser_schulessen_testing vendor/bin/phpstan analyse --error-format=gitlab > phpstan-report.json
    
    # Export test artifacts
    - docker exec -i unser_schulessen_testing zip -r test-artifacts.zip build/coverage build/logs phpstan-report.json
    - docker cp unser_schulessen_testing:/var/www/test-artifacts.zip .
    
  artifacts:
    paths: [test-artifacts.zip, phpstan-report.json]
    reports:
      junit: build/junit.xml
      coverage_report:
        coverage_format: cobertura
        path: build/logs/cobertura.xml
    expire_in: 7 days
  coverage: '/Lines:\s+(\d+\.\d+)%/'
```

#### 9. Test Data Management

**Fixtures for Testing:**
```php
// src/DataFixtures/TestFixtures.php
<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\QualityCheck\Questionnaire;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class TestFixtures extends Fixture
{
    private UserPasswordEncoderInterface $passwordEncoder;
    
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }
    
    public function load(ObjectManager $manager): void
    {
        // Create test users
        $adminUser = new User();
        $adminUser->setEmail('admin@test.com');
        $adminUser->setPassword($this->passwordEncoder->encodePassword($adminUser, 'admin123'));
        $adminUser->setState(User::STATE_ACTIVE);
        $adminUser->addRole(User::ROLE_ADMIN);
        $manager->persist($adminUser);
        
        $schoolUser = new User();
        $schoolUser->setEmail('school@test.com');
        $schoolUser->setPassword($this->passwordEncoder->encodePassword($schoolUser, 'school123'));
        $schoolUser->setState(User::STATE_ACTIVE);
        $manager->persist($schoolUser);
        
        // Create test school
        $school = new School();
        $school->setName('Test Grundschule');
        $school->setSchoolNumber('12345');
        $school->setHeadmaster('Max Mustermann');
        $manager->persist($school);
        
        // Create test school year
        $schoolYear = new SchoolYear();
        $schoolYear->setYear('2024');
        $schoolYear->setLabel('2024/2025');
        $schoolYear->setPeriodBegin(new \DateTime('2024-08-01'));
        $schoolYear->setPeriodEnd(new \DateTime('2025-07-31'));
        $manager->persist($schoolYear);
        
        // Create test questionnaire
        $questionnaire = new Questionnaire();
        $questionnaire->setName('Test Fragebogen');
        $questionnaire->setDate(new \DateTime());
        $questionnaire->setCreatedBy($adminUser);
        $questionnaire->setState(Questionnaire::STATE_ACTIVE);
        $manager->persist($questionnaire);
        
        $manager->flush();
    }
}
```

#### 10. Continuous Testing Metrics

**Test Coverage Requirements:**
- **Minimum Coverage**: 80% overall code coverage
- **Critical Components**: 95% coverage for services and repositories
- **Controllers**: 85% coverage for all controller actions
- **Entities**: 90% coverage for business logic methods

**Quality Gates:**
- All tests must pass before deployment
- Code coverage must not decrease
- No critical security vulnerabilities
- Performance tests must meet SLA requirements
- Static analysis must show no errors at level 8
- Flag system functionality must be fully tested
- Vue.js components must render correctly across all browsers

**Test Reporting:**
- Daily test execution reports
- Coverage trend analysis
- Performance regression detection
- Failed test notifications via GitLab
- Weekly quality metrics dashboard

## Deployment Architecture

The deployment strategy uses a sophisticated multi-environment Docker-based approach with GitLab CI/CD, supporting 16 separate German federal state instances with automated testing, deployment, and monitoring.

### Infrastructure Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        GitLab CI/CD Pipeline                    │
├─────────────────────────────────────────────────────────────────┤
│  Build Stage → Test Stage → Deploy Stage (16 parallel jobs)     │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Production Server                           │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │ Brandenburg │  │ Rheinland-  │  │ Thüringen   │   ... (16)   │
│  │ (bb)        │  │ Pfalz (rp)  │  │ (th)        │              │
│  │ Port: 8080  │  │ Port: 6004  │  │ Port: 6000  │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
│                                                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │              Shared Infrastructure                         │ │
│  │  • Reverse Proxy (Nginx)                                   │ │
│  │  • SSL Certificates (Let's Encrypt)                        │ │
│  │  • Shared File Storage (/datastore/)                       │ │
│  │  • Database Cluster (MariaDB 10.3)                         │ │
│  │  • Backup Storage                                          │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### CI/CD Pipeline Detailed Breakdown

#### Stage 1: Build
```yaml
build_image:
  stage: build
  script:
    # Create deployment Docker image
    - docker build --rm -t unser-schulessen:deployment_build -f ./Dockerfile_deploy .
    
    # Stop and remove existing deployment container
    - docker ps -q -a --filter name="unser_schulessen_deployment" | xargs -r docker rm -f
    
    # Start new deployment container with caching
    - docker run -d -p 127.0.0.1:8888:80 
        -v /home/gitlab-runner/composer_cache/:/root/.composer/cache/ 
        -v /home/gitlab-runner/yarn-cache/:/tmp/yarn/ 
        --name unser_schulessen_deployment unser-schulessen:deployment_build
    
    # Configure environment
    - docker exec -i unser_schulessen_deployment mv .env.dist .env
    - docker exec -i unser_schulessen_deployment sed -i -e 's!^DATABASE_URL=[^\n]*!DATABASE_URL='"$DEPLOY_DB_URL"'!g' .env
    - docker exec -i unser_schulessen_deployment sed -i -e 's!^MAILER_DSN=[^\n]*!MAILER_DSN='"$MAILER_DSN"'!g' .env
```

#### Stage 2: Dependency Installation
```yaml
install_dependencies:
  stage: build
  script:
    # Install PHP dependencies with caching
    - docker exec -i unser_schulessen_deployment composer install
    - docker exec -i unser_schulessen_deployment composer dump-autoload
    
    # Install and build frontend assets
    - docker exec -i unser_schulessen_deployment yarn install --cache-folder /tmp/yarn
    - docker exec -i unser_schulessen_deployment yarn encore production
```

#### Stage 3: Testing
```yaml
# Code Quality Testing
test_code:
  stage: test
  script:
    - docker exec -i unser_schulessen_deployment composer cs-check -- -n
  needs: [install_dependencies]

# Database Schema Testing
test_schema:
  stage: test
  script:
    # Create fresh test database
    - mysql -uunser-schulessen -p$MYSQL_PASSWORD -e "DROP DATABASE unser_schulessen_deploy;"
    - mysql -uunser-schulessen -p$MYSQL_PASSWORD -e "CREATE DATABASE unser_schulessen_deploy CHARACTER SET = 'utf8mb4' COLLATE = 'utf8mb4_general_ci';"
    
    # Import current production data
    - mysqldump -uunser-schulessen -p$MYSQL_PASSWORD unser_schulessen | mysql -uunser-schulessen -p$MYSQL_PASSWORD unser_schulessen_deploy
    
    # Test migrations
    - docker exec -i unser_schulessen_deployment php bin/console doctrine:migrations:migrate --no-interaction
    - docker exec -i unser_schulessen_deployment php bin/console doctrine:schema:update --dump-sql
    - docker exec -i unser_schulessen_deployment php bin/console doctrine:schema:validate

# PHPUnit Testing (except for stage/production branches)
test_phpunit:
  stage: test
  except: [stage, production]
  script:
    # Build separate test container
    - docker build --rm -t unser-schulessen:test_build -f ./Dockerfile_test .
    - docker ps -q -a --filter name="unser_schulessen_testing" | xargs -r docker rm -f
    - docker run -d -p 127.0.0.1:8899:80 
        -v /home/gitlab-runner/composer_cache/:/root/.composer/cache/ 
        -v /home/gitlab-runner/yarn-cache/:/tmp/yarn/ 
        --name unser_schulessen_testing unser-schulessen:test_build
    
    # Configure test environment
    - docker exec -i unser_schulessen_testing cp .env.dist .env
    - docker exec -i unser_schulessen_testing sed -i -e 's!APP_ENV=dev!APP_ENV=test!g' .env
    - docker exec -i unser_schulessen_testing sed -i -e 's!^TEST_DATABASE_URL=[^\n]*!TEST_DATABASE_URL='"$TEST_DB_URL"'!g' .env
    
    # Install dependencies and build assets
    - docker exec -i unser_schulessen_testing composer install
    - docker exec -i unser_schulessen_testing composer dump-autoload
    - docker exec -i unser_schulessen_testing yarn install --cache-folder /tmp/yarn
    - docker exec -i unser_schulessen_testing yarn encore production
    
    # Prepare test database
    - docker exec -i unser_schulessen_testing php bin/console cache:clear
    - docker exec -i unser_schulessen_testing composer run rebuild-testdb
    
    # Run tests with coverage
    - docker exec -i unser_schulessen_testing bin/phpunit --stop-on-failure --coverage-text --colors=never
    
    # Export coverage reports
    - docker exec -i unser_schulessen_testing zip -r coverage-unser-schulessen.zip build/coverage
    - docker cp unser_schulessen_testing:/var/www/coverage-unser-schulessen.zip .
  artifacts:
    paths: [coverage-unser-schulessen.zip]
    expire_in: 3 days
```

#### Stage 4: Multi-State Deployment

The deployment stage runs 16 parallel jobs, one for each German federal state:

```yaml
# Example: Brandenburg (Primary Production)
deploy_production_image:
  stage: deploy
  environment:
    name: production
    url: https://bb.unser-schulessen.de
  only: [production]
  script:
    # Stop existing production container
    - docker ps -q -a --filter name="unser_schulessen_production" | xargs -r docker rm -f
    
    # Create new production image from deployment build
    - docker commit unser_schulessen_deployment unser-schulessen:new-production
    
    # Start production container with persistent storage
    - docker run -d -p 127.0.0.1:8080:80 
        -v /datastore/production/:/var/www/var/data/ 
        --restart always 
        --name unser_schulessen_production unser-schulessen:new-production
    
    # Configure production environment
    - docker exec -i unser_schulessen_production sed -i -e 's!APP_ENV=dev!APP_ENV=prod!g' .env
    - docker exec -i unser_schulessen_production sed -i -e 's!APP_STATE_COUNTRY=[^\n]*!APP_STATE_COUNTRY=bb!g' .env
    - docker exec -i unser_schulessen_production sed -i -e 's!^DATABASE_URL=[^\n]*!DATABASE_URL='"$PRODUCTION_DB_URL"'!g' .env
    
    # Clear cache and set permissions
    - docker exec -i unser_schulessen_production php bin/console cache:clear
    - docker exec -i unser_schulessen_production chmod -R 777 /var/www/var
    
    # Backup database before migration
    - mysqldump -uunser-schulessen -p$MYSQL_PASSWORD unser_schulessen > /home/gitlab-runner/unser_schulessen-$(date +%Y-%m-%d-%H-%M-%S).sql
    
    # Run database migrations
    - docker exec -i unser_schulessen_production php bin/console doctrine:migrations:migrate --no-interaction

# Example: Rheinland-Pfalz State
deploy_rheinlandpfalz_image:
  stage: deploy
  environment:
    name: rheinlandpfalz
    url: https://rp.unser-schulessen.de
  only: [production]
  script:
    - docker ps -q -a --filter name="unser_schulessen_rheinlandpfalz" | xargs -r docker rm -f
    - docker commit unser_schulessen_deployment unser-schulessen:new-rheinlandpfalz
    - docker run -d -p 127.0.0.1:6004:80 
        -v /datastore/rp_production/:/var/www/var/data/ 
        --restart always 
        --name unser_schulessen_rheinlandpfalz unser-schulessen:new-rheinlandpfalz
    - docker exec -i unser_schulessen_rheinlandpfalz sed -i -e 's!APP_ENV=dev!APP_ENV=prod!g' .env
    - docker exec -i unser_schulessen_rheinlandpfalz sed -i -e 's!APP_STATE_COUNTRY=[^\n]*!APP_STATE_COUNTRY=rp!g' .env
    - docker exec -i unser_schulessen_rheinlandpfalz sed -i -e 's!^DATABASE_URL=[^\n]*!DATABASE_URL='"$RHEINLANDPFALZ_DB_URL"'!g' .env
    - docker exec -i unser_schulessen_rheinlandpfalz php bin/console cache:clear
    - docker exec -i unser_schulessen_rheinlandpfalz chmod -R 777 /var/www/var
    - mysqldump -uunser-schulessen -p$MYSQL_PASSWORD unser_schulessen_rp > /home/gitlab-runner/unser_schulessen-rp-$(date +%Y-%m-%d-%H-%M-%S).sql
    - docker exec -i unser_schulessen_rheinlandpfalz php bin/console doctrine:migrations:migrate --no-interaction
```

### State-Specific Deployment Configuration

| Federal State | Code | Port | Domain | Database | Data Volume |
|---------------|------|------|--------|----------|-------------|
| Brandenburg (Primary) | bb | 8080 | bb.unser-schulessen.de | unser_schulessen | /datastore/production/ |
| Rheinland-Pfalz | rp | 6004 | rp.unser-schulessen.de | unser_schulessen_rp | /datastore/rp_production/ |
| Thüringen | th | 6000 | th.unser-schulessen.de | unser_schulessen_th | /datastore/thueringen/ |
| Sachsen | sn | 6001 | sn.unser-schulessen.de | unser_schulessen_sn | /datastore/sachsen/ |
| Niedersachsen | ni | 6002 | ni.unser-schulessen.de | unser_schulessen_ni | /datastore/niedersachsen/ |
| Hamburg | hh | 6003 | hh.unser-schulessen.de | unser_schulessen_hh | /datastore/hamburg/ |
| Berlin | be | 6005 | be.unser-schulessen.de | unser_schulessen_be | /datastore/be_production/ |
| Sachsen-Anhalt | st | 6006 | st.unser-schulessen.de | unser_schulessen_st | /datastore/st_production/ |
| Baden-Württemberg | bw | 6007 | bw.unser-schulessen.de | unser_schulessen_bw | /datastore/bw_production/ |
| Saarland | sl | 6008 | sl.unser-schulessen.de | unser_schulessen_sl | /datastore/sl_production/ |
| Bayern | by | 6009 | by.unser-schulessen.de | unser_schulessen_by | /datastore/by_production/ |
| Bremen | hb | 6010 | hb.unser-schulessen.de | unser_schulessen_hb | /datastore/hb_production/ |
| Hessen | he | 6011 | he.unser-schulessen.de | unser_schulessen_he | /datastore/he_production/ |
| Mecklenburg-Vorpommern | mv | 6012 | mv.unser-schulessen.de | unser_schulessen_mv | /datastore/mv_production/ |
| Nordrhein-Westfalen | nw | 6013 | nw.unser-schulessen.de | unser_schulessen_nw | /datastore/nw_production/ |
| Schleswig-Holstein | sh | 6014 | sh.unser-schulessen.de | unser_schulessen_sh | /datastore/sh_production/ |

### Docker Container Architecture

#### Base Images
```dockerfile
# Dockerfile_deploy - Production deployment image
FROM php:7.4-apache
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libmagickwand-dev \
    mariadb-client \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd pdo pdo_mysql
RUN pecl install imagick && docker-php-ext-enable imagick

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Yarn
RUN npm install -g yarn

# Configure Apache
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

WORKDIR /var/www
COPY . .
RUN chown -R www-data:www-data /var/www
```

#### Container Lifecycle Management
```bash
# Container startup sequence
1. Stop existing container (if running)
2. Create new image from deployment build
3. Start container with persistent volumes
4. Configure environment variables
5. Clear application cache
6. Set file permissions
7. Run database migrations
8. Health check verification

# Container monitoring
- Automatic restart on failure (--restart always)
- Health checks via HTTP endpoints
- Log aggregation to host system
- Resource usage monitoring
```

### Environment-Specific Configuration

#### Production Environment Variables
```bash
# Application Configuration
APP_ENV=prod
APP_SECRET=<production-secret>
APP_STATE_COUNTRY=<state-code>

# Database Configuration
DATABASE_URL=mysql://user:pass@host:port/database_<state>

# Mail Configuration
MAILER_DSN=sendinblue+api://api-key@default

# File Storage
DOCUMENTS_DIRECTORY=/var/www/var/data/documents
FOOD_SURVEY_DIRECTORY=/var/www/var/data/food_survey

# Security
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
TRUSTED_HOSTS=<state>.unser-schulessen.de
```

#### Stage Environment (Testing)
```bash
# Stage-specific configuration for testing
APP_ENV=dev
APP_STATE_COUNTRY=rp
DATABASE_URL=mysql://user:pass@host:8880/unser_schulessen_stage
MAILER_DSN=null://null  # Disable email in staging
```

### Load Balancing and Reverse Proxy

#### Nginx Configuration
```nginx
# /etc/nginx/sites-available/unser-schulessen
upstream backend_bb {
    server 127.0.0.1:8080;
}

upstream backend_rp {
    server 127.0.0.1:6004;
}

# Brandenburg (Primary)
server {
    listen 80;
    server_name bb.unser-schulessen.de;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name bb.unser-schulessen.de;
    
    ssl_certificate /etc/letsencrypt/live/bb.unser-schulessen.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/bb.unser-schulessen.de/privkey.pem;
    
    location / {
        proxy_pass http://backend_bb;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Static file caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        proxy_pass http://backend_bb;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# Rheinland-Pfalz
server {
    listen 443 ssl http2;
    server_name rp.unser-schulessen.de;
    
    ssl_certificate /etc/letsencrypt/live/rp.unser-schulessen.de/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/rp.unser-schulessen.de/privkey.pem;
    
    location / {
        proxy_pass http://backend_rp;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Database Deployment Strategy

#### Migration Management
```bash
# Automated migration process
1. Create database backup before deployment
2. Run doctrine:migrations:migrate --no-interaction
3. Validate schema integrity
4. Rollback capability if migration fails

# Migration safety checks
- Schema validation before deployment
- Dry-run migration testing
- Rollback scripts for critical changes
- Data integrity verification
```

#### Database Backup Strategy
```bash
# Pre-deployment backup
mysqldump -uunser-schulessen -p$MYSQL_PASSWORD unser_schulessen_<state> > \
  /home/gitlab-runner/unser_schulessen-<state>-$(date +%Y-%m-%d-%H-%M-%S).sql

# Backup retention policy
- Daily backups: 30 days retention
- Weekly backups: 12 weeks retention  
- Monthly backups: 12 months retention
- Yearly backups: 7 years retention

# Backup verification
- Automated backup integrity checks
- Test restore procedures monthly
- Cross-site backup replication
```

### Monitoring and Health Checks

#### Application Health Monitoring
```bash
# Health check endpoints
GET /health/status - Application status
GET /health/database - Database connectivity
GET /health/cache - Cache system status
GET /health/storage - File system status

# Monitoring metrics
- Response time monitoring
- Error rate tracking
- Database query performance
- Memory and CPU usage
- Disk space monitoring
```

#### Log Management
```bash
# Log aggregation
- Application logs: /var/www/var/log/
- Apache access logs: /var/log/apache2/
- Error logs: /var/log/apache2/error.log
- Database logs: /var/log/mysql/

# Log rotation and retention
- Daily log rotation
- 30-day log retention
- Compressed archive storage
- Centralized log analysis
```

### Disaster Recovery

#### Recovery Procedures
```bash
# Container recovery
1. Stop failed container
2. Restore from last known good image
3. Mount persistent data volumes
4. Restore database from backup
5. Verify application functionality
6. Update DNS if necessary

# Data recovery
1. Identify backup point for recovery
2. Stop application containers
3. Restore database from backup
4. Restore file system data
5. Restart containers
6. Verify data integrity
```

#### Backup and Recovery Testing
```bash
# Monthly recovery testing
1. Create test environment
2. Restore from production backup
3. Verify application functionality
4. Test user workflows
5. Document any issues
6. Update recovery procedures
```

### Security Considerations

#### Container Security
```bash
# Security measures
- Non-root user execution
- Read-only file systems where possible
- Resource limits and quotas
- Network isolation
- Regular security updates

# SSL/TLS Configuration
- Let's Encrypt certificates
- Automatic certificate renewal
- Strong cipher suites
- HSTS headers
- Certificate monitoring
```

#### Access Control
```bash
# Server access
- SSH key-based authentication
- VPN access required
- Audit logging enabled
- Regular access reviews

# Application security
- Environment variable encryption
- Database credential rotation
- API key management
- Security header configuration
```

## Security

### Authentication & Authorization
- Symfony Security Bundle
- Role-based access control (RBAC)
- Session-based authentication
- Password hashing with bcrypt
- CSRF protection on forms

### Data Protection
- Input validation and sanitization
- SQL injection prevention via Doctrine ORM
- XSS protection via Twig auto-escaping
- File upload restrictions
- Secure session configuration

### Multi-Tenancy Security
- State-based data isolation
- User-school relationship validation
- Cross-state data access prevention

## Performance Considerations

### Database Optimization
- Doctrine query optimization
- Database indexing strategy
- Connection pooling
- Query result caching

### Frontend Performance
- Webpack asset optimization
- CSS/JS minification and compression
- Image optimization
- Lazy loading for large datasets

### Caching Strategy
- Symfony cache system
- Doctrine result caching
- Template caching via Twig
- Static asset caching

## Maintenance & Operations

### Database Migrations
- Doctrine Migrations for schema changes
- Automated migration execution in CI/CD
- Rollback capabilities
- Data migration scripts

### Monitoring & Logging
- Application logs in `var/log/`
- Error tracking and reporting
- Performance monitoring
- Database query logging

### Backup Strategy
- Automated database backups before deployments
- File storage backups via Docker volumes
- State-specific backup retention
- Disaster recovery procedures

## API Documentation

### Internal APIs
- RESTful endpoints for AJAX operations
- JSON response format
- Authentication required for most endpoints
- CSRF token validation

### Key Endpoints
- User authentication and profile management
- School data management
- Quality check operations
- Survey data collection
- Mini-check public API
- Flag-based filtering and statistics endpoints

---

## Support & Contact

For technical support and development questions, refer to the project's GitLab repository and internal documentation.

**Development Team:** Helliwood media & education
**Project Repository:** GitLab (internal)
**Documentation Version:** 1.0
**Last Updated:** January 2025