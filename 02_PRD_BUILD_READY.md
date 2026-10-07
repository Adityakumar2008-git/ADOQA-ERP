# ADOQA ERP — Product Requirements Document v2.0
## Build-Ready Commercial Product Specification

**Status:** Product baseline for implementation  
**Audience:** Product owner, architect, Laravel engineers, QA, security reviewers, Antigravity agents

---

# 1. PRODUCT DEFINITION

## 1.1 Product

ADOQA ERP is a multi-business ERP and commerce operations platform.

It combines:
- POS
- invoicing
- inventory
- purchasing
- supplier/customer ledgers
- payments
- EMI
- finance
- staff/HR
- reporting
- business analytics
- platform administration

## 1.2 Product surfaces

### Master Admin
Platform control plane.

### Owner ERP
Business management and ERP control plane.

### Staff/Cashier
Fast operational workspace.

These are different UX surfaces over the same application/domain model.

## 1.3 Business model

ADOQA creates and manages business accounts.

There is no public self-service business registration.

A normal business account belongs to exactly one business.

---

# 2. USERS AND ACTORS

## 2.1 Master Admin

Platform actor.

Can create/suspend/manage businesses and platform operations.

Does not inherit every business permission automatically.

## 2.2 Business Owner

Primary administrator of exactly one business.

## 2.3 Manager

Operational manager within one business.

Permissions are explicit.

## 2.4 Staff

Operational employee within one business.

## 2.5 Cashier

POS/payment-focused employee.

---

# 3. GLOBAL BUSINESS RULES

BR-GLOBAL-001  
Every business-owned record belongs to exactly one business.

BR-GLOBAL-002  
A user cannot access another business's records even if they alter an ID in the request.

BR-GLOBAL-003  
Server calculations are authoritative.

BR-GLOBAL-004  
Finalized financial documents are not silently editable.

BR-GLOBAL-005  
Reversals/cancellations preserve historical facts.

BR-GLOBAL-006  
Money uses fixed precision decimal arithmetic.

BR-GLOBAL-007  
All important mutations are auditable.

BR-GLOBAL-008  
All timestamps are stored consistently and displayed in the business/user timezone.

BR-GLOBAL-009  
No public business signup.

BR-GLOBAL-010  
No feature may depend on client-side authorization.

---

# 4. AUTHENTICATION & ACCOUNT LIFECYCLE

## Actors
All users.

## Preconditions
User account exists and is active.

## Required behavior
- login
- logout
- session regeneration
- account status enforcement
- password reset/recovery where enabled
- session expiration policy
- optional app lock/PIN for staff workflow

## Rules
- inactive/suspended account cannot authenticate normally
- login errors do not reveal whether an account exists
- sensitive operations may require re-authentication where appropriate
- lock/PIN is an operational control, not a replacement for server authorization

## Acceptance criteria
Given a suspended account, login is rejected.
Given a valid account, successful login creates a secure session.
Given a logout, the authenticated session cannot continue using protected routes.

---

# 5. MASTER ADMIN — BUSINESS CREATION

## Actor
Master Admin.

## Required fields
- legal/display business name
- business code/slug
- owner name
- owner contact
- business type
- address
- city/state/country
- timezone
- currency
- tax configuration
- status
- subscription plan if applicable

## Workflow
Draft → Validate → Create Business → Create Owner → Initialize Defaults → Audit → Onboarding

## Rules
- business code/slug unique
- owner membership created atomically
- default settings initialized
- default financial/categories configuration created only through deterministic setup
- no partial business creation after a failed transaction

## Acceptance criteria
When admin creates a valid business, the business, owner membership and required defaults exist together.
If any critical step fails, no half-created tenant remains.

---

# 6. BUSINESS SETTINGS

Settings include:
- business identity
- contact information
- address
- invoice numbering
- tax configuration
- currency
- timezone
- payment modes
- UPI configuration
- notification preferences
- receipt/invoice preferences
- operational preferences

Changes must:
- be authorized
- be validated
- be audited

---

# 7. ROLE & PERMISSION SYSTEM

## Permission model

Deny by default.

Permission examples:
- business.view
- business.update
- staff.view
- staff.manage
- attendance.view
- attendance.manage
- payroll.view
- payroll.manage
- catalogue.view
- catalogue.manage
- inventory.view
- inventory.adjust
- purchase.create
- purchase.manage
- supplier.view
- supplier.manage
- supplier.payment
- customer.view
- customer.manage
- sale.create
- sale.cancel
- invoice.view
- invoice.cancel
- return.create
- quotation.manage
- booking.manage
- finance.view
- finance.manage
- expense.create
- emi.view
- emi.manage
- emi.payment
- report.view
- settings.manage
- audit.view

Permission checks occur server-side.

