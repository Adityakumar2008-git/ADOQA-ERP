# ADOQA ERP — AI Commercial Build Pack v2.0

This package is the implementation contract for building ADOQA ERP from scratch with an agentic coding environment such as Antigravity.

## What changed in v2

Version 2 is not merely a larger feature list. It turns the product requirements into a **build-ready specification**.

Every major domain is defined using:

**Actor → Preconditions → Data → Workflow → State → Business Rules → Validation → Authorization → Failure Cases → Acceptance Criteria → Audit → Notifications → Tests**

The package also aligns architecture, database, security, QA, agent orchestration, and release gates to that PRD.

## Stack

- PHP 8.3+
- Laravel 13
- Laravel Blade
- PostgreSQL
- Firebase only for explicitly approved supporting services
- Tailwind CSS
- Vite

## Product surfaces

1. Master Admin
2. Business Owner ERP
3. Staff/Cashier App

## Core product decisions

- No public business signup.
- Master Admin creates businesses.
- One ordinary business account = one business.
- A business cannot see another business's data.
- Master Admin is platform scope, not a business tenant.
- Owner ERP is a full ERP, not a dressed-up POS.
- Staff/Cashier is optimized for operational speed.
- Build from scratch.
- Do not fork or copy proprietary ERP/POS source code.
- A reference product may inform requirements and UX patterns, never copied implementation/branding.
- PostgreSQL is the transactional source of truth.
- Firebase is supporting infrastructure unless a later approved ADR says otherwise.
- Financial and inventory operations are server-authoritative and transactional.
- No critical feature is complete without automated tests and required browser verification.

## Important engineering truth

No software specification can honestly guarantee zero defects.

This package instead defines a strict evidence-based process:
requirements → design → implementation → automated tests → negative tests → browser verification → security review → reconciliation → release gate.

If an agent cannot prove an assumption, it must stop and ask rather than guess.
