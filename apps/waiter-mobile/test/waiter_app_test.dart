import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:waiter_mobile/core/state/app_controller.dart';
import 'package:waiter_mobile/core/storage/secure_store.dart';
import 'package:waiter_mobile/features/auth/login_screen.dart';
import 'package:waiter_mobile/features/connectivity/reconnect_screen.dart';
import 'package:waiter_mobile/features/orders/order_builder_screen.dart';
import 'package:waiter_mobile/features/pairing/pairing_screen.dart';

Widget screen(Widget child) => ProviderScope(
  overrides: [
    appControllerProvider.overrideWith(
      (ref) => AppController(const SecureStore()),
    ),
  ],
  child: MaterialApp(home: child),
);

void main() {
  testWidgets('pairing starts with QR and manual code options', (tester) async {
    await tester.pumpWidget(screen(const PairingScreen()));
    expect(find.text('Vincula este dispositivo'), findsOneWidget);
    expect(find.text('Escanear QR'), findsOneWidget);
    expect(find.text('Código del restaurante'), findsOneWidget);
  });

  testWidgets('waiter login protects the PIN input', (tester) async {
    await tester.pumpWidget(screen(const LoginScreen()));
    expect(find.text('Acceso para meseros'), findsOneWidget);
    final pin = tester.widget<TextField>(find.byType(TextField).last);
    expect(pin.obscureText, isTrue);
  });

  testWidgets('core disconnect blocks operations and offers retry', (
    tester,
  ) async {
    await tester.pumpWidget(screen(const ReconnectScreen()));
    expect(find.text('Reconectando con el restaurante...'), findsOneWidget);
    expect(find.text('Reintentar'), findsOneWidget);
  });

  test('client operation id is a valid ULID', () {
    final first = createUlid(), second = createUlid();
    expect(first, matches(RegExp(r'^[0-9A-HJKMNP-TV-Z]{26}$')));
    expect(second, isNot(first));
  });
}