---

# 8. CATALOGUE

## Entities
- Category
- Brand
- Item
- Variant
- Barcode
- Unit
- Price history

## Item fields
At minimum:
- name
- SKU/internal code
- barcode(s)
- category
- brand
- unit
- item type
- sale price
- cost price
- MRP where applicable
- tax configuration
- minimum stock
- active/inactive
- inventory tracking mode

## Inventory tracking modes
- quantity
- batch
- serial
- IMEI
- accessory

## Rules
- inactive item cannot be newly sold
- prices are resolved server-side
- historical invoice lines retain historical prices

---

# 9. INVENTORY

## 9.1 Quantity inventory

Fields:
- item
- quantity
- location
- cost
- stock threshold

Operations:
- stock in
- stock out
- adjustment
- sale consumption
- sale return
- purchase receipt
- purchase return

## 9.2 Batch inventory

Batch fields:
- item
- batch number
- received quantity
- remaining quantity
- cost
- manufacturing date where applicable
- expiry date where applicable
- supplier/reference

Rules:
- batch number required when item tracking mode requires it
- expired stock behavior is configurable but cannot be silently sold if business policy blocks it

## 9.3 Serial inventory

Fields:
- item
- serial number
- status
- source
- cost
- sale reference

Rules:
- serial unique according to business policy
- one serialized unit cannot be sold twice

## 9.4 IMEI inventory

Fields:
- item
- IMEI
- secondary IMEI if applicable
- serial
- status
- source
- cost
- sale reference

Rules:
- IMEI uniqueness enforced
- sold IMEI cannot be sold again

## 9.5 Stock movement

Every mutation creates a movement containing:
- business
- item
- tracking identifier
- movement type
- quantity
- source
- actor
- timestamp
- cost where relevant

## Acceptance criteria

If stock is 10 and a sale consumes 3:
- invoice line quantity = 3
- stock movement = -3
- resulting available stock = 7
- audit exists

If a serialized unit is already sold:
- second sale is rejected
- no financial or stock mutation occurs

---

# 10. SUPPLIERS

## Supplier
- name
- phone
- email
- address
- tax details where needed
- notes
- status

## Supplier ledger
Tracks:
- purchase credit
- payment
- purchase return
- adjustment where authorized

## Rule
Supplier outstanding is derived from authoritative transactions.

Manual balance overwrite is prohibited.

---

# 11. PURCHASING

## Purchase workflow

Draft → Received → Partially Paid/Paid/Credit → Closed/Returned as applicable

Purchase document:
- supplier
- date
- reference
- lines
- quantities
- cost
- tax
- discount
- total
- payment
- outstanding

Receiving stock must create inventory movements.

For tracked items, batch/serial/IMEI data is captured during receipt.

---

# 12. CUSTOMERS

## Customer fields
- name
- mobile
- email
- address
- type
- notes
- communication preferences where needed

## Customer financial history
- invoices
- payments
- outstanding
- EMI
- returns
- credit transactions

## Analytics
- total spend
- purchase count
- average bill
- last purchase
- frequency
- category behavior where sufficient data exists

---

# 13. POS / SALE

## Actor
Owner/Manager/Staff/Cashier with sale.create.

## Flow

1. create cart
2. search/scan items
3. select customer optionally
4. resolve authoritative item data
5. select batch/serial/IMEI where required
6. enter quantity
7. apply allowed discount
8. calculate tax
9. calculate round-off
10. calculate final payable
11. choose payment
12. confirm
13. server revalidates everything
14. transaction commits invoice/payment/stock/ledger effects
15. receipt becomes available
16. non-critical notifications run after commit

## Server rules

Client may propose:
- item IDs
- quantity
- discount request
- payment mode

Server decides:
- item validity
- price
- stock
- tax
- allowed discount
- totals
- payment validity
- permission

## Acceptance criteria

A successful sale produces:
- invoice
- invoice lines
- payment allocation(s)
- stock movement(s)
- inventory update
- required receivable/ledger entries
- audit record

All critical records belong to the same business.

---

# 14. PAYMENTS

## Payment modes
At minimum:
- cash
- UPI
- card
- bank transfer
- credit/udhar
- EMI
- split payment if enabled

## Payment record
- business
- source document
- amount
- mode
- provider/reference
- status
- received by
- timestamp

## Rules
- duplicate provider references rejected/idempotently handled
- client-side "success" is not authoritative
- payment callbacks/webhooks are verified
- overpayment behavior must be explicitly configured

---

# 15. DYNAMIC UPI

Dynamic UPI is a payment request.

It is not proof of settlement.

Required:
- invoice amount
- merchant identity
- transaction/reference
- expiry where applicable
- callback/reconciliation boundary

Payment becomes confirmed only through the approved verification mechanism.

