<?php
    /**
     * news_admin/news.json からお知らせ情報を読み込み、配列 $lists に格納する。
     * 書き込み中の不完全なデータを読まないよう共有ロック(LOCK_SH)を使用する。
     */
    $json_path = __DIR__ . '/news.json';
    $lists = [];

    if (is_readable($json_path)) {
        $fp = fopen($json_path, 'rb');
        if ($fp !== false) {
            if (flock($fp, LOCK_SH)) {
                $json_raw = stream_get_contents($fp);
                flock($fp, LOCK_UN);
            } else {
                $json_raw = '';
            }
            fclose($fp);

            $decoded = json_decode($json_raw, true);
            if (is_array($decoded)) {
                $lists = $decoded;
            }
        }
    }
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせ管理システム</title>
    <style>
        .container{
            margin: 20px auto;
            padding-left: 15px;
            padding-right: 15px;
            box-sizing: border-box;
        }
        @media (max-width: 575px){
            .container{ width: 100%; }
        }
        @media (min-width: 576px){
            .container{ width: 540px; }
        }
        @media (min-width: 768px){
            .container{ width: 720px; }
        }
        @media (min-width: 992px){
            .container{ width: 960px; }
        }
        @media (min-width: 1200px){
            .container{ width: 1140px; }
        }
    </style>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>

    <header>
        <div class="container">
            <h1>お知らせ　管理画面</h1>
        </div>
    </header>

    <main>

        <section id="control_area">
            <div class="container">
                <button type="button" name="add">項目を追加</button>
                <button type="button" name="all_submit" disabled>現在の状態で保存</button>
                <button type="button" name="all_delete">選択した項目を削除</button>
                <button type="button" name="reload">すべてやり直す</button>
            </div>
        </section>

        <section id="news_list">
            <div class="container">
                <table>
                    <thead><tr>
                        <th><input type="button" name="save" value="すべて選択"></th><th>削除</th><th>日付</th><th>内容</th>
                    </tr></thead>
                    <tbody id="news_tbody">
                        <?php foreach($lists as $list){ ?>
                        <tr>
                            <td><input type="checkbox" name="select"></td>
                            <td><button type="button" name="delete"><img src="./images/trush.png" alt="削除"></button></td>
                            <td><input type="date" name="last_update" value="<?php echo htmlspecialchars($list['date'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                            <td><textarea name="content"><?php echo htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8'); ?></textarea></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <footer>
        <div class="container">
            <p class="copyright">&copy; <?php echo date('Y'); ?> Office DS All Rights Reserved.</p>
        </div>
    </footer>

    <!-- 保存結果ポップアップ -->
    <div id="save_popup" class="popup" role="alertdialog" aria-live="assertive" aria-labelledby="save_popup_text" hidden>
        <p id="save_popup_text" class="popup__text"></p>
        <button type="button" class="popup__close" aria-label="メッセージを閉じる">&times;</button>
    </div>

</body>
</html>