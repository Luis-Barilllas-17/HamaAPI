# ProyectoAPI — CRUD de patrones Hama

Aplicación creada con Laravel 13 para administrar patrones de cuentas Hama mediante una API REST y MySQL.

## Cumplimiento de la tarea

- Framework Laravel 13.
- Base de datos MySQL `ProyectoAPI`.
- CRUD completo expuesto exclusivamente desde `routes/api.php`.
- Endpoints REST para crear, consultar, actualizar y eliminar Hamas.
- Colección de Postman lista para la presentación.
- Interfaz web opcional que consume la misma API; no contiene lógica CRUD en `routes/web.php`.

## Campos de un Hama

- `nombre`: nombre del patrón.
- `descripcion`: información opcional.
- `ancho`: ancho del patrón, entre 1 y 50.
- `alto`: alto del patrón, entre 1 y 50.
- `cantidad_cuentas`: cuentas necesarias para construirlo.
- `color_principal`: color hexadecimal, por ejemplo `#8B5CF6`.
- `patron`: lista de posiciones y colores pintados en el tablero de 50 × 50.

## Endpoints

| Método | Endpoint | Operación |
| --- | --- | --- |
| GET | `/api/hamas` | Listar todos |
| POST | `/api/hamas` | Crear |
| GET | `/api/hamas/{hama}` | Consultar uno |
| PUT/PATCH | `/api/hamas/{hama}` | Actualizar |
| DELETE | `/api/hamas/{hama}` | Eliminar |

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
- Visor JSON: `http://127.0.0.1:8000/respuesta`
- API: `http://127.0.0.1:8000/api/hamas`

## Presentación con Postman

Importa el archivo:

```text
postman/ProyectoAPI-Hamas.postman_collection.json
```

Ejecuta las peticiones en orden. La petición **Crear hama** guarda automáticamente el ID del registro, que luego usan las peticiones de consulta, actualización y eliminación.
