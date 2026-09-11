# ADR-0003: WebAppKeyRequiredFilter multi-caller diferido

## Estado

Diferido — 2026-08-25.

## Contexto

El base tiene un solo caller server-to-server verificable para el BFF: el
ejemplo `PageBootstrapController`, protegido por `webappkey` y respaldado por la
configuración única `BFF_API_KEY`/`WEB_API_KEY`. No existe un segundo sitio o
cliente con una credencial y una política de acceso distintas.

El backport de Teatro Museo resuelve Web y Totem como callers separados, pero
esa multiplicidad no es un contrato universal del starter. Construir una
allow-list multi-caller ahora agregaría configuración y ramas de autorización
sin una ruta que las ejercite.

## Decisión

Se mantiene el `WebAppKeyRequiredFilter` actual, fail-closed y con una sola
credencial de aplicación. No se agrega resolución por caller, allow-list
multi-key ni selección basada en headers no autenticados hasta que exista un
segundo caller real del BFF.

Cuando aparezca esa necesidad, se deberá definir primero el contrato de cada
caller y sus permisos, mantener las claves explícitas en variables de entorno o
secret manager, rechazar callers desconocidos y probar aislamiento entre
credenciales. La extensión debe implementarse junto con sus rutas consumidoras,
no como una abstracción anticipada.

## Alternativas descartadas

- Portar el resolver multi-caller de Teatro Museo: rechazado porque depende de
  un segundo caller que el base no tiene.
- Aceptar cualquier `X-App-Key` presente o seleccionar caller desde un header
  libre: rechazado por degradar el control de acceso.

## Consecuencias

- El único flujo público server-to-server del starter conserva una política
  simple y auditable.
- No se introducen defaults de hosting ni secretos adicionales.
- La evolución futura queda condicionada a un endpoint y caller concretos,
  con pruebas de autorización para cada combinación.
