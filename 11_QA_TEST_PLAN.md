# ADOQA ERP — QA & Verification Plan v2

## Unit tests
Required for:
- money
- tax
- discounts
- EMI
- state transitions
- payment allocation
- stock calculations
- permission checks

## Feature tests
Required for:
- business creation
- authentication
- tenant isolation
- catalogue
- inventory
- purchasing
- sales
- invoices
- payments
- returns
- supplier ledger
- customer outstanding
- EMI
- attendance
- payroll
- platform operations

## Browser tests
Critical journeys:
1. admin login
2. create business
3. owner login
4. create item
5. receive stock
6. create sale
7. confirm payment
8. view invoice
9. process return
10. create EMI
11. receive EMI payment
12. staff login
13. attendance
14. payroll
15. permission denial

## Negative tests
- unauthorized
- wrong role
- wrong business
- invalid amount
- negative quantity
- duplicate serial
- duplicate IMEI
- duplicate webhook
- duplicate finalization
- over-return
- over-payment
- invalid state transition
- stale stock
- concurrent sale

## Reconciliation tests
- inventory movement vs balance
- invoice totals vs payments
- customer outstanding
- supplier outstanding
- EMI scheduled vs paid vs outstanding
- payroll totals
- ledger balance

## Release gate
All critical tests pass.
No critical/high security issue.
No critical tenant isolation issue.
No unresolved financial integrity issue.
