import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../shared/app_theme.dart';
import '../home/operation_controller.dart';

class CartLine {
  CartLine({
    required this.product,
    required this.variant,
    required this.modifierIds,
    required this.quantity,
    required this.notes,
  });
  final Map<String, dynamic> product, variant;
  final List<String> modifierIds;
  int quantity;
  final String notes;
  double get total =>
      (double.tryParse(variant['price'].toString()) ?? 0) * quantity;
}

class OrderBuilderScreen extends ConsumerStatefulWidget {
  const OrderBuilderScreen({
    super.key,
    required this.tableId,
    required this.title,
    this.sessionId,
  });
  final String tableId, title;
  final String? sessionId;
  @override
  ConsumerState<OrderBuilderScreen> createState() => _OrderBuilderScreenState();
}

class _OrderBuilderScreenState extends ConsumerState<OrderBuilderScreen> {
  List<Map<String, dynamic>> categories = [];
  final cart = <CartLine>[];
  int selectedCategory = 0;
  bool loading = true, sending = false;
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
          .get('/api/waiter/catalog');
      setState(() {
        categories = ((data['categories'] as List?) ?? [])
            .cast<Map<String, dynamic>>();
        loading = false;
      });
    } on ApiFailure catch (failure) {
      setState(() {
        error = failure.message;
        loading = false;
      });
    }
  }

  Future<void> addProduct(Map<String, dynamic> product) async {
    if (product['isAvailable'] != true) return;
    final variants = ((product['variants'] as List?) ?? [])
        .cast<Map<String, dynamic>>()
        .where(
          (item) => item['isActive'] == true && item['isAvailable'] == true,
        )
        .toList();
    final groups = ((product['modifierAssignments'] as List?) ?? [])
        .cast<Map<String, dynamic>>();
    if (variants.isEmpty) {
      setState(() => error = 'Este producto no tiene presentación disponible.');
      return;
    }
    if (variants.length == 1 &&
        groups.isEmpty &&
        product['allowNotes'] != true) {
      setState(
        () => cart.add(
          CartLine(
            product: product,
            variant: variants.first,
            modifierIds: const [],
            quantity: 1,
            notes: '',
          ),
        ),
      );
      return;
    }
    final line = await showModalBottomSheet<CartLine>(
      context: context,
      isScrollControlled: true,
      builder: (_) => ProductConfigurator(
        product: product,
        variants: variants,
        groups: groups,
      ),
    );
    if (line != null) setState(() => cart.add(line));
  }

  Future<void> send() async {
    setState(() {
      sending = true;
      error = null;
    });
    try {
      final path = widget.sessionId == null
          ? '/api/waiter/tables/${widget.tableId}/orders'
          : '/api/waiter/table-sessions/${widget.sessionId}/orders';
      await ref.read(operationProvider.notifier).post(path, {
        'clientOperationId': createUlid(),
        'items': cart
            .map(
              (line) => {
                'productId': line.product['id'],
                'variantId': line.variant['id'],
                'quantity': line.quantity,
                'modifierOptionIds': line.modifierIds,
                if (line.notes.isNotEmpty) 'notes': line.notes,
              },
            )
            .toList(),
      });
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(const SnackBar(content: Text('Pedido enviado.')));
        Navigator.pop(context, true);
      }
    } on ApiFailure catch (failure) {
      setState(() => error = failure.message);
    } finally {
      if (mounted) setState(() => sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final products = categories.isEmpty
        ? <Map<String, dynamic>>[]
        : ((categories[selectedCategory]['products'] as List?) ?? [])
              .cast<Map<String, dynamic>>();
    final total = cart.fold<double>(0, (sum, line) => sum + line.total);
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                if (error != null)
                  MaterialBanner(
                    content: Text(error!),
                    actions: [
                      TextButton(
                        onPressed: () => setState(() => error = null),
                        child: const Text('Cerrar'),
                      ),
                    ],
                  ),
                SizedBox(
                  height: 52,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    scrollDirection: Axis.horizontal,
                    itemCount: categories.length,
                    separatorBuilder: (_, _) => const SizedBox(width: 8),
                    itemBuilder: (_, index) => ChoiceChip(
                      label: Text(categories[index]['name'].toString()),
                      selected: selectedCategory == index,
                      onSelected: (_) =>
                          setState(() => selectedCategory = index),
                    ),
                  ),
                ),
                Expanded(
                  child: GridView.builder(
                    padding: const EdgeInsets.all(12),
                    gridDelegate:
                        const SliverGridDelegateWithMaxCrossAxisExtent(
                          maxCrossAxisExtent: 210,
                          childAspectRatio: 1.35,
                          mainAxisSpacing: 10,
                          crossAxisSpacing: 10,
                        ),
                    itemCount: products.length,
                    itemBuilder: (_, index) {
                      final product = products[index],
                          available = product['isAvailable'] == true;
                      return Card(
                        color: available ? Colors.white : Colors.grey.shade200,
                        child: InkWell(
                          onTap: available ? () => addProduct(product) : null,
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  product['name'].toString(),
                                  maxLines: 2,
                                  style: const TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                const Spacer(),
                                Text(
                                  available
                                      ? money(product['basePrice'])
                                      : 'Agotado',
                                  style: TextStyle(
                                    color: available
                                        ? Colors.black
                                        : Colors.red,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
                if (cart.isNotEmpty)
                  Container(
                    color: Colors.white,
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        SizedBox(
                          height: min(130, cart.length * 52),
                          child: ListView.builder(
                            itemCount: cart.length,
                            itemBuilder: (_, index) {
                              final line = cart[index];
                              return Row(
                                children: [
                                  IconButton(
                                    onPressed: () => setState(
                                      () => line.quantity = max(
                                        1,
                                        line.quantity - 1,
                                      ),
                                    ),
                                    icon: const Icon(
                                      Icons.remove_circle_outline,
                                    ),
                                  ),
                                  Text('${line.quantity}'),
                                  IconButton(
                                    onPressed: () =>
                                        setState(() => line.quantity++),
                                    icon: const Icon(Icons.add_circle_outline),
                                  ),
                                  Expanded(
                                    child: Text(
                                      line.product['name'].toString(),
                                    ),
                                  ),
                                  Text(money(line.total)),
                                  IconButton(
                                    onPressed: () =>
                                        setState(() => cart.removeAt(index)),
                                    icon: const Icon(Icons.delete_outline),
                                  ),
                                ],
                              );
                            },
                          ),
                        ),
                        ElevatedButton(
                          onPressed: sending ? null : send,
                          child: sending
                              ? const CircularProgressIndicator()
                              : Text('ENVIAR PEDIDO · ${money(total)}'),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
    );
  }
}

class ProductConfigurator extends StatefulWidget {
  const ProductConfigurator({
    super.key,
    required this.product,
    required this.variants,
    required this.groups,
  });
  final Map<String, dynamic> product;
  final List<Map<String, dynamic>> variants, groups;
  @override
  State<ProductConfigurator> createState() => _ProductConfiguratorState();
}

class _ProductConfiguratorState extends State<ProductConfigurator> {
  late Map<String, dynamic> variant = widget.variants.firstWhere(
    (item) => item['isDefault'] == true,
    orElse: () => widget.variants.first,
  );
  final selected = <String>{}, notes = TextEditingController();
  int quantity = 1;
  bool get valid => widget.groups.every((assignment) {
    final options =
        ((assignment['group'] as Map<String, dynamic>)['options'] as List?) ??
        [];
    final ids = options.map(
      (option) => (option as Map<String, dynamic>)['id'].toString(),
    );
    final count = ids.where(selected.contains).length;
    return count >= (assignment['minSelect'] as int? ?? 0) &&
        count <= (assignment['maxSelect'] as int? ?? 1);
  });

  @override
  Widget build(BuildContext context) => SafeArea(
    child: Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.viewInsetsOf(context).bottom + 20,
      ),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              widget.product['name'].toString(),
              style: Theme.of(context).textTheme.headlineSmall
                  ?.copyWith(fontWeight: FontWeight.bold),
            ),
            if (widget.product['description'] != null)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(widget.product['description'].toString()),
              ),
            if (widget.variants.length > 1)
              Wrap(
                spacing: 8,
                children: widget.variants
                    .map(
                      (item) => ChoiceChip(
                        label: Text(
                          '${item['name']} · ${money(item['price'])}',
                        ),
                        selected: variant['id'] == item['id'],
                        onSelected: (_) => setState(() => variant = item),
                      ),
                    )
                    .toList(),
              ),
            ...widget.groups.map((assignment) {
              final group = assignment['group'] as Map<String, dynamic>,
                  maxSelect = assignment['maxSelect'] as int? ?? 1,
                  minSelect = assignment['minSelect'] as int? ?? 0;
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 12),
                  Text(
                    group['name'].toString(),
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    minSelect > 0
                        ? 'Selecciona mínimo $minSelect · máximo $maxSelect'
                        : 'Puedes elegir hasta $maxSelect',
                  ),
                  ...((group['options'] as List?) ?? []).map((raw) {
                    final option = raw as Map<String, dynamic>,
                        id = option['id'].toString();
                    return CheckboxListTile(
                      value: selected.contains(id),
                      title: Text(option['name'].toString()),
                      onChanged: (checked) => setState(() {
                        final groupIds = ((group['options'] as List?) ?? [])
                            .map(
                              (item) => (item as Map<String, dynamic>)['id']
                                  .toString(),
                            )
                            .toSet();
                        if (checked == true &&
                            selected.intersection(groupIds).length <
                                maxSelect) {
                          selected.add(id);
                        }
                        if (checked == false) {
                          selected.remove(id);
                        }
                      }),
                    );
                  }),
                ],
              );
            }),
            if (widget.product['allowNotes'] == true)
              TextField(
                controller: notes,
                decoration: const InputDecoration(labelText: 'Observación'),
              ),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                IconButton(
                  onPressed: quantity > 1
                      ? () => setState(() => quantity--)
                      : null,
                  icon: const Icon(Icons.remove_circle),
                ),
                Text('$quantity', style: const TextStyle(fontSize: 20)),
                IconButton(
                  onPressed: () => setState(() => quantity++),
                  icon: const Icon(Icons.add_circle),
                ),
              ],
            ),
            ElevatedButton(
              onPressed: valid
                  ? () => Navigator.pop(
                      context,
                      CartLine(
                        product: widget.product,
                        variant: variant,
                        modifierIds: selected.toList(),
                        quantity: quantity,
                        notes: notes.text.trim(),
                      ),
                    )
                  : null,
              child: const Text('Agregar al pedido'),
            ),
          ],
        ),
      ),
    ),
  );
}

String createUlid() {
  const alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
  var time = DateTime.now().millisecondsSinceEpoch;
  final chars = List.filled(26, '0');
  for (var i = 9; i >= 0; i--) {
    chars[i] = alphabet[time & 31];
    time ~/= 32;
  }
  final random = Random.secure();
  for (var i = 10; i < 26; i++) {
    chars[i] = alphabet[random.nextInt(32)];
  }
  return chars.join();
}
