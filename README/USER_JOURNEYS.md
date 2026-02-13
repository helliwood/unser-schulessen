# User Journeys and Data Flows

This document describes how users interact with the system, which data they enter, and where that data flows across the stack.

Scope is based on current source code and documentation:
- controllers/forms/entities in `/src`
- architecture and ERD docs in `/README`
- environment and deployment config (`APP_STATE_COUNTRY`, Docker, CI)

## 1) Common Runtime Flow (applies to all journeys)

1. **Browser** sends HTTP(S) request to a state domain (`<state>.unser-schulessen.de`).
2. **Web server** (Apache in app container) forwards request to Symfony.
3. **Backend controller/service/form** validates input and applies business rules.
4. **Doctrine ORM** persists/retrieves data from MariaDB (state-specific DB).
5. Optional side effects:
- file writes to mounted storage (`/var/www/var/data/...`)
- outbound email via configured mail transport (`MAILER_DSN`)
- PDF/image generation for download or attachment.
6. **Response** returned as HTML/JSON/file stream to browser.

## 2) Actors and Typical Input Data

| Actor | Typical roles | Typical data they enter |
|---|---|---|
| Platform admin | `ROLE_ADMIN` | schools, users, questionnaires, administrative settings |
| School coordinators | `ROLE_FOOD_COMMISSIONER`, `ROLE_SCHOOL_AUTHORITIES_ACTIVE`, `ROLE_MENSA_AG` | master data, quality check answers, survey setup, member invitations |
| School participants | `ROLE_HEADMASTER`, `ROLE_KITCHEN`, `ROLE_GUEST` (as assigned) | profile data, participation in internal workflows |
| Public participant | anonymous | survey answers, food survey ratings, mini-check answers + contact details |

## 3) Journey Details

## Journey A: Login and Session Start

**Who enters data**
- Registered user enters `email` + `password` in login form.

**Main entry points**
- `GET/POST /login` (`/src/Controller/SecurityController.php`)

**Data flow**
1. Browser posts credentials.
2. Symfony Security firewall validates credentials against `User` provider.
3. On success, user session is established.
4. Request listeners enforce state/school constraints:
- temp-password redirect to `/change-temp-password`
- current-school/state checks for specific state rules.

**Data sinks**
- Session state (server-side).
- `User` login metadata may be updated by security listeners/subscribers (depending on login flow).

## Journey B: Password Reset

**Who enters data**
- User enters account email to request reset.
- User enters new password via reset token link.

**Main entry points**
- `POST /reset`, `GET/POST /login/{token}` (`/src/Controller/SecurityController.php`)

**Data flow**
1. Browser submits email to `/reset`.
2. Backend locates `User`, generates reset hash + expiration timestamp, stores both.
3. Backend sends reset email with tokenized link.
4. User opens link and submits new password.
5. Backend validates token expiration, hashes password, clears reset token fields.

**Data sinks**
- `user.reset_password_hash`, `user.hash_expiration_date`, `user.password` (DB).
- outbound email via mail transport.

## Journey C: Invite School Member and Activate Access

**Who enters data**
- School coordinator/admin enters member email, role/person-type assignment, invitation option.
- Invited user sets password via invitation link.

**Main entry points**
- `POST /master_data/members/new` and `/master_data/members/edit` (`/src/Controller/MasterData/MemberController.php`)
- `GET/POST /invitation/{token}/{user}/{school}` (`/src/Controller/SecurityController.php`)

**Data flow**
1. Coordinator submits member form.
2. Backend creates/loads `User`, creates `UserHasSchool` assignment for current school.
3. Optional: backend sends invitation email with tokenized URL.
4. Invited user follows link and completes activation/password flow.

**Data sinks**
- `user` and `user_has_school` records (DB).
- invitation email delivery.

## Journey D: Maintain and Finalize Master Data

**Who enters data**
- School-side roles enter multi-step school master data and school details.

**Main entry points**
- `GET/POST /master_data/edit/{step}`
- `GET/POST /master_data/edit-school`
- `POST ... finalise` action
(`/src/Controller/MasterData/IndexController.php`, `/src/Service/MasterDataService.php`)

**Data flow**
1. Browser submits step-based form values.
2. Backend validates by field type and business constraints.
3. Backend writes entries into master-data structures linked to school and school year.
4. Finalization marks dataset as complete and enables downstream quality-check workflows.

**Data sinks**
- `master_data` + `master_data_entry` (+ related `school` fields) in DB.

## Journey E: Execute and Finalize Quality Check

**Who enters data**
- Authorized school role answers questionnaire questions step-by-step.

**Main entry points**
- `GET/POST /quality_check/edit/{step}`
- `GET /quality_check/check/{question_id}/{value}`
- `POST ... finalise`
(`/src/Controller/QualityCheck/IndexController.php`, `/src/Service/QualityCheckService.php`)

