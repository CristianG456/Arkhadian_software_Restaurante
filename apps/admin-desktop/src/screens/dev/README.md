# screens/dev

Development-only screens. Not part of the product's navigation.

`ComponentShowcaseScreen` is reachable only via the "Componentes UI (dev)"
button `AppShell` renders when `import.meta.env.DEV` is true (Vite sets
this to `false` in a production/`tauri build` bundle, so the button and
the code path that could show this screen are both absent there). It's
also loaded through `React.lazy()` in `AppShell.tsx`, so its module graph
is a separate chunk Vite only fetches if a developer actually opens it.