---

# 16. INVOICES

## Invoice fields
- invoice number
- business
- date/time
- customer
- lines
- discounts
- taxes
- round-off
- total
- payment status
- invoice status
- created by

## Status
Suggested:
- draft
- finalized
- partially_paid
- paid
- cancelled

## Rules
- invoice number unique per business
- finalized invoice history preserved
- cancellation requires permission
- cancellation requires reason
- cancellation reverses relevant effects
- invoice lines retain historical commercial facts

---

# 17. SALE RETURNS

## Preconditions
Original sale exists and returnable quantity remains.

## Flow
Select invoice → select lines/quantities → validate → calculate return → reverse stock → financial reversal/refund/credit → audit

## Rules
Cannot return more than sold/returnable quantity.

Serialized/IMEI return must identify the exact unit.

---

# 18. QUOTATIONS

## States
- draft
- sent
- accepted
- rejected
- expired
- converted

Conversion to sale must create a new authoritative sale/invoice transaction.

---

# 19. BOOKINGS

Fields:
- customer
- service/item
- scheduled date/time
- status
- notes
- deposit/payment where applicable

States:
- pending
- confirmed
- completed
- cancelled
- no_show

---

# 20. FINANCE LEDGER

## Principle

Financial state is event/entry driven.

Ledger entry:
- business
- account
- debit
- credit
- source
- source ID
- timestamp
- description

## Rules
- fixed precision
- balanced entries where double-entry is used
- no silent historical overwrite
- reversals use compensating entries

---

# 21. EXPENSES

Fields:
- category
- amount
- date
- payee/vendor
- payment mode
- note
- attachment where enabled
- status/approval where required

Creation requires permission.

Expense creates the appropriate financial effect.

---

# 22. RECEIVABLES & PAYABLES

Receivables:
- customer credit
- outstanding invoices
- EMI balances

Payables:
- supplier credit
- approved unpaid expenses

Balances must reconcile against source transactions.

---

# 23. EMI — DETERMINISTIC SPECIFICATION

## EMI account fields
- business
- customer
- source invoice
- principal
- down payment
- financed amount
- interest model
- interest rate
- processing fee
- tenure
- installment count
- installment frequency
- first due date
- installment amount
- status

## Supported interest models

The first production release must explicitly support only models implemented and tested.

Recommended initial model:
**0% / flat installment model**

If interest-bearing EMI is required, define a separate approved ADR with exact mathematical formula before implementation.

## Example: 0% EMI

Invoice = ₹60,000  
Down payment = ₹10,000  
Financed amount = ₹50,000  
Tenure = 10 installments

Installment principal = ₹5,000 each.

Any rounding remainder must be assigned deterministically, normally to the final installment.

## EMI states
- draft
- active
- partially_paid
- overdue
- completed
- cancelled
- written_off

## Installment states
- scheduled
- due
- partially_paid
- paid
- overdue
- waived/cancelled where explicitly authorized

## Payment allocation

Default:
- oldest due installment first
- then current installment
- then future installments only if advance payment is explicitly supported

Partial payment is allowed only if configured.

## Rules

- EMI cannot exceed eligible invoice amount
- down payment cannot exceed invoice payable
- duplicate payment must be prevented
- completed account cannot accept ordinary payments
- cancellation requires permission/reason
- write-off requires elevated permission and audit

## Notifications

- account created
- upcoming due
- due today
- overdue
- payment received
- completed

## Acceptance criteria

Given principal ₹50,000 and 10 installments:
- total scheduled principal = ₹50,000
- installment rounding is deterministic
- payment allocation never exceeds outstanding
- final completion occurs exactly when outstanding reaches zero

---

# 24. STAFF

Fields:
- user
- business
- role
- employment status
- joining date
- salary configuration
- PIN/operational security where enabled

Staff cannot access another business.

---

# 25. ATTENDANCE

Operations:
- check-in
- check-out
- correction request where authorized
- history

Rules:
- duplicate active check-in prevented
- correction audited
- staff sees only permitted scope

---

# 26. LEAVE

Workflow:
Requested → Approved/Rejected → Cancelled where applicable

Fields:
- staff
- leave type
- start
- end
- reason
- approver
- decision
- timestamps

---

# 27. SALARY ADVANCES

Workflow:
Requested → Approved → Disbursed → Repayment/Deduction → Settled

Rules:
- approval required
- outstanding advance tracked
- payroll deduction cannot exceed balance
- all financial effects audited

---

# 28. PAYROLL

## Payroll period
- business
- start date
- end date
- staff
- base salary
- adjustments
- approved advances
- deductions
- net payable
- payment status

## Workflow
Draft → Review → Finalized → Paid/Partially Paid

Finalization must be idempotent and protected against duplicate runs.

