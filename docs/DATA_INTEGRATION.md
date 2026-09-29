# Vue data integration

## Replace API response keys in one file

Edit:

`resources/js/data/config/apiFieldMap.ts`

Each UI key accepts one or more raw response paths:

```ts
name: ["name", "full_name", "display_name"]
```

Move your API's key to the first position. The Vue views and design components
do not need to change.

## Architecture

1. `lib/platformApi.ts` performs HTTP requests only.
2. `data/repositories/platformRepository.ts` exposes feature operations.
3. `data/mappers/*.ts` maps raw API records into stable UI models.
4. `data/models/platform.ts` defines the keys consumed by Vue.
5. `data/repositories/studentWorkspaceRepository.ts` persists student workspace
   records through the authenticated app-state API.
6. `data/repositories/tutorFeedbackRepository.ts` owns tutor review feedback.
7. Vue views render UI models and hold form interaction state only.

## API-backed sections

| Section | API source | Dummy fallback |
| --- | --- | --- |
| Onboarding | catalog + account | None |
| Student academic setup | catalog + account | None |
| Student plan setup | account | None |
| Dashboard subjects/profile | account subject metadata | None |
| Subjects and exam boards | account + subscriptions | None |
| Pricing | catalog plans + subscriptions | None |
| Profile settings | account | None |
| Notification settings | account notification preferences | None |
| Study preferences | account preferences + notifications | None |
| Learn/exam academic subjects and topics | account + catalog | None |
| Guest demo persistence | catalog + guest demo endpoints | None for persisted state |
| Calendar | app-state key `aistar.calendar.v1` | None |
| Goals | app-state key `aistar.goals.v1` + account subjects | None |
| Notifications page/dropdown | app-state key `aistar.notifications.v1` | None |
| Dashboard side panel | session and achievement stores | Neutral empty states only |
| Tutor question feedback | `/api/v1/tutor-question-feedback` | None |

Empty API values remain empty or show neutral text such as `Not selected` or
`Not enough data`; they are never replaced with invented academic records.

## Student catalog flow

Administrators manage boards, courses, subjects and topics in the catalog CRUD.
Courses store their subject assignments, and topics belong to subjects. The
public catalog endpoint returns those relationships. Student onboarding saves
the selected board/course/subjects/topics to account preferences, and Learn
setup reads those live selections. Vue never imports admin CRUD payloads
directly; `platformMapper.ts` converts them into stable UI keys.

All catalog entities use the same availability rule. `active` entries are
selectable without a badge. `coming_soon` entries are returned publicly but
disabled and labelled “Coming soon”; the legacy API value `enabled` maps to the
same state. Every other status is omitted from the public catalog. Account,
registration, institute-student and guest-demo validation also reject
non-active catalog IDs, so the restriction is enforced beyond the UI.

## Persisted workspace records

Calendar, goals and notifications use `/api/v1/app-state/{key}` as a per-user
JSON document store. To replace app-state with dedicated tables later, keep the
methods and return shapes in `studentWorkspaceRepository.ts` and change only
their HTTP implementation. The pages and components will not need API-key
changes.

The dashboard side panel derives its rows from actual session and achievement
stores. It renders empty states when nothing has been recorded instead of
inventing sessions.

## Tutor feedback

Only authenticated users with the `tutor` role may list, create/update or
delete feedback. One feedback record is upserted per tutor, session and question.
The dedicated tutor review screen receives question/answer/scoring snapshots
without student identity. Non-tutors receive HTTP 403 if they call the endpoint.

Learn and Exam completions are persisted through `/api/v1/learning-sessions`;
active Learn progress is debounced to `/learning-sessions/{uid}/progress`.
Teachers receive daily progress, active/completed sessions and readiness only
for students resolved from active class/course/student/subject grants.

Tutors do not use Learn/Exam or general results as a student reviewer. Their
queue contains only persisted sessions marked as AI-generated and only for
subjects covered by an active grant. The backend repeats both checks when
feedback is read or written, so a hand-crafted request cannot bypass the queue.

## Login activation and content visibility

Parent, educator and institute invitations are activation links, not immediately usable
password accounts. The activation screens validate the token, collect a name
and password, issue a Sanctum token, then route the user to the workspace for
their assigned role. If the invited email already owns an account, activation
requires that account's current password and never resets it.

Student routes require the `student` role, teacher routes require `teacher`, the dedicated AI-review route requires `tutor`, parent
routes require `parent`, and institute routes require an active institute role
for the requested institute context. Parent student data is returned as a
controlled DTO: academic data is available only for active links, while
performance and subscription blocks are included only when their individual
link permissions are enabled.

Parent-student relationships are administered by a system admin or the relevant
institute admin. An administrator may also delegate parent management to a
teacher on an individual class, course, subject or student grant; the backend
resolves that grant to students before allowing links to be listed, created or
revoked. Parents, tutors, system staff and institute staff have no parent-link
management access.

## Remaining isolated demonstration content

Question-bank/demo questions are isolated in `resources/js/lib/questionBank.ts`;
teacher demo CRUD is isolated in `resources/js/lib/teacherStore.ts`; local
assessment/session state is isolated in the remaining `*Store.ts` files. None
of those datasets is embedded in the Vue design templates.

## Verification

```bash
yarn typecheck
yarn check:import-case
yarn check:data-boundary
yarn build
```

The boundary check scans Vue views and components. It fails for raw API-client
imports, mock-data imports, browser `alert`/`confirm`, legacy onboarding-cache
reads or injected default subscription datasets.
## Dependent academic selection

Signup, guest diagnostic setup, onboarding, and Create Learning Path use one catalog rule:

1. Select an active course.
2. Show only exam boards assigned to that course in `course_exam_boards`.
3. Show only subjects assigned to that course in `course_subjects`.
4. Display `coming_soon` records with a badge but do not allow selection.
5. Clear the board and subjects when the course changes.

System Admins manage both the allowed boards and subjects from **Academic catalog → Courses**. The API validates the same relationships through `AcademicSelectionService`; frontend filtering is not the authorization boundary. Guest diagnostic sessions require exactly one allowed subject, while signup and learning-path setup accept one or more allowed subjects.
