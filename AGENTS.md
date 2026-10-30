# AGENTS.md

Rules for any agent working in this repo:

1. **Tests first** - Write or update tests before implementation. Run tests after each change.
2. **No code comments** - Do not write comments in code unless explicitly requested.
3. **Never edit or print .env files** - Environment files contain secrets. Use .env.example for documentation.
4. **One stage at a time** - Complete each staged plan step fully before moving to the next.
5. **Languages** - Macedonian (mk) is the default language, English (en) is the second. No Arabic.
6. **API contract** - The OpenAPI spec lives in `backend/openapi.yaml`. The client in `packages/api-client` is generated from it via `openapi-typescript` and `openapi-fetch`.
7. **Test-only code** - Test routes and helpers never go into production code. Register test routes inside tests (e.g., in setUp or individual tests).