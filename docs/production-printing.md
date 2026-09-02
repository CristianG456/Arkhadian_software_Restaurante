# Producción, impresión y precuenta

Al confirmar un pedido, Restaurant Core agrupa productos activos por centro y persiste un `PrintJob` por centro. El usuario nunca selecciona impresora. El worker `php artisan production:work` procesa la cola y conserva pendientes ante reinicios.

## Configuración

- Centros configurables; producto con destino predeterminado y variante con sobrescritura opcional.
- Impresora principal por centro y estructura preparada para prioridad o fallback explícito.
- `TEST_FILE` escribe localmente solo para desarrollo y pruebas.
- `WINDOWS_RAW_SHARE` escribe en una impresora previamente compartida en Windows con Generic/Text o RAW. No ejecuta comandos del frontend.

`PRINTED` significa que el driver aceptó el trabajo; no confirma salida física del papel. Las pruebas automatizadas no usan hardware real.

## Operación

- Comanda: instrucción para producción.
- Precuenta: resumen previo al pago, excluye cancelados, no cierra la mesa y muestra `NO ES FACTURA`.
- Factura: documento posterior al pago y fuera de esta fase.

`CUSTOMER_QR` queda marcado como solicitud del cliente y mantiene al mesero responsable. Cada reimpresión crea un trabajo distinto marcado `REIMPRESIÓN`. Los reintentos son limitados y no revierten el pedido.

Las cancelaciones notifican solo si la comanda original fue aceptada por el driver. Los traslados avisan únicamente a centros involucrados.

## Contrato futuro del mesero

Los endpoints `/api/waiter/staff/{staffMember}/...` reutilizan el motor y verifican propiedad y permisos. Permanecen bajo acceso administrativo local hasta incorporar autenticación real en una futura app Flutter. El identificador de mesero no es autenticación.

## Prueba manual con hardware

1. Compartir la impresora en Windows y registrar el mismo nombre.
2. Enviar prueba y verificar el papel manualmente.
3. Confirmar pedidos para Cocina y Bebidas.
4. Desconectar una impresora, comprobar `FAILED`, reconectar y reintentar.
5. Reiniciar el worker con un trabajo `PENDING` y comprobar continuidad.
