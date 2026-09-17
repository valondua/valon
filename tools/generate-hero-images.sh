#!/bin/sh
# Rebuild the homepage portrait sources with libwebp's cwebp (tested with 1.6.0).
# Preserve the full photograph; the theme controls its displayed crop.
# Keep the source's Adobe RGB profile for correct colors; discard EXIF and XMP.
set -eu

command -v cwebp >/dev/null 2>&1 || {
    echo "Install libwebp to provide cwebp before running this script." >&2
    exit 1
}

asset_dir=$(CDPATH= cd -- "$(dirname -- "$0")/../assets" && pwd)
for width in 480 768 1024 1586; do
    cwebp -q 85 -m 6 -sharp_yuv -metadata icc -resize "$width" 0 \
        "$asset_dir/valon-hero.jpeg" \
        -o "$asset_dir/valon-hero-$width.webp"
done
