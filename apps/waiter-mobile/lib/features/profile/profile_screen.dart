import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/state/app_controller.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(appControllerProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Perfil')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const CircleAvatar(radius: 38, child: Icon(Icons.person, size: 42)),
          const SizedBox(height: 12),
          Text(
            state.user?.name ?? '',
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.headlineSmall
                ?.copyWith(fontWeight: FontWeight.bold),
          ),
          const Text('Mesero', textAlign: TextAlign.center),
          const SizedBox(height: 24),
          ListTile(
            leading: const Icon(Icons.restaurant),
            title: const Text('Restaurante'),
            subtitle: Text(state.binding?.name ?? ''),
          ),
          ListTile(
            leading: const Icon(Icons.phone_android),
            title: const Text('Dispositivo'),
            subtitle: Text(state.binding?.deviceName ?? ''),
          ),
          ListTile(
            leading: const Icon(Icons.lan),
            title: const Text('Modo'),
            subtitle: Text(state.binding?.mode == 'CLOUD' ? 'Nube' : 'Local'),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () => ref.read(appControllerProvider.notifier).logout(),
            child: const Text('Cerrar sesión'),
          ),
          TextButton(
            onPressed: () => showDialog(
              context: context,
              builder: (_) => AlertDialog(
                title: const Text('Desvincular dispositivo'),
                content: const Text(
                  'Necesitarás un nuevo código temporal del administrador para volver a vincularlo.',
                ),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancelar'),
                  ),
                  FilledButton(
                    onPressed: () {
                      Navigator.pop(context);
                      ref.read(appControllerProvider.notifier).unpair();
                    },
                    child: const Text('Desvincular'),
                  ),
                ],
              ),
            ),
            child: const Text('Desvincular dispositivo'),
          ),
        ],
      ),
    );
  }
}
