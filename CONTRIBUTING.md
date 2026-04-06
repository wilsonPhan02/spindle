# 📘 Panduan Pengembangan Proyek Spindle (`CONTRIBUTING.md`)

Dokumen ini berisi kesepakatan alur kerja teknis tim dalam mengimplementasikan framework **Adaptive Software Development (ASD)**. Tujuannya agar kolaborasi penulisan kode berjalan rapi, minim konflik, dan siap dievaluasi pada setiap akhir siklus pengembangan.

## 1. Strategi Percabangan (Branching Strategy)
Kita menggunakan model percabangan yang ringan untuk mendukung kecepatan integrasi dan rilis fitur.

### Cabang Utama (Main Branches)
* **`main`:** Berisi kode versi *Production* yang sudah stabil. Kode di *branch* ini harus selalu dalam keadaan berfungsi (*deployable*).
* **`develop`:** Pusat integrasi kode dari seluruh anggota tim. Proses *testing* dan evaluasi (Fase *Learn* ASD) dilakukan di sini sebelum fitur dirilis ke `main`.

### Cabang Pendukung (Supporting Branches)
Gunakan format `tipe/nama-singkat-fitur` saat membuat *branch* baru dari `develop`.
* **`feat/`**: Untuk pengerjaan fitur baru.
* **`fix/`**: Untuk perbaikan *bug* yang ditemukan saat *testing* di `develop`.
* **`hotfix/`**: Untuk perbaikan *bug* kritis darurat yang langsung terjadi di `main`.
* **`refactor/`**: Untuk optimasi atau merapikan struktur kode tanpa mengubah fungsi.
* **`chore/`**: Untuk pemeliharaan di luar kode utama.
* **`docs/`**: Untuk penambahan atau perubahan dokumen/panduan teknis (contoh: `docs/setup-guidelines`).

### Penamaan Fitur Besar & Sub-fitur (Epic & Task)
Jika mengerjakan fitur besar yang dipecah agar bisa dikerjakan paralel, gunakan tanda garis miring (`/`) untuk mengelompokkannya seperti *folder*.
* **Format:** `feat/<fitur-utama>/<sub-fitur>`
* **Contoh:** `feat/workspace/drag-drop` dan `feat/workspace/zoom-canvas`.

> ⚠️ **Peringatan Penting (Jebakan Folder Git):**
> Karena Git membaca garis miring (`/`) sebagai folder fisik di sistem, **jangan membuat nama *branch* tunggal yang persis sama dengan nama folder utama**.
> * **SALAH:** Membuat *branch* `feat/workspace`, lalu membuat `feat/workspace/drag-drop` (Akan *error* bentrok nama).
> * **BENAR:** Biarkan `feat/workspace/` murni menjadi folder penampung. Jika butuh *branch* untuk mengintegrasikan fitur utamanya, gunakan nama **`feat/workspace-main`**.

---

## 2. Konvensi Pesan Commit (Commit Convention)
Kita menggunakan standar **Conventional Commits** tanpa *scope* agar riwayat pengembangan mudah ditelusuri dan cepat ditulis. Gunakan bahasa Inggris dan kata kerja perintah (*imperative*) untuk pesan singkat.

**Format Dasar:**
`<tipe>: <pesan singkat>`

**Daftar Tipe Commit:**

| Tipe Commit | Kegunaan | Contoh Penulisan |
| :--- | :--- | :--- |
| **`feat`** | Menambahkan fungsionalitas baru | `feat: add drag and drop feature` |
| **`fix`** | Memperbaiki *bug* atau *error* | `fix: resolve endpoint error on save` |
| **`refactor`** | Merapikan kode utama tanpa ubah fitur/fungsi | `refactor: optimize query fetching speed` |
| **`chore`** | *Maintenance* rutin, konfigurasi, *update library* | `chore: update laravel to latest version` |
| **`docs`** | Pembaruan teks dokumentasi, README, & komentar | `docs: add docblocks to login logic` |
| **`style`** | Memperbaiki format kode (spasi, indentasi, dll) | `style: fix indentation in config files` |

---

## 3. Cheat Sheet Commit (Contoh Kasus Sehari-hari)
Masih bingung pakai tipe *commit* apa? Coba cek panduan di bawah ini:

* **Kapan pakai `feat`?**
  * *Branch:* `feat/api-story-node`  👉  *Commit:* `feat: create endpoint for saving storyline data`
  * *Branch:* `feat/save-button`  👉  *Commit:* `feat: add save button to workspace interface`
* **Kapan pakai `fix`?**
  * *Branch:* `fix/cursor-position`  👉  *Commit:* `fix: correct cursor position calculation on drag`
  * *Branch:* `fix/typo-error-500`  👉  *Commit:* `fix: fix variable typo causing server error`
* **Kapan pakai `refactor`?**
  * *Branch:* `refactor/split-controller`  👉  *Commit:* `refactor: extract node logic into separate functions`
  * *Branch:* `refactor/clean-dead-code`  👉  *Commit:* `refactor: remove commented-out dead code in workspace`
* **Kapan pakai `chore`?**
  * *Branch:* `chore/update-dependencies`  👉  *Commit:* `chore: update framework to latest patch`
  * *Branch:* `chore/update-gitignore`  👉  *Commit:* `chore: add node_modules to gitignore`
* **Kapan pakai `docs`?**
  * *Branch:* `docs/add-docblocks`  👉  *Commit:* `docs: add docblocks to node calculation logic`
  * *Branch:* `docs/update-readme`  👉  *Commit:* `docs: add Postman API collection link to readme`
* **Kapan pakai `style`?**
  * *Branch:* `style/fix-indentation`  👉  *Commit:* `style: fix indentation in auth config files`

> 💡 **Pro-Tip Pengerjaan Fitur:**
> Sangat disarankan untuk melakukan banyak *commit* kecil-kecil dalam satu *branch* fitur selama pengerjaan (misal: `feat: create UI`, `feat: add drag logic`, `fix: limit drag area`). Jangan takut riwayatnya berantakan!