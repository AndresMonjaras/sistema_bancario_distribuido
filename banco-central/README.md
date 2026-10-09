# Banco central — Laravel

API y panel del núcleo bancario. Persistencia: PostgreSQL de Supabase `banco_central_eqm`.

Instalación: `composer install`; copiar `.env.example` a `.env`, configurar PostgreSQL y credenciales administrativas; ejecutar `php artisan key:generate`, `php artisan migrate --force` y `php artisan serve --host=0.0.0.0 --port=8100`.

El panel registra nodos, emite sus API keys, asigna responsables y efectivo, consulta cuentas, bloquea cuentas/nodos y exporta reportes CSV. Las escrituras monetarias utilizan transacciones e idempotencia; el historial se protege mediante trigger.

Contrato: [OpenAPI del banco central](../contratos/banco-central.openapi.yaml).
