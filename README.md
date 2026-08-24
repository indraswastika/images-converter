# Images Converter

> A fully **client-side** image & PDF toolkit that runs 100% in the browser — no server, no uploads, no tracking. Every pixel is processed locally on your machine.

Live demo: **https://indraswastika.github.io/images-converter/**

---

## 🎯 Goal

Build a **privacy-first, zero-backend file utility**. Instead of sending photos or documents to a remote API (what most online converters do), Images Converter processes everything locally with the browser's native **Canvas API**, **JSZip**, **PDF.js**, and **PDF-lib**. The aim is a fast, offline-capable toolkit you can run by just opening a file — no install, no account, no data leaving the device.

---

## ✨ Features

The app is a single-page terminal-styled UI with five tools, switched from the top navigation.

### 1. Convert Format
- Batch convert multiple images (PNG · JPG · WEBP · GIF · BMP) in one go.
- Input also accepts **PDF** files (each page is rasterized into an image) and **ZIP archives** (images are auto-extracted from inside). `.rar` files are accepted by the picker but flagged as unsupported for automatic extraction in-browser.
- Output targets: **PNG**, **JPG/JPEG**, **WebP**, or **PDF** (merges the selected images into one PDF, with drag-to-reorder and a page-size picker: A4/A3/A5/Letter/Legal/Tabloid/landscape variants/Auto).
- **Adjustable quality** (1–100%, default 92) for lossy formats.
- **Five resize modes:**
  - *Original size* — keep source dimensions.
  - *Exact pixel* — force a target width & height.
  - *Aspect-ratio crop* — center-crop to a ratio (presets 16:9 · 4:3 · 1:1 · 9:16 · 3:2 · 21:9, or custom).
  - *Scale by %* — uniformly scale dimensions (1–400%).
  - *Upscale* — 2x/3x/4x enlargement via the canvas' high-quality smoothing (labeled "Lanczos3" in the UI).
- **Interactive crop-after-convert** — every output card has its own Crop button that opens a modal with a draggable/resizable selection box, applied directly to that result.
- Batch controls: **Select All / Deselect**, a **Cancel** button to stop an in-progress batch, and progress/size-saved stats.
- Per-file result **+ bulk download as a single ZIP**.

### 2. Duplicate Images
- Take **one** image and generate **N copies** (1–500).
- **Byte-exact duplication** — the source is copied verbatim (read into an `ArrayBuffer` and wrapped in a `Blob`), so there is **no re-encoding and zero quality loss**.
- Three naming schemes:
  - Sequential number (`1.jpg`, `2.jpg` …)
  - Custom prefix + number (`photo_1.jpg` …)
  - Original name + number (`IMG_001_1.jpg` …)
- Configurable start number.
- Bulk download as a ZIP, with UI batching (50 items/chunk) to stay responsive on large counts.

