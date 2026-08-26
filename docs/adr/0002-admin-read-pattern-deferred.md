# ADR-0002: AdminRead completo diferido hasta tener un consumidor

## Estado

Diferido — 2026-08-25.

## Contexto

El BFF base actual expone proxies HTTP y una composición pública de página. No
existe una pantalla Admin, endpoint agregado ni comando que necesite leer varias
bases de datos directamente desde el BFF. El Admin actual consulta sus upstreams
correspondientes.

Portar el directorio `app/AdminRead/` completo introduciría permisos, guardas de
base de datos y lectores sin un consumidor verificable. Eso ampliaría el acople
arquitectónico y dejaría código que no puede ejercitarse como parte de un flujo
real del starter.

## Decisión

No se porta el patrón `AdminRead` completo mientras no exista un endpoint, vista o
comando admin agregado que lo invoque. El BFF conserva únicamente las primitivas
SQL standalone de solo lectura en `App\Support\Sql`, sin nombres de dominio ni
lectores de negocio.

Cuando un proyecto derivado tenga ese consumidor real, la implementación deberá
volver a evaluarse como una unidad: contrato del endpoint, autorización upstream,
conexión explícita con permisos `SELECT` solamente, comportamiento fail-closed,
telemetría consumida por una pantalla o health endpoint y pruebas de integración.
No se debe activar una conexión ni portar `PermissionGuard` por anticipado.

## Alternativas descartadas

- Portar `app/AdminRead/` como esqueleto opt-in: rechazado por código fantasma y
  por acoplar el starter a una arquitectura de dashboard que hoy no existe.
- Crear una pantalla o endpoint agregado solo para justificar el port: rechazado
  porque sería una decisión de producto, no una remediación genérica.

## Consecuencias

- El BFF base permanece stateless y no adquiere una lectura administrativa sin
  consumidor.
- El costo de retomar el patrón queda explícito y acotado a una futura necesidad
  verificable.
- Las utilidades SQL existentes no implican que exista un módulo `AdminRead` ni
  autorizan conexiones directas por defecto.
