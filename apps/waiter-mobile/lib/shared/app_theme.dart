import 'package:flutter/material.dart';

ThemeData waiterTheme() => ThemeData(
  colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF3155D9)),
  scaffoldBackgroundColor: const Color(0xFFF5F6FA),
  useMaterial3: true,
  inputDecorationTheme: const InputDecorationTheme(
    border: OutlineInputBorder(),
    filled: true,
    fillColor: Colors.white,
  ),
  elevatedButtonTheme: ElevatedButtonThemeData(
    style: ElevatedButton.styleFrom(
      minimumSize: const Size.fromHeight(54),
      textStyle: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700),
    ),
  ),
  cardTheme: const CardThemeData(
    margin: EdgeInsets.symmetric(vertical: 6),
    elevation: 0,
  ),
);

String money(Object value) {
  final number = double.tryParse(value.toString()) ?? 0;
  final whole = number.round().toString().replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (match) => '.',
  );
  return '\$$whole';
}
