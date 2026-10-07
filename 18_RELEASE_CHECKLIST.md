# ADOQA ERP — Commercial Release Gate v2

## Product
- [ ] all PRD acceptance criteria mapped
- [ ] no unresolved product ambiguity
- [ ] no fake data paths

## Architecture
- [ ] ADRs current
- [ ] no accidental architecture drift
- [ ] dependency review complete

## Database
- [ ] migrations rehearsed
- [ ] constraints reviewed
- [ ] indexes reviewed
- [ ] tenant ownership verified
- [ ] backup created
- [ ] restore successfully tested
- [ ] reconciliation checks available

## Security
- [ ] authentication
- [ ] authorization
- [ ] tenant isolation
- [ ] CSRF
- [ ] rate limits
- [ ] secrets
- [ ] file upload
- [ ] logs
- [ ] dependency audit

## Commerce
- [ ] catalogue
- [ ] quantity inventory
- [ ] batch
- [ ] serial
- [ ] IMEI
- [ ] purchasing
- [ ] supplier ledger
- [ ] POS
- [ ] invoices
- [ ] payments
- [ ] UPI verification boundary
- [ ] returns
- [ ] customers

## Finance
- [ ] ledger
- [ ] expenses
- [ ] receivables
- [ ] payables
- [ ] EMI
- [ ] payroll

## Platform
- [ ] businesses
- [ ] subscriptions
- [ ] partners
- [ ] commissions
- [ ] payouts
- [ ] logs

## UX
- [ ] admin
- [ ] owner
- [ ] staff
- [ ] responsive
- [ ] accessibility
- [ ] loading/error/empty states
- [ ] destructive confirmations

## Testing
- [ ] unit
- [ ] feature
- [ ] negative
- [ ] concurrency-sensitive
- [ ] browser
- [ ] regression
- [ ] reconciliation

## Operations
- [ ] queue workers
- [ ] scheduler
- [ ] monitoring
- [ ] logs
- [ ] backup
- [ ] restore
- [ ] deployment procedure

## Final rule

If a critical gate is unchecked, release is blocked.
