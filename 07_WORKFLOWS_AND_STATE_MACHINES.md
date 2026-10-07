# ADOQA ERP — Workflow & State Machine Specification

## Business creation
Draft → Validated → Created → Initialized → Active

Failure: rollback critical creation.

## Invoice
Draft → Finalized → Paid/Partially Paid → Cancelled

Finalized cannot be silently edited.

## Payment
Pending → Confirmed → Reconciled
or
Pending → Failed/Expired

External provider callbacks must be idempotent.

## Quotation
Draft → Sent → Accepted/Rejected/Expired → Converted

## Booking
Pending → Confirmed → Completed
Pending/Confirmed → Cancelled
Confirmed → No-show

## Purchase
Draft → Received → Partially Paid/Paid → Closed
Can enter return workflow.

## Return
Requested → Approved → Processed → Refunded/Credited

## EMI account
Draft → Active → Partially Paid → Completed
Active → Overdue
Overdue → Partially Paid/Completed
Active/Overdue → Cancelled only with authorized reason
Active/Overdue → Written Off only with elevated permission

## EMI installment
Scheduled → Due → Partially Paid → Paid
Due → Overdue
Overdue → Partially Paid/Paid

## Payroll
Draft → Reviewed → Finalized → Paid/Partially Paid

Finalized payroll cannot be regenerated over itself without explicit reversal/re-run policy.

## Subscription
Trial → Active → Past Due → Suspended
Active → Cancelled/Expired

## Payout
Requested → Under Review → Approved → Paid
Under Review → Rejected
Approved → Failed where provider supports failure state

Every state transition:
- validates current state
- validates actor permission
- records timestamp
- audits sensitive transitions
