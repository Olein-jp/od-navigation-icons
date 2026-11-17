# OD Pagination Icons

クエリーループの「ページ送り」ブロックを拡張し、前後のリンクに WordPress Icons API の登録済みアイコンを設定するプラグインです。コア・テーマ・プラグインのコレクションを利用でき、URL生成・ページ判定・Enhanced Paginationはコアが処理します。

## 要件とインストール

- WordPress 7.1 以上、PHP 8.1 以上
- 開発: Docker、Node.js 24.18 以上（`.nvmrc`）、npm、PHP、Composer 2、Python 3

配布用の `dist/od-pagination-icons.zip` または GitHub Releases に添付されたZIPを、WordPressの「プラグインを追加」からアップロードし、有効化します。GitHubが自動生成するSource code ZIPには実行時依存が含まれません。

## 使い方

1. 投稿・固定ページまたはサイトエディターで、クエリーループ内の親「ページ送り」ブロックを選択します。
2. 設定サイドバーの「カスタムアイコンを使用」を有効にします。
3. 「前のページ」「次のページ」それぞれで「アイコンを選択」を開き、コレクションと検索で選択します。
4. コアの「ラベルのテキストを表示」で、アイコンと文字・アイコンのみを切り替え、保存します。標準矢印が「なし」の場合は、先に「矢印」または「シェブロン」を選択するとラベル表示をオフにできます。

未選択・未登録アイコンは標準矢印になります。外部アイコンの提供元を停止しても識別子を保持し、再登録後に再表示します。カスタム設定を無効にすると以前の標準矢印設定へ戻り、本プラグインを停止してもコアのページ送りリンクは機能します。

既存のコア矢印コントロールは残ります。カスタムモード中はカスタムアイコンの表示が優先され、コアの矢印コントロールによる変更はモード解除後に反映されます。

## 開発と検証

```sh
nvm use
npm ci
composer install
npm run build
npm run env:start
npm run env:cli -- plugin activate od-pagination-icons
composer lint
npm run check
npm run test:php
```

`wp-env` が起動時に表示するURLを使用してください。自動ポート割当が有効で、今回の検証環境は `http://localhost:8890` でした。

ブラウザテストは専用ページ・テンプレート・投稿と1200件の外部アイコンを一時作成します。ローカル開発環境で実行し、最後に必ずcleanupを実行してください。テストは対象プラグインの停止・再有効化も行います。

```sh
npm run test:e2e:setup
# ローカルChromeを使用。CIではODPI_BROWSER=chromiumを指定。
WP_BASE_URL=http://localhost:8890 npm run test:e2e
npm run test:e2e:cleanup
```

PHPテストは実コアを使うWP-CLI統合テストです。作成した投稿はfinallyで削除します。ブラウザテストはPlaywrightで選択・保存・プレビュー・ページ遷移・外部登録解除・プラグイン停止を確認します。

## 翻訳

日本語PO/MOとエディター用JSONを同梱します。すべての独自UIは `od-pagination-icons` ドメインです。

```sh
npm run i18n:pot
# POを更新・翻訳後
npm run i18n:mo
npm run env:cli -- i18n make-json wp-content/plugins/od-pagination-icons/languages --no-purge
```

## ビルドとリリース

```sh
npm run build
npm run release:build -- --version 0.1.0
```

出力は `dist/od-pagination-icons.zip`。PHP、ビルド成果物、翻訳、既存の `inc2734/wp-github-plugin-updater` を含み、開発ツール・テスト・ローカル設定は含めません。

数値3桁のバージョンタグ（例: `0.1.0`）をpushすると、GitHub Actionsが品質確認・ビルドを行い、インストール可能なZIPをGitHub Releasesへ添付します。タグとPHPヘッダー・package.json・readme.txtのバージョンを合わせてください。`v`接頭辞やプレリリースタグは対象外です。公開結果はGitHub ReleasesとActionsで確認できます。

## 技術情報と制約

- [技術仕様](docs/architecture.md)
- [Phase 1と検証結果](docs/validation.md)
- [互換性と受け入れテスト](docs/compatibility.md)

WordPress 7.1のRESTはサーバーでのページ分割に対応していません。コレクション単位で取得し、表示を48件ずつに制限します。エディター内のプレビューは公開API経由のCSS描画で、フロントエンドは `wp_get_icon()` のSVGです。CSPでdata画像を禁止している環境ではエディタープレビューを別途確認してください。

コメントのページ送り・投稿ナビゲーション・SVGアップロード・アイコン登録管理・専用サイズ/色設定は対象外です。npm依存は開発時に使用し、配布ZIPへは含めません。依存監査の結果は検証文書に記録しています。

GPL-2.0-or-later。詳細は `LICENSE` を参照してください。
