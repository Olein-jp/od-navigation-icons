# OD Pagination Icons

OD Pagination Icons の開発用ひな形です。ページネーションの表示機能は、これから実装します。

## 要件

- プラグイン: WordPress 5.9 以上、PHP 7.4 以上
- 開発: Docker、Node.js 24（`.nvmrc`）、npm、PHP、Composer 2
- ZIP 作成: Python 3

## 開発を始める

```sh
nvm use
npm ci
composer install
npm run env:start
```

`wp-env` はこのディレクトリをプラグインとしてマウントします。起動時に表示される URL と認証情報で WordPress にログインしてください。必要なら `npm run env:cli -- plugin activate od-pagination-icons` で有効化します。

```sh
composer lint                 # WPCS
composer format               # 自分で実行した場合のみ自動修正
npm run env:status
npm run env:logs
npm run env:cli -- plugin list
npm run env:stop
```

Docker 内で Composer を使う場合:

```sh
npx wp-env run cli --env-cwd=wp-content/plugins/od-pagination-icons composer install
```

個人用のポート等は `.wp-env.override.json` に設定してください。このファイルは Git 管理対象外です。

`autoPort` を有効にしているため、ほかの開発環境とポートが重なる場合は空きポートを使用します。wp-env 12.1.0 は既定でテスト環境も起動し、非推奨の動作として警告します。構成には非推奨の `testsEnvironment` キーを追加していません。

開発依存の注意: 現在の `npm audit` は wp-env の推移依存に 11 件（high 1 件、moderate 10 件）を報告します。npm が提示する自動修正は wp-env のメジャーダウングレードを伴うため適用していません。npm 依存は配布 ZIP には同梱しません。

## 多言語化

テキストドメインは `od-pagination-icons` です。表示文字列には `__()`、`esc_html_e()` 等を使用し、翻訳は `languages/` に配置します。`init` で翻訳を登録します。

```sh
npm run i18n:pot
# POT の変更を各言語の PO に反映し、翻訳する。
npm run i18n:mo
```

日本語の `od-pagination-icons-ja.po` とコンパイル済み MO を同梱しています。他言語は `od-pagination-icons-<locale>.po` を追加して MO を生成してください。POT・PO・MO は更新後にコミットします。

## GitHub からの更新

[inc2734/wp-github-plugin-updater](https://github.com/inc2734/wp-github-plugin-updater) を使用します。公開リポジトリ `Olein-jp/od-pagination-icons` の最新の正式な GitHub Release を参照します。初回は Releases の `od-pagination-icons.zip` を WordPress にインストールしてください。それ以降は、新しいバージョンが公開されると WordPress の更新画面から更新できます。

GitHub が自動生成する Source code ZIP には Composer の依存が入らないため、必ず添付された `od-pagination-icons.zip` を使用してください。

## リリース

`0.0.0` のような `数字.数字.数字` 形式のタグを push すると、GitHub Actions が WPCS とバージョンの一致を確認し、配布 ZIP を Releases に添付します。`v` 接頭辞とプレリリースタグは対象外です。タグ名とメイン PHP の `Version` は一致させてください。`package.json` のバージョンも併せて更新し、`npm install --package-lock-only` で lockfile に反映します。

初回リリースの操作例（実行すると公開されます）:

```sh
git tag 0.0.0
git push origin 0.0.0
```

次回以降はバージョンを更新してコミット・push してから、そのバージョンのタグを作成します。Actions は標準の `GITHUB_TOKEN` を使用するため、追加のトークン登録は不要です。

ローカルで ZIP だけを作る場合:

```sh
npm run release:build -- --version 0.0.0
```

出力は `dist/od-pagination-icons.zip`。ZIP の先頭ディレクトリは `od-pagination-icons/` です。実行時 Composer 依存と翻訳を含み、開発ツール・ローカル設定を含めません。配布対象を追加する際は `scripts/build-release.py` の許可リスト、PHP ファイルを追加する際は `phpcs.xml.dist` の検査対象も更新してください。

## ライセンス

GPL-2.0-or-later。詳細は `LICENSE` を参照してください。
