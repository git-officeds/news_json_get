<?php
/**
 * お知らせ 保存 API（管理画面「現在の状態で保存」用）
 *
 * リクエスト:
 *   POST  Content-Type: application/json
 *   本文 : [ { "date": "YYYY-MM-DD", "title": "..." }, ... ]
 *
 * レスポンス(JSON):
 *   { "success": true,  "message": "保存しました。", "count": N }
 *   { "success": false, "message": "..." }
 *
 * 仕様(CLAUDE.md 準拠):
 *   - 書き込みは file_put_contents(..., LOCK_EX) で排他ロック
 *   - このファイルは news_admin/ 配下にあり Basic 認証で保護される
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

/**
 * JSON を返して終了する
 */
function respond(bool $ok, string $message, int $status = 200, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(
        array_merge(['success' => $ok, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// ----- メソッド確認 -----
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, '不正なリクエストです（POST のみ受け付けます）。', 405);
}

// ----- 受信データのデコード -----
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    respond(false, '送信データが空です。', 400);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(false, '送信データの形式が正しくありません。', 400);
}

// ----- 正規化・バリデーション -----
$records = [];
foreach ($data as $row) {
    if (!is_array($row)) {
        respond(false, 'レコードの形式が正しくありません。', 422);
    }

    $date  = trim((string)($row['date'] ?? ''));
    $title = trim((string)($row['title'] ?? ''));

    // 日付・内容ともに空の行は無視（未入力の追加行など）
    if ($date === '' && $title === '') {
        continue;
    }

    // 日付形式チェック（厳密な YYYY-MM-DD）
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        respond(false, '日付が未入力、または形式が正しくない行があります（YYYY-MM-DD）。', 422);
    }

    // 内容チェック
    if ($title === '') {
        respond(false, '内容が未入力の行があります。', 422);
    }
    if (mb_strlen($title) > 500) {
        respond(false, '内容が長すぎる行があります（500文字以内）。', 422);
    }

    $records[] = ['date' => $date, 'title' => $title];
}

// 日付の新しい順（降順）に整列してから保存
usort($records, static function ($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// ----- JSON 生成 -----
$json = json_encode(
    $records,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
if ($json === false) {
    respond(false, 'JSON への変換に失敗しました。', 500);
}

// ----- 書き込み（排他ロック）-----
$json_path = __DIR__ . '/news.json';

if (file_exists($json_path) && !is_writable($json_path)) {
    respond(false, '保存先ファイルに書き込み権限がありません。', 500);
}

$result = file_put_contents($json_path, $json . "\n", LOCK_EX);
if ($result === false) {
    respond(false, 'ファイルの保存に失敗しました。', 500);
}

respond(true, '保存しました。', 200, ['count' => count($records)]);
