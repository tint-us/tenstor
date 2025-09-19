<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

// Timezone (opsional)
$tz = getenv('PHP_TZ') ?: 'Asia/Jakarta';
date_default_timezone_set($tz);

// Konfigurasi via ENV (fallback ke default)
$uploadDir    = rtrim(getenv('TENSTOR_UPLOAD_DIR') ?: '/tenable/hasil', '/') . '/';
$logFile      = getenv('TENSTOR_LOG_FILE') ?: '/var/log/report-tenable.log';
$authUser     = getenv('TENSTOR_USER') ?: 'CHANGE_ME_WITH_USERNAME';
$authPassword = getenv('TENSTOR_PASSWORD') ?: 'CHANGE_ME_WITH_PASSWORD';

// Pastikan direktori ada & writable
@is_dir($uploadDir) || @mkdir($uploadDir, 0775, true);
@is_dir(dirname($logFile)) || @mkdir(dirname($logFile), 0775, true);

// Logging sederhana
function logMessage(string $message): void {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    $entry = "[$date] $message\n";
    error_log($entry, 3, $logFile);
}

// Parser PDF → ambil pola (Scan: ...)
function extractScanLabel(string $filePath): ?string {
    try {
        $parser = new Parser();
        $pdf    = $parser->parseFile($filePath);
        $pages  = $pdf->getPages();
        if (empty($pages)) {
            logMessage("No pages in PDF '$filePath'.");
            return null;
        }
        // Halaman pertama saja
        $text = preg_replace('/\s+/', ' ', trim($pages[0]->getText()));
        if (preg_match('/\(Scan:\s*(.*?)\)/', $text, $m)) {
            $val = $m[1];
            // Bersihkan karakter berisiko untuk filename
            $val = str_replace(['"', "'", '/', '\\', ':', '*', '?', '<', '>', '|'], ' ', $val);
            $val = preg_replace('/\s+/', ' ', trim($val));
            return $val ?: null;
        }
        logMessage("No matching pattern '(Scan: ...)' in '$filePath'.");
        return null;
    } catch (Throwable $e) {
        logMessage("PDF parse error: " . $e->getMessage());
        return null;
    }
}

// Basic Auth
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    logMessage("Auth failed: no credentials provided.");
    exit;
}
if ($_SERVER['PHP_AUTH_USER'] !== $authUser || ($_SERVER['PHP_AUTH_PW'] ?? '') !== $authPassword) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Incorrect credentials';
    logMessage("Auth failed for user: " . ($_SERVER['PHP_AUTH_USER'] ?? 'NULL'));
    exit;
}

// Healthcheck sederhana
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['health'] ?? '') === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'ok',
        'time'   => date(DATE_ATOM),
        'uploadDir' => $uploadDir,
        'tz'     => $tz,
    ]);
    exit;
}

// Terima file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['reportContent'])) {
    $origName   = basename($_FILES['reportContent']['name']);
    $tmp        = $_FILES['reportContent']['tmp_name'];
    $targetFile = $uploadDir . $origName;

    if (!is_uploaded_file($tmp)) {
        http_response_code(400);
        echo 'Invalid upload';
        logMessage("Invalid upload for '$origName'.");
        exit;
    }

    if (!move_uploaded_file($tmp, $targetFile)) {
        http_response_code(500);
        echo 'Failed to save file';
        logMessage("move_uploaded_file failed for '$origName'.");
        exit;
    }

    logMessage("Uploaded '$origName' by '{$_SERVER['PHP_AUTH_USER']}'.");

    $label = extractScanLabel($targetFile);
    if ($label) {
        $ymd = date('y-m-d');
        $newFile = $uploadDir . "{$ymd}-{$label}.pdf";

        // Hindari tabrakan nama
        $i = 2;
        $base = $newFile;
        while (file_exists($newFile)) {
            $newFile = preg_replace('/\.pdf$/i', "-{$i}.pdf", $base);
            $i++;
        }

        if (@rename($targetFile, $newFile)) {
            echo "OK: saved as '" . basename($newFile) . "'";
            logMessage("Renamed '$origName' -> '" . basename($newFile) . "'");
        } else {
            echo "OK: uploaded, but rename failed";
            logMessage("Rename failed for '$origName'");
        }
    } else {
        echo "OK: uploaded, but pattern not found";
        logMessage("Pattern not found in '$origName'");
    }
    exit;
}

// Fallback
http_response_code(400);
echo 'No file uploaded (expect multipart field "reportContent")';
logMessage('No file uploaded via POST.');
