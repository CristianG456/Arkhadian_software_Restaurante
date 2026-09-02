# Catálogo de productos

El catálogo local mantiene la jerarquía `Category → Product → Variant`. Todo producto nace en una transacción con una variante predeterminada y visibilidad en `ADMIN_POS`, `WAITER`, `CUSTOMER_QR` y `PUBLIC_WEB`. Los cuatro canales se crean visibles; `is_active` controla administración y `is_available` representa disponibilidad operativa.

## Límites del dominio

- `ProductCategory` organiza el menú; no es un `ProductionCenter`.
- `Product` no es inventario y no almacena stock.
- `ProductVariant` define una presentación y su precio; no es un modificador.
- `ModifierGroup` es reutilizable y sus opciones suman deltas de precio.
- Los futuros pedidos enviarán IDs y Laravel resolverá precio de variante más modificadores. El frontend no será autoridad de totales.
- Producción, recetas, impuestos, combos, nube y snapshots de pedidos quedan para fases futuras.

```text
Product / Variant
  ├── Production Configuration  FUTURO
  ├── Recipe                    FUTURO
  ├── Tax Profile               FUTURO
  └── Order Item Snapshot       FUTURO
```

Los importes son `decimal(14,2)` en PostgreSQL y cadenas decimales en JSON. No se realizan cálculos monetarios con `float`.

## API administrativa

Las lecturas viven bajo `/api/catalog` y las escrituras usan el middleware `local.admin`, limitado a loopback mientras no exista autenticación. La lista de categorías incluye un modelo liviano de productos; `GET /api/catalog/products/{id}` devuelve variantes, canales, imágenes y asignaciones completas.

Las imágenes JPG, PNG y WEBP se guardan en el disco privado local con nombre ULID aleatorio. El JSON nunca expone el path interno; el binario se entrega mediante `/api/catalog/images/{id}` con `nosniff`.

## Desarrollo

Desde la raíz:

```powershell
.\scripts\dev.ps1
```

El seeder de desarrollo crea categorías y ejemplos solamente en entornos `local` y `testing`. Para validar de forma destructiva, use exclusivamente una base de pruebas:

```powershell
$env:DB_DATABASE='restaurant_core_test'
.\scripts\db-reset.ps1
```

El catálogo funciona sin Internet; requiere Restaurant Core y PostgreSQL locales.
