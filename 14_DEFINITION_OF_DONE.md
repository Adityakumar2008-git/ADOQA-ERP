# ADOQA ERP — Definition of Done v2

A feature is DONE only if applicable gates pass.

## Requirements
- PRD requirement mapped
- acceptance criteria implemented
- edge cases covered

## Backend
- validation
- authorization
- tenant scope
- action/use-case
- correct transaction
- error handling

## Database
- migration
- foreign keys
- constraints
- indexes reviewed
- data migration safety

## Security
- unauthorized blocked
- cross-tenant blocked
- sensitive input reviewed
- secrets absent

## Tests
- unit
- feature
- negative
- regression
- browser for critical flow

## UX
- loading
- empty
- error
- success
- responsive
- accessibility basics

## Operations
- logs
- queues
- scheduler
- failure handling

## Documentation
- architecture/ADR updated if necessary
- handoff evidence

Critical missing gate = NOT DONE.
