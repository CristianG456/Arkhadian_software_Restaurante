import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiFailure implements Exception {
  ApiFailure(this.code, this.message, [this.status]);
  final String code;
  final String message;
  final int? status;
  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({
    required this.endpoint,
    this.deviceToken,
    this.accessToken,
    http.Client? client,
  }) : _client = client ?? http.Client();
  final String endpoint;
  final String? deviceToken;
  final String? accessToken;
  final http.Client _client;

  Future<Map<String, dynamic>> get(String path) => request('GET', path);
  Future<Map<String, dynamic>> post(
    String path, [
    Map<String, dynamic>? body,
  ]) => request('POST', path, body);

  Future<Map<String, dynamic>> request(
    String method,
    String path, [
    Map<String, dynamic>? body,
  ]) async {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (deviceToken != null) headers['X-Device-Token'] = deviceToken!;
    if (accessToken != null) headers['Authorization'] = 'Bearer $accessToken';
    try {
      final uri = Uri.parse('${endpoint.replaceAll(RegExp(r'/$'), '')}$path');
      final response = await _client
          .send(
            http.Request(method, uri)
              ..headers.addAll(headers)
              ..body = body == null ? '' : jsonEncode(body),
          )
          .timeout(const Duration(seconds: 8));
      final text = await response.stream.bytesToString();
      final decoded = text.isEmpty
          ? <String, dynamic>{}
          : jsonDecode(text) as Map<String, dynamic>;
      if (response.statusCode < 200 ||
          response.statusCode >= 300 ||
          decoded['success'] == false) {
        final error = decoded['error'] as Map<String, dynamic>?;
        throw ApiFailure(
          error?['code']?.toString() ?? 'HTTP_ERROR',
          error?['message']?.toString() ?? 'No se pudo completar la operación.',
          response.statusCode,
        );
      }
      return (decoded['data'] as Map<String, dynamic>?) ?? decoded;
    } on ApiFailure {
      rethrow;
    } on TimeoutException {
      throw ApiFailure(
        'CORE_UNAVAILABLE',
        'No se pudo conectar con el restaurante.',
      );
    } on FormatException {
      throw ApiFailure(
        'INVALID_RESPONSE',
        'El servidor devolvió una respuesta inválida.',
      );
    } catch (_) {
      throw ApiFailure(
        'CORE_UNAVAILABLE',
        'No se pudo conectar con el restaurante.',
      );
    }
  }
}
