# App Mesero

## Ejecutar

Configura `MOBILE_CORE_URL=http://IP_LAN_DEL_PC:8000` y `RESTAURANT_NAME` en `services/restaurant-core/.env`. Para un teléfono físico no uses `127.0.0.1`. Inicia Core en la red local:

```powershell
cd services\restaurant-core
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

En otra terminal:

```powershell
cd apps\waiter-mobile
flutter pub get
flutter devices
flutter run -d <device-id>
```

El teléfono y el PC deben compartir red. El APK debug se genera con `flutter build apk --debug` en `build/app/outputs/flutter-apk/app-debug.apk`. iOS requiere macOS, Xcode y Apple Developer.

## Vinculación y autenticación

En Admin Desktop abre `Configuración > Dispositivos`, genera una vinculación y escanea el QR desde Flutter. También puedes usar Restaurant Code y Pairing Code manuales. El Restaurant Code identifica la instalación y no es secreto; el Pairing Code/Token expira y se consume una vez. El QR versionado incluye la URL local de Core y no abre un navegador.

Core entrega una credencial opaca de dispositivo que Flutter guarda en Android Keystore o iOS Keychain. Admin puede revocar el teléfono. Luego el mesero entra con username y PIN; Core guarda el PIN hasheado, limita intentos y emite un token temporal ligado al dispositivo. El rol debe ser `WAITER` y estar activo.

Seed de desarrollo: usuarios `cristian`, `andres` y `camila`, PIN `1234`. Cámbialos antes de uso real.

## Operación

`Mis mesas` muestra las sesiones propias y `Mesas` las libres. El primer pedido abre la sesión con el mesero autenticado. El catálogo usa canal `WAITER`, variantes, opciones y notas. `clientOperationId` evita duplicados; Core siempre recalcula los precios y valida propiedad/permisos.

La app permite pedidos, alertas `CUSTOMER_QR`, precuenta, solicitud de cuenta, reimpresión autorizada, traslado de mesa y transferencia de mesero. Mover conserva sesión/pedidos/total; transferir cambia responsable y notifica. La precuenta no factura ni cierra caja.

## Local-First y seguridad

Flutter nunca conecta a PostgreSQL ni a impresoras. Si Internet cae y Core local sigue activo, opera normalmente. Si Core cae, la UI se bloquea, muestra reconexión y revalida al volver. No hay cola offline de pedidos.

- Restaurant Code: identidad pública.
- Pairing Code/Token: autorización temporal de un uso.
- Device Credential: secreto revocable del teléfono.
- User Credential: username/PIN hasheado.
- Access Token: temporal, hasheado y unido al dispositivo.
- Authorization: siempre en Core; no se confían precios, waiterId ni propiedad enviados por Flutter.
- Logs: no deben registrar PIN ni tokens.

Android permite HTTP local solo en debug; producción requiere HTTPS. iOS ya declara cámara y red local. Cloud Sync completo y cambio automático Local/Cloud no forman parte de esta fase.

## Validación

Ejecuta `flutter analyze`, `flutter test`, `flutter build apk --debug`, `php artisan test` y `vendor\bin\pint`. Solo en una base desechable y con confirmación explícita usa `php artisan migrate:fresh --seed`.
