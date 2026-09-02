import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStore {
  const SecureStore([this.storage = const FlutterSecureStorage()]);
  final FlutterSecureStorage storage;
  Future<String?> read(String key) => storage.read(key: key);
  Future<void> write(String key, String? value) => value == null
      ? storage.delete(key: key)
      : storage.write(key: key, value: value);
  Future<void> clearSession() async {
    await storage.delete(key: 'access_token');
  }

  Future<void> clearBinding() async {
    await storage.deleteAll();
  }
}
