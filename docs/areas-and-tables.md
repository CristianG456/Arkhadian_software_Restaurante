# Áreas y mesas

El módulo configura la distribución física del restaurante sin mezclarla
con el estado operativo de pedidos. Restaurant Admin consume la API local;
Restaurant Core es la única capa que accede a PostgreSQL.

## Entidades

- `Area`: espacio configurable con ULID estable, nombre, orden e indicador
  `is_active`.
- `RestaurantTable`: mesa con ULID estable, etiqueta visible, orden,
  indicador `is_active` y pertenencia obligatoria a un área.

Los nombres se guardan recortados y con espacios internos normalizados. Las
columnas `normalized_name` y `normalized_label` garantizan en PostgreSQL que
no existan áreas conceptualmente duplicadas ni dos mesas con la misma etiqueta
dentro de un área. La misma etiqueta sí puede existir en áreas distintas.

`is_active` solo indica si el elemento está habilitado en configuración. No
significa libre u ocupado. El estado operativo y el mesero responsable
pertenecerán en el futuro a `TableSession`, no a `restaurant_tables`.

## Eliminación y conservación

Un área vacía puede eliminarse físicamente. Un área con mesas se conserva y
solo puede desactivarse; Restaurant Core rechaza su eliminación. Las mesas
pueden desactivarse y reactivarse. La capa de servicio contiene el punto donde
se agregarán las comprobaciones de sesiones antes de permitir eliminarlas en
una fase futura.

## API local

Los endpoints viven bajo `/api/areas` y `/api/tables`. Listados, CRUD,
reordenamiento, movimientos y creación masiva usan el envelope estándar
`{ success, data|error }`. Bulk y reorder son operaciones transaccionales y
envían una sola solicitud.

## Migraciones y datos de desarrollo

Después de integrar la feature:

```powershell
.\scripts\migrate.ps1
```

Para reconstruir únicamente una base local o de testing confirmada como
descartable:

```powershell
.\scripts\db-reset.ps1
```

`DatabaseSeeder` crea ejemplos de salón, VIP, terraza y mesas solo en los
entornos `local` y `testing`. No existen áreas obligatorias de producción y
el seeder es idempotente.

## Límites de operación

RestaurantTable representa configuración física. Nunca guarda ocupación,
mesero, pedidos, cliente ni total. Mover una mesa a otra área cambia su
configuración; trasladar un consumo futuro cambiará TableSession.table_id y
no moverá la mesa física.

La normalización es conservadora: 04 y 4 siguen siendo etiquetas distintas,
igual que A01 y A1. Frontend valida para UX, Form Requests validan la API y
los constraints PostgreSQL son la autoridad final ante concurrencia.

## Credenciales QR

TableQrCredential mantiene una identidad opaca y revocable separada de
RestaurantTable y de las futuras TableSession y CustomerSession.

- El token contiene 256 bits aleatorios y no deriva de IDs o nombres.
- Un índice único parcial permite una sola credencial active por mesa.
- Regenerar revoca la anterior y crea una nueva versión en una transacción.
- Revocar invalida el QR inmediatamente en la autoridad local.
- Renombrar área o mesa no cambia la identidad de la credencial.

TABLE_QR_PUBLIC_BASE_URL configura el prefijo público codificado. La subweb,
las sesiones y los pedidos por QR permanecen fuera de este módulo.

Los endpoints de QR viven bajo /api/tables/{table}/qr, con operaciones
regenerate y revoke. Mover configuración usa /api/tables/{table}/move.
Todos conservan el envelope estándar y nunca exponen SQLSTATE ni tokens en
logs normales.
