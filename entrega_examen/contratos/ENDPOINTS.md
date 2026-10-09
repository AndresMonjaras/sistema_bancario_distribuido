# Contrato de endpoints

Todas las rutas utilizaron el prefijo `/api/v1`, JSON y montos en MXN. Las respuestas exitosas utilizaron `{"data": ...}` y los errores `{"error":{"code":"...","message":"..."}}`.

Las operaciones monetarias exigieron `Idempotency-Key` de 16 a 128 caracteres. Los nodos enviaron `X-API-Key` al banco central; las consultas y operaciones de clientes enviaron `X-Account-Pin`.

## Banco central — Laravel

| Método | Endpoint | Acceso | Función |
|---|---|---|---|
| GET | `/health` | Público | Disponibilidad |
| GET | `/csrf` | Público | Obtener token CSRF para la sesión web |
| POST | `/auth/login` | Credenciales + CSRF | Iniciar sesión administrativa |
| POST | `/auth/logout` | Sesión + CSRF | Cerrar sesión |
| GET | `/admin/dashboard` | Administrador | Resumen global |
| GET / POST | `/admin/nodes` | Administrador | Consultar / crear nodos y generar API key |
| PATCH | `/admin/nodes/{id}` | Administrador | Actualizar responsable, nombre o estado |
| POST | `/admin/nodes/{id}/cash` | Administrador | Dispersar efectivo |
| POST | `/admin/nodes/{id}/rotate-key` | Administrador | Renovar API key |
| GET | `/admin/accounts` | Administrador | Consultar cuentas centrales |
| PATCH | `/admin/accounts/{number}/status` | Administrador | Activar o bloquear cuenta |
| GET | `/admin/transactions` | Administrador | Historial global con filtros |
| GET | `/admin/reports` | Administrador | Reporte JSON o CSV (`format=csv`) |
| GET | `/node` | API key | Identidad y efectivo del nodo |
| GET / POST | `/accounts` | API key de sucursal | Consultar / abrir cuentas |
| GET | `/accounts/{number}/balance` | API key + PIN | Consultar saldo |
| GET | `/transactions` | API key | Historial del nodo |
| POST | `/withdrawals` | API key ATM + PIN + idempotencia | Retirar dinero |
| POST | `/deposits` | API key + PIN + idempotencia | Depositar dinero |
| POST | `/transfers` | API key + PIN origen + idempotencia | Transferir entre cuentas |

Cuerpos principales:

```json
{"type":"branch","name":"Sucursal Centro","responsible":"Onavi","initial_cash":"5000.00"}
```

```json
{"holder_name":"Cliente Demostración","initial_balance":"1000.00","pin":"1234"}
```

```json
{"account_number":"NUMERO_DE_CUENTA","amount":"300.00"}
```

```json
{"source_account":"CUENTA_ORIGEN","destination_account":"CUENTA_DESTINO","amount":"100.00"}
```

## Sucursal — Laravel

| Método | Endpoint | Función |
|---|---|---|
| GET | `/health` | Disponibilidad |
| GET | `/csrf` | Token CSRF |
| POST | `/auth/login`, `/auth/logout` | Sesión del ejecutivo |
| GET / PUT | `/config` | Consultar / guardar configuración del nodo |
| GET | `/dashboard` | Resumen de sucursal |
| GET / POST | `/accounts` | Consultar / abrir cuentas centrales |
| GET | `/transactions` | Historial local y filtro por cuenta |
| GET | `/reports` | Reporte JSON o CSV |

Los endpoints de gestión requirieron sesión del ejecutivo y `X-CSRF-TOKEN` en las escrituras. La apertura requirió `Idempotency-Key`.

## Cajero — Express.js

| Método | Endpoint | Función |
|---|---|---|
| GET | `/health` | Disponibilidad |
| GET | `/csrf` | Token CSRF |
| POST | `/auth/login`, `/auth/logout` | Sesión administrativa |
| GET / PUT | `/config` | Configuración administrativa |
| GET | `/node` | Efectivo y disponibilidad |
| POST | `/customer/login`, `/customer/logout` | Sesión de cliente |
| GET | `/balance` | Saldo central del cliente |
| POST | `/withdrawals` | Retiro de cliente (`amount`) |
| POST | `/deposits` | Depósito de cliente (`amount`) |
| POST | `/transfers` | Transferencia (`destination_account`, `amount`) |
| GET | `/transactions` | Historial del cliente o del administrador |

El cajero obtuvo la cuenta y el PIN desde la sesión de cliente. Las escrituras requirieron `X-CSRF-TOKEN`; las operaciones monetarias también requirieron `Idempotency-Key`.

## Estados HTTP

`200`: consulta o repetición idempotente; `201`: creación u operación nueva; `401`: credenciales inválidas; `403`: operación no autorizada o bloqueo; `404`: cuenta o nodo inexistente; `409`: saldo/efectivo insuficiente o conflicto de idempotencia; `419`: CSRF; `422`: validación; `429`: límite de solicitudes; `503`: servicio central no disponible.
