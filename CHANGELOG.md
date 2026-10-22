# Changelog

## 1.1.0

### Added
- Children's templates and a `kids` subject group: `rainbow-meadow`, `balloon-party`, `alphabet-blocks`, `starry-night` and
  `butterfly-garden` (58 templates now), chosen for colouring, activity, nursery, rhymes, stories and alphabet books.
- `->subject($label, $variant, $title)` / `TemplateLibrary::pickFor($label, $seed, $title)`: the product title refines the
  choice. A children's title wins over the subject filter, and an unrecognised subject falls back to keywords in the title.

### Fixed
- Banners that are not very wide (about 4:1, e.g. 1200x300) were cramped: the illustrations and their reserved zones now
  shrink together until the headline has room, instead of squeezing the text into a narrow column.
- The physics-only `books-emc2` (atom) template is no longer in the `general` pool, so books without a recognised subject do
  not get a physics illustration.

## 1.0.1

### Fixed
- "Allowed memory size exhausted" in `Cutout` when generating many ads from large cover photos: the cut-out keeps one byte per
  pixel instead of a PHP array entry (about 16 bytes), only queues background pixels that border something else, and decoded
  images are limited to 2000 px on the longest side with a smaller cache. Output is pixel-identical; peak memory for a cover
  cut-out fell from about 10 MB to 1.7 MB.

## 1.0.0

First tagged release.

### Added
- Fluent `AdBuilder` (`Laragdads::ad()`): size, images, logo, headline, sub-headline, call-to-action, badge, features, fonts,
  theme, layout, formats and multi-size rendering.
- Layouts: `stacked` (plain, product on top and text below), `showcase`, `hero` and `companion`.
- Theme extraction from the book cover (palette, WCAG contrast) and smart backgrounds.
- 53 illustrated banner templates in 17 subject groups, chosen with `->template($name)` or `->subject($label)`; subject labels
  are normalised (typos, short forms such as `Eng/Mar/His`, `PCM`).
- Template banners for strips (3.6:1 and wider, e.g. 1200x200, 2000x256) and a 336x453 portrait layout.
- Fixed "Buy now" button position and a fixed, large product box on every template; illustrations are drawn small and kept at
  the edges so the book stays the focus; each template's free text area is measured from its own drawing and covered by tests.
- Optional AI Horde backgrounds (API key from the environment only).

### Changed
- Removed the promo, book-plus-text landscape and square top/bottom styles in favour of one plain layout.
