#!/usr/bin/env python3
"""実行時依存だけを同梱した WordPress 用 ZIP を生成する。"""

import argparse
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
from zipfile import ZIP_DEFLATED, ZipFile

ROOT = Path(__file__).resolve().parent.parent
SLUG = "od-pagination-icons"


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--version", help="タグとプラグインバージョンの一致を検証する")
    args = parser.parse_args()
    source = ROOT / f"{SLUG}.php"
    version = re.search(r"^ \* Version: (.+)$", source.read_text(), re.MULTILINE).group(1)
    if args.version and (
        not re.fullmatch(r"\d+\.\d+\.\d+", args.version) or args.version != version
    ):
        parser.error(f"タグ {args.version} とプラグインバージョン {version} が一致しません。")

    output = ROOT / "dist" / f"{SLUG}.zip"
    output.parent.mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix=f"{SLUG}-") as temporary:
        plugin = Path(temporary) / SLUG
        plugin.mkdir()
        # 配布対象を明示し、ローカル設定や開発依存の混入を防ぐ。
        for name in (f"{SLUG}.php", "README.md", "LICENSE", "composer.json", "composer.lock"):
            shutil.copy2(ROOT / name, plugin / name)
        shutil.copytree(ROOT / "languages", plugin / "languages")
        subprocess.run(
            ["composer", "install", "--no-dev", "--prefer-dist", "--no-interaction",
             "--no-progress", "--no-scripts", "--optimize-autoloader",
             f"--working-dir={plugin}"],
            check=True,
        )
        (plugin / "composer.json").unlink()
        (plugin / "composer.lock").unlink()
        # 依存ライブラリに含まれる CI・テスト用ディレクトリも配布から除く。
        for path in sorted((plugin / "vendor").rglob("*"), reverse=True):
            if path.is_dir() and path.name in {".github", ".git", "tests", "Tests"}:
                shutil.rmtree(path)
        if not (plugin / "vendor/autoload.php").is_file():
            raise RuntimeError("Composer autoload.php がありません。")
        if (plugin / "vendor/wp-coding-standards").exists():
            raise RuntimeError("開発依存が混入しています。")
        with ZipFile(output, "w", ZIP_DEFLATED) as archive:
            for path in sorted(plugin.rglob("*")):
                if path.is_file():
                    archive.write(path, path.relative_to(plugin.parent))
        with ZipFile(output) as archive:
            if archive.testzip() is not None:
                raise RuntimeError("ZIP の整合性チェックに失敗しました。")
    print(f"配布 ZIP: {output} (バージョン {version})")


if __name__ == "__main__":
    main()
