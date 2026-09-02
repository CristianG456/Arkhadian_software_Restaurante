import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/state/app_controller.dart';
import '../features/auth/login_screen.dart';
import '../features/connectivity/reconnect_screen.dart';
import '../features/home/home_shell.dart';
import '../features/pairing/pairing_screen.dart';
import '../shared/app_theme.dart';

class WaiterApp extends ConsumerWidget {
  const WaiterApp({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(appControllerProvider);
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Mesero',
      theme: waiterTheme(),
      home: switch (state.stage) {
        AppStage.loading => const Scaffold(
          body: Center(child: CircularProgressIndicator()),
        ),
        AppStage.pairing => const PairingScreen(),
        AppStage.login => const LoginScreen(),
        AppStage.ready => const HomeShell(),
        AppStage.reconnecting => const ReconnectScreen(),
      },
    );
  }
}
