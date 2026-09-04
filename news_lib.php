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

/**
 * 詳細本文（text）を表示用HTMLへ変換する。
 *
 * - HTML特殊文字はすべてエスケープする（XSS対策）
 * - 改行は <br> に変換する
 * - 文中の URL（http(s):// または www. で始まる文字列）を
 *   別タブで開く <a> リンクに変換する（rel="noopener noreferrer"）
 *
 * エスケープ前の生テキストに対して URL 抽出を行い、URL部分と
 * それ以外の部分をそれぞれ個別にエスケープして組み立てるため、
 * クエリ文字列中の & などがあってもリンクが壊れない。
 *
 * @param string $text 生の詳細本文
 * @return string 安全な表示用HTML
 */
function news_text_to_html(string $text): string
{
    // http(s):// もしくは www. で始まり、URL に使用される文字が続く範囲を URL とみなす。
    // 直前が単語構成文字・@・/ の場合は URL 開始と見なさない（メールアドレス等の誤検出防止）。
    // 文字クラスを ASCII の URL 使用文字に限定し、日本語の句読点などで自然に区切る。
    $pattern = '~(?<![\w@/])((?:https?://|www\.)[-A-Za-z0-9._\~:/?\#\[\]@!$&()+,;=%]+)~i';

    $out    = '';
    $offset = 0;

    if (preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $match) {
            $raw_url  = $match[0];
            $pos      = $match[1];
            $full_len = strlen($raw_url);

            // URL より前の通常テキスト
            $out .= nl2br(htmlspecialchars(substr($text, $offset, $pos - $offset), ENT_QUOTES, 'UTF-8'), false);

            // 末尾の句読点・閉じ括弧は URL に含めない（例: 「(https://example.com/)。」）
            $trail = '';
            while ($raw_url !== '' && strpos('.,;:!?)]}', substr($raw_url, -1)) !== false) {
                $trail   = substr($raw_url, -1) . $trail;
                $raw_url = substr($raw_url, 0, -1);
            }

            if ($raw_url !== '') {
                $href = (stripos($raw_url, 'www.') === 0) ? 'https://' . $raw_url : $raw_url;
                $out .= '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"'
                      . ' target="_blank" rel="noopener noreferrer">'
                      . htmlspecialchars($raw_url, ENT_QUOTES, 'UTF-8') . '</a>';
            }
            $out .= htmlspecialchars($trail, ENT_QUOTES, 'UTF-8');

            $offset = $pos + $full_len;
        }
    }

    // 残りの通常テキスト
    $out .= nl2br(htmlspecialchars(substr($text, $offset), ENT_QUOTES, 'UTF-8'), false);

    return $out;
}
