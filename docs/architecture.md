# 技術仕様

## データと登録

親 `core/query-pagination` のブロックコメントに `odpiEnabled`（boolean）、`odpiPreviousIcon` / `odpiNextIcon`（string）、`odpiIconSize`（string）を追加する。SVGは保存しない。PHPの `register_block_type_args` とJSの `blocks.registerBlockType` で同じ定義を追加し、`od-navigation-icons/属性名` のコンテキストで子へ伝える。コアのsave関数は変更しない。

`paginationArrow` の保存値は常にコアの値。カスタム有効時だけPHPの `render_block_context` とJSの `editor.BlockEdit` でarrowを描画させる。親の編集コンポーネントの属性は変更せず、コアのshowLabel制約も維持する。標準矢印がnoneの場合は、コア設定をarrow/chevronに変更してからラベル表示をオフにする。これによりプラグイン停止後も空のリンクにならない。オフにすると元の標準矢印設定へ戻る。

## PHP描画

子のrenderフィルタで、識別子の形式・コレクション登録・利用可能ならpublic属性を検証し、`wp_get_icon()` からSVGを取得する。SVGのサニタイズは登録時のコア処理に任せる。投稿からSVGは受け取らない。

HTML APIでコアのアンカーと矢印span、その直下の矢印テキストと閉じspanを検証し、テキストだけを置換する。リンク属性・URL・ページ判定・Interactivityディレクティブを保持する。未選択・削除済み・非公開・不正識別子・想定外HTMLはコア出力を維持する。空リンクを新規生成しない。

## エディター

公開された再利用可能なIcon Pickerがないため、componentsのModal、TabPanel、SearchControl、Buttonで実装する。標準REST権限とapiFetchのnonce処理を利用する。コアに合わせて全画面モーダル、左側の検索・コレクションタブ、150px基準のグリッドと24pxアイコンを配置する。配色とフォーカス表示はWordPress標準コンポーネントを使用する。コレクション単位の遅延取得、リクエスト共有、ローカル検索、48件のページ分割を行う。7.1のRESTはサーバーでのページ分割に対応しないため、1コレクション分は一括受信する。

プレビューはBlockListBlockのwrapperPropsからCSS変数としてSVG画像を渡す。単色SVGはmaskでcurrentColorを継承し、固定色を持つSVGは背景画像で元の色を表示する。コアのリンク・テキスト編集要素はそのまま使う。保存HTMLにはプレビュー属性を含めない。DOMの監視・直接変更・内部コンポーネントのimportはしない。

## 配布

フロントはPHPと共通CSSのみ。JSはエディター限定。ビルド成果物・PHP includes・翻訳・既存GitHub updaterを配布する。GitHub Actionsはlint・JSテスト・ビルドと実コアテストを行い、数値3桁のタグでインストール可能なZIPをReleaseに添付する。

## 投稿ナビゲーションとサイズ

`core/post-navigation-link` に `odpiEnabled`、`odpiIcon`、`odpiIconSize` を追加する。前後はコアの `type` で判定し、各ブロックが設定を保持する。カスタム有効時だけ `render_block_data` とエディターの編集コンポーネントへ渡す属性で `arrow` を上書きし、保存済みの矢印設定は保つ。PHPではコア出力の矢印span内の文字だけを置換する。リンク先、ラベル、投稿タイトル、分類による絞り込み、隣接投稿がない場合の挙動はコアに任せる。

サイズは正の数値と px・em・rem の組み合わせだけを受け付ける。空欄・無効値は従来の `1em`。ページ送りは親コンテキストで前後へ共有し、投稿ナビゲーションは個別設定とする。公開画面ではSVG、編集画面ではプレビューのCSS変数 `--odpi-icon-size` へ反映する。コアの標準矢印と文字のサイズは変更しない。

投稿ナビゲーションの `odpiIconOnly`（boolean、初期値false）が有効な場合は、検証済みの矢印spanをコアのアンカー内へ移動する。アンカーのURLと属性を保持し、元のリンク内容は `odpi-post-link-text` のspanで視覚的に隠して読み上げ用に残す。未取得アイコンは標準矢印をリンク内で表示する。無効時の出力は従来どおり。エディターはwrapperのクラスでコアのラベル・タイトルを視覚的に隠し、DOMは変更しない。属性と元のラベルは保存時に維持される。
