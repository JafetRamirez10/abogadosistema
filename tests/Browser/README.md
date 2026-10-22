# Tests Browser (Playwright)

Omitidos en Fase 7: el proyecto no tiene Playwright instalado (`package.json` solo incluye Vite/Tailwind) y el tiempo se priorizó en seeders + QA PHP.

Para añadir capturas responsive (375 / 768 / 1280) de login o dashboard:

```bash
npm init playwright@latest
# crear tests/Browser/responsive.spec.ts
npx playwright test
```

Empty states Filament ya están en español (clientes, expedientes, documentos, agenda, tareas, etc.).
