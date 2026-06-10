# Bintang Bot - Auto Claim Telegram

Bot untuk auto-claim task Bintang di spinhub.cc dengan dukungan multi-akun.

## Fitur

- Multi-akun (tambah, lihat, hapus)
- Auto-claim per round untuk semua akun
- Auto-retry saat cooldown
- Tampilan hasil semua akun dalam satu tabel
- Penyimpanan akun di file `bintang_accounts.json`

## Persyaratan

- PHP 8.0+
- PHP Curl (`php-curl`)
- Server API Vercel (lihat [vercel-encryptor](https://github.com/bowowiwendi/vercel-encryptor))

## Instalasi

### 1. Clone repo

```bash
git clone https://github.com/bowowiwendi/bintang-bot.git
cd bintang-bot
```

### 2. Set API Vercel

Edit atau generate `claim-loader.php` dengan API Vercel kamu:

```bash
node encrypt.mjs source-bot.php RAHASIA_KAMU claim-loader.php https://PROJECT_ANDA.vercel.app/api/run
```

Atau langsung edit URL di `source-bot.php` (untuk versi standalone tanpa enkripsi).

### 3. Jalankan

```bash
php claim-loader.php
```

Atau versi tanpa enkripsi:

```bash
php source-bot.php
```

## Cara Pakai

1. Pilih **Tambah Akun** (menu 1)
2. Masukkan **nama akun** (opsional)
3. Masukkan **Telegram Init Data**
4. Ulangi untuk akun lain
5. Pilih **Sync / Claim Semua** (menu 4)
6. Bot akan memproses semua akun dan menampilkan hasilnya

## Generate Loader Terenkripsi

```bash
node encrypt.mjs source-bot.php <KEY> loader.php https://API_ANDA.vercel.app/api/run
```

Upload `loader.php` ke server target. Tanpa KEY yang sesuai, file tidak bisa didekripsi.

## Struktur File

```
bintang-bot/
├── source-bot.php       # Source code bot (utama)
├── encrypt.mjs          # Tool enkripsi untuk generate loader
├── claim-loader.php     # Loader terenkripsi (generated)
├── bintang_accounts.json # Data akun (auto-generated)
└── README.md
```
