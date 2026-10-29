# ProyectoAPI — CRUD de patrones Hama

Aplicación creada con Laravel 13 para administrar patrones de cuentas Hama mediante una API REST y MySQL.

## Cumplimiento de la tarea

- Framework Laravel 13.
- Base de datos MySQL `ProyectoAPI`.
- CRUD completo expuesto exclusivamente desde `routes/api.php`.
- Endpoints REST para crear, consultar, actualizar y eliminar Hamas.
- Colección de Postman lista para la presentación.
- Laravel Passport 13 configurado como servidor OAuth2.
- Credenciales de cliente y tokens Bearer mediante el flujo Client Credentials.
- Rutas API versionadas y protegidas con los scopes `hamas.read`, `hamas.write` y `hamas.delete`.
- Interfaz web opcional que consume la misma API; no contiene lógica CRUD en `routes/web.php`.

## Editor de patrones

La interfaz incluye un tablero real de 50 × 50. Cada celda representa una cuenta Hama y el patrón completo se guarda en MySQL.

- **Pincel:** pinta mediante clic o arrastre.
- **Relleno:** rellena la zona conectada que tenga el mismo color o esté vacía.
- **Borrador:** elimina cuentas mediante clic o arrastre.
- **Deshacer y rehacer:** conserva hasta 60 cambios durante la edición.
- **Vista previa en vivo:** muestra el resultado antes de guardarlo.
- **Vistas previas guardadas:** cada tarjeta reproduce las posiciones y colores almacenados en `patron`.

## Campos de un Hama

- `nombre`: nombre del patrón.
- `descripcion`: información opcional.
- `ancho`: ancho del patrón, entre 1 y 50.
- `alto`: alto del patrón, entre 1 y 50.
- `cantidad_cuentas`: cuentas necesarias para construirlo.
- `color_principal`: color hexadecimal, por ejemplo `#8B5CF6`.
- `patron`: lista de posiciones y colores pintados en el tablero de 50 × 50.

## Endpoints

Todos los endpoints del CRUD requieren este encabezado:

```http
Authorization: Bearer <access_token>
Accept: application/json
```

| Método | Endpoint | Operación | Scope requerido |
| --- | --- | --- | --- |
| GET | `/api/v1/hamas` | Listar todos | `hamas.read` |
| POST | `/api/v1/hamas` | Crear | `hamas.write` |
| GET | `/api/v1/hamas/{hama}` | Consultar uno | `hamas.read` |
| PUT/PATCH | `/api/v1/hamas/{hama}` | Actualizar | `hamas.write` |
| DELETE | `/api/v1/hamas/{hama}` | Eliminar | `hamas.delete` |
| POST | `/api/v1/connections/handshake` | Confirmar conexión y permisos | Token válido |

Las rutas anteriores bajo `/api/hamas` permanecen activas por compatibilidad, pero las integraciones nuevas deben utilizar `/api/v1/hamas`.

El listado admite filtros útiles para sincronizar otra aplicación:

```text
GET /api/v1/hamas?search=estrella
GET /api/v1/hamas?updated_since=2026-10-01T00:00:00Z
```

La respuesta incluye `meta.api_version`, `meta.count` y `meta.generated_at`.

## Seguridad con Laravel Passport

La aplicación utiliza OAuth2 Client Credentials porque Postman actúa como cliente de la API sin iniciar sesión como un usuario. El cliente se crea con:

```bash
php artisan passport:client --client --name="ProyectoAPI Postman"
```

Para generar un token se envía `POST /oauth/token` como `x-www-form-urlencoded`:

```text
grant_type=client_credentials
client_id=<client_id>
client_secret=<client_secret>
scope=hamas.read hamas.write hamas.delete
```

El token dura una hora. Las llaves privadas y públicas de Passport se guardan en `storage/oauth-private.key` y `storage/oauth-public.key`; ambas están excluidas de Git.

## Centro de conexiones

La pantalla `http://127.0.0.1:8000/conexiones` permite preparar y comprobar el intercambio entre aplicaciones sin escribir credenciales manualmente.

