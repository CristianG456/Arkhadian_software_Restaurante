# Pedidos y operación de mesas

## Modelo operativo

`RestaurantTable` representa la configuración física de una mesa. `TableSession` representa una atención temporal y es la única fuente para saber si una mesa está libre u ocupada. Una mesa libre no tiene sesión `OPEN`; el primer pedido confirmado crea la sesión y el pedido en una sola transacción.

PostgreSQL impide dos sesiones abiertas para la misma mesa mediante el índice parcial `table_sessions_one_open_per_table`. Una futura caja cerrará la sesión (`CLOSED`); Pedidos no simula pagos ni cierres.

## Responsable y origen

Cada sesión abierta tiene exactamente un `assigned_waiter_id`. Cada pedido conserva el mesero responsable histórico y su origen (`WAITER`, `ADMIN_POS` o `CUSTOMER_QR`). Por eso `responsible_waiter = Cristian` y `source = CUSTOMER_QR` es una combinación válida.

Las transferencias cambian únicamente el responsable actual, crean historial en `table_waiter_assignments` y emiten `TableWaiterTransferred` después del commit. Los pedidos anteriores no se reescriben.

## Traslados

Mover una mesa en configuración cambia `RestaurantTable.area_id`. Trasladar una atención cambia `TableSession.table_id`, conserva el mismo ID de sesión, mesero, pedidos y total, y registra el movimiento. El destino debe estar activo y libre; no se combinan cuentas.

## Precios, snapshots e idempotencia

El cliente solo envía IDs, cantidad, modificadores y notas. `OrderPricingService` valida producto, variante, canal y reglas min/max, y calcula en unidades menores, sin `float`.

`order_items` y `order_item_modifiers` guardan nombres y precios snapshot. Los cambios posteriores del catálogo no alteran el historial. `client_operation_id` es único y permite repetir una solicitud sin duplicar el pedido.

## API local

- `GET /api/operations/tables`: read model resumido.
- `GET /api/tables/{table}/current-session` y `GET /api/table-sessions/{session}`.
- `POST /api/orders`, cancelación de pedido e ítem.
- Transferencia de mesero y traslado de sesión.
- Administración mínima de `/api/staff`.

Todas las escrituras conservan `LocalAdminOnly`. El frontend usa polling moderado de 10 segundos y mantiene la pantalla visible si Core o PostgreSQL dejan de responder.
