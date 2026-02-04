# Database Entity Relationship Diagram - Unser Schulessen

## Complete System ERD

```mermaid
erDiagram
    User {
        int id PK
        string email UK
        string password
        int person_id FK
        int state
        json roles
        int current_school FK
        datetime created_at
        datetime current_login
        datetime last_login
        boolean employee
        boolean temp_password
        string reset_password_hash
        datetime hash_expiration_date
    }
    
    Person {
        int id PK
        string salutation
        string academic_title
        string first_name
        string last_name
        string phone_number
        string email_address
        int school_id FK
        int user_id FK
    }
    
    School {
        int id PK
        string school_number
        string name
        string headmaster
        string phone_number
        string fax_number
        string email_address
        string webpage
        string education_authority
        string school_type
        string school_operator
        string particularity
        int address_id FK
        datetime created_at
        datetime audit_end
        json flags
        boolean mini_check
        string mini_check_name
        string mini_check_email
    }
    
    Address {
        int id PK
        string street
        string postal_code
        string city
        string district
        string state
        string country
    }
    
    UserHasSchool {
        int user_id PK,FK
        int school_id PK,FK
        string person_type FK
        string role
        int state
        datetime created_at
        datetime responded_at
    }
    
    PersonType {
        string name PK
    }
    
    SchoolYear {
        string year PK
        string label
        date period_begin
        date period_end
    }
    
    MasterData {
        int id PK
        int school_id FK
        string school_year FK
        boolean finalised
        int finalised_by FK
        datetime created_at
        datetime finalised_at
    }
    
    MasterDataEntry {
        int id PK
        int master_data_id FK
        string step
        string field
        text value
        datetime created_at
    }
    
    Questionnaire {
        int id PK
        string name UK
        datetime date
        int created_by FK
        int based_on FK
        int state
        string state_country
        boolean mini_check
    }
    
    Category {
        int id PK
        int questionnaire_id FK
        string name
        int order
        boolean mini_check
        string mini_check_info
        json flags
    }
    
    Question {
        int id PK
        int category_id FK
        string question
        boolean sustainable
        boolean mini_check
        string mini_check_info
        int order
        string type
        json flags
    }
    
    Result {
        int id PK
        int school_id FK
        int created_by FK
        datetime created_at
        datetime last_edited_at
        int last_edited_by FK
        int questionnaire_id FK
        string school_year FK
        boolean finalised
        datetime finalised_at
        int finalised_by FK
    }
    
    Answer {
        int id PK
        int question_id FK
        int result_id FK
        string answer
    }
    
    Ideabox {
        int id PK
        int result_id FK
        int question_id FK
        text idea
        int icon_id FK
    }
    
    MiniCheckResult {
        int id PK
        int school_id FK
        datetime created_at
        string session_id
        json evaluation_data
    }
    
    MiniCheckAnswer {
        int id PK
        int mini_check_result_id FK
        int question_id FK
        string answer
    }
    
    Survey {
        int id PK
        int school_id FK
        string name
        text description
        datetime created_at
        int created_by FK
        boolean active
        boolean public
    }
    
    SurveyQuestion {
        int id PK
        int survey_id FK
        int question_id FK
        int order
        boolean required
    }
    
    SurveyQuestionChoice {
        int id PK
        int question_id FK
        string choice_text
        int order
    }
    
    SurveyQuestionAnswer {
        int id PK
        int question_id FK
        text answer_text
        datetime created_at
        string session_id
    }
    
    SurveyQuestionChoiceAnswer {
        int id PK
        int choice_id FK
        int answer_id FK
    }
    
    Media {
        int id PK
        int school_id FK
        text description
        string file_name
        string mime_type
        datetime created_at
        int created_by FK
    }

    %% Relationships
    User ||--o{ UserHasSchool : "has"
    School ||--o{ UserHasSchool : "belongs to"
    User ||--o| Person : "profile"
    School ||--o| Address : "located at"
    School ||--o{ Person : "contacts"
    School ||--o{ MasterData : "has"
    School ||--o{ Result : "assessed"
    School ||--o{ Survey : "conducts"
    School ||--o{ Media : "uploads"
    School ||--o{ MiniCheckResult : "mini-assessed"
    
    UserHasSchool }o--|| PersonType : "categorized as"
    
    SchoolYear ||--o{ MasterData : "applies to"
    MasterData ||--o{ MasterDataEntry : "contains"
    
    User ||--o{ MasterData : "finalizes"
    User ||--o{ Questionnaire : "creates"
    User ||--o{ Result : "creates"
    User ||--o{ Survey : "creates"
    User ||--o{ Media : "uploads"
    
    Questionnaire ||--o{ Category : "contains"
    Questionnaire ||--o| Questionnaire : "based on"
    Category ||--o{ Question : "contains"
    
    Result ||--o{ Answer : "contains"
    Result ||--o{ Ideabox : "has ideas"
    Question ||--o{ Answer : "answered by"
    Question ||--o{ Ideabox : "inspires"
    Question ||--o{ MiniCheckAnswer : "mini-answered"
    
    SchoolYear ||--o{ Result : "assessed in"
    Questionnaire ||--o{ Result : "used for"
    
    MiniCheckResult ||--o{ MiniCheckAnswer : "contains"
    
    Survey ||--o{ SurveyQuestion : "contains"
    SurveyQuestion }o--|| Question : "references"
    Question ||--o{ SurveyQuestionChoice : "has choices"
    SurveyQuestionChoice ||--o{ SurveyQuestionChoiceAnswer : "selected in"
    SurveyQuestion ||--o{ SurveyQuestionAnswer : "answered"
    SurveyQuestionAnswer ||--o{ SurveyQuestionChoiceAnswer : "choice details"
```

