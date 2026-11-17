# 技術仕様

## データと登録

親 `core/query-pagination` のブロックコメントに `odpiEnabled`（boolean）、`odpiPreviousIcon` / `odpiNextIcon`（string）だけを追加する。SVGは保存しない。PHPの `register_block_type_args` とJSの `blocks.registerBlockType` で同じ定義を追加し、`od-pagination-icons/属性名` のコンテキストで子へ伝える。コアのsave関数は変更しない。

`paginationArrow` の保存値は常にコアの値。カスタム有効時だけPHPの `render_block_context` とJSの `editor.BlockEdit` でarrowを描画させる。親の編集コンポーネントの属性は変更せず、コアのshowLabel制約も維持する。標準矢印がnoneの場合は、コア設定をarrow/chevronに変更してからラベル表示をオフにする。これによりプラグイン停止後も空のリンクにならない。オフにすると元の標準矢印設定へ戻る。

## PHP描画

子のrenderフィルタで、識別子の形式・コレクション登録・利用可能ならpublic属性を検証し、`wp_get_icon()` からSVGを取得する。SVGのサニタイズは登録時のコア処理に任せる。投稿からSVGは受け取らない。

HTML APIでコアのアンカーと矢印span、その直下の矢印テキストと閉じspanを検証し、テキストだけを置換する。リンク属性・URL・ページ判定・Interactivityディレクティブを保持する。未選択・削除済み・非公開・不正識別子・想定外HTMLはコア出力を維持する。空リンクを新規生成しない。

## エディター

公開された再利用可能なIcon Pickerがないため、componentsのModal、SelectControl、SearchControl、Buttonで実装する。標準REST権限とapiFetchのnonce処理を利用する。コレクション単位の遅延取得、リクエスト共有、ローカル検索、48件のページ分割を行う。7.1のRESTはサーバーでのページ分割に対応しないため、1コレクション分は一括受信する。

プレビューはBlockListBlockのwrapperPropsからCSS変数としてSVG画像を渡す。単色SVGはmaskでcurrentColorを継承し、固定色を持つSVGは背景画像で元の色を表示する。コアのリンク・テキスト編集要素はそのまま使う。保存HTMLにはプレビュー属性を含めない。DOMの監視・直接変更・内部コンポーネントのimportはしない。

## 配布

フロントはPHPと共通CSSのみ。JSはエディター限定。ビルド成果物・PHP includes・翻訳・既存GitHub updaterを配布する。GitHub Actionsはlint・JSテスト・ビルドと実コアテストを行い、数値3桁のタグでインストール可能なZIPをReleaseに添付する。
