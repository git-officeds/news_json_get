<?php
    /**
     * news_admin/news.json からお知らせ情報を読み込む。
     * 初期表示は最新3件。以降は「もっと見る」ボタンから news_api.php 経由で
     * 5件ずつ非同期に追加読み込みする（Load More 型）。
     */
    require __DIR__ . '/news_lib.php';

    $initial_count = 3;   // 初期表示件数
    $load_step     = 5;   // 「もっと見る」1回あたりの追加件数

    $news_data = news_load_slice(0, $initial_count);
    $lists     = $news_data['items'];
    $has_more  = $news_data['has_more'];
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせサンプル</title>
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
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

    <main>

        <h3>お知らせ</h3>
        <section id="news">
            <dl id="news-list">
            <?php foreach($lists as $list){ ?>
                <dt><time datetime="<?php echo htmlspecialchars($list['date'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($list['date'], ENT_QUOTES, 'UTF-8'); ?></time></dt>
                <dd><?php echo htmlspecialchars($list['title'], ENT_QUOTES, 'UTF-8'); ?></dd>
            <?php } ?>
            </dl>

            <div class="news-more"<?php echo $has_more ? '' : ' hidden'; ?>>
                <button type="button" id="news-more-btn"
                        data-endpoint="news_api.php"
                        data-offset="<?php echo (int)count($lists); ?>"
                        data-step="<?php echo (int)$load_step; ?>">
                    もっと見る
                </button>
            </div>
        </section>

    </main>

</body>
</html>