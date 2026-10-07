# ADOQA ERP — Verified Execution Plan v2

Do not build the entire ERP in one pass.

## Phase 0 — Foundation
- Laravel 13 baseline
- PHP environment
- PostgreSQL connection
- Tailwind/Vite baseline
- testing baseline
- agent rules
- CI baseline
- environment example

Gate: app boots, tests execute, no secrets, clean Git.

## Phase 1 — Identity, RBAC, Tenancy
- users
- authentication
- roles
- permissions
- businesses
- memberships
- tenant context
- admin business creation

Gate: cross-tenant and permission attack tests.

## Phase 2 — Design System & Shells
- admin
- owner
- staff
- Blade components
- navigation
- responsive foundation

Gate: browser smoke tests.

## Phase 3 — Catalogue
- categories
- brands
- units
- items
- variants
- barcodes
- price history

## Phase 4 — Inventory
- quantity
- batch
- serial
- IMEI
- movements
- adjustments
- reconciliation

Gate: inventory integrity suite.

## Phase 5 — Customers/Suppliers/Purchasing
- customers
- suppliers
- purchases
- supplier payments
- supplier ledger
- returns

Gate: payable reconciliation.

## Phase 6 — POS/Invoices/Payments
- POS
- invoice
- payments
- UPI integration boundary
- printing/PDF
- returns

Gate: end-to-end sale.

## Phase 7 — Finance
- ledger
- expenses
- receivables
- payables

Gate: financial reconciliation.

## Phase 8 — EMI
- accounts
- schedules
- payments
- overdue
- notifications

Gate: deterministic EMI tests.

## Phase 9 — Staff/HR
- staff
- attendance
- leave
- advances
- payroll
- performance

Gate: payroll integrity.

## Phase 10 — Reports/Analytics
- owner dashboards
- platform dashboards
- reports
- exports
- forecast

## Phase 11 — Master Admin Platform
- plans
- subscriptions
- agents
- leads
- campaigns
- commissions
- payouts
- logs

## Phase 12 — Notifications/Firebase
Implement only approved services.

## Phase 13 — Hardening
- security
- tenant isolation
- performance
- dependency audit
- accessibility
- backup/restore
- browser regression

## Phase 14 — Release Candidate
- staging data
- full test suite
- migration rehearsal
- queue/scheduler verification
- monitoring
- backup verification

## Phase 15 — Production
Only after release checklist passes.
