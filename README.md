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

## Secciones de la interfaz

- **Crear Hama (`/crear-hama`):** editor dedicado con tablero 50 × 50, pincel, relleno, borrador, historial, vista previa y visibilidad pública o privada.
- **Proyectos (`/proyectos`):** galería de patrones guardados con búsqueda, edición y eliminación.
- **Inventario (`/inventario`):** colores utilizados, cantidad de cuentas y proyectos donde aparece cada tono.
- **MeshiVerse (`/meshiverse`):** información recibida automáticamente desde la API pública externa.
- **Configuración (`/configuracion`):** preferencias generales para usuarios y conexiones API exclusivas para administradores.
- **Conexiones API (`/configuracion/api`):** generación de tokens Passport, estado de MeshiVerse y contrato compartido.

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
- `es_publico`: indica si el proyecto puede compartirse mediante la API externa.
- `patron`: lista de posiciones y colores pintados en el tablero de 50 × 50.

## Endpoints

Todos los endpoints del CRUD requieren este encabezado:

```http
Authorization: Bearer <access_token>
Accept: application/json
```

| Método | Endpoint | Operación | Scope requerido |
| --- | --- | --- | --- |
| GET | `/api/v1/hamas` | Listar proyectos públicos para clientes externos | `hamas.read` |
| GET | `/api/v1/colors` | Listar colores utilizados y sus Hamas | `hamas.read` |
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

La pantalla `http://127.0.0.1:8000/configuracion/api` permite comprobar el intercambio y preparar accesos para otra aplicación sin escribir credenciales OAuth manualmente. La dirección anterior `/conexiones` redirige a esta sección.

- **Estado automático:** al abrir la pantalla, Laravel consulta MeshiVerse desde el servidor e indica si realmente está recibiendo datos. No hay que pegar URLs ni probar tokens a mano.
- **Generar token para compartir:** ProyectoAPI crea internamente las credenciales Passport y devuelve un Bearer token listo para copiar. Solo se solicita el nombre del proyecto y los permisos.
- **Verificar al generar:** si se concede `hamas.read`, la interfaz usa el token recién creado para consultar automáticamente los endpoints de proyectos y colores.
- **Contrato compartido:** muestra los endpoints que permiten ver proyectos Hama, consultar sus colores y crear un patrón de hasta 50 × 50.

La generación automática está limitada al entorno local y protegida con CSRF. Solo se comparte el `access_token`; el Client Secret permanece administrado por Laravel Passport. El endpoint `POST /api/v1/connections/handshake` continúa disponible para que Postman u otra aplicación confirmen los permisos del token.

## Integración con MeshiVerse

El apartado `http://127.0.0.1:8000/meshiverse` muestra automáticamente los datos públicos enviados por `https://meshiverse.fataldesk.lat`.

- Consulta `GET /api/v1/status` para comprobar que el servicio está activo.
- Consulta `GET /api/v1/dish-of-the-day` para mostrar el platillo, imagen, ingredientes y preparación.
- No solicita tokens, URLs ni configuración al visitante.
- Si la API no responde, la interfaz muestra el estado sin interrumpir el CRUD de Hamas.

## Ejemplo para crear un Hama

```json
{
    "nombre": "Estrella cósmica",
    "descripcion": "Patrón de estrella con cuentas amarillas",
    "ancho": 50,
    "alto": 50,
    "cantidad_cuentas": 4,
    "color_principal": "#F59E0B",
    "es_publico": true,
    "patron": [
        { "posicion": 1124, "color": "#F59E0B" },
        { "posicion": 1125, "color": "#F59E0B" },
        { "posicion": 1174, "color": "#F59E0B" },
        { "posicion": 1175, "color": "#F59E0B" }
    ]
}
```

Cada posición va de `0` a `2499`. Cuando se envía `patron`, Laravel calcula automáticamente `cantidad_cuentas` según las posiciones recibidas.

Los tokens externos solo reciben proyectos con `es_publico: true`. Los usuarios autenticados en el estudio conservan acceso a sus propios proyectos privados y el administrador puede revisar todos.

## Ejecución local

### Usuarios de demostración

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@proyectoapi.local` | `AdminHama2026!` |
| Usuario | `usuario@proyectoapi.local` | `UsuarioHama2026!` |

Estas contraseñas son únicamente para la demostración local y pueden reemplazarse con `DEMO_ADMIN_PASSWORD` y `DEMO_USER_PASSWORD` antes de ejecutar el seeder.

Desde la terminal de Laragon:

```bash
php artisan migrate
php artisan serve
```

Direcciones disponibles:

- Galería de proyectos: `http://127.0.0.1:8000/proyectos`
- Editor Hama: `http://127.0.0.1:8000/crear-hama`
- Inventario de colores: `http://127.0.0.1:8000/inventario`
- Datos de MeshiVerse: `http://127.0.0.1:8000/meshiverse`
- Configuración: `http://127.0.0.1:8000/configuracion`
- Centro de conexiones: `http://127.0.0.1:8000/configuracion/api`
- API de Hamas: `http://127.0.0.1:8000/api/v1/hamas`
- API de colores: `http://127.0.0.1:8000/api/v1/colors`

## Integración con otro proyecto

Este proyecto ya actúa como proveedor de datos para otra aplicación. El flujo recomendado es:

1. Generar el token desde `/configuracion/api` seleccionando los permisos necesarios.
2. Entregar ese Bearer token al proyecto consumidor.
3. Con `hamas.read`, el consumidor consulta `GET /api/v1/hamas` y `GET /api/v1/colors`.
4. Con `hamas.write`, envía un diseño mediante `POST /api/v1/hamas`.
5. Puede confirmar sus permisos mediante `POST /api/v1/connections/handshake`.

Cuando se defina otra API privada, sus credenciales deben configurarse en el servidor y nunca incrustarse en el navegador.

## Presentación con Postman

Importa estos dos archivos:

```text
postman/ProyectoAPI-Hamas.postman_collection.json
postman/ProyectoAPI-Passport.local.postman_environment.json
```

Selecciona el entorno **ProyectoAPI Passport (local)** y ejecuta las peticiones en orden:

1. **0. Generar token OAuth2** obtiene el token y lo guarda automáticamente.
2. **0.1 Confirmar conexión entre proyectos** verifica el token y los permisos para consultar proyectos, colores y crear Hamas.
3. **1. Listar hamas** confirma que el Bearer token permite consultar la API.
4. **1.1 Listar colores utilizados** demuestra la información adicional compartida.
5. **2. Crear hama** guarda automáticamente el ID del nuevo registro.
6. Ejecuta consultar, actualizar y eliminar para completar la demostración del CRUD.

El archivo de entorno contiene un secreto local y está excluido de Git. Si vuelves a generar el cliente, reemplaza `client_id` y `client_secret` dentro del entorno de Postman.
