# Bintang Bot - Auto Claim Telegram

Bot untuk auto-claim task Bintang di spinhub.cc dengan dukungan multi-akun dan mode service.

## Fitur

- Multi-akun (tambah, lihat, hapus)
- Auto-claim per round untuk semua akun
- Auto-retry saat cooldown (30 menit di mode service)
- Tampilan hasil semua akun dalam satu tabel
- **Mode daemon** — jalan terus sebagai background service
- **Systemd support** — auto-start saat server nyala
- **Smart interval** — claim cepat (10dtk) saat sukses, lambat (30mnt) saat cooldown
- Penyimpanan akun di file `bintang_accounts.json`

## Persyaratan

- PHP 8.0+ (`php`, `php-curl`)
- Server API Vercel (lihat [vercel-encryptor](https://github.com/bowowiwendi/vercel-encryptor))
- `nodejs` (hanya untuk generate loader)

## Instalasi

### 1. Clone repo

```bash
git clone https://github.com/bowowiwendi/bintang-bot.git
cd bintang-bot
```

### 2. Generate Loader (untuk server target)

```bash
node encrypt.mjs source-bot.php RAHASIA_KAMU claim-loader.php https://PROJECT_ANDA.vercel.app/api/run
```

### 3. Tambah Akun (pertama kali)

Jalankan manual untuk menambah akun:

```bash
php source-bot.php
```

Pilih menu **1** (Tambah Akun) dan masukkan Init Data Telegram.

### 4. Jalankan sebagai Service

#### Opsi A — Systemd (recommended)

```bash
cp bintang-bot.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable bintang-bot
systemctl start bintang-bot
systemctl status bintang-bot
```

#### Opsi B — Langsung

```bash
php source-bot.php --daemon
```

Log tersimpan di `bintang-service.log`.

### 5. Cek Log

```bash
tail -f bintang-service.log
```

## Cara Pakai Manual

```
  ┌──────────────────────────────────────────┐
  │             MENU UTAMA                  │
  │  1. [+] Tambah Akun                    │
  │  2. [i] Lihat Akun                     │
  │  3. [-] Hapus Akun                     │
  │  4. [↻] Sync / Claim Semua            │
  │  5. [x] Keluar                         │
  └──────────────────────────────────────────┘
```

## Struktur File

```
bintang-bot/
├── source-bot.php           # Source code bot (utama)
├── encrypt.mjs              # Tool enkripsi
├── claim-loader.php         # Loader terenkripsi (generated)
├── bintang-bot.service      # Systemd unit file
├── bintang_accounts.json    # Data akun (auto-generated)
├── bintang-service.log      # Log file (daemon mode)
└── README.md
```
