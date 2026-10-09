# Configuración de despliegue

## Vercel — ATM

Importar `AndresMonjaras/sistema_bancario_distribuido`, seleccionar rama `main` y Root Directory `cajero`. El archivo `vercel.json` define el servidor Express y los recursos del panel. Agregar las variables de `cajero/.env.example`; usar `COOKIE_SECURE=true`, `NODE_ENV=production` y una URL central HTTPS accesible públicamente. `NODES_DATABASE_URL` corresponde únicamente a `Atm_Sucursales_eqm`.

Las sesiones y configuraciones utilizan PostgreSQL. Los archivos no deben incluir credenciales en Git. Tras publicar, abrir `/api/v1/health` y realizar ingreso y consulta desde el panel.

## Render — sucursal

Crear un Web Service desde Git, rama `main`, Root Directory `sucursal`, Runtime Docker, Dockerfile `./Dockerfile` y Health Check `/api/v1/health`. Agregar las variables de `sucursal/.env.example`; `DB_*` corresponde únicamente a `Atm_Sucursales_eqm`. También se puede importar `render.yaml` mediante un Blueprint. Utilizar `CENTRAL_URL` y `NODE_API_KEY` como variables de entorno para conservar la configuración durante nuevos despliegues.

`APP_KEY` debe tener el formato generado por `php artisan key:generate --show`. El contenedor utiliza el puerto proporcionado por Render mediante `PORT`. La URL central debe ser accesible desde la plataforma.

## Coolify — alternativa Docker

Aplicación desde Git, rama `main`, base `/sucursal`, Dockerfile y puerto `8001`. En la ejecución del examen se eligió Render para sustituir la instancia de Coolify que presentó problemas.

## Demostrar despliegue continuo

Conectar la plataforma con GitHub y habilitar despliegues automáticos desde `main`. Publicar un cambio identificable, conservar el hash del commit y capturar en la plataforma la ejecución finalizada y la aplicación que corresponde a ese commit. Hasta completar esa comprobación, el despliegue continuo se considera preparado y pendiente de verificación.