## Data Flow Diagrams

### User Registration and School Association Flow

```mermaid
flowchart TD
    A[User Registration] --> B[Create User Entity]
    B --> C[User State: NOT_ACTIVATED]
    C --> D[Send Activation Email]
    D --> E[User Activates Account]
    E --> F[User State: ACTIVE]
    
    F --> G[School Invitation Process]
    G --> H[Create UserHasSchool]
    H --> I[UserHasSchool State: REQUESTED]
    I --> J{School Admin Decision}
    
    J -->|Accept| K[UserHasSchool State: ACCEPTED]
    J -->|Reject| L[UserHasSchool State: REJECTED]
    J -->|Block| M[UserHasSchool State: BLOCKED]
    
    K --> N[User Can Access School]
    L --> O[User Cannot Access School]
    M --> O
    
    N --> P[Set Current School]
    P --> Q[Role-Based Access Control]
```

### Quality Check Assessment Flow

```mermaid
flowchart TD
    A[Admin Creates Questionnaire] --> B[Add Categories]
    B --> C[Add Questions to Categories]
    C --> D[Set Mini-Check Flags]
    D --> E[Activate Questionnaire]
    
    E --> F[School Starts Assessment]
    F --> G[Create Result Entity]
    G --> H[Answer Questions]
    H --> I[Create Answer Entities]
    
    I --> J[Add Ideas to Ideabox]
    J --> K{Assessment Complete?}
    K -->|No| H
    K -->|Yes| L[Finalize Result]
    
    L --> M[Generate Reports]
    M --> N[Historical Comparison]
    
    %% Mini-Check Branch
    E --> O[Public Mini-Check Access]
    O --> P[Create MiniCheckResult]
    P --> Q[Answer Mini-Check Questions]
    Q --> R[Create MiniCheckAnswer]
    R --> S[Generate Mini-Check Report]
    S --> T[Lead Generation]
```

### Master Data Management Flow

```mermaid
flowchart TD
    A[School Year Created] --> B[School Accesses Master Data]
    B --> C{Master Data Exists?}
    
    C -->|No| D[Create New MasterData]
    C -->|Yes| E[Load Existing MasterData]
    
    D --> F[Create MasterDataEntry Records]
    E --> G{Is Finalized?}
    
    G -->|No| H[Allow Editing]
    G -->|Yes| I[Read-Only Mode]
    
    H --> J[Update MasterDataEntry Values]
    J --> K{Ready to Finalize?}
    
    K -->|No| H
    K -->|Yes| L[Finalize MasterData]
    
    L --> M[Set finalised = true]
    M --> N[Set finalised_by and finalised_at]
    N --> O[Prevent Further Changes]
    
    I --> P[View Historical Data]
```

## Database Constraints and Business Rules

### Unique Constraints

```sql
-- User email must be unique across the system
ALTER TABLE user ADD CONSTRAINT uk_user_email UNIQUE (email);

-- Questionnaire names must be unique
ALTER TABLE questionnaire ADD CONSTRAINT uk_questionnaire_name UNIQUE (name);

-- One user-school relationship per pair
ALTER TABLE user_has_school ADD CONSTRAINT uk_user_school 
    UNIQUE (user_id, school_id);

-- One master data set per school per year
ALTER TABLE master_data ADD CONSTRAINT uk_school_year 
    UNIQUE (school_id, school_year);

-- Questions must be unique within categories
ALTER TABLE question ADD CONSTRAINT uk_category_question 
    UNIQUE (category_id, question);

-- One answer per question per result
ALTER TABLE answer ADD CONSTRAINT uk_question_result 
    UNIQUE (question_id, result_id);
```

### Foreign Key Constraints with Cascade Rules

```sql
-- User deletion cascades to UserHasSchool
ALTER TABLE user_has_school 
    ADD CONSTRAINT fk_user_cascade 
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE;

-- School deletion cascades to related data
ALTER TABLE user_has_school 
    ADD CONSTRAINT fk_school_cascade 
    FOREIGN KEY (school_id) REFERENCES school(id) ON DELETE CASCADE;

-- Restrict deletion of referenced entities
ALTER TABLE result 
    ADD CONSTRAINT fk_created_by_restrict 
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE RESTRICT;

-- Set null when referenced entity is deleted
ALTER TABLE school 
    ADD CONSTRAINT fk_address_set_null 
    FOREIGN KEY (address_id) REFERENCES address(id) ON DELETE SET NULL;
```

