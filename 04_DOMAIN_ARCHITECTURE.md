# ADOQA ERP — Domain Architecture v2

## Style
Modular monolith.

## Domains

### Identity
Users, sessions, authentication, account lifecycle.

### Access
Roles, permissions, memberships, policies.

### Business
Businesses, settings, onboarding, subscription attachment.

### Catalogue
Items, variants, brands, categories, units, barcodes, pricing.

### Inventory
Stock projections, movements, batches, serials, IMEIs, adjustments, reconciliation.

### Purchasing
Suppliers, purchase documents, receiving, supplier payments, purchase returns.

### Sales
Customers, carts, invoices, quotations, bookings, returns.

### Payments
Payment records, allocations, provider references, reconciliation.

### Finance
Ledger, expenses, receivables, payables.

### EMI
Accounts, installments, payments, state transitions, notifications.

### People
Staff, attendance, leave, salary advances, payroll, performance.

### Platform
Plans, subscriptions, agents, leads, campaigns, commissions, payouts.

### Notifications
In-app notifications and external delivery adapters.

### Analytics
Business and platform aggregates, reports, forecasts.

### Audit
Security, financial and system logs.

## Layers

Presentation:
- controllers
- requests
- Blade
- components

Application:
- actions/use cases
- DTOs
- orchestration

Domain:
- rules
- state transitions
- calculations
- domain services

Infrastructure:
- Eloquent
- queues
- Firebase
- external providers
- storage

## Critical action pattern

Examples:
- CreateBusiness
- CreateSale
- FinalizeInvoice
- RecordPayment
- CancelInvoice
- CreateReturn
- ReceivePurchase
- RecordSupplierPayment
- AdjustStock
- CreateEmiAccount
- ApplyEmiPayment
- FinalizePayroll

Actions own orchestration, not controllers or Blade.

## Transaction rule

All critical mutations use explicit transactions.

External network calls are not placed inside the critical transaction unless there is a deliberate reason.

## State machines

Implement explicit transition guards for:
- invoice
- quotation
- booking
- EMI
- installment
- payroll
- subscription
- payout

Do not scatter state strings across controllers.

## Events

Events are for decoupled side effects, not for hiding mandatory atomic financial changes.
