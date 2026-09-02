import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../core/api/api_client.dart';
import '../../core/state/app_controller.dart';

class PairingScreen extends ConsumerStatefulWidget {
  const PairingScreen({super.key});
  @override
  ConsumerState<PairingScreen> createState() => _PairingScreenState();
}

class _PairingScreenState extends ConsumerState<PairingScreen> {
  final restaurant = TextEditingController(),
      code = TextEditingController(),
      device = TextEditingController(text: 'Mesero 1'),
      endpoint = TextEditingController(text: 'http://10.0.2.2:8000');
  String? token, error;
  bool loading = false, advanced = false, scanning = false;

  Future<void> submit() async {
    setState(() {
      loading = true;
      error = null;
    });
    try {
      await ref
          .read(appControllerProvider.notifier)
          .pair(
            endpoint: endpoint.text.trim(),
            restaurantCode: restaurant.text,
            pairingCode: token == null ? code.text.trim() : null,
            pairingToken: token,
            deviceName: device.text,
            platform: defaultTargetPlatform == TargetPlatform.iOS
                ? 'IOS'
                : 'ANDROID',
          );
    } on ApiFailure catch (failure) {
      setState(() => error = failure.message);
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  void scan(BarcodeCapture capture) {
    final raw = capture.barcodes.firstOrNull?.rawValue;
    if (raw == null) return;
    try {
      final payload = jsonDecode(raw) as Map<String, dynamic>;
      if (payload['type'] != 'RESTAURANT_DEVICE_PAIRING' ||
          payload['version'] != 1) {
        throw const FormatException();
      }
      setState(() {
        restaurant.text = payload['restaurantCode'].toString();
        token = payload['pairingToken'].toString();
        if (payload['coreEndpoint'] != null) {
          endpoint.text = payload['coreEndpoint'].toString();
        }
        scanning = false;
        error = null;
      });
    } catch (_) {
      setState(
        () => error = 'Este código no es válido para vincular el dispositivo.',
      );
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Icon(
                  Icons.restaurant_menu,
                  size: 64,
                  color: Color(0xFF3155D9),
                ),
                const SizedBox(height: 16),
                Text(
                  'Vincula este dispositivo',
                  style: Theme.of(context).textTheme.headlineMedium
                      ?.copyWith(fontWeight: FontWeight.bold),
                  textAlign: TextAlign.center,
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),
                  child: Text(
                    'Escanea el QR generado en Restaurant Admin o utiliza el código temporal.',
                    textAlign: TextAlign.center,
                  ),
                ),
                if (error != null)
                  Card(
                    color: Colors.red.shade50,
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Text(
                        error!,
                        style: TextStyle(color: Colors.red.shade800),
                      ),
                    ),
                  ),
                if (scanning)
                  SizedBox(
                    height: 280,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: MobileScanner(onDetect: scan),
                    ),
                  )
                else
                  OutlinedButton.icon(
                    onPressed: () => setState(() => scanning = true),
                    icon: const Icon(Icons.qr_code_scanner),
                    label: const Text('Escanear QR'),
                  ),
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 14),
                  child: Row(
                    children: [
                      Expanded(child: Divider()),
                      Padding(
                        padding: EdgeInsets.symmetric(horizontal: 12),
                        child: Text('o ingresa los códigos'),
                      ),
                      Expanded(child: Divider()),
                    ],
                  ),
                ),
                TextField(
                  controller: restaurant,
                  textCapitalization: TextCapitalization.characters,
                  decoration: const InputDecoration(
                    labelText: 'Código del restaurante',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: code,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  enabled: token == null,
                  decoration: InputDecoration(
                    labelText: token == null
                        ? 'Código de vinculación'
                        : 'Token QR cargado',
                  ),
                ),
                const SizedBox(height: 4),
                TextField(
                  controller: device,
                  decoration: const InputDecoration(
                    labelText: 'Nombre del dispositivo',
                  ),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: () => setState(() => advanced = !advanced),
                  child: const Text('Configuración avanzada'),
                ),
                if (advanced)
                  TextField(
                    controller: endpoint,
                    keyboardType: TextInputType.url,
                    decoration: const InputDecoration(
                      labelText: 'Dirección de Restaurant Core',
                      helperText: 'Solo para instalación o soporte técnico.',
                    ),
                  ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed:
                      loading ||
                          restaurant.text.trim().isEmpty ||
                          device.text.trim().isEmpty ||
                          (token == null && code.text.length != 6)
                      ? null
                      : submit,
                  child: loading
                      ? const CircularProgressIndicator()
                      : const Text('Vincular'),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}
