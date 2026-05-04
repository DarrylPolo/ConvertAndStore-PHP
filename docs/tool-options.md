# Conversion Option Notes

The Convert and Store API supports many tool-specific request fields. The PHP SDK passes these through as plain arrays so new tools can be supported without waiting on a package update.

## Common options

- `store_original` as `true` or `false`
- `output_format` such as `png`, `jpg`, `webp`, `mp3`, `wav`, `gif`
- `quality` such as `86`
- `archive_name` for archive builders
- `pdf_password` for `unlock-pdf`

## Image editor and image tools

- `width`
- `height`
- `fit`
- `crop_enabled`
- `crop_x`
- `crop_y`
- `crop_width`
- `crop_height`
- `rotate_angle`
- `flip_direction`
- `grayscale`
- `watermark_text`

## Video and audio tools

- `quality_preset`
- `audio_bitrate`
- `gif_width`
- `gif_fps`
- `time_offset`
- `thumbnail_width`

## PDF tools

- `page_range`
- `page_order`
- `dpi`
- `image_format`
- `pdf_password`

## Multi-upload tools

Use `convertMany()` for:

- `zip-create`
- `tar-create`
- `tar-gz-create`
- `seven-z-create`
- `rar-create`
- `merge-pdf`

For single-upload tools, use `convert()`.
