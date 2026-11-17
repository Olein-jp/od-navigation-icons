# 技術検証

検証日: 2026年11月17日。実環境: 既存wp-env、WordPress 7.1.4、PHP 8.2。

## Phase 1

- コアの `query-pagination/block.json` と PHP、`wp-includes/js/dist/block-library.js` を確認。親は `paginationArrow` と `showLabel` を提供し、子が受け取る。
- `register_block_type_args` で追加した3属性と `provides_context` を `wp eval` で取得できた。登録順はプラグイン読込時にフィルタ登録 → initでコア登録。
- `wp_get_icon('core/plus')` と `WP_HTML_Tag_Processor` の実行で、href・data-wp属性を保持した矢印テキストのSVG置換に成功した。
- コアIconブロックの `CustomInserterModal` / `IconGrid` はblock-library内部にあり、block-editor/componentsの公開エクスポートにIcon Pickerは存在しない。内部ファイルには依存せず公開標準コンポーネントで実装する。
- RESTのアイコンリストはcollectionで絞り込めるが、7.1.4ではper_page/pageによる分割を実装していない。コレクション単位で遅延取得・成功結果をキャッシュし、検索と48件ごとのDOM分割をクライアントで行う。
- コア編集コンポーネントにSVG差込用の公開slotはない。`editor.BlockEdit`で描画時のみarrowコンテキスト、`editor.BlockListBlock`の公開wrapperPropsとCSSでプレビューする。コアのテキスト編集・選択・ツールバー・保存処理は維持し、DOM監視/直接変更は行わない。
- HTML APIに任意の内部HTML挿入APIはない。標準矢印span直下の単一テキストトークンと閉じspanを検証し、一意な仮テキストをSVGへ置換する。構造が異なる場合は元HTMLを返す。アンカーの属性は再構築しない。

致命的な制約はなく、上記の代替方式でMVP実装へ進んだ。

## MVPの検証結果

- PHP統合テスト: 20項目。コア・外部アイコン、親子コンテキスト、無効化、登録解除、複数クエリー、親クエリー継承、アクセシブルネーム、リンク属性のバイト単位保持、想定外HTMLの保護を確認。
- JSテスト: 3項目。識別子検証、5000件の名前/ラベル検索、SVGのviewBox正規化を確認。コアのサニタイズで小文字になるviewboxを、CSS画像用のXMLへ正規化する。
- ブラウザE2E: 6シナリオ。固定ページでの選択/キーボード/保存/再編集/プレビュー、Enhanced Paginationの2→3ページ遷移、外部アイコン停止/復帰、本プラグイン停止、サイトエディターのテンプレート保存/再編集、アイコンのみのaria-labelと余白、日本語/英語UIを確認。
- PHP構文: 本体・includes・テストの8ファイル。WPCS、Composer validate --strict、JS/CSS lint、@wordpress/scriptsのproductionビルドに合格。
- 配布ZIP: ビルド成果物、includes、翻訳、既存updaterの実行時Composer依存を同梱。開発依存・テスト・ローカル設定は除外。
- npm導入時の監査: 39件（moderate 32 / high 7）。@wordpress/scriptsやwp-envなどの開発環境に含まれる推移依存を含む。npmパッケージは配布ZIPに同梱せず、WordPressの提供するJSパッケージを参照する。破壊的な強制更新は適用していない。

初回E2Eでは、テスト用コレクションに後続版のpublic引数を渡したことで7.1.4が登録を拒否した。fixtureを7.1の公開APIに合わせて修正し、再実行で確認した。PHPの親クエリーテストは、実コアが参照するグローバルpagedを含めて検証する。

検証範囲と未実施事項の詳細は [互換性記録](compatibility.md) を参照。

## アイコン選択モーダルの外観調整

WordPress 7.1.4のIconブロックと同一ブラウザで比較した。公開Modalの全画面表示とTabPanelを使い、左側に検索・コレクション一覧、右側に150px基準のグリッドと24pxのアイコンを配置する。未選択アイコンの枠線を除き、文字サイズと余白をコアに合わせた。

- 1280×900の画面で、コア・本プラグインともモーダル位置は40×40、外寸1200×820。
- 選択背景色は両方とも `rgb(56, 88, 233)`。インスペクターから継承する中立色をモーダル内で管理画面アクセント色に合わせる。ホバー・押下にも管理画面の色変数を使用する。
- 390×844の画面でモーダル幅・スクロール幅とも390px。検索・コレクション一覧を上部、グリッドを下部へ配置し、横はみ出しを防ぐ。
- 大量アイコンに備える48件単位のページ分割・コレクション単位の取得は維持する。選択済みアイコンを含むページから開く。
- JS/CSS lint、JSテスト3項目、productionビルド、既存ブラウザE2E 6シナリオに合格。コレクション選択のE2Eをタブに対応させ、保存済みのサイドバー開閉状態に依存しないよう調整した。

コアの内部コンポーネントや内部CSSへの依存は追加していない。

## 一次情報

- [Icons APIの公式解説](https://developer.wordpress.org/news/2026/08/hands-on-with-the-wordpress-7-1-icon-registration-api/)
- [wp_get_icon](https://developer.wordpress.org/reference/functions/wp_get_icon/)
- [REST Iconsコントローラー](https://github.com/WordPress/wordpress-develop/blob/trunk/src/wp-includes/rest-api/endpoints/class-wp-rest-icons-controller.php)
- [Paginationのコア実装](https://github.com/WordPress/gutenberg/tree/trunk/packages/block-library/src/query-pagination)
- 具体的な互換性判断は上記trunkだけでなく、ローカルに導入済みの7.1.4の実装を基準とする。
