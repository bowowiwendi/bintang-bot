define('INNER_WIDTH', 44);
define('ACCOUNT_FILE', 'bintang_accounts.json');
define('LOG_FILE', 'bintang-service.log');

// Daemon mode
$is_daemon = in_array('--daemon', $argv ?? []);
$is_help = in_array('--help', $argv ?? []) || in_array('-h', $argv ?? []);

if ($is_help) {
    echo "Bintang Bot - Auto Claim Telegram\n";
    echo "Usage: php source-bot.php [--daemon]\n";
    echo "\n";
    echo "  --daemon    Run as background service (logs to bintang-service.log)\n";
    echo "  --help      Show this help\n";
    exit;
}

if ($is_daemon) {
    // Redirect output to log file
    $log = fopen(LOG_FILE, 'a');
    fwrite($log, "[" . date('Y-m-d H:i:s') . "] SERVICE STARTED\n");
    fclose($log);

    // Override echo to log
    ob_start(function($buffer) use ($log) {
        file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] " . $buffer, FILE_APPEND);
        return '';
    }, 1);

    // Write PID
    file_put_contents('/tmp/bintang-bot.pid', getmypid());
}

function log_msg($msg) {
    global $is_daemon;
    if ($is_daemon) {
        file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
    }
}

function color($color, $text) {
    $colors = [
        'green'  => "\e[1;32m", 'red'    => "\e[1;31m",
        'yellow' => "\e[1;33m", 'blue'   => "\e[1;34m",
        'cyan'   => "\e[1;36m", 'white'  => "\e[1;37m",
        'reset'  => "\e[0m"
    ];
    $code = isset($colors[$color]) ? $colors[$color] : $colors['reset'];
    return $code . $text . $colors['reset'];
}

function get_visual_length($text) {
    $plain = preg_replace('/\x1b\[[0-9;]*m/', '', $text);
    return mb_strwidth($plain, 'UTF-8');
}

function center_text($text) {
    $width = INNER_WIDTH;
    $visual_len = get_visual_length($text);
    $padding = max(0, ($width - $visual_len) / 2);
    return str_repeat(" ", floor($padding)) . $text . str_repeat(" ", ceil($padding));
}

function draw_box($lines, $color = 'cyan') {
    $width = INNER_WIDTH;
    echo color($color, "┌" . str_repeat("─", $width) . "┐\n");
    foreach ($lines as $line) {
        $visual_len = get_visual_length($line);
        $padding = max(0, $width - $visual_len);
        echo color($color, "│") . $line . str_repeat(" ", $padding) . color($color, "│\n");
    }
    echo color($color, "└" . str_repeat("─", $width) . "┘\n");
}

function cooldown($seconds) {
    for ($i = $seconds; $i > 0; $i--) {
        echo "\r " . color('yellow', "[⏳] COOLDOWN : ") . color('white', $i . " detik... ");
        sleep(1);
    }
    echo "\r" . str_repeat(' ', INNER_WIDTH + 4) . "\r";
}

function show_banner() {
    global $is_daemon;
    if (!$is_daemon) system("clear");
    draw_box([
        color('cyan', center_text("AUTO CLAIM BINTANG BOT TELEGRAM")),
        color('blue', center_text("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")),
        " " . color('white', "Author   : Mr.Tr3v!0n"),
        " " . color('white', "Channel  : t.me/config_geratis"),
        " " . color('white', "Status   : Multi-Account Sync Version")
    ], 'cyan');
    echo "\n";
}

function generate_client_id($init_data) {
    parse_str($init_data, $parsed);
    $user_id = "default_device";
    if (isset($parsed['user'])) {
        $user_obj = json_decode($parsed['user'], true);
        if (isset($user_obj['id'])) $user_id = $user_obj['id'];
    }
    $hash = md5($user_id);
    return sprintf('%08s-%04s-%04s-%04s-%12s',
        substr($hash, 0, 8), substr($hash, 8, 4),
        substr($hash, 12, 4), substr($hash, 16, 4),
        substr($hash, 20, 12));
}

function load_accounts() {
    if (!file_exists(ACCOUNT_FILE)) return [];
    $data = json_decode(file_get_contents(ACCOUNT_FILE), true);
    return $data !== null ? $data['accounts'] ?? [] : [];
}

