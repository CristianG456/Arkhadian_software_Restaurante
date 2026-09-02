import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:uuid/uuid.dart';

import '../api/api_client.dart';
import '../models/app_models.dart';
import '../storage/secure_store.dart';

enum AppStage { loading, pairing, login, ready, reconnecting }

class AppState {
  const AppState({
    this.stage = AppStage.loading,
    this.binding,
    this.user,
    this.message,
  });
  final AppStage stage;
  final RestaurantBinding? binding;
  final WaiterUser? user;
  final String? message;
  AppState copyWith({
    AppStage? stage,
    RestaurantBinding? binding,
    WaiterUser? user,
    String? message,
    bool clearMessage = false,
  }) => AppState(
    stage: stage ?? this.stage,
    binding: binding ?? this.binding,
    user: user ?? this.user,
    message: clearMessage ? null : message ?? this.message,
  );
}

final secureStoreProvider = Provider<SecureStore>((ref) => const SecureStore());
final appControllerProvider = StateNotifierProvider<AppController, AppState>(
  (ref) => AppController(ref.read(secureStoreProvider))..start(),
);

class AppController extends StateNotifier<AppState> {
  AppController(this.store) : super(const AppState());
  final SecureStore store;
  Timer? _reconnectTimer;
  String? _deviceToken, _accessToken;

  ApiClient get api => ApiClient(
    endpoint: state.binding?.endpoint ?? '',
    deviceToken: _deviceToken,
    accessToken: _accessToken,
  );

  Future<void> start() async {
    final endpoint = await store.read('endpoint');
    _deviceToken = await store.read('device_token');
    _accessToken = await store.read('access_token');
    if (endpoint == null || _deviceToken == null) {
      state = const AppState(stage: AppStage.pairing);
      return;
    }
    final binding = RestaurantBinding(
      endpoint: endpoint,
      name: await store.read('restaurant_name') ?? 'Restaurante',
      code: await store.read('restaurant_code') ?? '',
      mode: await store.read('restaurant_mode') ?? 'LOCAL',
      deviceName: await store.read('device_name') ?? 'Dispositivo',
    );
    state = AppState(
      stage: _accessToken == null ? AppStage.login : AppStage.loading,
      binding: binding,
    );
    if (_accessToken == null) return;
    await validateSession();
  }

  Future<void> pair({
    required String endpoint,
    required String restaurantCode,
    String? pairingCode,
    String? pairingToken,
    required String deviceName,
    required String platform,
  }) async {
    final identifier =
        await store.read('device_identifier') ?? const Uuid().v4();
    final client = ApiClient(endpoint: endpoint);
    final data = await client.post('/api/device-pairing/pair', {
      'restaurantCode': restaurantCode.trim().toUpperCase(),
      'pairingCode': ?pairingCode,
      'pairingToken': ?pairingToken,
      'deviceName': deviceName.trim(),
      'deviceIdentifier': identifier,
      'platform': platform,
    });
    final restaurant = data['restaurant'] as Map<String, dynamic>;
    _deviceToken = data['deviceToken'].toString();
    final binding = RestaurantBinding(
      endpoint: endpoint,
      name: restaurant['name'].toString(),
      code: restaurant['code'].toString(),
      mode: restaurant['operatingMode'].toString(),
      deviceName: deviceName.trim(),
    );
    await Future.wait([
      store.write('endpoint', endpoint),
      store.write('device_token', _deviceToken),
      store.write('device_identifier', identifier),
      store.write('restaurant_name', binding.name),
      store.write('restaurant_code', binding.code),
      store.write('restaurant_mode', binding.mode),
      store.write('device_name', binding.deviceName),
    ]);
    state = AppState(stage: AppStage.login, binding: binding);
  }

  Future<void> login(String username, String pin) async {
    final data = await api.post('/api/auth/waiter/login', {
      'username': username,
      'pin': pin,
    });
    _accessToken = data['accessToken'].toString();
    await store.write('access_token', _accessToken);
    state = AppState(
      stage: AppStage.ready,
      binding: state.binding,
      user: WaiterUser.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<void> validateSession() async {
    try {
      final data = await api.get('/api/auth/me');
      state = AppState(
        stage: AppStage.ready,
        binding: state.binding,
        user: WaiterUser.fromJson(data['user'] as Map<String, dynamic>),
      );
      _reconnectTimer?.cancel();
    } on ApiFailure catch (error) {
      await handleFailure(error);
    }
  }

  Future<void> handleFailure(ApiFailure error) async {
    if (error.code == 'DEVICE_REVOKED' || error.code == 'DEVICE_NOT_PAIRED') {
      await unpair(message: error.message);
      return;
    }
    if (error.code == 'UNAUTHENTICATED' || error.code == 'WAITER_INACTIVE') {
      _accessToken = null;
      await store.clearSession();
      state = AppState(
        stage: AppStage.login,
        binding: state.binding,
        message: error.message,
      );
      return;
    }
    if (error.code == 'CORE_UNAVAILABLE') {
      state = state.copyWith(
        stage: AppStage.reconnecting,
        message: error.message,
      );
      _reconnectTimer ??= Timer.periodic(
        const Duration(seconds: 5),
        (_) => validateSession(),
      );
      return;
    }
    state = state.copyWith(message: error.message);
  }

  Future<void> logout() async {
    try {
      await api.post('/api/auth/logout');
    } catch (_) {}
    _accessToken = null;
    await store.clearSession();
    state = AppState(stage: AppStage.login, binding: state.binding);
  }

  Future<void> unpair({String? message}) async {
    _deviceToken = null;
    _accessToken = null;
    await store.clearBinding();
    state = AppState(stage: AppStage.pairing, message: message);
  }

  void clearMessage() => state = state.copyWith(clearMessage: true);
  @override
  void dispose() {
    _reconnectTimer?.cancel();
    super.dispose();
  }
}
