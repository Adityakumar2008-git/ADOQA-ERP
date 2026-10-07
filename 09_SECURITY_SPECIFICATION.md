# ADOQA ERP — Security Specification v2

## Authentication
- secure hashing
- session regeneration
- secure cookies
- logout invalidation
- account status checks
- rate limits for sensitive endpoints

## Authorization
Deny by default.
Server-side policies/permissions.
Tenant ownership checks.

## Tenant attack tests
Attempt cross-business access by modifying:
- business IDs
- invoice IDs
- customer IDs
- supplier IDs
- item IDs
- staff IDs
- EMI IDs
- payment IDs

All must fail safely.

## Financial security
- server-side calculation
- idempotency
- transaction boundaries
- webhook signature verification
- audit

## Input
- validation
- allowlists
- output escaping
- safe uploads
- import validation

## Secrets
No credentials in source.
No secrets in logs.

## Files
Validate type, size, storage location and authorization.

## Logging
Mask tokens, passwords, OTPs and payment secrets.

## Privacy
Collect only required information.
Provide appropriate legal documents before commercial deployment.

## Backup
Backup policy, retention, restore test.

## Dependency security
Lock versions.
Audit dependencies.
Remove unused packages.
