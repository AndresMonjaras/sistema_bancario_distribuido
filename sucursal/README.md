# Sucursal — Laravel

Panel de ejecutivos para apertura de cuentas e historial. Consumimos la API central con una API key de sucursal y persistimos registros locales en Supabase `Atm_Sucursales_eqm`.

Instalación: `composer install`; copiar `.env.example` a `.env`; configurar PostgreSQL local de nodos, acceso administrativo, `CENTRAL_URL` y `NODE_API_KEY`; ejecutar `php artisan key:generate`, `php artisan migrate --force` y `php artisan serve --host=0.0.0.0 --port=8001`.

El Dockerfile permite desplegar la sucursal en Coolify o Render con puerto `8001`. La configuración privada se guarda cifrada en el servidor; en Docker debe conservarse `storage/` como volumen si se configura desde el panel.

Contrato: `../entrega_examen/contratos/sucursal.openapi.yaml`.
