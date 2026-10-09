# Cajero — Express.js

Interfaz y API de consulta, retiro, depósito y transferencia. Consumimos la API central con una API key de ATM; el cliente accede mediante cuenta y PIN. Verificamos el efectivo local antes de autorizar un retiro central.

Instalación: Node.js 22+, `npm ci`, copiar `.env.example` a `.env`, configurar las variables y ejecutar `npm start`. Puerto predeterminado: `3000`.

Vercel utiliza `vercel.json`; seleccionar `cajero` como raíz e ingresar las variables privadas en el panel de la plataforma. La aplicación no abre un puerto cuando corre en Vercel. Para producción se utiliza `COOKIE_SECURE=true`. Las sesiones, la configuración cifrada y los registros locales utilizan el proyecto PostgreSQL `Atm_Sucursales_eqm`.

Contrato: [OpenAPI del cajero](../contratos/cajero.openapi.yaml).
