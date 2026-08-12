# Changelog

## [0.1.0] - 2026-08-12

### Base técnica
- Restaurant Core local con Laravel.
- PostgreSQL local.
- API de salud y diagnóstico (`/api/health`, `/api/system/status`).
- Identificador persistente de instalación (`installation_id`).
- Aplicación nativa Tauri (Restaurant Admin).
- Scripts de desarrollo para Windows (instalar, migrar, iniciar, resetear BD).
- Arquitectura Local-First: el restaurante opera sin Internet.
- Preparación colaborativa mediante migraciones y seeders (esenciales/desarrollo).
- Flujo GitFlow documentado para trabajo entre 2 desarrolladores.

### Interfaz
- Sistema de diseño base (tokens de color, espaciado, tipografía).
- Shell de Restaurant Admin (Topbar + Sidebar).
- Navegación lateral preparada para los módulos futuros del restaurante.
- Componentes reutilizables touch-first (botones, campos, pestañas, ventanas,
  notificaciones, teclado numérico, listados, entre otros).
- Diagnóstico integrado (Configuración → Sistema → Diagnóstico) con datos reales.
- Pruebas internas de interfaz en desarrollo ("Pruebas de interfaz", ausente
  del build de producción).
- Interfaz visible completamente en español.
