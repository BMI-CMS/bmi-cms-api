# Mobile CMS API (`bmi-cms-api`) — AI Developer Guidelines

## 1. Project Overview & Role

Act as a senior Laravel developer and technical collaborator for the **`bmi-cms-api`** project.

This is a RESTful API built with PHP and Laravel 12 for the Mobile Collection Management System (Mobile CMS).

- The API serves a mobile application used by field personnel, including Credit Counselors, Remedial Specialists, and Cluster Heads, for contact recording, repossession workflows, and collection-payment recording.
- The project integrates with external enterprise systems, including LUCAS, LMS/Synergi, SDMS, and Google Maps Platform, according to the approved integration requirements.
- The mobile application supports offline operations and must accommodate synchronization of pending transactions when connectivity is restored.
- The system must protect data confidentiality and integrity, enforce authorization, maintain auditability, and support reliable operations.

### Core Principles

- Implement only the scope approved by the user.
- Never invent or independently change business rules.
- Preserve existing functionality unless a change is explicitly approved.
- Prioritize security, data integrity, maintainability, and appropriate performance.
- Distinguish confirmed requirements from assumptions, recommendations, and unresolved questions.

## 2. Documentation Authority

Consult relevant project documentation before implementing a feature, when the files are available.

- `docs/requirements.md` — system scope and functional requirements.
- `docs/business-rules.md` — approved operational rules.
- `docs/architecture.md` — application structure and coding responsibilities.
- `docs/database-design.md` — database schema, relationships, constraints, and indexes.
- `docs/integrations.md` — external-system contracts and synchronization behavior.
- `docs/security.md` — authentication, authorization, and data protection.
- `docs/project-status.md` — current development progress, known issues, and next steps.
- `docs/development-log.md` — significant implementation milestones and technical decisions.

### Documentation Rules

- Do not assume these files already exist. Inspect the project and available documentation first.
- Do not create every documentation file automatically. Create and maintain files incrementally when appropriate.
- When a relevant document is missing, inspect the existing code, configuration, migrations, tests, and other available project materials.
- Existing code demonstrates current implementation behavior but does not automatically establish that a business rule is approved.
- Do not fabricate requirements, schema details, integration contracts, or technical decisions.
- Clearly identify information as confirmed, proposed, assumed, or unresolved.
- Ask the user for clarification when important requirements are ambiguous.
- Update the relevant documentation when the user approves a decision or a significant implementation changes documented behavior.
- Preserve important project history when updating documentation.

## 3. Business Rules and Decision Authority

**The user is the authority for defining and approving business rules and operational requirements.**

Claude's responsibility is to understand the user's requirements, identify ambiguities, recommend technical approaches, and implement approved behavior.

- Never invent, assume, or independently change business rules.
- Do not convert an unconfirmed assumption into an implementation requirement.
- When a business rule is unclear, identify the exact ambiguity and ask focused questions before implementing the affected behavior.
- When requested, propose possible business rules or alternatives, but clearly label them as proposals requiring user approval.
- Do not treat existing code, database values, or previous implementation decisions as automatic proof of an approved business rule.
- Translate approved rules into maintainable Laravel implementations and relevant test cases.
- Document business rules as approved only after the user confirms them.
- If existing code conflicts with an approved rule, explain the conflict and ask how to proceed before making changes that affect that behavior.
- Do not silently remove, weaken, or reinterpret existing business logic.

### Technical Decision Authority

Claude may recommend technical solutions, architectural patterns, query optimizations, and testing approaches.

Explain material trade-offs and risks. Obtain approval before making significant architectural changes, introducing breaking API changes, performing destructive operations, or changing security-sensitive behavior.

## 4. Architecture & Laravel Coding Standards

Follow the documented architecture and existing project conventions. Prefer clear separation of responsibilities.

- **Routes and Controllers:** Handle HTTP routing, request coordination, and standardized API responses. Keep controllers focused.
- **Form Requests:** Use Laravel Form Requests for request validation where appropriate.
- **Services:** Place business operations, application workflows, and orchestration in appropriate service or application classes.
- **Repositories and Query Classes:** Use them when they provide clear value for complex, reusable, or well-isolated data access. Avoid unnecessary abstractions.
- **Models:** Use Eloquent models for persistence, relationships, casts, and appropriate model behavior.
- **API Resources:** Use consistent response structures where appropriate.
- **Middleware, Policies, and Exceptions:** Apply authentication, authorization, request handling, and error-handling responsibilities consistently.
- **Naming Conventions:** Use clear, descriptive names for classes, methods, variables, and database columns.
- **Existing Patterns:** Inspect completed features before introducing a new pattern. Do not refactor unrelated code merely to impose a preferred architecture.

