#[cfg(desktop)]
use tauri::Manager;
use tauri_plugin_log::{RotationStrategy, Target, TargetKind};

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    let mut builder = tauri::Builder::default();

    // Must be registered before any other plugin/setup logic: a second
    // launch should just focus the already-running window (see
    // docs/architecture.md "Single instance"), not race it for the port
    // Restaurant Core listens on or open a duplicate.
    #[cfg(desktop)]
    {
        builder = builder.plugin(tauri_plugin_single_instance::init(|app, _args, _cwd| {
            if let Some(window) = app.get_webview_window("main") {
                let _ = window.unminimize();
                let _ = window.show();
                let _ = window.set_focus();
            }
        }));
    }

    let log_level = if cfg!(debug_assertions) {
        log::LevelFilter::Info
    } else {
        log::LevelFilter::Warn
    };

    builder
        .plugin(
            tauri_plugin_log::Builder::default()
                .level(log_level)
                .targets([
                    Target::new(TargetKind::LogDir { file_name: None }),
                    Target::new(TargetKind::Stdout),
                ])
                // Caps each log file and keeps a single rotated backup instead
                // of growing forever across a caja running full shifts every day.
                .max_file_size(5 * 1024 * 1024)
                .rotation_strategy(RotationStrategy::KeepOne)
                .build(),
        )
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
