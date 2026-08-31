<?php
/**
 * お知らせ（news.json）共通読み込みライブラリ
 *
 * index.php（初期表示）と news_api.php（Load More の非同期取得）で
 * 同じ読み込み・並べ替えロジックを共有するためのファイル。
 */

if (!defined('NEWS_JSON_PATH')) {
    define('NEWS_JSON_PATH', __DIR__ . '/news_admin/news.json');
}

/**
 * news.json を読み込み、日付の新しい順に並べ替えた配列を返す。
 * 書き込み中の不完全なデータを読まないよう共有ロック(LOCK_SH)を使用する。
 *
 * @return array<int, array{date:string, title:string}>
 */
function news_load_all(): array
{
    if (!is_readable(NEWS_JSON_PATH)) {
        return [];
    }

    $fp = fopen(NEWS_JSON_PATH, 'rb');
    if ($fp === false) {
        return [];
    }

    $json_raw = '';
    if (flock($fp, LOCK_SH)) {
        $json_raw = stream_get_contents($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);

    $decoded = json_decode($json_raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    // 日付の新しい順（降順）に並べ替え
    usort($decoded, static function ($a, $b) {
        return strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
    });

    return $decoded;
}

/**
 * 指定範囲のお知らせを取得する。
 *
 * @param int $offset 取得開始位置（0起点）
 * @param int $limit  取得件数
 * @return array{items: array<int, array{date:string, title:string}>, total: int, has_more: bool}
 */
function news_load_slice(int $offset, int $limit): array
{
    $all   = news_load_all();
    $total = count($all);

    $offset = max(0, $offset);
    $limit  = max(0, $limit);

    $items = array_slice($all, $offset, $limit);

    return [
        'items'    => $items,
        'total'    => $total,
        'has_more' => ($offset + count($items)) < $total,
    ];
}
