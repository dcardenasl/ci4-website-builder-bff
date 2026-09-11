# TASKS — ci4-bff-starter

> Trabajo abierto de este repositorio. Lo implementado está reconciliado en
> [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md). Plan cross-repo:
> [`../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md`](../docs/plans/2026-09-11-plan-nivelacion-stack-modular-suite.md).

## 🔴 En progreso

*(vacío)*

## 🟡 Próximo

*(vacío; la autorización por recurso se impone aguas abajo y BFF no la duplica)*

## ⚪ Diferido por diseño

- `AdminRead` completo, multi-caller de `WebAppKeyRequiredFilter`, health de grupos read-only y
  telemetría SQL/cache: requieren consumidores reales que hoy no existen.

## 🏗️ Contratos

- BFF stateless, sin DB de escritura, sin usuarios ni validación local de JWT.
- `IntrospectAuthFilter` opt-in por ruta; `HubClient::introspect()` para identidad.
- CORS fail-closed; no wildcard con credenciales; cualquier seam nuevo necesita consumidor real.

## ✅ Cerrado con evidencia

- **GAP-05-BFF — Gate de reconciliación.** `composer quality` pasó con 161 tests y 409
  aserciones; arquitectura stateless, CORS wildcard+credentials, `PublicReadSupport`, SQL
  portable, CI y ADRs fueron revisados. No se implementa `AdminRead` ni nuevos seams porque no
  existe un consumidor real.

- **CNV-007-F8 — Evidencia operativa.** El BFF queda clasificado como implementado y reconciliado;
  los seams sin consumidor real permanecen diferidos por diseño. La auditoría externa de
  dependencias se ejecutó el 2026-09-11 sin advisories de seguridad; su evidencia y el único
  warning upstream de paquetes dev abandonados están en el tracker raíz.
- **CNV-007-F9 — Reconciliación de alcance.** BFF reenvía la autorización downstream sin modelos,
  ACL local ni cache de permisos; los seams sin consumidor siguen diferidos por diseño. Evidencia
  Domain: `729aa89`.
