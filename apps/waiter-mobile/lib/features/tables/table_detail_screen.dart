import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../shared/app_theme.dart';
import '../home/operation_controller.dart';
import '../orders/order_builder_screen.dart';

class TableDetailScreen extends ConsumerStatefulWidget {
  const TableDetailScreen({super.key, required this.sessionId});
  final String sessionId;
  @override
  ConsumerState<TableDetailScreen> createState() => _TableDetailScreenState();
}

class _TableDetailScreenState extends ConsumerState<TableDetailScreen> {
  Map<String, dynamic>? session;
  List<String> permissions = [];
  bool loading = true;
  String? error;
  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    try {
      final data = await ref
          .read(operationProvider.notifier)
          .get('/api/waiter/table-sessions/${widget.sessionId}');
      setState(() {
        session = data['session'] as Map<String, dynamic>;
        permissions = ((data['permissions'] as List?) ?? [])
            .map((item) => item.toString())
            .toList();
        loading = false;
      });
    } on ApiFailure catch (failure) {
      setState(() {
        error = failure.message;
        loading = false;
      });
    }
  }

  Future<void> action(
    String path,
    String message, [
    Map<String, dynamic>? body,
  ]) async {
    try {
      await ref.read(operationProvider.notifier).post(path, body);
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(message)));
      }
      await load();
    } on ApiFailure catch (failure) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(failure.message)));
      }
    }
  }

  Future<void> cancelItem(Map<String, dynamic> item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Cancelar producto'),
        content: Text(
          '¿Confirmas cancelar ${item['quantity']} × ${item['productName']}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Volver'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Cancelar producto'),
          ),
        ],
      ),
    );
    if (confirmed == true) {
      await action(
        '/api/waiter/order-items/${item['id']}/cancel',
        'Producto cancelado.',
        {'reason': 'Cancelado por el mesero'},
      );
    }
  }

  Future<void> showPreaccount() async {
    final data = await ref
        .read(operationProvider.notifier)
        .get('/api/waiter/table-sessions/${widget.sessionId}/preaccount');
    if (!mounted) return;
    final account = data['preaccount'] as Map<String, dynamic>;
    await showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('PRECUENTA'),
        content: SizedBox(
          width: double.maxFinite,
          child: ListView(
            shrinkWrap: true,
            children: [
              const Text(
                'NO ES FACTURA',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.bold),
              ),
              ...((account['items'] as List?) ?? []).map((raw) {
                final item = raw as Map<String, dynamic>;
                return ListTile(
                  title: Text('${item['quantity']} × ${item['name']}'),
                  trailing: Text(money(item['lineTotal'])),
                );
              }),
              const Divider(),
              ListTile(
                title: const Text('TOTAL'),
                trailing: Text(money(account['total'])),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cerrar'),
          ),
          if (permissions.contains('preaccount.print'))
            FilledButton(
              onPressed: () {
                Navigator.pop(dialogContext);
                action(
                  '/api/waiter/table-sessions/${widget.sessionId}/preaccount/print',
                  'Precuenta enviada a impresión.',
                );
              },
              child: const Text('Imprimir'),
            ),
        ],
      ),
    );
  }

  Future<void> transfer() async {
    final data = await ref
        .read(operationProvider.notifier)
        .get('/api/waiter/staff');
    if (!mounted) return;
    final people = ((data['staff'] as List?) ?? [])
        .cast<Map<String, dynamic>>();
    final selected = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => SimpleDialog(
        title: const Text('Transferir mesa'),
        children: people
            .map(
              (person) => SimpleDialogOption(
                onPressed: () => Navigator.pop(dialogContext, person),
                child: Padding(
                  padding: const EdgeInsets.all(10),
                  child: Text(person['name'].toString()),
                ),
              ),
            )
            .toList(),
      ),
    );
    if (selected != null) {
      await action(
        '/api/waiter/table-sessions/${widget.sessionId}/transfer',
        'Mesa transferida.',
        {
          'waiterId': selected['id'],
          'reason': 'Transferencia desde app Mesero',
        },
      );
      if (mounted) Navigator.pop(context);
    }
  }

  Future<void> move() async {
    final operation = ref.read(operationProvider);
    final free = operation.areas
        .expand(
          (area) => ((area['tables'] as List?) ?? [])
              .cast<Map<String, dynamic>>()
              .where((table) => table['state'] == 'FREE')
              .map((table) => {...table, 'areaName': area['name']}),
        )
        .toList();
    final selected = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => SimpleDialog(
        title: const Text('Trasladar atención'),
        children: free
            .map(
              (table) => SimpleDialogOption(
                onPressed: () => Navigator.pop(dialogContext, table),
                child: Padding(
                  padding: const EdgeInsets.all(10),
                  child: Text('${table['areaName']} · ${table['label']}'),
                ),
              ),
            )
            .toList(),
      ),
    );
    if (selected != null) {
      await action(
        '/api/waiter/table-sessions/${widget.sessionId}/move',
        'Atención trasladada.',
        {
          'targetTableId': selected['id'],
          'reason': 'Traslado desde app Mesero',
        },
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (session == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(error ?? 'No se pudo cargar la mesa.')),
      );
    }
    final table = session!['table'] as Map<String, dynamic>;
    final orders = ((session!['orders'] as List?) ?? [])
        .cast<Map<String, dynamic>>();
    return Scaffold(
      appBar: AppBar(
        title: Text('${table['areaName']} · ${table['label']}'),
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) {
              if (value == 'transfer') transfer();
              if (value == 'move') move();
            },
            itemBuilder: (_) => [
              if (permissions.contains('tables.transfer_own'))
                const PopupMenuItem(
                  value: 'transfer',
                  child: Text('Transferir mesa'),
                ),
              if (permissions.contains('tables.move_own'))
                const PopupMenuItem(
                  value: 'move',
                  child: Text('Trasladar atención'),
                ),
            ],
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'TOTAL',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
                    Text(
                      money(session!['total']),
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),
            if (session!['billRequestedAt'] != null)
              const Chip(label: Text('CUENTA SOLICITADA')),
            ...orders.map(
              (order) => Card(
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              'Pedido #${order['orderNumber']}',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          if (order['source'] == 'CUSTOMER_QR')
                            const Chip(label: Text('Solicitado por cliente')),
                        ],
                      ),
                      ...((order['items'] as List?) ?? []).map((raw) {
                        final item = raw as Map<String, dynamic>;
                        return ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            '${item['quantity']} × ${item['productName']}',
                          ),
                          subtitle: Text(item['notes']?.toString() ?? ''),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(money(item['lineTotal'])),
                              if (permissions.contains('orders.cancel') &&
                                  item['status'] != 'CANCELLED')
                                IconButton(
                                  tooltip: 'Cancelar producto',
                                  onPressed: () => cancelItem(item),
                                  icon: const Icon(Icons.cancel_outlined),
                                ),
                            ],
                          ),
                        );
                      }),
                      if (permissions.contains('orders.reprint'))
                        TextButton.icon(
                          onPressed: () => action(
                            '/api/waiter/orders/${order['id']}/reprint',
                            'Reimpresión agregada.',
                            {'reason': 'Solicitada por mesero'},
                          ),
                          icon: const Icon(Icons.print),
                          label: const Text('Reimprimir comanda'),
                        ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 100),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ElevatedButton.icon(
                onPressed: () async {
                  await Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => OrderBuilderScreen(
                        tableId: table['id'].toString(),
                        sessionId: widget.sessionId,
                        title: '${table['areaName']} · ${table['label']}',
                      ),
                    ),
                  );
                  load();
                },
                icon: const Icon(Icons.add),
                label: const Text('AGREGAR PEDIDO'),
              ),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: permissions.contains('preaccount.view')
                          ? showPreaccount
                          : null,
                      child: const Text('Precuenta'),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton(
                      onPressed: session!['billRequestedAt'] == null
                          ? () => action(
                              '/api/waiter/table-sessions/${widget.sessionId}/request-bill',
                              'Cuenta solicitada.',
                            )
                          : null,
                      child: Text(
                        session!['billRequestedAt'] == null
                            ? 'Solicitar cuenta'
                            : 'Cuenta solicitada',
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
