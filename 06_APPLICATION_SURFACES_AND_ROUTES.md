# ADOQA ERP — Application Surfaces & Routes v2

## Admin
`/admin/*`

Core:
- dashboard
- businesses
- subscription-plans
- sales-agents
- leads
- message-templates
- campaign-logs
- marketing-assets
- commissions
- payout-requests
- users
- roles
- settings
- system-logs

## Owner
`/owner/*`

Core:
- dashboard
- pos
- invoices
- returns
- quotations
- bookings
- ledger
- expenses
- customers
- suppliers
- purchases
- items
- categories
- brands
- inventory
- emi
- staff
- attendance
- payroll
- leave
- salary-advances
- performance
- reports
- settings
- system-logs

## Staff
`/app/*`

Core:
- home
- sale
- invoices
- customers
- permitted inventory
- profile
- lock

## Route rules

- GET is non-mutating.
- POST/PUT/PATCH/DELETE are used appropriately.
- CSRF protected.
- Authorization at route/controller/policy/action boundary.
- Business context comes from authenticated membership.
- Scoped route model binding where appropriate.
- No arbitrary tenant selector.

## API

Only add when needed for:
- mobile clients
- external callbacks
- integrations

Public API requires versioning and a documented contract.
