import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../home/operation_controller.dart';
import '../orders/order_builder_screen.dart';

class AvailableTablesScreen extends ConsumerWidget {
  const AvailableTablesScreen({super.key});
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(operationProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Mesas')),
      body: RefreshIndicator(
        onRefresh: () => ref.read(operationProvider.notifier).load(),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: state.areas
              .expand(
                (area) => [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(4, 16, 4, 8),
                    child: Text(
                      area['name'].toString(),
                      style: Theme.of(context).textTheme.titleLarge
                          ?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ),
                  GridView.count(
                    crossAxisCount: MediaQuery.sizeOf(context).width > 600
                        ? 3
                        : 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    childAspectRatio: 1.35,
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    children: ((area['tables'] as List?) ?? []).map((raw) {
                      final table = raw as Map<String, dynamic>;
                      final free = table['state'] == 'FREE';
                      return Card(
                        color: free ? Colors.white : Colors.grey.shade200,
                        child: InkWell(
                          onTap: free
                              ? () => Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => OrderBuilderScreen(
                                      tableId: table['id'].toString(),
                                      title:
                                          '${area['name']} · ${table['label']}',
                                    ),
                                  ),
                                )
                              : null,
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  table['label'].toString(),
                                  style: const TextStyle(
                                    fontSize: 20,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                const Spacer(),
                                Text(
                                  free
                                      ? 'Libre · Tomar pedido'
                                      : 'Ocupada · ${table['waiterName'] ?? ''}',
                                  style: TextStyle(
                                    color: free
                                        ? Colors.green.shade700
                                        : Colors.grey.shade700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                ].whereType<Widget>(),
              )
              .toList(),
        ),
      ),
    );
  }
}
