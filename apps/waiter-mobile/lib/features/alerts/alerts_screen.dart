import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../home/operation_controller.dart';

class AlertsScreen extends ConsumerWidget {
  const AlertsScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(operationProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Alertas')),
      body: RefreshIndicator(
        onRefresh: () => ref.read(operationProvider.notifier).load(),
        child: state.alerts.isEmpty
            ? ListView(
                children: const [
                  SizedBox(height: 180),
                  Icon(Icons.notifications_none, size: 64),
                  Text('No hay alertas', textAlign: TextAlign.center),
                ],
              )
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: state.alerts.length,
                separatorBuilder: (_, _) => const Divider(),
                itemBuilder: (_, index) {
                  final alert = state.alerts[index],
                      payload = alert['payload'] as Map<String, dynamic>? ?? {};
                  final unread = alert['readAt'] == null;
                  return ListTile(
                    tileColor: unread
                        ? Theme.of(context).colorScheme.primaryContainer
                              .withValues(alpha: .35)
                        : null,
                    leading: Icon(
                      alert['type'] == 'CUSTOMER_ORDER'
                          ? Icons.qr_code
                          : Icons.swap_horiz,
                    ),
                    title: Text(
                      payload['message']?.toString() ?? 'Nueva alerta',
                      style: TextStyle(
                        fontWeight: unread
                            ? FontWeight.bold
                            : FontWeight.normal,
                      ),
                    ),
                    subtitle: Text(
                      [
                        payload['areaName'],
                        payload['tableLabel'],
                      ].where((item) => item != null).join(' · '),
                    ),
                    onTap: unread
                        ? () => ref
                              .read(operationProvider.notifier)
                              .post('/api/waiter/alerts/${alert['id']}/read')
                        : null,
                  );
                },
              ),
      ),
    );
  }
}