### Check Constraints for Data Integrity

```sql
-- User state validation
ALTER TABLE user ADD CONSTRAINT chk_user_state 
    CHECK (state IN (0, 1, 2));

-- UserHasSchool state validation
ALTER TABLE user_has_school ADD CONSTRAINT chk_uhs_state 
    CHECK (state IN (0, 1, 2, 3, 4));

-- Answer value validation
ALTER TABLE answer ADD CONSTRAINT chk_answer_value 
    CHECK (answer IN ('true', 'partial', 'false') OR answer IS NULL);

-- Questionnaire state validation
ALTER TABLE questionnaire ADD CONSTRAINT chk_questionnaire_state 
    CHECK (state IN (0, 1, 2));
```

## Indexing Strategy

### Performance Indexes

```sql
-- Composite indexes for common queries
CREATE INDEX idx_user_has_school_state ON user_has_school(user_id, state);
CREATE INDEX idx_result_school_year ON result(school_id, school_year);
CREATE INDEX idx_answer_result_question ON answer(result_id, question_id);
CREATE INDEX idx_master_data_school_year ON master_data(school_id, school_year);

-- Indexes for foreign key lookups
CREATE INDEX idx_person_school ON person(school_id);
CREATE INDEX idx_person_user ON person(user_id);
CREATE INDEX idx_category_questionnaire ON category(questionnaire_id);
CREATE INDEX idx_question_category ON question(category_id);

-- Indexes for filtering and sorting
CREATE INDEX idx_user_created_at ON user(created_at);
CREATE INDEX idx_school_mini_check ON school(mini_check);
CREATE INDEX idx_question_mini_check ON question(mini_check);
CREATE INDEX idx_result_finalised ON result(finalised, created_at);

-- Full-text search indexes
CREATE FULLTEXT INDEX idx_school_name_search ON school(name, headmaster);
CREATE FULLTEXT INDEX idx_question_text_search ON question(question);
```

### Query Optimization Examples

```sql
-- Optimized query for user's schools with accepted status
SELECT s.*, a.city, a.postal_code
FROM school s
JOIN user_has_school uhs ON s.id = uhs.school_id
LEFT JOIN address a ON s.address_id = a.id
WHERE uhs.user_id = ? AND uhs.state = 1
ORDER BY s.name;

-- Optimized query for questionnaire results with pagination
SELECT r.*, s.name as school_name, u.email as created_by_email
FROM result r
JOIN school s ON r.school_id = s.id
JOIN user u ON r.created_by = u.id
WHERE r.questionnaire_id = ? AND r.school_year = ?
ORDER BY r.created_at DESC
LIMIT 20 OFFSET ?;

-- Optimized query for mini-check questions
SELECT q.*, c.name as category_name
FROM question q
JOIN category c ON q.category_id = c.id
JOIN questionnaire qn ON c.questionnaire_id = qn.id
WHERE q.mini_check = 1 AND qn.state = 1 AND qn.state_country = ?
ORDER BY c.order, q.order;
```

## Data Archival and Retention

### Archival Strategy

```sql
-- Archive old results (older than 7 years)
CREATE TABLE result_archive AS SELECT * FROM result WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 YEAR);
CREATE TABLE answer_archive AS SELECT * FROM answer WHERE result_id IN (SELECT id FROM result_archive);

-- Archive inactive users (not logged in for 2 years)
CREATE TABLE user_archive AS SELECT * FROM user WHERE last_login < DATE_SUB(NOW(), INTERVAL 2 YEAR) AND state != 1;

-- Archive completed surveys
CREATE TABLE survey_archive AS SELECT * FROM survey WHERE active = 0 AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### Data Retention Policies

| Entity | Retention Period | Archive Strategy |
|--------|------------------|------------------|
| User Activity Logs | 2 years | Monthly archival |
| Quality Check Results | 7 years | Annual archival |
| Survey Responses | 3 years | Quarterly archival |
| Master Data | Permanent | No archival |
| Media Files | 5 years | Annual cleanup |
| Mini-Check Results | 1 year | Monthly cleanup |

## Database Monitoring and Maintenance

### Performance Monitoring Queries

```sql
-- Monitor slow queries
SELECT query_time, lock_time, rows_sent, rows_examined, sql_text
FROM mysql.slow_log
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY query_time DESC;

-- Monitor table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
    table_rows
FROM information_schema.tables
WHERE table_schema = DATABASE()
ORDER BY (data_length + index_length) DESC;

-- Monitor index usage
SELECT 
    table_name,
    index_name,
    cardinality,
    non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE()
ORDER BY table_name, cardinality DESC;
```

### Maintenance Tasks

```sql
-- Weekly maintenance tasks
OPTIMIZE TABLE user, school, result, answer;
ANALYZE TABLE user, school, result, answer;

-- Monthly cleanup tasks
DELETE FROM mini_check_result WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MONTH);
DELETE FROM survey_question_answer WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH);

-- Quarterly statistics update
UPDATE TABLE user SET statistics_updated = NOW();
UPDATE TABLE school SET statistics_updated = NOW();
```