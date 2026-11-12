# Roadmap Kolaborasi — KiNet eBilling

Panduan kerja tim untuk repo `troopsmalay-source/KiyNet`. Disesuaikan untuk tim kecil (owner + 1–3 pengembang).

## 1. Struktur Tim & Akses

| Peran | Akun | Akses | Tanggung jawab |
|---|---|---|---|
| Owner | `troopsmalay-source` | admin | Keputusan, merge, deploy, secrets |
| Pengembang | `metamarkid-crypto` | write | Fitur, bugfix, Pull Request |

**Aturan akses:**
- Hanya owner yang boleh force push ke `main`.
- Siapa pun (termasuk owner) wajib membuat Pull Request untuk perubahan kode.
- Jangan ubah `Settings → Collaborators` tanpa koordinasi di Issue.

## 2. Workflow Git

```
main  (deploy, selalu stabil)
  ├── feature/xxx     (fitur baru)
  ├── bugfix/xxx      (perbaikan)
  └── release/xxx     (release stabil, test + review)
```

**Alur kerja:**
1. Buat branch dari `main`: `git checkout -b feature/nama-fitur`
2. Kerja lokal, commit kecil-kecil dengan pesan jelas.
3. Push branch: `git push origin feature/nama-fitur`
4. Buka Pull Request (PR) ke `main`, label reviewer.
5. Reviewer cek: logic, test, style, keamanan.
6. Tunggu CI (`php artisan test`) lulus sebelum merge.
7. Merge via GitHub UI, lalu hapus branch.

**Pesan commit** (`<type>: <subject>`):
- `feat:` fitur baru
- `fix:` perbaikan bug
- `style:` perubahan tampilan (CSS/JS)
- `refactor:` restrukturisasi tanpa mengubah perilaku
- `test:` penambahan/penyesuaian test
- `docs:` perubahan dokumentasi
- `chore:` maintenance (deps, CI, build)

Contoh: `feat: add invoice pagination di /admin/billing`

## 3. Roadmap Tahapan

### Phase 1 — Build (minggu ini)
- Setup: `.env`, `composer install`, `npm install`, `php artisan serve --host=127.0.0.1 --port=8080`
- Seed database: `php artisan migrate --seed`
- Repo GitHub: branch protection, CI
- Setup environment: `development` (lokal) dan `production` (deploy)
- Login dev: `admin@kinet.local` / `ChangeMe123!`

### Phase 2 — Shine (2 minggu)
- Fitur inti: Invoice, Tagihan, Pembayaran, Pengingat WA, Laporan
- UI: responsive, dark mode konsisten, lazy load
- Test: target `php artisan test` lulus 100% (59 passed saat ini)
- Mock data billing sudah ter-seed, pastikan konsisten

### Phase 3 — Ship (4 minggu)
- Deploy production (VPS atau hosting PHP 8.3+)
- Setup HTTPS (Let's Encrypt / Nginx reverse proxy)
- Smoke test di production
- Tulis dokumentasi deployment (README + `.env.example`)
- Review final + sign-off owner

### Phase 4 — Extend (berkelanjutan)
- Integrasi MikroTik / API eksternal
- Webhook & notifikasi (WA, email)
- Monitoring: health checks, log
- Rilis fitur minor (UI mobile, dashboard tambahan)

## 4. Standar Issue & Tracking

- Setiap bug/fitur = 1 Issue di `https://github.com/troopsmalay-source/KiyNet/issues`
- Label: `bug`, `enhancement`, `priority:high`, `wip`, `review`
- Assign owner & developer di Issue
- Link PR ke Issue: tulis `Closes #12` di deskripsi PR

## 5. Secrets & Key Management

- `.env` **tidak pernah** masuk git (sudah di `.gitignore`, tetap dicek sebelum push)
- `.env.example` adalah template kosong yang boleh di-push
- API key di CI: `Settings → Secrets and variables → Actions`
- Jangan pernah memindahkan `.env` antar machine lewat chat/email

## 6. Perintah Lokal

```bash
# Setup pertama kali
git clone https://github.com/troopsmalay-source/KiyNet.git
cd KiyNet
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed

# Development
php artisan serve --host=127.0.0.1 --port=8080   # API
npm run dev                                        # asset

# Test
php artisan test
```

## 7. Checklist Code Review (setiap PR)

- [ ] `php artisan test` lulus
- [ ] Tidak ada `var_dump`, `dd()`, `console.log`, `print_r` tersisa
- [ ] Validasi input di controller/middleware (bukan di view)
- [ ] SQL query pakai parameter binding (bukan string concat)
- [ ] File baru tidak melebihi 300 baris (split bila perlu)
- [ ] Dokumentasi/comments diperbarui bila ada perubahan perilaku
- [ ] CI GitHub Actions hijau sebelum merge

## 8. Manajemen Risiko

| Risiko | Mitigasi |
|---|---|
| Kehilangan kode lokal | Commit & push rutin; backup GitHub |
| Merge conflict besar | Branch pendek (maks. 2 minggu), rebase dari `main` sering |
| API key bocor | Rotasi segera + cek `.gitignore`; jangan pernah log ke file |
| Force push merusak `main` | Branch protection: no force push, require PR |
| Test regresi | Jalankan test lokal sebelum push |
| Blokir deploy | Owner jaga akses deploy; document runbook |

## 9. Kontak & Eskalasi

- Pertanyaan teknis → buat Issue / tag di PR
- Blok progress > 1 hari → eskalasi ke owner via Issue dengan label `blocker`
- Owner: `troopsmalay-source`
- Developer: `metamarkid-crypto`

## 10. Jadwal Rutin

| Aktivitas | Frekuensi |
|---|---|
| Code review PR | Setiap ada PR |
| Review fitur (spike) | Setiap 2 minggu |
| Audit repo & security | Setiap 3 bulan |
| Rotate credential | Setiap 3 bulan |

---

Dibuat: 12 Nov 2026
