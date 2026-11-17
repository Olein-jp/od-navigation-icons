=== OD Pagination Icons ===
Contributors: olein
Tags: pagination, icons, query-loop, block-editor
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

クエリーループのページ送りに、WordPress Icons API の登録済みアイコンを設定します。

== Description ==

「ページ送り」ブロックの設定でカスタムアイコンを有効にし、前後それぞれのアイコンを選択できます。
コア・テーマ・プラグインが公式 API で登録したコレクションを利用します。
リンク生成とページ判定は WordPress コアの処理を維持します。

== Installation ==

1. 配布 ZIP を「プラグインを追加」からアップロードして有効化します。
2. クエリーループ内の「ページ送り」ブロックを選択します。
3. 「カスタムアイコンを使用」を有効にし、前後のアイコンを選択して保存します。

== Frequently Asked Questions ==

= 外部アイコンの提供プラグインを停止するとどうなりますか ? =

標準矢印に戻ります。保存済みの識別子は保持し、再登録後に再表示します。

= このプラグインを停止するとページ送りは使えますか ? =

コアのリンク機能と元の標準矢印設定を使用できます。

== Changelog ==

= 0.1.0 =
* MVP: 前後個別のアイコン選択、検索、プレビュー、SVG描画、フォールバック。
