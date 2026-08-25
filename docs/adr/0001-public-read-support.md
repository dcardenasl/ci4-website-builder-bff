# ADR-0001: Public-read support is opt-in

## Estado

Aceptado — 2026-08-25.

## Decisión

El BFF puede exponer una conexión de solo lectura hacia el dominio mediante
`App\PublicRead\PublicReadSupport`, pero la capacidad queda apagada por defecto
con `BFF_PUBLIC_READ_SUPPORT=false`. La conexión usa únicamente el grupo
`public_readonly` y las variables `DOMAIN_READONLY_DB_*`.

El flujo normal usa HTTP contra el dominio (`DomainClient`). La lectura directa
por SQL no se activa automáticamente, no recibe credenciales de escritura y no
define lectores de entidades concretas en el starter.

Las rutas públicas entre servidores validan `X-App-Key` con un filtro fail-closed
antes de delegar al dominio. El BFF no almacena usuarios ni valida tokens localmente.

## Consecuencias

- Un sitio simple no necesita una base de datos ni configuración de lectura directa.
- Un sitio que necesite optimizar lecturas puede implementar lectores genéricos como
  extensión explícita y revisar sus permisos SQL de solo lectura.
- El contrato de página de ejemplo permanece libre de nombres de negocio de Teatro Museo.
