# ADOQA ERP — Agent Rules v2

1. Read before editing.
2. Never guess on product policy.
3. Server is authoritative.
4. Tenant scope is mandatory.
5. Authorization is mandatory.
6. Financial mutation is explicit and transactional.
7. Inventory mutation requires movement history.
8. Finalized financial records are not silently deleted.
9. Blade is presentation only.
10. Controllers coordinate, Actions implement important workflows.
11. Database constraints protect critical invariants.
12. Tests are required.
13. Negative tests are required for sensitive workflows.
14. Browser verification is required for critical UI.
15. Review your own diff.
16. Never commit secrets.
17. Do not add unnecessary dependencies.
18. Architecture changes require ADR.
19. Do not use TODO/mock placeholders for required production behavior.
20. Do not claim completion without evidence.
21. Reference products are requirements/UX inspiration only.
22. Keep business/accounting calculations deterministic.
23. External provider failures must not corrupt committed core state.
24. Queue non-critical slow work.
25. Prefer small verified increments.
26. Preserve existing ADOQA functionality unless explicitly removed in the PRD.
27. Never expose another business's data.
28. Never trust client-side payment success.
29. Never trust client-supplied profit/stock/totals.
30. When uncertain, stop and ask.
