# Audio Translation Documentation – Zudoku

This folder contains the **Zudoku**-based documentation for the Audio Translation project.

## Quick Start

```bash
npm install
npm run dev
```

Open http://localhost:3000 to view the docs.

## Build for Production

```bash
npm run build
npm run preview
```

The static site is output to `dist/` and can be deployed to Vercel, Netlify, Cloudflare Pages, etc.

## Deploy to Audio-Translation-Docs

To use this in `C:\Users\GAMING\Desktop\Stage\Audio-Translation-Docs`:

```powershell
Copy-Item -Path ".\*" -Destination "C:\Users\GAMING\Desktop\Stage\Audio-Translation-Docs\" -Recurse -Force
cd "C:\Users\GAMING\Desktop\Stage\Audio-Translation-Docs"
npm install
npm run dev
```

## Structure

- `pages/` – Markdown documentation (01-overview through 10-google-service-account-setup)
- `zudoku.config.ts` – Zudoku configuration (navigation, site title, etc.)
- `public/` – Logos and static assets

## Powered by

[Zudoku](https://zudoku.dev/) – Open source API documentation framework
