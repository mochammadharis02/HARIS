<?php
// =============================================
// GANTI API KEY OCR.SPACE ANDA DI BAWAH INI
// =============================================
$OCR_API_KEY = "K86696350588957";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method tidak valid"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageBase64 = $input['image'] ?? '';

if (!$imageBase64) {
    echo json_encode(["status" => "error", "message" => "Tidak ada gambar"]);
    exit;
}

if (strpos($imageBase64, ',') !== false) {
    $imageBase64 = explode(',', $imageBase64)[1];
}
$imageBase64 = preg_replace('/\s+/', '', $imageBase64);

// Kirim ke OCR.space
$postData = [
    'base64Image'       => 'data:image/jpeg;base64,' . $imageBase64,
    'language'          => 'eng',
    'isOverlayRequired' => 'false',
    'detectOrientation' => 'true',
    'scale'             => 'true',
    'OCREngine'         => '2',
    'filetype'          => 'jpg',
];

$ch = curl_init('https://api.ocr.space/parse/image');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_HTTPHEADER     => ['apikey: ' . $OCR_API_KEY],
    CURLOPT_TIMEOUT        => 30,
]);
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(["status" => "error", "message" => "Curl error: " . $curlError]);
    exit;
}

$ocrResult = json_decode($response, true);
if ($ocrResult['IsErroredOnProcessing'] ?? true) {
    $errMsg = $ocrResult['ErrorMessage'][0] ?? 'OCR gagal';
    echo json_encode(["status" => "error", "message" => $errMsg]);
    exit;
}

$rawText = $ocrResult['ParsedResults'][0]['ParsedText'] ?? '';
if (empty(trim($rawText))) {
    echo json_encode(["status" => "error", "message" => "Tidak ada teks terbaca"]);
    exit;
}

// =============================================
// Parse KTP - lebih akurat
// =============================================
$lines = preg_split('/\r\n|\r|\n/', $rawText);
$lines = array_values(array_filter(array_map('trim', $lines)));

$data = ['nama' => '', 'nik' => '', 'gender' => '', 'alamat' => ''];

// Kata-kata header KTP yang harus diabaikan untuk nama
$ignoredWords = [
    'provinsi', 'kabupaten', 'kota', 'republik', 'indonesia',
    'kartu', 'tanda', 'penduduk', 'ktp', 'dinas', 'dukcapil',
    'jawa', 'barat', 'timur', 'tengah', 'selatan', 'utara',
    'sumatera', 'kalimantan', 'sulawesi', 'papua', 'bali',
    'nusa', 'tenggara', 'dki', 'jakarta', 'yogyakarta',
    'nik', 'nama', 'alamat', 'rt', 'rw', 'tempat', 'lahir',
    'tanggal', 'jenis', 'kelamin', 'golongan', 'darah',
    'agama', 'status', 'perkawinan', 'pekerjaan', 'kewarganegaraan',
    'berlaku', 'hingga', 'seumur', 'hidup', 'ttd', 'tanda tangan'
];

foreach ($lines as $i => $line) {
    $lineLower = strtolower($line);
    $lineClean = trim($line);

    // ── NIK: 16 digit angka ──
    if (empty($data['nik'])) {
        preg_match('/\b(\d{16})\b/', $line, $m);
        if ($m) $data['nik'] = $m[1];
    }

    // ── NAMA: baris setelah label "Nama" ──
    if (empty($data['nama'])) {
        if (preg_match('/^nama\s*[:\-]?\s*(.+)$/i', $line, $m)) {
            $kandidat = trim($m[1]);
            // Pastikan bukan kata header
            $isHeader = false;
            foreach ($ignoredWords as $w) {
                if (stripos($kandidat, $w) !== false && strlen($kandidat) < 20) {
                    $isHeader = true; break;
                }
            }
            if (!$isHeader && strlen($kandidat) > 2) {
                $data['nama'] = strtoupper($kandidat);
            }
        }
        // Jika nama di baris berikutnya setelah label "Nama"
        if (empty($data['nama']) && preg_match('/^nama\s*[:\-]?\s*$/i', $line)) {
            $nextLine = $lines[$i + 1] ?? '';
            if (strlen(trim($nextLine)) > 2) {
                $data['nama'] = strtoupper(trim($nextLine));
            }
        }
    }

    // ── JENIS KELAMIN ──
    if (empty($data['gender'])) {
        if (strpos($lineLower, 'laki-laki') !== false || strpos($lineLower, 'laki laki') !== false) {
            $data['gender'] = 'Laki-laki';
        } elseif (strpos($lineLower, 'perempuan') !== false || strpos($lineLower, 'wanita') !== false) {
            $data['gender'] = 'Perempuan';
        }
    }

    // ── ALAMAT ──
    if (empty($data['alamat'])) {
        if (preg_match('/^alamat\s*[:\-]?\s*(.+)$/i', $line, $m)) {
            $alamat = trim($m[1]);
            if (strlen($alamat) > 3) $data['alamat'] = $alamat;
        }
        if (empty($data['alamat']) && preg_match('/^alamat\s*[:\-]?\s*$/i', $line)) {
            $nextLine = $lines[$i + 1] ?? '';
            if (strlen(trim($nextLine)) > 3) {
                $data['alamat'] = trim($nextLine);
            }
        }
    }
}

// ── Fallback nama: cari baris ALL CAPS yang bukan header ──
if (empty($data['nama'])) {
    foreach ($lines as $line) {
        $trimmed = trim($line);
        // ALL CAPS, minimal 4 karakter, bukan angka, bukan header
        if (preg_match('/^[A-Z][A-Z\s]{3,}$/', $trimmed)) {
            $isHeader = false;
            foreach ($ignoredWords as $w) {
                if (stripos($trimmed, $w) !== false) { $isHeader = true; break; }
            }
            if (!$isHeader) {
                $data['nama'] = $trimmed;
                break;
            }
        }
    }
}

if (empty($data['nik']) && empty($data['nama'])) {
    echo json_encode([
        "status"   => "error",
        "message"  => "Gagal membaca KTP. Coba foto lebih jelas dan terang.",
        "raw_text" => $rawText
    ]);
    exit;
}

echo json_encode(["status" => "success", "data" => $data]);
?>