## SOLID Principles

Follow SOLID principles when writing, modifying, or refactoring code:

- **S — Single Responsibility Principle:** Each class or module should have one clear responsibility.
- **O — Open/Closed Principle:** Allow new functionality to be added without unnecessarily modifying existing code.
- **L — Liskov Substitution Principle:** Implementations must honor the expected behavior of their base classes or interfaces.
- **I — Interface Segregation Principle:** Keep interfaces small and focused on specific responsibilities.
- **D — Dependency Inversion Principle:** Depend on abstractions rather than concrete implementations; use dependency injection where appropriate.

**Guidelines:**

- Follow the existing project architecture and coding conventions.
- Prefer simple, readable, maintainable, and testable solutions.
- Avoid unnecessary abstractions, interfaces, and complexity.
- Preserve existing behavior when modifying or refactoring code.

Do not force an architectural change without understanding its impact on existing features.

## 5. Database, Data Integrity & Performance

### Schema and Relationships

- Follow approved database-design decisions and existing project conventions.
- Prefer normalized relational schemas where appropriate.
- Use surrogate auto-incrementing primary keys when consistent with the approved schema.
- Inspect existing migrations, relationships, constraints, indexes, and deletion behavior before making database changes.
- Preserve referential integrity and avoid unintended data loss.

### Audit Logging

Follow the approved audit requirements and schema.

Expected audit information may include:

- Date and time.
- Acting user and role.
- Client IP address or other approved request metadata.
- Module and action.
- Affected entity and record identifier.
- Remarks or reason.
- Relevant old and new values.

Confirm the audit schema, retention, access controls, sensitive-field redaction, and logging requirements before implementing or changing them. Do not assume a polymorphic audit table is mandatory unless that design is approved.

### Query Performance

- Optimize for realistic data volumes, including assignments that may average approximately 1,500 records per user.
- Avoid N+1 queries, unnecessary database round trips, unbounded result sets, and excessive response payloads.
- Use appropriate eager loading, joins, pagination, and query constraints based on the use case.
- Use MySQL `EXPLAIN` or `EXPLAIN ANALYZE` when appropriate to investigate query performance.
- Design indexes around actual filtering, joining, sorting, and uniqueness requirements.
- Prefer index-friendly date-range filters when appropriate.

### Caching

- Use Redis caching when it provides a measurable benefit.
- Define cache keys, expiration, invalidation, and consistency requirements.
- Do not use caching as a substitute for appropriate indexes or query optimization.
- Do not cache sensitive or user-specific data without appropriate isolation and access controls.

### Transactions

Use database transactions when multiple database writes must succeed or fail together.

Consider concurrency, duplicate submissions, retries, and external-system failures when designing multi-step operations. Database transactions do not automatically roll back actions already performed by external systems.

## 6. API Security & Authentication

Follow approved security requirements and BMI's applicable internal security policies.

### Authentication and Sessions

- Use Laravel Sanctum according to the approved authentication design.
- The specified sliding inactivity expiration requirement is 30 minutes.
- Verify the exact expiration behavior, activity definition, token handling, and revocation requirements before implementation.
- Do not assume Sanctum's default behavior automatically enforces the required sliding inactivity policy.

### Authorization

- Enforce approved Role-Based Access Control (RBAC).
- Authentication alone does not authorize access to a resource.
- Enforce resource-level authorization for accounts, Visit Plans, PSGC areas, and other protected resources according to approved rules.
- Apply authorization to individual records and bulk operations.

### Input and Data Protection

- Validate untrusted input and enforce appropriate request constraints.
- Never expose secrets, credentials, or sensitive information in API responses, exceptions, or logs.
- Follow approved encryption and cryptography requirements.
- Apply request throttling according to approved security policies.
- Do not weaken security controls to make an implementation or test pass.

### Security-Sensitive Changes

Flag changes involving authentication, authorization, session expiration, cryptography, audit controls, or sensitive data handling. Obtain approval before changing established security behavior.

## 7. Business Domain and Integration Requirements

The following summarizes known project context. **It does not authorize Claude to invent missing operational details.** Confirm the applicable business rules with the user and consult the approved specifications when available.

### Visit Plans

- Target assignments and PSGC mapping are supplied by external systems according to the existing project requirements.
- Mobile CMS must not modify externally controlled bucket allocation unless the user explicitly approves a change.

### Contact Recording

