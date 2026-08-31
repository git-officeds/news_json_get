'use strict';


/****************************************
  Load More型の動作部
****************************************/
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('news-more-btn');
    var list = document.getElementById('news-list');
    if (!btn || !list) return;

    var wrap = btn.parentNode;
    var loading = false;

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function appendItems(items) {
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var d = esc(items[i].date);
            var label = esc(items[i].date_label || items[i].date);
            var title = esc(items[i].title);
            html += '<dt><time datetime="' + d + '">' + label + '</time></dt>';
            html += '<dd>' + title + '</dd>';
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
