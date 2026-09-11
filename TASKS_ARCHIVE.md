# TASKS_ARCHIVE — ci4-bff-starter

> Historial de tareas completadas. Movido desde TASKS.md para mantener el tracker activo liviano.
> Última actualización: 2026-08-25

---

## ✅ Remediación de huecos profundos — Fase 0 (2026-08-25)

- **GAP-00-bff** — `AppExceptionHandler` sanitiza mensajes de excepciones fuera de development,
  con regresiones de no filtración y JSON válido. Commit `b9d33e6`; `composer quality`, suite de
  integración, `composer cs-check` y pre-commit completados.

---

## ✅ Backport de mejoras de Teatro Museo — Fase 5 (2026-08-25)

- **BACKPORT-05-bff** — documentación del BFF opcional, runtime Docker y operación en el puerto
  `8188`; `composer quality` y smoke de generación completados.

## ✅ Milestone BFF v1.1 — Architecture Hardening (2026-06-10 y anteriores)

| ID | Descripción | Estado |
|---|---|---|
| BFF-M0 | Forwarding de headers de firma de webhooks — `DomainClient::buildForwardedHeaders()` reenvía `X-Twilio-Email-Event-Webhook-Signature/-Timestamp` y `X-Webhook-Token` para que los domains puedan verificar firmas de webhooks proxied. Backport del stack multi-subscription (auditoría 2026-06-10, H-2). | ✅ |
| BFF-M1 | Unificación de Throttling — `ThrottleFilter` local eliminado en favor de la implementación del core. `RateLimitResponseHelpers` eliminado (ahora consumido desde `ci4-api-core`). | ✅ |
| — | Propagación de `app_id` — el BFF ahora es consciente de la aplicación a través de la propagación automática en `IntrospectAuthFilter` y `ContextHolder`. | ✅ |
| BFF-M2 | Soporte Multi-Domain — `Config/Bff.php` y `Services.php` refactorizados para admitir un array asociativo de dominios dinámicos mapeados vía `DomainClient`. | ✅ |
| BFF-M3 | Generador de Proxy Dinámico — comando CLI Spark `bff:make-proxy` para generar automáticamente controladores de proxy transparentes y archivos de rutas. | ✅ |

**Nota:** BFF-101/102/107/111 (refactor de `HubClient` sobre `AbstractServiceClient` compartido del core, Sentry breadcrumbs) se rastrearon del lado de `ci4-api-core` y `ci4-domain-starter` — ver sus respectivos `TASKS_ARCHIVE.md`.

## ✅ BACKPORT-02-bff — Fase 2 del backport Teatro Museo (2026-08-25)

Se incorporó el BFF genérico opcional con `ci4-api-core ^1.5`, clientes Hub/Domain, proxy de
contenido, agregación de página, telemetría acotada, filtro `X-App-Key` fail-closed y seam
`PublicReadSupport` de solo lectura apagado por defecto. No contiene código de negocio de Teatro
Museo ni secreto local de firma JWT. Historia limpia: `2457137` inicializa el snapshot del
starter base; `b8b56cb`, `477eb04`, `ff42c15`, `cd02aa0`, `a941b09`, `d4505cc` y `9a30856`
aplican el backport paso a paso.

## ✅ GAP-05 reconciliado — 2026-09-11

Verificación del código y la historia confirmó como implementados:

- `PublicReadSupport` y primitivas SQL portables (`ReadOnlyQuery`, `JsonArrayAggregateSql`,
  `JsonProjectionDecoder`), con pruebas unitarias de proyección;
- guardrail `StatelessArchitectureTest` y tooling de calidad/CI;
- guard CORS contra wildcard con credenciales, con regresión de configuración.

Los siguientes puntos permanecen diferidos por diseño, no olvidados: `AdminRead` completo,
multi-caller de `WebAppKeyRequiredFilter`, health de grupos read-only y telemetría SQL/cache. No
existe consumidor real suficiente para justificar esas abstracciones; cualquier reapertura requiere
un caso de uso, contrato y pruebas primero.

## ✅ GAP-05-BFF — Gate de reconciliación verificado — 2026-09-11

`composer quality` pasó con 161 tests y 409 aserciones (1 skip preexistente). La revisión confirmó
que `PublicReadSupport`, las primitivas SQL portables, el guard stateless, CORS fail-closed y los
ADRs están alineados con el código. No se añadió código: `AdminRead`, multi-caller, health de grupos
read-only y telemetría SQL/cache siguen diferidos por falta de consumidor real.

## ✅ CNV-007-F8 — Evidencia operativa — 2026-09-11

El BFF queda reconciliado y clasificado como implementado; sus seams sin consumidor real permanecen
diferidos por diseño. La auditoría externa de dependencias se mantiene en el tracker raíz porque
Packagist/npm no resolvían en el entorno del gate.
