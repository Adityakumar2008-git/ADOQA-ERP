# ADOQA ERP — Firebase Integration Boundary

## Source of truth
PostgreSQL.

## Firebase may provide
- FCM push
- approved storage
- approved realtime support
- Firebase Auth only if an explicit ADR approves it as identity authority

## Default recommendation
Use Laravel authentication for core identity unless a later ADR changes this.

Use Firebase primarily for push notifications and supporting infrastructure.

## Notification pipeline
Application event → persisted notification → queued delivery → provider → delivery status/retry

Provider failure must not roll back an already committed invoice/payment.

## External payment providers
- verify signatures
- idempotency
- provider reference
- reconciliation
- retry-safe webhook handling

Never mark a payment successful because a browser says "success".
