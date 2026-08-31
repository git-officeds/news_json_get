<?php
/**
 * お知らせ Load More 用 API
 *
 * GET パラメータ:
 *   offset : 取得開始位置（0起点、既定 0）
 *   limit  : 取得件数（既定 5、最大 50）
 *
 * レスポンス(JSON):
 *   { "items": [ { "date": "...", "title": "...", "date_label": "..." } ],
 *     "total": 20, "has_more": true }
 */

declare(strict_types=1);

require __DIR__ . '/news_lib.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT, [
    'options' => ['default' => 0, 'min_range' => 0],
]);
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT, [
    'options' => ['default' => 5, 'min_range' => 1, 'max_range' => 50],
]);

$offset = is_int($offset) ? $offset : 0;
$limit  = is_int($limit) ? $limit : 5;

$result = news_load_slice($offset, $limit);

// 表示用の日付ラベルを付与（datetime 属性は元の date をそのまま使用）
$items = array_map(static function ($item) {
    $date  = (string)($item['date'] ?? '');
    $title = (string)($item['title'] ?? '');
    $ts    = strtotime($date);

    return [
        'date'       => $date,
        'title'      => $title,
        'date_label' => $ts !== false ? date('Y-m-d', $ts) : $date,
    ];
}, $result['items']);

echo json_encode([
    'items'    => $items,
    'total'    => $result['total'],
    'has_more' => $result['has_more'],
], JSON_UNESCAPED_UNICODE);
