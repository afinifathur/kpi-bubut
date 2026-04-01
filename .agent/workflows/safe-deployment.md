# KPI Bubut - Safe Deployment Workflow

## Overview Masalah Utama

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| Database production terhapus | `migrate:fresh` atau `docker compose down -v` | **JANGAN** pakai `migrate:fresh`, gunakan `migrate` saja |
| .env perlu setting ulang | `.env` tidak di-commit ke git | Simpan `.env.production` terpisah |
| vendor/public hilang | `docker compose down` atau rebuild | Gunakan volume persistent |
| Struktur DB conflict | Migrasi baru vs DB lama | Jalankan `migrate` (bukan `fresh`) |

---

## Aturan Emas Deployment

> [!CAUTION]
> **JANGAN PERNAH** jalankan command ini di production:
> - `php artisan migrate:fresh` ❌ (menghapus SEMUA data)
> - `php artisan migrate:refresh` ❌ (rollback lalu migrate ulang)
> - `docker compose down -v` ❌ (flag `-v` menghapus volume/database)

> [!TIP]
> **SELALU** gunakan:
> - `php artisan migrate` ✅ (hanya jalankan migrasi baru)
> - `docker compose down` ✅ (tanpa `-v`)
> - `docker compose up -d` ✅

---

## Workflow Deployment Aman

```mermaid
flowchart TD
    subgraph DEV ["Development (Windows/Laptop)"]
        A[Edit code & migrasi baru] --> B[Test di Laragon]
        B --> C{Stabil?}
        C -->|Ya| D[Push ke github dev]
        C -->|Tidak| A
        D --> E[Push ke github prod]
    end
    
    subgraph SERVER ["Production Server"]
        F[1. Backup database dulu] --> G[2. Pull code dari github]
        G --> H[3. Jalankan migrate]
        H --> I[4. Clear cache]
        I --> J{Test aplikasi}
        J -->|Error| K[Rollback: restore backup]
        J -->|Sukses| L[✓ Selesai]
    end
    
    E --> F
```

---

## Step-by-Step Deployment

### SEBELUM Update (Di Windows)

```powershell
# 1. Backup database production ke lokal (Opsional tapi disarankan)
# Gunakan mysqldump via SSH atau script backup yang ada

# 2. Push ke github prod
cd C:\laragon\www\kpi-bubut
git push prod main
```

### DI SERVER (Via SSH)

```bash
# 1. SSH ke server
ssh peroniks@peroniks-ppicserver

# 2. Masuk ke folder project
cd /srv/docker/apps/kpi-bubut

# 3. BACKUP database SEBELUM update (WAJIB!)
# Pastikan nama container db sesuai (biasanya kpi-bubut-db-1 atau db)
docker exec kpi-bubut-db-1 mysqldump -u root -p[PASSWORD] kpi_bubut > ~/backup_before_update_$(date +%Y%m%d_%H%M%S).sql

# 4. Pull code terbaru
git pull origin main

# 5. Rebuild dan restart container (WAJIB agar perubahan PHP terbaca)
sudo docker compose up --build -d

# 6. Masuk ke container PHP dan jalankan migrasi
docker exec -it kpi-bubut-app-1 php artisan migrate

# 7. Clear semua cache
docker exec -it kpi-bubut-app-1 php artisan config:clear
docker exec -it kpi-bubut-app-1 php artisan cache:clear
docker exec -it kpi-bubut-app-1 php artisan view:clear
docker exec -it kpi-bubut-app-1 php artisan route:clear

# 8. Jika ada perubahan di composer.json
docker exec -it kpi-bubut-app-1 composer install --no-dev --optimize-autoloader

# 9. Jika ada perubahan di package.json / vite
docker exec -it kpi-bubut-app-1 npm install
docker exec -it kpi-bubut-app-1 npm run build

# 10. Test aplikasi di browser
```

### JIKA ERROR - Rollback

```bash
# Restore database dari backup
docker exec -i kpi-bubut-db-1 mysql -u root -p[PASSWORD] kpi_bubut < ~/backup_before_update_XXXXXXXX_XXXXXX.sql

# Rollback code ke commit sebelumnya
git reset --hard HEAD~1
```

---

## Struktur File yang TIDAK boleh di-commit

Buat file-file ini di server dan jangan pernah overwrite:

```
/srv/docker/apps/kpi-bubut/
├── .env                    # Jangan commit, buat manual di server
├── docker-compose.yml      # Commit, tapi jangan sering ubah
├── storage/                # Jangan commit isinya
│   ├── app/
│   ├── framework/
│   └── logs/
└── public/storage -> ../storage/app/public  # Symlink
```

---

## Tips Migrasi Database

### 1. Gunakan Migrasi yang Idempotent

Migrasi yang aman adalah yang mengecek dulu sebelum eksekusi:

```php
// Contoh migrasi aman
public function up()
{
    // Cek apakah kolom sudah ada
    if (!Schema::hasColumn('your_table', 'new_column')) {
        Schema::table('your_table', function (Blueprint $table) {
            $table->string('new_column')->nullable();
        });
    }
}
```

### 2. Jangan Pernah Modify Migrasi Lama

Jika perlu ubah struktur, buat migrasi BARU:

```bash
# SALAH: Edit file migrasi lama
# BENAR: Buat migrasi baru
php artisan make:migration add_new_column_to_table
```

---

## Checklist Deployment

```markdown
## Pre-Deployment
- [ ] Semua fitur sudah di-test di Laragon
- [ ] Code sudah di-push ke github prod
- [ ] Backup database production sudah di-download/tersedia

## Deployment
- [ ] SSH ke server
- [ ] Backup database di server (sebelum pull)
- [ ] `git pull origin main`
- [ ] `sudo docker compose up --build -d`
- [ ] `php artisan migrate` (BUKAN migrate:fresh!)
- [ ] Clear cache (config, cache, view, route)
- [ ] Test aplikasi di browser

## Post-Deployment
- [ ] Verifikasi semua fitur baru berfungsi
- [ ] Verifikasi data lama masih ada
- [ ] Hapus backup lama jika sudah stabil (setelah 1-2 hari)
```

---

## Docker Compose Best Practices

### Jangan Sampai Volume Terhapus

```yaml
# docker-compose.yml
volumes:
  mysql_data:  # Named volume - JANGAN hapus dengan down -v

services:
  db:
    image: mysql:8.0
    volumes:
      - mysql_data:/var/lib/mysql  # Data persistent
```

### Perintah Docker yang Aman

```bash
# AMAN - Stop dan remove containers, network tetap
docker compose down

# AMAN - Rebuild tanpa hapus volume
docker compose up -d --build

# BERBAHAYA - Hapus SEMUA termasuk volume (database ikut hilang!)
docker compose down -v  # ❌ JANGAN di production!
```
