# Design Direction: e-Exam STPM

Dial: ENERGY 1 / RHYTHM 2 / MOTION 1

## Product Identity
e-Exam STPM (Sistem Peperiksaan STPM) is an academic examination and student grading management workstation designed for Malaysian Sixth Form (STPM) centers, teachers, and students. It provides role-based access for students to check exam results and progress, for subject teachers and class teachers to record and calculate CGPA/pointer marks, and for school administrators to manage registrations, classes, and official grade distributions.

## Personality & Tone
- **Authoritative & Academic:** Calm, institutional, and dependable. The interface respects the serious nature of national examination records.
- **Direct & Respectful (Bahasa Melayu):** Natural, formal Malay terminology without generic marketing hype, artificial English loanwords, or AI buzzwords ("revolusioner", "kuasa AI", "seamless").
- **Focused Utility:** Clean layout hierarchy where actionable workflows (student lookup, mark entry, grade verification) take center stage over decorative filler.

## Viewport & Layout Architecture (R-03, R-05)
- **Container Scale:** Max-width `1120px` with fluid responsive padding `clamp(16px, 2.5vw, 32px)`, centered with balanced gutters across mobile (360px+), tablet, and desktop viewports.
- **Header & Navigation:** Clean 64px bar featuring the official institutional crest/mark, product name, authenticated user role badge, and semantic accessible logout action.
- **Card Hierarchy & Rhythm:** Content-driven structures rather than copy-paste cards. Form surfaces maintain generous breathing room (padding 28px-32px), distinct section headings, and structured data tables.

## Color System (R-01, R-25, R-29)
The color system strictly adheres to WCAG AA 4.5:1 contrast standards, replacing low-contrast `#718096` (which failed at 4.1:1):

### Light Theme
- **Background Canvas:** `#F4F6F9` (Clean academic slate)
- **Card & Form Surfaces:** `#FFFFFF` with border `#DCE3EB`
- **Primary Brand / Action:** `#173B67` (Deep Academic Navy, 4.5:1+ against white)
- **Primary Hover / Active:** `#0E2D52`
- **Text Main:** `#0F1E36` (Contrast > 13.5:1 against canvas)
- **Text Muted / Subtitle:** `#3B4C63` (Contrast > 6.2:1 against canvas and white surfaces, fully passing WCAG AA 4.5:1)
- **Focus Ring:** `#173B67` (2px solid outline with 2px offset)
- **Alert / Error:** Background `#FEF2F2`, Border `#FCA5A5`, Text `#991B1B` (Contrast > 7.1:1)
- **Success:** Background `#F0FDF4`, Border `#86EFAC`, Text `#166534` (Contrast > 6.5:1)

### Dark Theme (R-21, R-34)
- **Background Canvas:** `#0C131F` (Deep institutional midnight)
- **Card & Form Surfaces:** `#141E2E` with border `#24334A`
- **Primary Action:** `#3B82F6` (Vibrant academic blue, high contrast on dark)
- **Text Main:** `#E8EEF5` (Contrast > 14:1)
- **Text Muted:** `#94A3B8` (Contrast > 6.8:1)
- **Focus Ring:** `#60A5FA` (2px solid outline with 2px offset)

## Typography (R-06)
- **Typeface:** Instrument Sans / System Sans (`ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`)
- **Scale:**
  - H1 Page Titles: 26px / 32px (weight 700)
  - Section Headings: 18px / 24px (weight 600)
  - Body Text: 15px / 22px (weight 400 & 500)
  - Small / Captions: 13px / 18px (weight 500)
- Monospace is reserved exclusively for system codes, student matric IDs (e.g. `STPM2024001`), and teacher identification numbers.

## Accessibility & Interaction (R-32, R-39)
- All interactive controls have visible focus rings (`:focus-visible`).
- Buttons maintain minimum tap target heights of 44px–48px.
- Labels are programmatically connected to form inputs with explicit `id` and `for` attributes.
- Forms provide semantic validation status with `role="alert"` and clear error summaries.
