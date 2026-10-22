# Changelog

## 2.1.0

### Added
- Template backgrounds follow the cover: the artwork's background colour is moved to the cover's main colour (logo, red button,
  lettering and colours far from the background family stay as designed; a white, grey or black cover leaves the artwork as
  designed). On by default; `->matchCoverColour(false)` keeps the original colours. Costs about 0.06 s per banner.

## 2.0.1

### Fixed
- Titles with characters the font cannot draw (Devanagari in a Latin display font) printed empty boxes; those characters are now left out.
- A language book for kids ("English Grammar Book for 8+ Years' Old Kids") keeps its English / Hindi / Marathi template; only
  colouring, activity, rhymes, alphabet, nursery and drawing books take the EVS / GK one. The subject decides the language
  template, not "Marathi Medium" in the title.
- The placeholder "[SUBJECT BOOK]" label no longer shows beside a cover narrower than the template's book (combo shots), and the
  generic template's book face is a plain page.
- A tall cover no longer runs off the top of the banner.

## 2.0.0

### Changed (breaking)
- The 58 GD-drawn illustrated templates (and their props, portrait layout, theme groups per template and
  `TemplateLibrary::zone/artwork/band/byGroup/forGroup`) are removed. They are replaced by the subject templates: finished
  artwork per subject (`mathematics`, `science`, `english`, `hindi`, `marathi`, `social-science`, `physics`, `chemistry`,
  `biology`, `evs-gk`, `general`) in `resources/templates`. The product cover is drawn over the template's book, the product
  title and an optional tagline in its copy zone; logo, benefit icons and button are part of the artwork.
- Templates have their own proportions (about 2.2:1 to 3:1, not 4:1): use `TemplateLibrary::dimensions($name, $width)`.
  Other proportions throw.
- `->subject()` / `TemplateLibrary::pickFor()` now return one of these templates (`PCM` and friends rotate through their subjects).

### Added
- `tools/prepare_templates.py` to rebuild the plates and manifest from new artwork.

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
