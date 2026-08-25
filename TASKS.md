# TASKS — ci4-bff-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-08-25

---

## 🔴 En progreso

### Remediación de huecos profundos (parte BFF)

> Plan completo: [`../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md`](../docs/plans/2026-08-25-plan-remediacion-huecos-profundos.md).
> Auditoría origen: [`../docs/audits/2026-08-25-auditoria-profunda-backport-git-history.md`](../docs/audits/2026-08-25-auditoria-profunda-backport-git-history.md).
> Tracker cross-repo: [`../TASKS.md`](../TASKS.md).

- [ ] **GAP-05-bff:** ítems del plan §Fase 5, revisados 2026-08-25 contra el criterio de "sin
      consumidor real no se porta como código". **NO se porta** el directorio `app/AdminRead/`
      completo — sería una pila de abstracción sin un solo punto de uso en el base (código
      fantasma). En su lugar: portar solo `ReadOnlyQuery`/`JsonArrayAggregateSql` (con soporte
      MariaDB legacy) /`JsonProjectionDecoder` como utilidades SQL standalone en
      `app/Support/Sql/` (son primitivas genéricas, útiles fuera del patrón "AdminRead"), y
      documentar el patrón `AdminRead` completo en un ADR para cuando exista una pantalla admin
      agregada real que lo necesite. `WebAppKeyRequiredFilter` multi-caller y `/health` para
      grupos de DB read-only quedan **diferidos** (resuelven necesidades que el base no tiene
      hoy — un segundo caller del BFF, `PublicReadSupport` habilitado). Sí entran sin cambios:
      completar `app/PublicRead/Support/` (tiene consumidores reales ya portados), guard CORS
      contra wildcard+credenciales, `StatelessArchitectureTest`, tooling de CI. `RequestTelemetry`
      solo si se le agrega un consumidor real al portarlo. Ver plan §Fase 5 para el detalle
      completo de la revisión.

---

## 🟡 Próximo

*(vacío)*

---

## ⚪ Backlog

*(vacío)*

---

## 🏗️ Contratos de arquitectura

Ver `CLAUDE.md` — sección "Boundaries" y "Adding an endpoint — three patterns" son la autoridad. Resumen:

- **Sin base de datos de escritura, sin validación local de tokens, sin storage de usuarios.** El BFF reenvía `Authorization`; el upstream valida. La conexión `PublicReadSupport` es opcional, de solo lectura y apagada por defecto.
- **`IntrospectAuthFilter` es opt-in por ruta**, nunca global.
- **Nunca decodificar JWTs localmente** — usar `HubClient::introspect()`.
- **Stateless siempre** — nada de sesiones/cache por usuario keyed por JWT (usar `auth_user_id` de `ContextHolder`).