- **Generar token para compartir:** al pulsar el botón, ProyectoAPI crea internamente las credenciales Passport y devuelve un Bearer token listo para copiar. Solo se solicita el nombre del proyecto y los permisos.
- **Validar token recibido:** llama a `POST /api/v1/connections/handshake` y confirma firma, vigencia, proyecto consumidor y scopes concedidos.
- **Comprobante:** indica de forma separada si el token permite consultar proyectos Hama y crear un patrón de 50 × 50.
- **Conexión inversa:** recibe únicamente la URL base y el token emitido por la otra API. La solicitud sale desde Laravel para evitar CORS y detecta automáticamente los contratos `/api/v1/connections/handshake` y `/api/v1/partner/connection`.

La generación automática está limitada al entorno local y protegida con CSRF. Solo se comparte el `access_token`; el Client Secret permanece administrado por Laravel Passport. Cada API emite y valida sus propios tokens: Dinosaurio usará un token emitido por ProyectoAPI para consultar Hamas, y ProyectoAPI usará otro token emitido por Dinosaurio para consultar esa API.

Para probar dos proyectos locales, ambos servidores deben estar activos al mismo tiempo. Por ejemplo, ProyectoAPI en `http://127.0.0.1:8000` y el proyecto asociado en `http://127.0.0.1:8001`.

## Ejemplo para crear un Hama

```json
{
    "nombre": "Estrella cósmica",
    "descripcion": "Patrón de estrella con cuentas amarillas",
    "ancho": 50,
    "alto": 50,
    "cantidad_cuentas": 4,
    "color_principal": "#F59E0B",
    "patron": [
        { "posicion": 1124, "color": "#F59E0B" },
        { "posicion": 1125, "color": "#F59E0B" },
        { "posicion": 1174, "color": "#F59E0B" },
        { "posicion": 1175, "color": "#F59E0B" }
    ]
}
```

Cada posición va de `0` a `2499`. Cuando se envía `patron`, Laravel calcula automáticamente `cantidad_cuentas` según las posiciones recibidas.

## Ejecución local

Desde la terminal de Laragon:

```bash
php artisan migrate
php artisan serve
```

Direcciones disponibles:

- Interfaz opcional: `http://127.0.0.1:8000`
- Centro de conexiones: `http://127.0.0.1:8000/conexiones`
- API recomendada: `http://127.0.0.1:8000/api/v1/hamas`

## Integración futura con la API Dinosaurio

Este proyecto ya puede actuar como proveedor de datos para otra aplicación. Cuando exista el proyecto Dinosaurio, el flujo será:

1. Crear en ProyectoAPI un cliente Passport exclusivo para Dinosaurio.
2. Dinosaurio solicitará un token en `POST /oauth/token` usando Client Credentials.
3. Con un token que tenga `hamas.read`, Dinosaurio consultará `GET /api/v1/hamas` y mostrará los patrones guardados.
4. En la API Dinosaurio se crearán sus propios endpoint, cliente OAuth y scopes.
5. ProyectoAPI guardará las credenciales de Dinosaurio en su `.env` y consumirá esa API para mostrar los dinosaurios.

No se ha agregado información ficticia de dinosaurios ni una URL simulada. Esa segunda conexión se implementará cuando exista el otro proyecto y se conozcan sus campos y endpoints.

## Presentación con Postman

Importa estos dos archivos:

```text
postman/ProyectoAPI-Hamas.postman_collection.json
postman/ProyectoAPI-Passport.local.postman_environment.json
```

Selecciona el entorno **ProyectoAPI Passport (local)** y ejecuta las peticiones en orden:

1. **0. Generar token OAuth2** obtiene el token y lo guarda automáticamente.
2. **0.1 Confirmar conexión entre proyectos** verifica el token y los permisos para consultar y crear Hamas.
3. **1. Listar hamas** confirma que el Bearer token permite consultar la API.
4. **2. Crear hama** guarda automáticamente el ID del nuevo registro.
5. Ejecuta consultar, actualizar y eliminar para completar la demostración del CRUD.

El archivo de entorno contiene un secreto local y está excluido de Git. Si vuelves a generar el cliente, reemplaza `client_id` y `client_secret` dentro del entorno de Postman.