function save_accounts($accounts) {
    file_put_contents(ACCOUNT_FILE, json_encode(['accounts' => $accounts], JSON_PRETTY_PRINT));
}

function add_account() {
    show_banner();
    echo " " . color('yellow', "[?] Nama Akun : ");
    $name = trim(fgets(STDIN));
    if (empty($name)) $name = "Akun " . (count(load_accounts()) + 1);

    echo " " . color('yellow', "[?] Telegram Init Data : ");
    $init_data = trim(fgets(STDIN));
    if (empty($init_data)) {
        draw_box([" " . color('red', "[❌] Init Data tidak boleh kosong!")], 'red');
        return;
    }

    $accounts = load_accounts();
    $accounts[] = [
        'name' => $name,
        'init_data' => $init_data,
        'client_id' => generate_client_id($init_data),
        'status' => 'active'
    ];
    save_accounts($accounts);

    draw_box([
        " " . color('green', "[✓] Akun '$name' berhasil ditambahkan!"),
        " " . color('white', " Client ID: " . $accounts[count($accounts)-1]['client_id'])
    ], 'green');
}

function list_accounts() {
    show_banner();
    $accounts = load_accounts();
    if (empty($accounts)) {
        draw_box([" " . color('yellow', "[!] Belum ada akun tersimpan.")], 'yellow');
        return;
    }

    draw_box([
        " " . color('cyan', "Total: " . count($accounts) . " akun"),
        color('blue', center_text("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"))
    ], 'cyan');

    foreach ($accounts as $i => $a) {
        $status = $a['status'] === 'active'
            ? color('green', "[AKTIF]")
            : color('red', "[COOLDOWN]");
        echo " " . color('white', ($i + 1) . ". ") . $status . " " . color('white', $a['name']) . "\n";
        echo "    " . color('blue', "ID: ") . color('white', substr($a['client_id'], 0, 20) . "...") . "\n";
    }
    echo "\n";
    echo " " . color('white', "Tekan ENTER untuk kembali...");
    fgets(STDIN);
}

function delete_account() {
    show_banner();
    $accounts = load_accounts();
    if (empty($accounts)) {
        draw_box([" " . color('yellow', "[!] Belum ada akun untuk dihapus.")], 'yellow');
        return;
    }

    draw_box([" " . color('red', "PILIH AKUN YANG DIHAPUS")], 'red');
    foreach ($accounts as $i => $a) {
        echo " " . color('white', ($i + 1) . ". " . $a['name']) . "\n";
    }
    echo "\n " . color('yellow', "[?] Nomor akun (0 = batal) : ");
    $choice = (int)trim(fgets(STDIN));

    if ($choice <= 0 || $choice > count($accounts)) return;

    $name = $accounts[$choice - 1]['name'];
    array_splice($accounts, $choice - 1, 1);
    save_accounts($accounts);

    draw_box([" " . color('green', "[✓] Akun '$name' berhasil dihapus!")], 'green');
}

function claim_account($url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    return [$response, $error];
}

