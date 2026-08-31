'use strict';

/****************************************
  お知らせ管理画面
****************************************/
document.addEventListener('DOMContentLoaded', function () {

    var addBtn    = document.querySelector('#control_area button[name="add"]');
    var submitBtn = document.querySelector('#control_area button[name="all_submit"]');
    var delBtn    = document.querySelector('#control_area button[name="all_delete"]');
    var reloadBtn = document.querySelector('#control_area button[name="reload"]');
    var fabSave   = document.getElementById('fab_save');
    var popup     = document.getElementById('save_popup');
    var popupText = document.getElementById('save_popup_text');
    var popupClose = popup ? popup.querySelector('.popup__close') : null;
    var tbody     = document.getElementById('news_tbody');

    if (!addBtn || !tbody) return;

    /**
     * 編集された状態にする（保存ボタンを有効化し、フローティング保存ボタンを表示）
     */
    function enableSubmit() {
        if (submitBtn) submitBtn.disabled = false;
        if (fabSave) fabSave.hidden = false;
    }

    /**
     * 未編集の状態に戻す（保存ボタンを無効化し、フローティング保存ボタンを隠す）
     */
    function markSaved() {
        if (submitBtn) submitBtn.disabled = true;
        if (fabSave) fabSave.hidden = true;
    }

    /**
     * 保存結果ポップアップを閉じる
     */
    function hidePopup() {
        if (popup) popup.hidden = true;
    }

    /**
     * 保存結果をポップアップ表示する（自動では消えない。閉じるボタンで消す）
     * @param {'success'|'error'|''} type
     * @param {string} text
     */
    function showMessage(type, text) {
        if (!popup || !popupText) return;
        if (!text) { hidePopup(); return; }

        popupText.textContent = text;
        popup.classList.remove('popup--success', 'popup--error');
        if (type) {
            popup.classList.add('popup--' + type);
        }
        popup.hidden = false;
        if (popupClose) popupClose.focus();
    }

    if (popupClose) {
        popupClose.addEventListener('click', hidePopup);
    }
    // Esc キーでも閉じられるように
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && popup && !popup.hidden) {
            hidePopup();
        }
    });

    /**
     * 主行に対応する詳細行（次の兄弟の .detail-row）を返す
     * @param {HTMLTableRowElement} mainRow
     * @returns {HTMLTableRowElement|null}
     */
    function detailRowOf(mainRow) {
        var next = mainRow.nextElementSibling;
        return (next && next.classList.contains('detail-row')) ? next : null;
    }

    /**
     * 詳細トグルの「入力済み」表示を更新する
     * @param {HTMLTableRowElement} mainRow
     */
    function refreshDetailToggle(mainRow) {
        var toggle = mainRow.querySelector('.detail-toggle');
        var detail = detailRowOf(mainRow);
        if (!toggle || !detail) return;
        var ta = detail.querySelector('textarea[name="text"]');
        var filled = !!(ta && ta.value.trim() !== '');
        toggle.classList.toggle('detail-toggle--filled', filled);
    }

    // 日付・内容・詳細フィールドが変更されたら保存ボタンを有効化（イベント委譲）
    tbody.addEventListener('input', function (e) {
        var t = e.target;
        if (!t) return;
        if (t.name === 'last_update' || t.name === 'content' || t.name === 'text') {
            enableSubmit();
            showMessage('', '');
            if (t.name === 'text') {
                var detailRow = t.closest('tr.detail-row');
                if (detailRow && detailRow.previousElementSibling) {
                    refreshDetailToggle(detailRow.previousElementSibling);
                }
            }
        }
    });

    // 詳細トグル: クリックで詳細本文の textarea を開閉（イベント委譲）
    tbody.addEventListener('click', function (e) {
        var toggle = e.target.closest('.detail-toggle');
        if (!toggle) return;

        var mainRow = toggle.closest('tr');
        var detail  = mainRow ? detailRowOf(mainRow) : null;
        if (!detail) return;

        var open = !detail.classList.contains('is-open');   // これから開くか
        detail.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));

        if (open) {
            var ta = detail.querySelector('textarea[name="text"]');
            if (ta) ta.focus();
        }
    });

    // 削除ボタン（name="delete"）: 該当の行（＋詳細行）を削除（イベント委譲）
    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('button[name="delete"]');
        if (!btn) return;

        var row = btn.closest('tr');
        if (row) {
            var detail = detailRowOf(row);
            if (detail) detail.remove();
            row.remove();
            enableSubmit();
        }
    });

    // 「選択した項目を削除」: チェックされている行（＋詳細行）をすべて削除
    if (delBtn) {
        delBtn.addEventListener('click', function () {
            var checked = tbody.querySelectorAll('input[type="checkbox"][name="select"]:checked');
            if (checked.length === 0) return;

            checked.forEach(function (cb) {
                var row = cb.closest('tr');
                if (!row) return;
                var detail = detailRowOf(row);
                if (detail) detail.remove();
                row.remove();
            });
            enableSubmit();
        });
    }

    /**
     * 空の主行＋詳細行を生成して返す
     * @returns {{ main: HTMLTableRowElement, detail: HTMLTableRowElement }}
     */
    function createEmptyRow() {
        var main = document.createElement('tr');
        main.innerHTML =
            '<td><input type="checkbox" name="select"></td>' +
            '<td><button type="button" name="delete"><img src="./images/trush.png" alt="削除"></button></td>' +
            '<td><input type="date" name="last_update" value=""></td>' +
            '<td class="content-cell">' +
                '<textarea name="content"></textarea>' +
                '<button type="button" class="detail-toggle" aria-expanded="false" title="詳細本文を編集">' +
                    '<span class="detail-toggle__mark" aria-hidden="true"></span>詳細' +
                '</button>' +
            '</td>';

        var detail = document.createElement('tr');
        detail.className = 'detail-row';
        detail.innerHTML =
            '<td colspan="4">' +
                '<div class="detail-row__collapse">' +
                    '<div class="detail-row__inner">' +
                        '<label class="detail-field">' +
                            '<span class="detail-field__label">詳細本文</span>' +
                            '<textarea name="text" rows="4" placeholder="お知らせの詳細（本文）を入力"></textarea>' +
                        '</label>' +
                    '</div>' +
                '</div>' +
            '</td>';

        return { main: main, detail: detail };
    }

    // 「項目を追加」: テーブルの1行目に空行（主行＋詳細行）を差し込む
    addBtn.addEventListener('click', function () {
        var pair = createEmptyRow();
        var first = tbody.firstElementChild;
        tbody.insertBefore(pair.main, first);
        tbody.insertBefore(pair.detail, first);
        enableSubmit();
        showMessage('', '');

        // 入力しやすいよう日付欄にフォーカス
        var dateInput = pair.main.querySelector('input[name="last_update"]');
        if (dateInput) dateInput.focus();
    });

    /**
     * 現在のテーブル内容をレコード配列にまとめる
     * @returns {Array<{date:string, title:string, text:string}>}
     */
    function collectRecords() {
        var records = [];
        tbody.querySelectorAll('tr:not(.detail-row)').forEach(function (row) {
            var dateEl    = row.querySelector('input[name="last_update"]');
            var contentEl = row.querySelector('textarea[name="content"]');
            var detail    = detailRowOf(row);
            var textEl    = detail ? detail.querySelector('textarea[name="text"]') : null;
            records.push({
                date:  dateEl ? dateEl.value.trim() : '',
                title: contentEl ? contentEl.value.trim() : '',
                text:  textEl ? textEl.value.trim() : ''
            });
        });
        return records;
    }

    // 「すべてやり直す」: 画面を再読み込みし、保存済みの状態に戻す
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function () {
            // 未保存の変更がある場合は確認（保存ボタンが押せる＝未保存）
            var dirty = submitBtn && !submitBtn.disabled;
            if (dirty && !window.confirm('保存していない変更は破棄されます。すべてやり直しますか？')) {
                return;
            }
            reloadBtn.disabled = true;
            reloadBtn.classList.add('is-loading');
            window.location.reload();
        });
    }

    var saving = false;

    /**
     * テーブル全体を news.json へ上書き保存する（非同期）
     * 「現在の状態で保存」ボタンとフローティング保存ボタンで共通利用。
     */
    function doSave() {
        if (saving) return;

        // 保存前にワンクッション確認
        if (!window.confirm('現在のテーブルの内容で news.json を上書き保存します。よろしいですか？')) {
            return;
        }

        saving = true;

        var records = collectRecords();

        if (submitBtn) {
            submitBtn.disabled = true;              // 二重送信防止
            submitBtn.classList.add('is-loading');
        }
        if (fabSave) fabSave.classList.add('is-loading');
        showMessage('', '');

        fetch('save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(records)
        })
            .then(function (res) {
                return res.json()
                    .catch(function () { return null; })
                    .then(function (body) { return { ok: res.ok, body: body }; });
            })
            .then(function (r) {
                if (r.ok && r.body && r.body.success) {
                    // 成功: 未編集状態に戻し、成功メッセージ
                    markSaved();
                    showMessage('success', r.body.message || '保存しました。');
                } else {
                    // 失敗: 再度保存できるよう編集状態を維持、失敗メッセージ
                    enableSubmit();
                    showMessage('error', (r.body && r.body.message) || '保存に失敗しました。');
                }
            })
            .catch(function () {
                enableSubmit();
                showMessage('error', '通信エラーにより保存に失敗しました。');
            })
            .finally(function () {
                saving = false;
                if (submitBtn) submitBtn.classList.remove('is-loading');
                if (fabSave) fabSave.classList.remove('is-loading');
            });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            if (submitBtn.disabled) return;
            doSave();
        });
    }

    // 編集時に表示されるフローティング保存ボタン
    if (fabSave) {
        fabSave.addEventListener('click', doSave);
    }

});
