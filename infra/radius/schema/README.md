# RADIUS vendor schema

KikiNet deliberately does **not** ship a hand-written copy of the FreeRADIUS
SQL schema. Table definitions, indexes and PostgreSQL functions differ between
major versions, and a mismatched schema silently breaks accounting.

Use the file shipped with the pinned package:

```
# FreeRADIUS 3.2.x
raddb/mods-config/sql/main/postgresql/schema.sql

# FreeRADIUS 4.0.x
raddb/mods-config/sql/main/postgresql/schema.sql   (verify path in the release)
```

Steps for the lab/production database (`kikinet_radius`):

1. Record the exact package version in the runbook.
2. `psql -f schema.sql` with the owner role (not the runtime role).
3. Create the roles described in `../README.md` and grant the minimum rights.
4. Verify with `lab/check-radius.sh` that a Start/Interim/Stop writes `radacct`.
5. Do not add application columns to vendor tables. KikiNet keeps its normalized
   samples in `radius_accounting_samples` on the application database.

Application-owned tables (projection + read model) are created by:

```
database/migrations/2026_11_13_100000_create_network_and_accounting_tables.php
```

`radacct` is read-only for KikiNet analytics; `radcheck`/`radreply` are written
only by the projection adapter for policy attributes (`Auth-Type`,
`Mikrotik-Rate-Limit`, `Framed-Pool`).
