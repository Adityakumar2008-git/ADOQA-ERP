# ADOQA ERP — AGENT INSTRUCTIONS & RULES

Lead Engineering Agent Master Directive: See [17_MASTER_AGENT_PROMPT.md](file:///d:/my%20projects/Adoca/17_MASTER_AGENT_PROMPT.md).
Product Specification: See [02_PRD_BUILD_READY.md](file:///d:/my%20projects/Adoca/02_PRD_BUILD_READY.md).
Build Contract: See [01_MASTER_BUILD_CONTRACT.md](file:///d:/my%20projects/Adoca/01_MASTER_BUILD_CONTRACT.md).
Agent Rules: See [13_AGENT_RULES.md](file:///d:/my%20projects/Adoca/13_AGENT_RULES.md).
Execution Plan: See [15_EXECUTION_PLAN.md](file:///d:/my%20projects/Adoca/15_EXECUTION_PLAN.md).
Definition of Done: See [14_DEFINITION_OF_DONE.md](file:///d:/my%20projects/Adoca/14_DEFINITION_OF_DONE.md).
Release Gate: See [18_RELEASE_CHECKLIST.md](file:///d:/my%20projects/Adoca/18_RELEASE_CHECKLIST.md).

## STACK
- PHP 8.3+ (Active runtime: PHP 8.5)
- Laravel 13
- Laravel Blade
- PostgreSQL
- Tailwind CSS
- Vite
- Firebase only where explicitly approved by ADR

Do not replace the frontend with React/Next/Vue.

## HARD PRODUCT RULES
- No public signup.
- Master Admin creates businesses.
- One ordinary business account = one business.
- Strict tenant isolation across all surfaces.
- Owner ERP and Staff/Cashier are separate UX surfaces.
- Master Admin is platform scope.
- Original implementation; no proprietary code copying.

## HARD ENGINEERING RULES
Never:
1. Trust client totals, prices, stock, permissions, or tenant IDs.
2. Bypass authorization.
3. Disable CSRF.
4. Put business logic in Blade.
5. Hide financial mutation in a GET request.
6. Delete finalized financial history when reversal/cancellation is required.
7. Mutate stock without an audit stock movement.
8. Mark payment successful from client-side state.
9. Apply the same payment twice.
10. Sell duplicate serialized/IMEI items.
11. Use floating point for money (use fixed precision decimal arithmetic).
12. Skip tests or negative tests.
13. Leave required placeholders or TODOs.
14. Commit secrets.
15. Make undocumented architecture changes (require ADR in `16_ADR_REGISTER.md`).

## PHASE GATES & WORKFLOW
- Follow `15_EXECUTION_PLAN.md` phase by phase.
- Execute only the current active phase.
- Pass all phase gates before moving to the next phase.
- Never claim "100% bug-free".
- Release only when `18_RELEASE_CHECKLIST.md` passes.