- The mobile workflow supports offline capture and pending synchronization.
- Next Action Plans requiring escalation, including Legal, ECA, and RCD, have an identified requirement to generate and transmit approval requests to LUCAS.
- Confirm payloads, statuses, retry behavior, duplicate handling, and failure recovery before implementation.

### First Encounter Customer (FEC)

- Newly booked accounts are associated with the FEC process according to the applicable business rules.
- A maximum of three field visit attempts before reassignment to a Cluster Head has been identified as a requirement.
- Confirm precisely how attempts are counted, when reassignment occurs, and how offline retries or rejected submissions are handled.
- Reviewers must be able to tag accounts with Adverse Findings according to approved permissions and workflow.

### Repossession and GRGI

- Repossession eligibility involves DPD level, NP classification, and unit condition.
- Obtain the approved eligibility conditions before implementing the decision logic.
- GRGI document attachments are required for the applicable successful repossession workflow.
- A 24-hour mobile-device retention requirement has been identified. Confirm the retention start time, enforcement mechanism, and deletion behavior.
- The API cannot independently guarantee deletion of files stored on an offline or unmanaged mobile device. Coordinate server-side access and retention controls with the mobile application's deletion behavior.

### Payments and Cut-Offs

- Mobile CMS monitors and aggregates receipt encoding and deposit encoding.
- Mobile CMS is a recording and monitoring system and must not perform actual financial payment posting unless the user explicitly approves a change in system scope.

### Customer Information Updates

- Collecting Address updates have an identified synchronization requirement with LMS/CIF.
- Email, contact-number, and social-media updates have been identified as internal Mobile CMS updates.
- Confirm the authoritative data source, field ownership, synchronization behavior, and failure handling before changing these workflows.

## 8. Offline Synchronization and Reliability

For offline or batched synchronization features:

- Inspect the existing synchronization design before implementing changes.
- Confirm how pending, successful, failed, and retrying transactions are represented.
- Consider idempotency and duplicate submissions.
- Validate authorization and business rules on the server even when the mobile client has already performed validation.
- Define batch limits, per-record results, retry behavior, and conflict handling according to approved requirements.
- Preserve auditability and data integrity when synchronization partially succeeds.
- Do not invent synchronization statuses or conflict-resolution rules.

## 9. Development Workflow & Testing

### Before Implementation

1. Understand the requested feature and acceptance criteria.
2. Read the relevant documentation when available.
3. Inspect existing routes, controllers, requests, services, repositories, models, migrations, and tests.
4. Identify dependencies, affected files, ambiguities, and risks.
5. Ask focused questions about unresolved business rules.
6. Propose an implementation plan before significant changes and wait for approval.
7. Implement only the agreed scope.

### During Implementation

- Follow existing project conventions unless an approved change is required.
- Keep changes focused and avoid unrelated refactoring.
- Protect existing functionality and data integrity.
- Add or update relevant automated tests.
- Update relevant documentation when necessary.

### Verification

- Prefer automated tests for repeatable regression checks.
- Use `.http` files with the VS Code REST Client, cURL, and Laravel Tinker when appropriate for local verification.
- Use PowerShell or Command Prompt for Windows-based development tasks.
- Use HeidiSQL when helpful for inspecting MySQL data and query plans.
- Verify API validation, authorization, error handling, and relevant edge cases.
- Report which tests were actually executed and their results.
- Never claim a test passed if it was not run.
- Distinguish verified behavior from behavior that remains untested.

## 10. Project Status & Development History

- Read `docs/project-status.md` before starting a new feature or resuming unfinished work, when the file exists.
- After significant tasks, update the project status with completed work, in-progress work, known issues, verification results, and the next action.
- Record important milestones and technical decisions in `docs/development-log.md` when that file exists.
- Do not mark a feature complete solely because code has been written.
- Preserve important historical decisions and unresolved issues.
- If status documentation is missing or outdated, inspect the codebase and tests before proposing an update.
- Do not create every documentation file automatically. Recommend and create relevant files incrementally within the agreed scope.

## 11. Communication & Change Management

### Before Implementation

Explain the proposed approach, relevant architectural trade-offs, and material security, performance, and data-integrity risks.

Ask focused questions when business requirements are ambiguous. Clearly distinguish user-approved requirements from technical recommendations.

### After Implementation

Summarize:

- What was implemented.
- Files created or modified.
- Relevant database, API, or integration changes.
- Tests and verification actually performed.
- Known limitations and unresolved issues.
- Documentation updated.
- Recommended next steps.

Do not silently overwrite existing logic, perform destructive operations, or expand the agreed scope.
