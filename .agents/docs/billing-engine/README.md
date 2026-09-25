# NBPDCL Billing Engine Documentation

This folder contains the complete technical specification, architectural blueprint, API credentials, encryption methods, and handover documentation for the NBPDCL download system and dual extraction engines.

👉 Please read the primary document:  
**[`ENGINE_SPECIFICATION.md`](./ENGINE_SPECIFICATION.md)**

### Quick Index:
1. **Context & Migration**: Why FluentGrid WSS replaced legacy ASMX in 2026.
2. **Download Protocol**: AES-256-CBC encryption details, endpoint URLs, and auto-fallback strategy.
3. **Dual Extraction**: Unicode JasperReports (`JasperUnicodeExtractor`) vs Kruti-Dev (`LegacyKrutiDevExtractor`) and signature detection (`BillExtractionManager`).
4. **Admin Panel**: Configuration options at `/admin/bills/engine-settings` and live diagnostic sandbox.
5. **Standalone Test Tool**: Instructions for running `php/test-tool`.
6. **Master File Map & Testing**: Paths to all services, tests, and CLI verification commands.
