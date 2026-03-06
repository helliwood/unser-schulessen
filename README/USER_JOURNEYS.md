# User Journeys and Data Flows

This document describes who enters which data and where that data flows in the system.

## 1) Standard Data Path

1. **Browser** -> state domain (`<state>.unser-schulessen.de`)
2. **Web server** (Apache in app container) -> Symfony
3. **Backend** (controller/service/form validation + business rules)
4. **Persistence** (Doctrine -> MariaDB state DB)
5. Optional side effects:
- filesystem writes (`/var/www/var/data/...`)
- outbound emails (`MAILER_DSN`)
- PDF generation/streaming

## 2) Main Actors

| Actor | Roles | Typical input data |
|---|---|---|
| System admins | `ROLE_ADMIN` | schools, users, questionnaires, authority entities |
| School users | `ROLE_FOOD_COMMISSIONER`, `ROLE_MENSA_AG`, `ROLE_SCHOOL_AUTHORITIES_ACTIVE`, others | master data, quality check answers, surveys |
| School authority users | `ROLE_SCHOOL_AUTHORITY` | authority profile data, authority survey configuration, school selection/participation |
| Public users | anonymous | mini-check and public survey answers |

## 3) Journeys

## A) Authentication and Access

**Input:** email, password, password reset data, invitation token/password setup.  
**Flow:** Browser -> `/login` / `/reset` / invitation endpoints -> security layer -> user/session updates in DB -> optional email.

## B) School Member Management

**Input:** member email, role/person-type, activation/invitation actions.  
**Flow:** Browser -> member controllers -> `User` + `UserHasSchool` persistence -> invitation email.

## C) Master Data Workflow

**Input:** multi-step school/year master data forms.  
**Flow:** Browser -> `/master_data/edit/{step}` -> form/service validation -> `MasterData` + `MasterDataEntry` DB writes -> optional PDF export stream.

## D) Quality Check Workflow

**Input:** question answers, formula-linked values, finalization action.  
**Flow:** Browser -> `/quality_check/*` -> quality service logic -> `Result`/`Answer` domain persistence -> optional email and PDF export.

## E) Survey Workflows (School)

**Input:** survey metadata, questions/choices, state changes, public responses.  
**Flow:** Browser -> `/survey/*` and `/Umfrage/*` -> survey controllers -> survey tables (`Survey*`) -> result/participant aggregation.

## F) Food Survey Workflows

**Input:** survey setup, spot definitions, public spot answers.  
**Flow:** Browser -> `/food-survey/*` and `/Essensumfrage/*` -> food survey controllers -> food survey entities -> optional image/result exports.

## G) Public Mini-Check

**Input:** school information, mini-check answers, contact details for report delivery.  
**Flow:** Browser -> `/Mini-Check/*` -> mini-check controller/service -> `MiniCheckResult`/`MiniCheckAnswer` + school mini-check fields -> PDF + email.

## H) School Authority Dashboard (Schulträger)

**Input:**
- school authority profile data
- authority survey/template definitions
- selected target schools for participation

**Flow:**
1. Browser -> `/schultraeger/*` (dashboard, profile, schools, surveys)
2. Backend validates authority scope (`ROLE_SCHOOL_AUTHORITY` and ownership)
3. Persistence in:
- `SchoolAuthority`
- `Survey` (authority-owned surveys/templates)
- `SurveySchoolParticipation` (school assignment/participation)
4. School-side/public responses flow into regular survey answer tables
5. Authority dashboard aggregates KPI and participation status across assigned schools

## 4) Data Ownership Matrix

| Input | Entered by | Primary target | Secondary targets |
|---|---|---|---|
| Credentials and password changes | registered users | `user` | mail transport |
| School member administration | admins/school coordinators | `user`, `user_has_school` | mail transport |
| Master data fields | school users | `master_data`, `master_data_entry` | PDF output |
| Quality check answers | school users | `result`, `answer` | mail + PDF |
| Public mini-check answers | public users | `mini_check_result`, `mini_check_answer`, school mini-check fields | PDF + mail |
| Public survey answers | public users | `survey_question_answer` / `survey_question_choice_answer` | stats/participation counters |
| Authority survey assignment | school authority users | `survey_school_participation` | dashboard aggregation |
| Documents/media | authorized users | `media` metadata | filesystem storage |

## 5) Federal-State Boundary

All journeys run in a state-scoped runtime context:
- state-specific env (`APP_STATE_COUNTRY`)
- state-specific DB binding
- state-specific storage binding in deployment
