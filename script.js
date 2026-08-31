'use strict';


document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('news-list');
    if (!list) return;

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function nl2br(str) {
        return esc(str).replace(/\r\n|\r|\n/g, '<br>');
    }

    /****************************************
      行クリックでドロップダウン開閉（イベント委譲）
    ****************************************/
    function toggleItem(item) {
        var open = item.classList.toggle('is-open');
        var summary = item.querySelector('.news-summary');
        if (summary) summary.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    list.addEventListener('click', function (e) {
        var item = e.target.closest('.news-item[data-expandable]');
        if (!item) return;
        if (e.target.closest('.news-detail')) return;
        toggleItem(item);
    });

    list.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar') return;
        var summary = e.target.closest('.news-summary[role="button"]');
        if (!summary) return;
        e.preventDefault();
        toggleItem(summary.parentNode);
    });

    /****************************************
      Load More型の動作部
    ****************************************/
    var btn = document.getElementById('news-more-btn');
    if (!btn) return;

    var wrap = btn.parentNode;
    var loading = false;

    function buildItem(item) {
        var d = esc(item.date);
        var label = esc(item.date_label || item.date);
        var title = esc(item.title);
        var text = (item.text == null) ? '' : String(item.text).replace(/^\s+|\s+$/g, '');
        var hasText = text !== '';

        var html = '<div class="news-item"' + (hasText ? ' data-expandable="1"' : '') + '>';
        html += '<dt><time datetime="' + d + '">' + label + '</time></dt>';
        html += '<dd class="news-summary"' +
            (hasText ? ' role="button" tabindex="0" aria-expanded="false"' : '') + '>';
        html += '<span class="news-title">' + title + '</span>';
        if (hasText) html += '<span class="news-toggle" aria-hidden="true"></span>';
        html += '</dd>';
        if (hasText) {
            html += '<dd class="news-detail"><div class="news-detail-inner">' + nl2br(text) + '</div></dd>';
        }
        html += '</div>';
        return html;
    }

    function appendItems(items) {
        var html = '';
        for (var i = 0; i < items.length; i++) {
            html += buildItem(items[i]);
        }
        list.insertAdjacentHTML('beforeend', html);
    }

    btn.addEventListener('click', function () {
        if (loading) return;
        loading = true;
        btn.disabled = true;
        btn.classList.add('is-loading');

        var offset = parseInt(btn.dataset.offset, 10) || 0;
        var step = parseInt(btn.dataset.step, 10) || 5;
        var url = btn.dataset.endpoint +
            '?offset=' + encodeURIComponent(offset) +
            '&limit=' + encodeURIComponent(step);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                var items = (data && data.items) || [];
                appendItems(items);

                btn.dataset.offset = String(offset + items.length);

                if (!data || !data.has_more) {
                    if (wrap && wrap.parentNode) {
                        wrap.parentNode.removeChild(wrap);
                    }
                }
            })
            .catch(function () {
                btn.textContent = '読み込みに失敗しました。再試行';
            })
            .finally(function () {
                loading = false;
                btn.disabled = false;
                btn.classList.remove('is-loading');
            });
    });
});
