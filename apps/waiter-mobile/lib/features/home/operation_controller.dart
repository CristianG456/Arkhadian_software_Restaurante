import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../../core/models/app_models.dart';
import '../../core/state/app_controller.dart';

class OperationState {
  const OperationState({
    this.myTables = const [],
    this.areas = const [],
    this.alerts = const [],
    this.loading = true,
    this.error,
  });
  final List<WaiterTable> myTables;
  final List<Map<String, dynamic>> areas, alerts;
  final bool loading;
  final String? error;
}

final operationProvider =
    StateNotifierProvider<OperationController, OperationState>(
      (ref) =>
          OperationController(ref.read(appControllerProvider.notifier))
            ..start(),
    );

class OperationController extends StateNotifier<OperationState> {
  OperationController(this.app) : super(const OperationState());
  final AppController app;
  Timer? timer;
  void start() {
    load();
    timer = Timer.periodic(
      const Duration(seconds: 15),
      (_) => load(silent: true),
    );
  }

  Future<void> load({bool silent = false}) async {
    if (!silent) {
      state = OperationState(
        myTables: state.myTables,
        areas: state.areas,
        alerts: state.alerts,
        loading: true,
      );
    }
    try {
      final values = await Future.wait([
        app.api.get('/api/waiter/me/tables'),
        app.api.get('/api/waiter/available-tables'),
        app.api.get('/api/waiter/alerts'),
      ]);
      state = OperationState(
        myTables: ((values[0]['tables'] as List?) ?? [])
            .map((item) => WaiterTable.fromJson(item as Map<String, dynamic>))
            .toList(),
        areas: ((values[1]['areas'] as List?) ?? [])
            .cast<Map<String, dynamic>>(),
        alerts: ((values[2]['alerts'] as List?) ?? [])
            .cast<Map<String, dynamic>>(),
        loading: false,
      );
    } on ApiFailure catch (error) {
      await app.handleFailure(error);
      state = OperationState(
        myTables: state.myTables,
        areas: state.areas,
        alerts: state.alerts,
        loading: false,
        error: error.message,
      );
    }
  }

  Future<void> post(String path, [Map<String, dynamic>? body]) async {
    try {
      await app.api.post(path, body);
      await load();
    } on ApiFailure catch (error) {
      await app.handleFailure(error);
      rethrow;
    }
  }

  Future<Map<String, dynamic>> get(String path) async {
    try {
      return await app.api.get(path);
    } on ApiFailure catch (error) {
      await app.handleFailure(error);
      rethrow;
    }
  }

  @override
  void dispose() {
    timer?.cancel();
    super.dispose();
  }
}
