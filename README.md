Laravel GD Ads
==============

Generate polished ad banners from product images with PHP and GD: book covers, boxes, bottles, courses, anything
with a packshot. Pick a size, give it images and copy, get a ready-to-publish PNG/JPG/WebP.

| Landscape, front + back cover | Square |
| --- | --- |
| ![landscape](docs/examples/landscape-front-back.png) | ![square](docs/examples/square.png) |

![billboard](docs/examples/billboard.png)
![leaderboard](docs/examples/leaderboard.png)

The images above come from `php examples/generate.php`.

## What you get

- **Fluent builder**: one readable chain per ad.
- **Ad-size presets** (`AdSize`): 300x250, 728x90, 970x250, 300x600, 160x600, 320x50/100, 1080x1080, 1080x1350, 1080x1920, 1200x628 and more. Render one creative to many sizes with `renderSizes()`.
- **Layouts**: `books` (packshots with the background removed, front + tilted back cover), `split`, `stacked`, `hero` (full-bleed photo with a scrim), `showcase` (ultra-wide strips). `auto` picks one from the size and the image. Register your own with `AdBuilder::registerLayout()`.
- **Colour from the image**: the dominant brand hue becomes the background; headline and button colours are picked for readable contrast (WCAG AA).
- **Packshot cut-out**: a flat white studio background is removed, white areas inside the product are kept, and the baked-in drop shadow turns into a real soft shadow.
- **Auto-fitting text**: headline, sub-headline and tick-list shrink and wrap to fit any size; text that cannot fit is ellipsised.
- **Elements**: CTA button, round badge (`-30%`, `NEW`), logo, tick-list of selling points.
- **Backgrounds**: `auto`/`mesh`, `gradient`, `solid`, `blur`, plus the original `wave1`, `stripes`, `radial`, `spotlight`, `burst`, `medical`, ... and your own via `Backgrounds::register()`.
- **Output**: `toPng()`, `toJpg()`, `toWebp()`, `toDataUri()`, `save($path)`, `saveTo($storagePath, $disk)`.

## Requirements

- PHP 8.1+ with `ext-gd` (FreeType) and `ext-mbstring`
- Optional: `illuminate/support` (Laravel) for `saveTo()` and config

## Install

```bash
composer require packitifotech/laragdads
php artisan vendor:publish --tag=laragdads-config   # optional
```

## Quick start

```php
use Packitifotech\Laragdads\Laragdads;
use Packitifotech\Laragdads\Ads\AdSize;

Laragdads::ad()
    ->size(AdSize::LANDSCAPE)                              // or ->size(1200, 628)
    ->images(public_path('chemistry-front.png'), public_path('chemistry-back.png'))
    ->headline('MHT-CET Triumph Chemistry')
    ->subheadline('Chapter-wise notes and MCQs for Engineering & Pharmacy entrance')
    ->features(['6400+ chapter-wise MCQs', "Previous years' questions (PYQs)", 'Model question papers'])
    ->cta('Buy now')
    ->badge('6400+ MCQs')
    ->saveTo('ads/mht-cet-chemistry.png');                 // Laravel disk; or ->save('/abs/path.png')
```

One creative, every placement:

```php
$files = Laragdads::ad()
    ->images($cover)->headline('MHT-CET Triumph Chemistry')->cta('Buy now')
    ->renderSizes([AdSize::SQUARE, AdSize::STORY, AdSize::LEADERBOARD, AdSize::MEDIUM_RECTANGLE]);

foreach ($files as $label => $binary) {                    // "1080x1080" => PNG bytes
    Storage::put("ads/{$label}.png", $binary);
}
```

### Builder reference

| Method | Purpose |
| --- | --- |
| `size(AdSize\|int $w, ?int $h)` | canvas size |
| `images(...$paths)` | product images (paths or `GdImage`); layouts use the first two |
| `logo($path)` | brand logo |
| `headline()`, `subheadline()`, `cta()`, `badge()` | copy |
| `features(array)` | tick list, up to 5 items, shown when the format has room |
| `font($path, $bodyFont = null)` | TrueType/OpenType font (DejaVu Sans Bold is bundled) |
| `layout($name)` | `auto`, `books`, `split`, `stacked`, `hero`, `showcase` or a registered one |
| `background($style)` | see `Backgrounds::names()` |
| `theme($hex \| [r,g,b] \| $imagePath \| Theme)` | brand colour or the image to sample colours from |
| `imageSide('left'\|'right')`, `imageFit('cover'\|'contain')`, `cutout(bool)`, `shadows(bool)` | image placement |
| `format('png'\|'jpg'\|'webp', $quality)` | encoding used by `toBinary()`/`save()` |

### Fonts and languages

Pass any TTF/OTF that has the glyphs you need, for example Noto Sans Devanagari for Hindi/Marathi copy:
`->font(resource_path('fonts/NotoSansDevanagari-Bold.ttf'))`, or set `default_font` in `config/laragdads.php`.

### Custom layout and background

```php
Backgrounds::register('brand', fn (Canvas $c, AdSpec $spec, Theme $t) => $c->gradient($t->backgroundFrom, [10, 10, 30], 'vertical'));
AdBuilder::registerLayout('mine', new MyLayout());   // implements Layouts\Layout
```

## Legacy API

The original static methods (`createHorizontalAdsBanner`, `createVerticalAdsBanner`, `createBlockAdsBanner`, `saveAdsBannerPng`, `saveAdsBannerJpg`) still work. `saveAdsBannerPng/Jpg` no longer send HTTP headers, and JPG quality is now 90 instead of 9.

## Tests

```bash
composer install && composer test
```

## Credits

Author: Tushar Sawant. Colour extraction by [color-thief-php](https://github.com/ksubileau/color-thief-php), text by [gd-text](https://github.com/stil/gd-text). Bundled font: DejaVu Sans (free licence, see https://dejavu-fonts.github.io/License.html).
