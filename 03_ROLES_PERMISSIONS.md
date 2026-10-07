# ADOQA ERP — RBAC & Authorization Specification v2

Authorization is deny-by-default.

## Master Admin
Platform scope:
- businesses.create
- businesses.view
- businesses.update
- businesses.suspend
- subscriptions.manage
- platform.users.view
- platform.roles.manage
- partners.manage
- leads.manage
- campaigns.manage
- commissions.manage
- payouts.manage
- platform.settings.manage
- system_logs.view
- platform.analytics.view

## Owner
Business scope:
- business.view/update
- staff.manage
- catalogue.manage
- inventory.manage
- purchases.manage
- suppliers.manage
- customers.manage
- sales.create
- invoices.manage
- returns.manage
- quotations.manage
- bookings.manage
- finance.manage
- expenses.manage
- emi.manage
- reports.view
- payroll.manage
- attendance.manage
- leave.manage
- advances.manage
- performance.view
- settings.manage
- audit.view

## Manager
Configurable business permissions.

Default:
- operational sales
- invoices
- customers
- inventory
- purchasing
- suppliers
- reports
- staff operational functions

Sensitive finance/payroll/settings actions require explicit grants.

## Staff
Default:
- sale.create
- customer.view/create
- invoice.view
- assigned inventory operations
- own attendance
- permitted operational reports

## Cashier
Default:
- sale.create
- payment.receive
- invoice.view
- invoice.print/share
- customer.view/create
- limited return if granted

## Policy requirements

Every entity access must check:
1. authentication
2. role/permission
3. business ownership
4. record state
5. action-specific restrictions

Never trust route parameters as tenant authority.