### 3. Open Multiple URLs
- Paste a list of URLs (one per line) and open them all at once in new tabs.
- **Configurable inter-open delay** (0–3000 ms) to avoid browser throttling.
- **Duplicate skipping** — dedupe the list and report how many were dropped.
- Per-URL status: **Opened** vs **Blocked** (when the browser's popup blocker intercepts).
- Copy the cleaned URL list back to the clipboard.

### 4. PDF Toolkit *(new)*
A dedicated tab with five modes, powered by **PDF.js** (rendering/reading) and **PDF-lib** (writing):
- **Merge Images → PDF** — combine PNG/JPG/WebP into one PDF, drag to reorder, choose a page size (A4/A3/A5/A6/Letter/Legal/Tabloid/Executive, landscape variants, Square, or Auto).
- **Merge Multiple PDFs** — combine several PDF files into one, drag to reorder before merging.
- **Split PDF Pages** — click pages in a visual grid to select them, then extract just the selected pages into a new PDF.
- **Rotate/Reorder Pages** — drag pages into a new order and save.
- **Compress PDF** — 7 levels (0 = no compression, up to Level 6 "maximum" ~65–80%), using PDF-lib's object-stream compression plus a byte-level stripping pass at higher levels; reports original vs. compressed size and % saved.

### 5. Stats *(new)*
A local, persistent (localStorage-based) dashboard tracking usage across every tool — nothing leaves the browser:
- **Overview** — totals like files processed, conversions, success rate.
- **Tool statistics** — per-tool usage count, success rate, average time, average file size.
- **File statistics** — PDF vs. image processing split.
- **Format statistics** — input/output format breakdown.
- **Compression statistics** — best ratio achieved, total storage saved, largest single-file reduction.
- **Performance statistics** — processing-time metrics.
- **Time-based statistics** — a daily activity log (rolling 90-day window).
- A **Reset All Stats** button clears everything.

---

## 🎨 UI / UX

- **Terminal / hacker aesthetic** — monospace (JetBrains Mono) type, boot-sequence overlay on first load (`[OK] loading kernel modules…`), scanline (CRT) overlay, a faint animated Matrix-style falling-character canvas background, and a glitch effect on the brand logo.
- **6 selectable color themes** (green / red / pink / blue / amber / purple), swapped live via CSS variables and remembered in `localStorage`.
- Drag-and-drop upload zones, live progress bars with percentage + sub-label, toast notifications, and empty states for every tool.
- Respects `prefers-reduced-motion` (disables the boot animation and other motion effects).

---

## 🧠 Algorithm Breakdown

All logic lives in the inline `<script>` of `index.html`. No build step, no server.

### A. Convert Format
1. **Intake** — accepts image files, PDFs, and ZIP archives. ZIPs are opened with `JSZip.loadAsync` and their image entries are extracted automatically; unsupported/invalid files are collected and reported together.
2. **PDF-as-source** — if a PDF is dropped in, `pdf.js` renders each page to a canvas at the chosen scale, and each page becomes its own image to run through the normal pipeline.
3. **Per-file (`convertFile`):**
   - Load the file into an `Image` via an object URL.
   - Resolve target dimensions based on the selected resize mode (original / exact pixel / aspect-ratio center-crop / scale % / upscale factor).
   - Draw onto an off-screen `<canvas>` with high-quality smoothing (`imageSmoothingQuality: 'high'`).
   - Encode via `canvas.toBlob(format, quality/100)` — the browser's native encoder produces PNG/JPG/WebP; if the output format is PDF, images are instead embedded into a `PDFDocument` via `pdf-lib` using the chosen page size.
   - Track original vs. converted byte sizes → "size saved", and log the run to the stats store.
4. **Batching** — files are processed sequentially with `await`; a `cancelRequested` flag lets the user abort mid-batch via the Cancel button.
5. **Post-hoc crop** — each result card can open the crop modal, which lets the user drag/resize a selection over that specific output and re-encode just that image.
6. **Packaging** — every blob goes into a `JSZip`; "download all" emits `converted_images.zip` (or, in PDF mode, a merged `.pdf`).

### B. Duplicate Images
1. Read the source into an `ArrayBuffer` and wrap it in a same-type `Blob` → **bit-for-bit copy**, no re-encoding.
2. Loop `count` times: build the output name from the chosen pattern + start number, add the same `Blob` to the ZIP, render a thumbnail card. Every 50 items, `await` a `setTimeout(0)` to yield to the event loop so the UI never blocks.
3. "Download all" → `duplicated_images.zip`.

### C. Open Multiple URLs
1. **Parse** the textarea by newline; if dedupe is on, drop repeats via a `seen` set and count them.
2. **Open** each URL with `window.open(url, '_blank')`; a returned handle means **Opened**, `null` means **Blocked** (popup blocker).
3. A configurable `delay` (`await sleep(ms)`) paces the opens.
4. Each item keeps its own "Open" button for manual re-triggering.

### D. PDF Toolkit
- **Merge Images → PDF** — each image is drawn/embedded into a new `PDFDocument` (`pdf-lib`) sized to the chosen page format, in the user-defined drag order.
- **Merge Multiple PDFs** — pages are copied page-by-page from each source `PDFDocument` into one new document via `copyPages`, in drag order.
- **Split PDF Pages** — `pdf.js` renders a thumbnail per page for the picker grid; selected page indices are copied into a fresh `PDFDocument` and saved.
- **Rotate/Reorder Pages** — pages are copied into a new `PDFDocument` following the user's drag order, with progress reported per page.
- **Compress PDF** — re-saves the document with `useObjectStreams`/`compress` options scaled to the chosen level; at level ≥ 4 an additional byte-level pass strips certain marker sequences from the output buffer; original vs. compressed size and % saved are reported.

### E. Stats
- Every processed file calls `trackFileProcessed(...)`, which updates counts, running sums (sizes, durations), per-tool and per-format breakdowns, compression ratios, and a daily log (auto-pruned past 90 days) — all persisted to `localStorage` and re-rendered live when the Stats tab is open.

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| Language | HTML5 + Vanilla JavaScript (ES2017+, `async/await`) |
| Image processing | Canvas 2D API (`drawImage`, `toBlob`) |
| PDF reading/rendering | [PDF.js](https://mozilla.github.io/pdf.js/) (CDN) |
| PDF writing/editing | [PDF-lib](https://pdf-lib.js.org/) (CDN) |
| Archiving | [JSZip](https://stuk.github.io/jszip/) (CDN + local bundle) |
| Persistence | `localStorage` (stats + theme preference) |
| Styling | CSS3 (responsive, themeable, CSS custom properties) — see live demo |

> **Note:** The repo also contains `assets/js/app.js` and `assets/plugins/*`, which are an **unused third-party admin-template boilerplate** (layout customizer, i18n, chart helpers). They do not power any of the tools above — only the inline script in `index.html` plus the CDN-loaded JSZip/PDF.js/PDF-lib do. They are retained for potential future dashboard work.

---

## 📁 Structure

```
images-converter/
├── index.html          # Markup + all app logic (inline <script>) — the whole app
├── assets/
│   ├── css/app.min.css
│   ├── js/
│   │   ├── app.js          # (boilerplate) unused admin-template helpers
│   │   ├── config.js       # empty
│   │   └── jszip.min.js    # local JSZip copy (fallback/offline use)
│   ├── plugins/            # (boilerplate) unused admin-template assets
│   └── images/logo-indra.png
├── README.md
└── LICENSE
```

---

## 🚀 Getting Started

No build, no dependencies to install.

```bash
git clone https://github.com/indraswastika/images-converter.git
cd images-converter
# open index.html directly, or serve statically:
python3 -m http.server 8000   # → http://localhost:8000
```

Works fully offline after first load for the Convert/Duplicate/URL-Opener tools (JSZip is bundled locally); the PDF Toolkit's PDF.js/PDF-lib libraries are loaded from CDN, so an internet connection is needed the first time to fetch them (they're then cached by the browser).

---

## 🔒 Privacy

- **No server, no uploads** — images and PDFs never leave the browser.
- **No analytics, no tracking.**
- All processing is in-memory via the Canvas, PDF.js, and PDF-lib APIs.
- Usage **Stats** are stored only in the browser's own `localStorage` — never sent anywhere, and can be cleared anytime with "Reset All Stats".

---

## 📄 License

Released under the [MIT License](./LICENSE).

---

© Images Converter by **Indra Swastika**.
