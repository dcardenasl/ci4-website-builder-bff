# TASKS — ci4-bff-starter

> Trabajo abierto de este repositorio. Lo implementado está reconciliado en
> [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md). Plan cross-repo:
> [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío)*

## 🟡 Próximo

- [ ] **GAP-05-BFF — Gate de reconciliación.** Ejecutar `composer quality`, revisar tests/ADR y
      confirmar que `PublicReadSupport`, utilidades SQL, guard stateless, CORS y CI siguen siendo
      correctos. Solo abrir implementación si aparece un gap reproducible con consumidor real.
- [ ] **CNV-007-F8 — Evidencia operativa.** Registrar la clasificación final: implementado,
      diferido por consumidor, diferido por producto o cerrado con evidencia.
- [ ] **CNV-007-F9 — Autorización por recurso.** Solo después de la nivelación completa.

## ⚪ Diferido por diseño

- `AdminRead` completo, multi-caller de `WebAppKeyRequiredFilter`, health de grupos read-only y
  telemetría SQL/cache: requieren consumidores reales que hoy no existen.

## 🏗️ Contratos

- BFF stateless, sin DB de escritura, sin usuarios ni validación local de JWT.
- `IntrospectAuthFilter` opt-in por ruta; `HubClient::introspect()` para identidad.
- CORS fail-closed; no wildcard con credenciales; cualquier seam nuevo necesita consumidor real.