---

# 29. STAFF PERFORMANCE

Metrics must be role-appropriate.

Possible metrics:
- sales
- invoices
- repeat customers
- attendance
- task completion

Performance is reporting, not an authorization mechanism.

---

# 30. REPORTS

Required report categories:
- sales
- profit
- invoices
- payments
- expenses
- inventory
- purchases
- customers
- suppliers
- EMI
- staff
- payroll

Every report defines:
- date semantics
- timezone
- filters
- scope
- actual vs estimated

Large reports are paginated/queued where appropriate.

---

# 31. DASHBOARDS & ANALYTICS

Owner dashboard:
- today's sales
- monthly revenue
- monthly expenses
- pending payments
- staff present
- invoice count
- profit today/week/month/year
- direct profit
- revenue trend
- category mix
- bill size distribution
- top items
- inventory valuation
- low stock
- WoW
- MoM
- peak-day heatmap
- margin analysis
- customer behavior
- payment mode mix

Master Admin:
- total platform revenue
- net platform profit
- platform users
- sales partners
- active businesses
- pending payouts
- revenue/profit trend
- profit distribution
- partner commissions

Forecasts must be clearly labeled as estimates.

---

# 32. MASTER ADMIN PLATFORM

Modules:
- System Overview
- Subscription Plans
- Businesses/Tenants
- Sales Agents
- Leads
- Message Templates
- Campaign Logs
- Marketing Assets
- Commissions
- Payout Requests
- All Users
- Roles & Permissions
- Settings
- System Logs

Business suspension must:
- be authorized
- be audited
- prevent normal business operations according to defined policy
- preserve data

---

# 33. SUBSCRIPTIONS

Plan fields:
- name
- price
- billing period
- feature limits
- status

Subscription states:
- trial where supported
- active
- past_due
- suspended
- cancelled
- expired

Feature limits must be enforced server-side.

---

# 34. SALES PARTNER PROGRAM

Entities:
- agent
- lead
- campaign
- commission
- payout

Commission must reference authoritative business/subscription events.

Payout:
Requested → Reviewed → Approved/Rejected → Paid

Financial payout state must be auditable.

---

# 35. NOTIFICATIONS

Notification categories:
- security
- invoice
- payment
- EMI
- stock
- staff
- payroll
- subscription
- platform

Channels may include:
- in-app
- push
- email
- WhatsApp/SMS if integrated

External notification failure must not corrupt the core transaction.

---

# 36. SEARCH

Search must:
- be scoped to the current business
- use allowlisted searchable fields
- paginate results
- avoid leaking hidden records
- use indexed search where practical

---

# 37. IMPORT / EXPORT

Imports must:
- validate row structure
- validate types
- validate business ownership
- reject/flag invalid rows
- produce an import report
- avoid partial silent corruption

Exports must:
- respect authorization
- respect tenant scope
- avoid spreadsheet formula injection
- paginate/queue large exports

---

# 38. OFFLINE / SYNC

Offline capability is allowed only for workflows that can be made safe.

Never allow offline mode to invent authoritative payment settlement.

Any offline mutation must have:
- local identity
- operation ID
- timestamp
- retry state
- conflict strategy

Sync must be idempotent.

If a conflict cannot be safely resolved, surface it to the user.

---

# 39. SECURITY UX

App lock/PIN:
- does not replace authentication
- must not expose business data after logout
- must have secure retry/lock behavior

Destructive actions require:
- clear warning
- reason where appropriate
- confirmation
- authorization

---

# 40. LOCALIZATION

Initial:
- English
- Hindi

Currency and tax labels configurable.

User-facing strings should be localization-ready.

---

# 41. ERROR HANDLING

Errors must have:
- user-safe message
- technical log
- correlation/request ID where appropriate
- no secret leakage

Financial failures must result in no partial critical mutation.

---

# 42. AUDIT REQUIREMENTS

Audit:
- login/security events
- business creation/suspension
- role changes
- permission changes
- invoices
- payments
- cancellations
- returns
- stock adjustments
- purchases
- supplier payments
- EMI
- payroll
- settings
- integrations

---

# 43. ACCEPTANCE STANDARD

A requirement is accepted only when:
1. implementation exists
2. server-side rules exist
3. authorization exists
4. data integrity is enforced
5. tests pass
6. negative cases pass
7. browser verification passes where applicable
8. audit behavior is verified
9. documentation is updated

---

# 44. OUT OF SCOPE UNLESS EXPLICITLY APPROVED

Do not silently add:
- multi-database tenancy
- microservices
- cryptocurrency payments
- autonomous accounting decisions
- AI-generated financial advice
- arbitrary external integrations
- public APIs
- advanced tax/legal claims

Each requires an explicit product/architecture decision.
