# ADOQA ERP — ADR Register v2

## ADR-0001 Modular monolith
Status: Approved
One Laravel application with domain boundaries and three UX surfaces.

## ADR-0002 PostgreSQL source of truth
Status: Approved
Core ERP transactional, inventory, ledger, and customer state remains strictly in PostgreSQL.

## ADR-0003 Firebase boundary
Status: Approved
Firebase supports push notifications (FCM) and optional external storage/realtime signaling only. Never transactional authority.

## ADR-0004 No public signup
Status: Approved
Master Admin creates businesses.

## ADR-0005 One business per ordinary account
Status: Approved.

## ADR-0006 Separate admin/owner/staff surfaces
Status: Approved.

## ADR-0007 Original implementation
Status: Approved.
Reference products may influence requirements, never copied source/branding.

## ADR-0008 Identity provider
Status: Approved.
Standard Laravel authentication (secure sessions/cookies, bcrypt/argon2 hashing) is the identity authority. Firebase Auth is not used for primary credentials.

## ADR-0009 Identifier strategy
Status: Approved.
Use ULID (Universally Unique Lexicographically Sortable Identifier) for business-owned records and public-facing entities. ULIDs provide non-enumerable, 128-bit security compatible with UUID, with chronological B-Tree sorting efficiency in PostgreSQL.


## ADR-0010 EMI calculation model
Status: Approved for v1:
0% / flat principal installments only.
Interest-bearing EMI requires a new ADR with exact formula.

## ADR process
Any architecture-affecting change requires:
context → alternatives → decision → consequences → migration → rollout → status.
