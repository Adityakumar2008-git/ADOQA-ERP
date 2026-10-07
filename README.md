# ADOQA ERP v2.0

Commercial Multi-Business ERP and Commerce Operations Platform.

## Architecture & Technology Stack
- **Backend**: Laravel 13 (PHP 8.3+)
- **Database**: PostgreSQL (Single source of truth)
- **Frontend**: Laravel Blade + Tailwind CSS (Vite bundler)
- **Integrations**: Firebase (strictly supporting: FCM push, optional file storage)

## Application Surfaces
1. **Master Admin (`/admin`)**: Platform control plane, subscription plans, business lifecycle, partner program.
2. **Business Owner ERP (`/owner`)**: Full commercial ERP — POS, inventory, catalog, purchasing, customers, suppliers, accounting ledger, EMI, staff/HR, analytics.
3. **Staff / Cashier App (`/app`)**: High-speed operational workspace optimized for mobile and counter POS.

## Core Directives & Business Rules
- **No Public Signup**: Businesses are provisioned exclusively through Master Admin.
- **Tenant Isolation**: One ordinary account = one business. Strict tenant scoping on every business-owned entity.
- **Authoritative Calculations**: Server-authoritative totals, stock, and permissions. Never trust client-sent totals.
- **Financial & Inventory Integrity**: Finalized records are never silently deleted. Inventory mutations strictly require movement history. Fixed-precision decimal arithmetic.

## Build Pack Documentation
- [00_README.md](file:///d:/my%20projects/Adoca/00_README.md) — Build Pack Overview
- [01_MASTER_BUILD_CONTRACT.md](file:///d:/my%20projects/Adoca/01_MASTER_BUILD_CONTRACT.md) — Master Build Contract
- [02_PRD_BUILD_READY.md](file:///d:/my%20projects/Adoca/02_PRD_BUILD_READY.md) — Product Requirements Document
- [03_ROLES_PERMISSIONS.md](file:///d:/my%20projects/Adoca/03_ROLES_PERMISSIONS.md) — RBAC & Authorization
- [04_DOMAIN_ARCHITECTURE.md](file:///d:/my%20projects/Adoca/04_DOMAIN_ARCHITECTURE.md) — Modular Monolith Domain Architecture
- [05_DATABASE_SPEC.md](file:///d:/my%20projects/Adoca/05_DATABASE_SPEC.md) — PostgreSQL Schema & Constraints
- [06_APPLICATION_SURFACES_AND_ROUTES.md](file:///d:/my%20projects/Adoca/06_APPLICATION_SURFACES_AND_ROUTES.md) — Surfaces & Routing
- [07_WORKFLOWS_AND_STATE_MACHINES.md](file:///d:/my%20projects/Adoca/07_WORKFLOWS_AND_STATE_MACHINES.md) — State Transitions
- [08_UI_UX_SPECIFICATION.md](file:///d:/my%20projects/Adoca/08_UI_UX_SPECIFICATION.md) — UI/UX & Blade Components
- [09_SECURITY_SPECIFICATION.md](file:///d:/my%20projects/Adoca/09_SECURITY_SPECIFICATION.md) — Security Hardening
- [10_FIREBASE_INTEGRATIONS.md](file:///d:/my%20projects/Adoca/10_FIREBASE_INTEGRATIONS.md) — Firebase Boundary
- [11_QA_TEST_PLAN.md](file:///d:/my%20projects/Adoca/11_QA_TEST_PLAN.md) — QA & Test Plan
- [12_AGENT_ORCHESTRATION.md](file:///d:/my%20projects/Adoca/12_AGENT_ORCHESTRATION.md) — Agent Orchestration
- [13_AGENT_RULES.md](file:///d:/my%20projects/Adoca/13_AGENT_RULES.md) — Agent Directives
- [14_DEFINITION_OF_DONE.md](file:///d:/my%20projects/Adoca/14_DEFINITION_OF_DONE.md) — Definition of Done
- [15_EXECUTION_PLAN.md](file:///d:/my%20projects/Adoca/15_EXECUTION_PLAN.md) — 15-Phase Execution Plan
- [16_ADR_REGISTER.md](file:///d:/my%20projects/Adoca/16_ADR_REGISTER.md) — Architecture Decision Records
- [17_MASTER_AGENT_PROMPT.md](file:///d:/my%20projects/Adoca/17_MASTER_AGENT_PROMPT.md) — Master Directive
- [18_RELEASE_CHECKLIST.md](file:///d:/my%20projects/Adoca/18_RELEASE_CHECKLIST.md) — Release Gate

## Development Setup

### 1. Database
Start local PostgreSQL:
```powershell
.\scripts\start-pg.ps1
```

### 2. Environment & Key
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrations & Tests
```bash
php artisan migrate
php artisan test
```

### 4. Frontend Assets
```bash
npm install
npm run build # or npm run dev
```
