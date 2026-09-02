import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/state/app_controller.dart';
import '../../shared/app_theme.dart';
import '../home/operation_controller.dart';
import 'table_detail_screen.dart';

class MyTablesScreen extends ConsumerWidget {
  const MyTablesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final app = ref.watch(appControllerProvider);
    final state = ref.watch(operationProvider);
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(app.binding?.name ?? 'Restaurante'),
            Text(
              app.user?.name ?? '',
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
        actions: [
          IconButton(
            onPressed: () => ref.read(operationProvider.notifier).load(),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(operationProvider.notifier).load(),
        child: state.loading && state.myTables.isEmpty
            ? const Center(child: CircularProgressIndicator())
            : state.myTables.isEmpty
            ? ListView(
                children: const [
                  SizedBox(height: 180),
                  Icon(Icons.table_restaurant_outlined, size: 64),
                  SizedBox(height: 12),
                  Text('Aún no tienes mesas', textAlign: TextAlign.center),
                  Text(
                    'Toma una mesa libre enviando su primer pedido.',
                    textAlign: TextAlign.center,
                  ),
                ],
              )
            : ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Text(
                    'MIS MESAS',
                    style: Theme.of(context).textTheme.labelLarge,
                  ),
                  const SizedBox(height: 8),
                  ...state.myTables.map(
                    (table) => Card(
                      child: InkWell(
                        borderRadius: BorderRadius.circular(12),
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) =>
                                TableDetailScreen(sessionId: table.sessionId),
                          ),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      table.tableLabel,
                                      style: Theme.of(context)
                                          .textTheme
                                          .titleLarge
                                          ?.copyWith(
                                            fontWeight: FontWeight.bold,
                                          ),
                                    ),
                                    Text(table.areaName),
                                    const SizedBox(height: 6),
                                    Text(
                                      '${table.ordersCount} pedidos · ${DateTime.now().difference(table.openedAt).inMinutes} min',
                                    ),
                                    if (table.billRequested)
                                      const Chip(
                                        label: Text('CUENTA SOLICITADA'),
                                      ),
                                    if (table.customerRequests > 0)
                                      Chip(
                                        label: Text(
                                          '${table.customerRequests} solicitud cliente',
                                        ),
                                      ),
                                  ],
                                ),
                              ),
                              Text(
                                money(table.total),
                                style: Theme.of(context).textTheme.titleLarge
                                    ?.copyWith(fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
      ),
    );
  }
}
