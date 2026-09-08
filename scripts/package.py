#!/usr/bin/env python3
"""Build the standalone OJS theme archive from explicitly allowed files."""

from hashlib import sha256
from pathlib import Path
import tarfile
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
version = ET.parse(root / "version.xml").findtext("release")
if not version or not all(part.isdecimal() for part in version.split(".")):
    raise SystemExit("Invalid plugin release version")

files = [root / name for name in (
    "index.php", "EncountersThemePlugin.php", "version.xml", "LICENSE", "README.md"
)]
for directory, suffixes in {
    "templates": {".tpl"}, "styles": {".less"},
    "js": {".js"}, "locale": {".po"},
    "images": {".svg"}, "fonts": {".woff2", ".txt"},
}.items():
    files.extend(p for p in (root / directory).rglob("*") if p.suffix in suffixes)

for path in files:
    if path.is_symlink() or not path.is_file() or not path.resolve().is_relative_to(root):
        raise SystemExit(f"Refusing to package unexpected file: {path}")

destination = root / "dist"
destination.mkdir(exist_ok=True)
archive = destination / f"encounters-{version}.tar.gz"
with tarfile.open(archive, "w:gz") as bundle:
    for path in sorted(files):
        bundle.add(path, arcname=str(Path("encounters") / path.relative_to(root)), recursive=False)
digest = sha256(archive.read_bytes()).hexdigest()
checksum = archive.with_suffix(archive.suffix + ".sha256")
checksum.write_text(f"{digest}  {archive.name}\n")
print(archive)
print(f"SHA-256: {digest}")
