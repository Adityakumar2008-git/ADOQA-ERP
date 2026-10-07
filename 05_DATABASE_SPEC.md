# ADOQA ERP — PostgreSQL Database Specification v2

## Identifier strategy

Choose ULID or UUID in an ADR and apply consistently.

## Money

Use PostgreSQL NUMERIC with fixed precision.

Never float/double for money.

## Core tables

### Identity/platform
users
roles
permissions
role_permissions
user_roles
businesses
business_members
subscriptions
subscription_plans
platform_settings

### Catalogue
categories
brands
units
items
item_variants
item_barcodes
price_histories

### Inventory
inventory_locations
inventory_balances
stock_batches
stock_serials
stock_imeis
stock_movements
stock_adjustments
inventory_counts

### Purchasing
suppliers
purchase_orders
purchase_invoices
purchase_invoice_lines
supplier_payments
supplier_ledger_entries
purchase_returns
purchase_return_lines

### Sales
customers
quotations
quotation_lines
bookings
invoices
invoice_lines
invoice_status_histories
sale_returns
sale_return_lines

### Payments
payments
payment_allocations
payment_provider_events
payment_reconciliations

### Finance
ledger_accounts
ledger_entries
expenses
expense_categories
receivables
payables

### EMI
emi_accounts
emi_installments
emi_payments
emi_status_histories
emi_notification_events

### People
staff_profiles
attendance_records
leave_requests
salary_advances
payroll_runs
payroll_entries
performance_records

### Platform
sales_agents
leads
message_templates
campaign_logs
marketing_assets
commissions
payout_requests

### System
notifications
notification_deliveries
audit_logs
system_logs
failed_jobs

## Required tenant strategy

Every business-owned table should contain business_id unless there is a documented reason not to.

Business ID is not accepted from the browser as tenant authority.

## Constraints

Database-enforce:
- unique business slug/code
- invoice number per business
- IMEI uniqueness
- serial uniqueness under defined scope
- idempotency keys
- provider references
- valid foreign keys
- non-negative quantities where applicable
- installment sequence uniqueness
- role/permission uniqueness

## Inventory integrity

A stock movement is immutable historical evidence.

Balance/projection tables may be updated transactionally.

Provide reconciliation queries/jobs to compare:
- movement-derived quantity
- current projection
- tracked unit counts

## Financial integrity

Ledger entries and payment allocations must be traceable to source documents.

Use compensating entries for reversals.

## Index strategy

At minimum review indexes for:
- business_id
- business_id + created_at
- business_id + status
- business_id + invoice_number
- business_id + barcode
- customer mobile
- supplier mobile
- EMI due date/status
- stock item/status
- serial/IMEI
- payment provider reference

Do not add indexes blindly; use query patterns and explain plans.

## Migration policy

Never edit deployed historical migrations.

Breaking changes require staged migrations and rollout notes.
