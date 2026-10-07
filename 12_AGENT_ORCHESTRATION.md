# ADOQA ERP — Antigravity Agent Orchestration v2

## Principle
Agents implement a controlled specification. They do not invent product policy.

## Agent roles

Architect:
- architecture
- ADRs
- boundaries

Product/Requirements:
- resolve PRD ambiguity
- maintain acceptance criteria

Database:
- schema
- constraints
- indexes
- migrations

Design:
- Blade/Tailwind system
- responsive/accessibility

Commerce:
- catalogue
- inventory
- purchasing
- sales
- returns
- customers/suppliers

Finance:
- payments
- ledger
- receivables/payables
- EMI
- payroll

People:
- staff
- attendance
- leave
- advances
- performance

Platform:
- admin
- subscriptions
- partners
- commissions
- payouts

Security:
- auth
- tenant isolation
- secrets
- attack review

QA:
- tests
- E2E
- regression
- reconciliation

Review:
- hostile review
- shortcut detection
- completeness

## Work protocol

For every task:
1. read relevant docs
2. inspect current implementation
3. state assumptions
4. create plan
5. implement
6. test
7. inspect diff
8. browser verify if relevant
9. security review
10. update docs
11. handoff with evidence

## Parallelism
Only parallelize independent file/domain scopes.

## Stop conditions
Stop when:
- requirement ambiguous
- calculation undefined
- authorization unclear
- migration destructive
- provider contract unknown
- architecture conflict

Do not guess.

## Required handoff
- files changed
- migrations
- tests
- commands
- results
- risks
- next task
