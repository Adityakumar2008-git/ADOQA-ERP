# ADOQA ERP — Master Build Contract v2

## 1. Purpose

Build a commercial-grade ERP/POS platform that can safely operate for multiple independent businesses while remaining maintainable by a professional engineering team.

## 2. Source-of-truth hierarchy

1. Security and data-integrity constraints
2. This Master Build Contract
3. Approved ADRs
4. PRD v2
5. Database/domain specifications
6. UX specification
7. Agent implementation preference

Conflicts must be surfaced, not silently resolved.

## 3. Non-negotiable engineering rules

Never:
- trust browser totals, prices, stock, permissions or tenant IDs
- bypass authorization
- disable CSRF
- put business logic in Blade
- hide financial mutation in a GET request
- delete finalized financial history when reversal/cancellation is required
- mutate stock without a stock movement
- mark payment successful from a client-side screenshot/state
- apply the same payment twice
- sell a serialized/IMEI item twice
- create duplicate finalized invoices
- use floating point for money
- commit secrets
- swallow exceptions silently
- use production data as test fixtures
- leave required functionality as TODO/mock/placeholder
- install arbitrary packages without compatibility/security review
- change architecture without an ADR

## 4. Architecture

Use a modular monolith:

- Core
- Identity & Access
- Businesses/Tenancy
- Catalogue
- Inventory
- Purchasing
- Sales
- Payments
- Customers
- Suppliers
- Finance
- EMI
- People/HR
- Platform
- Notifications
- Reporting/Analytics
- Audit

One Laravel application with three authenticated surfaces:
- `/admin`
- `/owner`
- `/app`

Do not create three separate applications.

## 5. Tenancy

Use shared PostgreSQL schema with explicit `business_id` on business-owned records.

Business scope is derived from authenticated membership.

Client-provided tenant IDs are never authoritative.

Where possible, enforce integrity using:
- foreign keys
- unique constraints
- indexes
- scoped model/query patterns
- policies
- service-layer checks

## 6. Financial transaction principle

Any operation that changes more than one of:
- invoice
- payment
- stock
- receivable/payable
- ledger
- EMI
must have a deliberate transaction boundary.

Non-critical notifications may be dispatched after commit.

## 7. Inventory principle

Inventory is event/history-driven.

Support:
- quantity
- batch
- serial
- IMEI
- accessories

Current stock may be cached, but movements remain traceable and reconciliation must be possible.

## 8. Audit principle

Important mutations must record:
- actor
- business
- action
- entity
- entity ID
- timestamp
- reason when applicable
- request/correlation ID when available
- before/after or meaningful change data where appropriate

## 9. Production quality

A feature is not done because it renders.

Done requires:
- requirements implemented
- authorization
- validation
- database integrity
- tests
- negative tests
- browser verification when applicable
- documentation
- no known critical defect

## 10. Performance

Prevent:
- N+1 queries
- unbounded result sets
- unindexed frequent lookups
- synchronous external calls inside critical transactions

Use:
- pagination
- indexes
- eager loading
- aggregates
- queues
- caching only with explicit invalidation strategy

Correctness beats premature optimization.