**Data flow**
1. Browser opens/edit steps and submits answers.
2. Backend maps input to question/result model, calculates derived values/formulas.
3. Backend stores answer set in unfinalized result.
4. On finalization, backend closes result and optionally sends notification email.
5. User can view/export result (`/quality_check/result/{id}`, `/quality_check/export/{id}`).

**Data sinks**
- `result`, `answer`, `ideabox` (where used), questionnaire-linked entities in DB.
- optional email notification.
- PDF stream output for exports.

## Journey F: Public Mini-Check

**Who enters data**
- Public user enters school data, mini-check answers, then contact name/email for result delivery.

**Main entry points**
- `/Mini-Check/`, `/Mini-Check/step2`, `/Mini-Check/summary`
(`/src/Controller/MiniCheckController.php`)

**Data flow**
1. Step 1: browser submits school metadata; backend creates/updates mini-check school context.
2. Step 2: browser submits answers; backend writes `MiniCheckResult` + `MiniCheckAnswer`.
3. Summary: user submits contact data; backend stores contact fields on school.
4. Backend generates gauge image + PDF summary and sends email with attachment.
5. Session mini-check state is cleared.

**Data sinks**
- `school` (mini-check fields), `mini_check_result`, `mini_check_answer` in DB.
- generated PDF in-memory attachment and outbound email.
- temporary session data during wizard.

## Journey G: Public Survey Participation (Anonymous)

**Who enters data**
- Public user answers survey questions (single/multi/happy-unhappy), optionally voucher code.

**Main entry points**
- `/Umfrage/{uuid}` (`/src/Controller/Survey/PublicController.php`)

**Data flow**
1. Browser loads active public survey by UUID.
2. User submits answers (+ voucher if required by survey type).
3. Backend validates survey state/close date and voucher validity.
4. Backend stores answers per question/choice and increments participant counters.
5. Technical metadata (IP, User-Agent) is stored with answer records.

**Data sinks**
- `survey_question_answer`, `survey_question_choice_answer`, `survey`, `survey_voucher` usage state in DB.

## Journey H: Public Food Survey Participation

**Who enters data**
- Public user submits per-spot ratings on a food survey image/scheme.

**Main entry points**
- `/Essensumfrage/{uuid}`, `/Essensumfrage/save-result/{uuid}`
(`/src/Controller/FoodSurvey/PublicController.php`)

**Data flow**
1. Browser loads survey UI and image/spot definitions.
2. User submits spot-by-spot ratings.
3. Backend creates one `FoodSurveyResult` and multiple `FoodSurveySpotAnswer` entries.
4. Backend stores technical metadata (IP, User-Agent) with result.

**Data sinks**
- `food_survey_result`, `food_survey_spot_answer` in DB.

## Journey I: Upload and Download School Documents

**Who enters data**
- Authorized school role uploads files and descriptions.

**Main entry points**
- `/master_data/media/file/new/{id}`, `/master_data/media/download/{id}`
(`/src/Controller/MasterData/MediaController.php`, `/src/Form/MediaType.php`)

**Data flow**
1. Browser uploads file (PDF/JPG/JPEG/PNG) + description.
2. Backend validates type/size, stores metadata as `Media`.
3. Backend writes binary file to state-mounted storage under school-specific path.
4. Authorized users request downloads; backend streams file with saved mime/name metadata.

**Data sinks**
- `media` metadata in DB.
- file blob on storage path: `/var/www/var/data/documents/<schoolId>/<mediaId>`.

## 4) Data Ownership by Input Type (Quick Matrix)

| Input data | Entered by | Primary persistence target | Secondary targets |
|---|---|---|---|
| Credentials/password changes | registered users | `user` | email transport (reset/invitation) |
| School/member administration | school coordinators/admins | `school`, `user`, `user_has_school`, `person` | email transport (invitation) |
| Master data questionnaire fields | school coordinators | `master_data`, `master_data_entry` | PDF export stream |
| Quality check answers | authorized school users | `result`, `answer` (+ related entities) | email transport, PDF export |
| Public mini-check answers/contact | anonymous public users | `mini_check_result`, `mini_check_answer`, `school` mini-check fields | PDF + email |
| Public survey responses | anonymous public users | survey answer tables + counters | voucher validation state |
| Public food survey responses | anonymous public users | food survey result/answer tables | none |
| Media uploads | authorized school users | `media` | filesystem storage |

## 5) State (Bundesland) Data Boundary

All journeys above execute inside a state-specific runtime context:
- `APP_STATE_COUNTRY` controls state-dependent behavior (translations, templates, rules).
- deployed production jobs bind each state instance to its own DB and storage mount.
- user data and workflow data are therefore isolated by state instance at deployment/runtime level.
