# TASKS — ci4-bff-starter

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-08-25

---

## 🔴 En progreso

*(vacío)*

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
