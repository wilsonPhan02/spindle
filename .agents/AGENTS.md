# Spindle Project Rules

When working on this repository, you MUST adhere to the following architecture and refactoring standards established to keep the codebase clean, modular, and maintainable.

## 1. Zero "God Files" (Strict Component Decomposition)
- **Do not create monolithic Blade/Volt files.** If a view (e.g., `show.blade.php` or `manuscript.blade.php`) exceeds ~400 lines or handles multiple distinct UI sections, you must break it down into smaller, logical partials.
- Place partials in `resources/views/livewire/[feature]/partials/` and use `@include` or `<livewire:...>` to compose them.

## 2. Strict Separation of Concerns (JavaScript & CSS)
- **NO Inline JavaScript:** Do not write complex or lengthy `<script>` blocks inside Blade files. Extract all JavaScript logic (especially heavy Alpine.js configurations like TipTap or complex canvas/animations) into dedicated `.js` files inside `resources/js/` and let Vite bundle them.
- **NO Inline CSS:** Do not use `<style>` blocks in Blade files unless it is an absolute necessity for dynamic, scoped component logic that cannot be handled via Tailwind classes or `app.css`.

## 3. Asset Management (No Embedded Base64/SVG Data)
- **Do not embed massive SVGs or Base64 images directly into Blade files.** This bloats the file size and hurts page load performance.
- Always save static assets (SVGs, PNGs) into the `public/images/` directory and reference them cleanly via `asset()` or `<img>` tags.

## 4. Backend Logic & DRY (Don't Repeat Yourself)
- **Use Traits for Reusable Logic:** If a backend pattern (like uploading, replacing, or deleting image files) is used in more than one Livewire component, utilize the `App\Traits\HandlesFileUpload` trait instead of rewriting `Storage::...` logic inline.
- **Use Helpers:** Utilize static helpers like `App\Helpers\TextHelper` for repetitive data manipulation (e.g., word counting, HTML stripping).

## 5. Model Consistency
- **Type Safety:** Always include explicit return type hints (e.g., `: BelongsTo`, `: HasMany`) on all Eloquent relationship methods.
- **Casts:** Use the modern Laravel `protected function casts(): array` method style instead of the `$casts` property array.

## 6. Alpine.js Syntax Safety
- When using `x-init` or event handlers with complex logic that includes `try...catch` blocks, wrap them in a self-executing arrow function (e.g., `x-init="() => { try { ... } catch (e) {} }"`) to prevent Alpine's expression evaluator from throwing a `SyntaxError`.