function sync_accounts() {
    global $is_daemon;
    $accounts = load_accounts();
    if (empty($accounts)) {
        show_banner();
        draw_box([" " . color('yellow', "[!] Tidak ada akun. Tambah akun dulu.")], 'yellow');
        echo "\n " . color('white', "Tekan ENTER...");
        fgets(STDIN);
        return;
    }

    $url = "https://spinhub.cc/api/tasks/1/claim";
    $id_klaim = 1;

    while (true) {
        $accounts = load_accounts();
        $results = [];

        show_banner();
        echo " " . color('cyan', "[🔄] Round #{$id_klaim} — Memproses semua akun...\n\n");

        foreach ($accounts as $i => &$a) {
            if ($a['status'] !== 'active') {
                $results[] = " " . color('white', ($i+1) . ". {$a['name']} : ") . color('red', "COOLDOWN");
                continue;
            }

            echo "\r " . color('blue', "  ⏳ {$a['name']}...");

            $headers = [
                "Host: spinhub.cc",
                "Connection: keep-alive",
                'sec-ch-ua: "Chromium";v="137", "Not/A)Brand";v="24"',
                "Content-Type: application/json",
                "X-Client-Id: " . $a['client_id'],
                "sec-ch-ua-mobile: ?1",
                "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 Chrome/137.0.0.0 Mobile Safari/537.36",
                "X-Telegram-Init-Data: " . $a['init_data'],
                'sec-ch-ua-platform: "Android"',
                "Accept: */*",
                "Origin: https://spinhub.cc",
                "Sec-Fetch-Site: same-origin",
                "Sec-Fetch-Mode: cors",
                "Sec-Fetch-Dest: empty",
                "Accept-Language: id-ID,id;q=0.9"
            ];

            list($response, $error) = claim_account($url, $headers);

            if ($error) {
                $results[] = " " . color('white', ($i+1) . ". {$a['name']} : ") . color('red', "❌ CONNECTION ERROR");
                continue;
            }

            $data = json_decode($response, true);

            if ($data === null) {
                $results[] = " " . color('white', ($i+1) . ". {$a['name']} : ") . color('red', "❌ INVALID RESPONSE");
                continue;
            }

            if (isset($data['ok']) && $data['ok'] === true) {
                $reward  = $data['reward'] ?? 0;
                $balance = $data['balance'] ?? 0;
                $results[] = " " . color('white', ($i+1) . ". {$a['name']} : ") . color('green', "✓ +{$reward} (Saldo: {$balance})");
            } else {
                $errorMsg = $data['error'] ?? 'unknown_error';
                if ($errorMsg === "on_cooldown") {
                    $a['status'] = 'cooldown';
                    save_accounts($accounts);
                    $results[] = " " . color('white', ($i+1) . ". {$a['name']} : ") . color('yellow', "⏳ COOLDOWN");
                } else {
                    $cleanError = ucwords(str_replace('_', ' ', $errorMsg));
                    $results[] = " " . color('white', ($i+1) . ". {$a['name']} : ") . color('red', "✗ {$cleanError}");
                }
            }
        }
        unset($a);

        echo "\r" . str_repeat(' ', INNER_WIDTH + 4) . "\r";

        show_banner();
        draw_box(array_merge(
            [color('cyan', center_text("HASIL CLAIM ROUND #{$id_klaim}")), color('blue', center_text("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"))],
            $results
        ), 'cyan');

        $active_count = 0;
        foreach ($accounts as $a) {
            if ($a['status'] === 'active') $active_count++;
        }

        if ($active_count === 0) {
            $retry_detik = $is_daemon ? 1800 : 30; // 30 menit daemon, 30 dtk manual
            log_msg("SEMUA AKUN COOLDOWN — retry dalam {$retry_detik}dtk");

            draw_box([" " . color('yellow', "[⏳] SEMUA AKUN COOLDOWN — retry {$retry_detik}dtk")], 'yellow');

            for ($i = $retry_detik; $i > 0; $i--) {
                echo "\r " . color('yellow', "[⏳] Retry {$i}dtk...");
                if (!$is_daemon) {
                    $r = [STDIN];
                    $w = null;
                    $e = null;
                    if (stream_select($r, $w, $e, 1) && trim(fgets(STDIN)) === 'q') {
                        echo "\n";
                        return;
                    }
                } else {
                    sleep(1);
                }
            }

            $accounts = load_accounts();
            foreach ($accounts as &$a) {
                $a['status'] = 'active';
            }
            unset($a);
            save_accounts($accounts);
            continue;
        }

        $id_klaim++;

        log_msg("Round #$id_klaim selesai. " . ($active_count > 0 ? "$active_count akun aktif" : "0 akun aktif"));

        if (!$is_daemon) {
            echo "\n " . color('white', "[ENTER] lanjut, [q] kembali ke menu... ");
            $r = [STDIN];
            $w = null;
            $e = null;
            $input = '';
            if (stream_select($r, $w, $e, 3)) {
                $input = trim(fgets(STDIN));
            }
            if (strtolower($input) === 'q') return;
        }

        cooldown($is_daemon ? 10 : 5);
    }
}

// ===== SERVICE MANAGEMENT =====
function service_start() {
    $pid_file = '/tmp/bintang-bot.pid';
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        if (file_exists("/proc/$pid")) {
            draw_box([" " . color('yellow', "[⚠️] Service sudah berjalan (PID: $pid)")], 'yellow');
            return;
        }
    }

    $cmd = "nohup php " . __FILE__ . " --daemon > /dev/null 2>&1 &";
    exec($cmd);
    sleep(1);

    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        draw_box([" " . color('green', "[✓] Service dimulai (PID: $pid)")], 'green');
    } else {
        draw_box([" " . color('red', "[❌] Gagal memulai service")], 'red');
    }
}

function service_stop() {
    $pid_file = '/tmp/bintang-bot.pid';
    if (!file_exists($pid_file)) {
        draw_box([" " . color('yellow', "[⚠️] Service tidak berjalan")], 'yellow');
        return;
    }

    $pid = trim(file_get_contents($pid_file));
    exec("kill $pid 2>/dev/null");
    sleep(1);
    @unlink($pid_file);
    draw_box([" " . color('green', "[✓] Service dihentikan")], 'green');
}

function service_status() {
    $pid_file = '/tmp/bintang-bot.pid';
    if (file_exists($pid_file)) {
        $pid = trim(file_get_contents($pid_file));
        if (file_exists("/proc/$pid")) {
            $cmd = file_get_contents("/proc/$pid/cmdline");
            $cmd = str_replace("\0", " ", $cmd);
            draw_box([
                " " . color('green', "[✓] SERVICE BERJALAN"),
                " " . color('white', " PID    : $pid"),
                " " . color('white', " CMD    : " . substr($cmd, 0, 60) . "...")
            ], 'green');
            return;
        }
    }
    draw_box([" " . color('red', "[✗] SERVICE TIDAK BERJALAN")], 'red');
}

function service_logs() {
    if (!file_exists(LOG_FILE)) {
        draw_box([" " . color('yellow', "[!] Belum ada log")], 'yellow');
        return;
    }
    $lines = file(LOG_FILE);
    $last = array_slice($lines, -20);
    echo "\n";
    draw_box([" " . color('cyan', "20 LOG TERAKHIR")], 'cyan');
    foreach ($last as $l) {
        $clean = trim(strip_tags($l));
        if (!empty($clean)) {
            echo " " . color('white', $clean) . "\n";
        }
    }
    echo "\n " . color('white', "Tekan ENTER...");
    fgets(STDIN);
}

// ===== MAIN MENU =====
while (true) {
    show_banner();
    $accounts = load_accounts();
    $active_count = 0;
    foreach ($accounts as $a) {
        if ($a['status'] === 'active') $active_count++;
    }
    $total = count($accounts);

    $service_status = file_exists('/tmp/bintang-bot.pid') ? color('green', "AKTIF") : color('red', "MATI");

    draw_box([
        " " . color('cyan', "MENU UTAMA"),
        color('blue', center_text("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")),
        " " . color('white', "Akun: $total (" . color('green', "$active_count aktif") . ")  Service: $service_status"),
        "",
        " " . color('white', "1. " . color('green', "[+] Tambah Akun")),
        " " . color('white', "2. " . color('cyan', "[i] Lihat Akun")),
        " " . color('white', "3. " . color('red', "[-] Hapus Akun")),
        " " . color('white', "4. " . color('yellow', "[↻] Sync / Claim Semua")),
        color('blue', center_text("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━")),
        " " . color('white', "5. " . color('green', "[▶] Start Service")),
        " " . color('white', "6. " . color('red', "[■] Stop Service")),
        " " . color('white', "7. " . color('cyan', "[ℹ] Cek Status Service")),
        " " . color('white', "8. " . color('white', "[📋] Lihat Log")),
        " " . color('white', "9. " . color('red', "[x] Keluar"))
    ], 'cyan');

    echo "\n " . color('yellow', "[?] Pilih menu (1-9) : ");
    $choice = trim(fgets(STDIN));

    switch ($choice) {
        case '1': add_account(); break;
        case '2': list_accounts(); break;
        case '3': delete_account(); break;
        case '4': sync_accounts(); break;
        case '5': service_start(); break;
        case '6': service_stop(); break;
        case '7': service_status(); break;
        case '8': service_logs(); break;
        case '9':
            draw_box([" " . color('white', "Sampai jumpa!")], 'cyan');
            exit;
        default:
            draw_box([" " . color('red', "[!] Pilihan tidak valid!")], 'red');
            sleep(1);
    }
}
