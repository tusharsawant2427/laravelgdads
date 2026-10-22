#!/usr/bin/env python3
"""
Build the banner template plates in resources/templates/ from the designers' artwork.

    python3 tools/prepare_templates.py SHEET.webp MASTER.webp

SHEET is the 10-up contact sheet (Mathematics ... EVS / GK), MASTER the single generic template. For every template this

  * cuts it out of the sheet and trims the white gutter,
  * removes the "N. SUBJECT TEMPLATE" label,
  * wipes the headline + tagline placeholder (the "copy zone") and fills it from the surrounding background,
  * samples the headline and tagline ink colours,
  * upscales the plate and saves it as a JPEG,

and writes resources/templates/manifest.php with every position as a fraction of the plate, so the same file works at
any resolution: drop in higher-resolution originals, adjust CELLS below if the cut-out boxes moved, and run it again.
The product cover is not baked in: it is drawn over the BOOK rectangle at render time, so the old placeholder book is
simply covered.

Needs Pillow only. Rectangles are (x0, y0, x1, y1) in pixels of the cell as it sits in the sheet.
"""
import json
import os
import sys

from PIL import Image, ImageDraw, ImageFilter

OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'resources', 'templates')
SCALE = 2.0            # sheet cells are small (about 500 px wide); plates are stored at 2x
HALO = (5, 8, 2)       # how far the patch grows left/right/top, and where its side/top edges are sampled from
# The placeholder book carries a "[SUBJECT BOOK]" label. A product cover that is narrower than the book (a combo shot, say)
# would leave part of it showing, so it is wiped from the plate: (left, right, top offset, bottom offset) inside the book.
LABEL = (10, 6, 24, 60)
LABEL_TALL = {'social-science': 74}   # its label runs to two lines
BOOK_PAD = (2, 1)      # grow the book rectangle by this much (x, y) so no placeholder edge peeks out

# key: cell in the sheet, label tab, copy zone, placeholder book
CELLS = {
    'mathematics':    dict(cell=(0, 0, 495, 224),     tab=(0, 0, 238, 32),   zone=(38, 76, 272, 133),  book=(319, 31, 441, 209)),
    'science':        dict(cell=(503, 0, 997, 224),   tab=(0, 0, 192, 32),   zone=(36, 76, 262, 133),  book=(318, 31, 440, 209)),
    'english':        dict(cell=(1005, 0, 1497, 224), tab=(0, 0, 190, 32),   zone=(36, 76, 262, 133),  book=(312, 31, 436, 209)),
    'hindi':          dict(cell=(1505, 0, 2000, 224), tab=(0, 0, 176, 32),   zone=(36, 76, 262, 133),  book=(318, 31, 441, 209)),
    'marathi':        dict(cell=(0, 229, 495, 445),   tab=(0, 0, 208, 33),   zone=(38, 77, 250, 130),  book=(319, 32, 441, 208)),
    'social-science': dict(cell=(503, 229, 997, 445), tab=(0, 0, 250, 33),   zone=(36, 77, 262, 130),  book=(317, 32, 438, 208)),
    'physics':        dict(cell=(1005, 229, 1497, 445), tab=(16, 3, 172, 27), zone=(36, 77, 250, 130), book=(313, 32, 435, 210)),
    'chemistry':      dict(cell=(1505, 229, 2000, 445), tab=(0, 0, 210, 33), zone=(36, 77, 250, 130), book=(318, 32, 440, 209)),
    'biology':        dict(cell=(383, 450, 996, 667), tab=(0, 0, 216, 32),   zone=(48, 72, 300, 127),  book=(400, 30, 523, 210)),
    'evs-gk':         dict(cell=(1004, 450, 1617, 667), tab=(0, 0, 298, 32), zone=(46, 72, 358, 127),  book=(410, 28, 531, 210)),
}
# The single generic template (no label to remove); here the zone also covers the class/board pill and the tagline underline.
MASTER = dict(key='general', zone=(180, 138, 935, 352), book=(1179, 52, 1634, 603), width=1400,
              # its book cover is full of placeholders (board logo, edition, [SUBJECT NAME], [PART]): turn the face into a plain page
              face=(1200, 56, 1630, 586))


def near_white(p):
    return p[0] >= 246 and p[1] >= 246 and p[2] >= 246


def trim_gutter(im):
    """Cut the white margin the sheet leaves around a cell; returns (image, left, top)."""
    px = im.load()
    w, h = im.size
    left = top = 0
    right, bottom = w, h
    for _ in range(12):
        if sum(near_white(px[left, y]) for y in range(top, bottom)) > 0.85 * (bottom - top):
            left += 1
    for _ in range(12):
        if sum(near_white(px[right - 1, y]) for y in range(top, bottom)) > 0.85 * (bottom - top):
            right -= 1
    for _ in range(12):
        if sum(near_white(px[x, top]) for x in range(left, right)) > 0.85 * (right - left):
            top += 1
    for _ in range(12):
        if sum(near_white(px[x, bottom - 1]) for x in range(left, right)) > 0.85 * (right - left):
            bottom -= 1
    left, top, right, bottom = left + 1, top + 1, right - 1, bottom - 1   # the edge pixel is blended with the gutter
    return im.crop((left, top, right, bottom)), left, top


def lerp(a, b, t):
    return tuple(a[i] + (b[i] - a[i]) * t for i in range(3))


def fill_patch(im, x0, y0, x1, y1, halo=None):
    """Replace a rectangle by a Coons patch of its four surrounding edges (smooth gradient, no text).

    The lettering sits in a soft halo, so the left, right and top edges are sampled a few pixels outside it; the bottom
    edge cannot move far because the benefit icons start right below the zone.
    """
    HALO_ = halo or HALO
    w, h = im.size
    x0, y0, x1, y1 = x0 - HALO_[0], y0 - HALO_[2], x1 + HALO_[0], y1
    x0, y0 = max(HALO_[0] + 2, x0), max(HALO_[2] + 2, y0)
    x1, y1 = min(w - HALO_[0] - 3, x1), min(h - 3, y1)
    px = im.load()
    avg = lambda pts: tuple(sum(p[i] for p in pts) / len(pts) for i in range(3))
    top = {x: avg([px[x, y0 - HALO_[2]], px[x, y0 - HALO_[2] + 1]]) for x in range(x0, x1 + 1)}
    bot = {x: avg([px[x, y1 + 1], px[x, y1 + 2]]) for x in range(x0, x1 + 1)}
    lef = {y: avg([px[x0 - HALO_[1], y], px[x0 - HALO_[1] + 1, y]]) for y in range(y0, y1 + 1)}
    rig = {y: avg([px[x1 + HALO_[1], y], px[x1 + HALO_[1] - 1, y]]) for y in range(y0, y1 + 1)}
    tl, tr, bl, br = lef[y0], rig[y0], lef[y1], rig[y1]
    out = {}
    for y in range(y0, y1 + 1):
        t = (y - y0) / max(1, y1 - y0)
        for x in range(x0, x1 + 1):
            s = (x - x0) / max(1, x1 - x0)
            v = []
            for i in range(3):
                c = ((1 - t) * top[x][i] + t * bot[x][i] + (1 - s) * lef[y][i] + s * rig[y][i]
                     - ((1 - s) * (1 - t) * tl[i] + s * (1 - t) * tr[i] + (1 - s) * t * bl[i] + s * t * br[i]))
                v.append(max(0, min(255, int(round(c)))))
            out[(x, y)] = tuple(v)
    for (x, y), c in out.items():
        px[x, y] = c
    # soften the patch so it matches the slightly blurry artwork, and blend its borders
    box = (x0 - 3, y0 - 3, x1 + 4, y1 + 4)
    region = im.crop(box).filter(ImageFilter.GaussianBlur(1.6))
    mask = Image.new('L', region.size, 0)
    mp = mask.load()
    for y in range(region.size[1]):
        for x in range(region.size[0]):
            d = min(x, y, region.size[0] - 1 - x, region.size[1] - 1 - y)
            mp[x, y] = 255 if d >= 6 else int(255 * d / 6)
    im.paste(region, box[:2], mask)


def clear_tab(im, x0, y0, x1, y1):
    """Remove the label: repeat the clean row just below it, smoothed sideways (the top edge is plain background)."""
    px = im.load()
    w, h = im.size
    y_ref = min(h - 1, y1 + 3)
    x1 = min(w - 1, x1 + 6)
    row = [px[x, y_ref] for x in range(w)]
    sm = []
    for x in range(w):
        lo, hi = max(0, x - 5), min(w, x + 6)
        sm.append(tuple(sum(row[i][c] for i in range(lo, hi)) / (hi - lo) for c in range(3)))
    for x in range(0, x1 + 1):
        for y in range(0, y1 + 3):
            # fade from the smoothed row into the untouched image over the last 3 rows
            t = 1.0 if y < y1 else max(0.0, 1 - (y - y1) / 3)
            old = px[x, y]
            edge = 1.0 if x < x1 - 4 else max(0.0, (x1 - x) / 4)
            k = t * edge
            px[x, y] = tuple(int(round(old[c] * (1 - k) + sm[x][c] * k)) for c in range(3))


def ink(im, box, zone):
    """Colour of the lettering inside box: the pixels farthest from the background just outside the zone."""
    w, h = im.size
    px = im.load()
    ring = [px[x, y] for x in range(zone[0], zone[2], 3) for y in (zone[1] - 3, zone[3] + 3)]
    ring += [px[x, y] for y in range(zone[1], zone[3], 3) for x in (zone[0] - 3, zone[2] + 3)]
    med = tuple(sorted(p[i] for p in ring)[len(ring) // 2] for i in range(3))
    pts = list(im.crop(box).getdata())
    pts.sort(key=lambda p: -sum(abs(p[i] - med[i]) for i in range(3)))
    top = pts[: max(8, len(pts) // 12)]
    return tuple(sorted(p[i] for p in top)[len(top) // 2] for i in range(3))


def background_colour(im):
    """Hue (degrees) and saturation of the artwork's background family: the commonest colourful hue, not counting the reds of the logo and button."""
    hsv = im.convert('HSV').resize((im.size[0] // 2, im.size[1] // 2))
    bins = {}
    for h8, s8, v8 in hsv.getdata():
        h, sat, v = h8 * 360 / 255, s8 / 255, v8 / 255
        if 0.12 <= sat <= 0.85 and v > 0.3 and not (h >= 340 or h <= 12):
            bins.setdefault(int(h // 10), []).append((h, sat))
    best = max(bins, key=lambda b: len(bins[b]) + len(bins.get(b - 1, [])) + len(bins.get((b + 1) % 36, [])))
    near = bins[best] + bins.get(best - 1, []) + bins.get((best + 1) % 36, [])
    hue = sorted(h for h, _ in near)[len(near) // 2]
    sat = sorted(x for _, x in near)[len(near) // 2]
    return round(hue, 1), round(sat, 3)


def frac(rect, w, h):
    return [round(rect[0] / w, 5), round(rect[1] / h, 5), round(rect[2] / w, 5), round(rect[3] / h, 5)]


def blank_face(im, rect):
    """Paint a book face as a plain, softly graded page."""
    draw = ImageDraw.Draw(im)
    x0, y0, x1, y1 = rect
    for y in range(y0, y1 + 1):
        t = (y - y0) / max(1, y1 - y0)
        v = tuple(int(round(a + (b - a) * t)) for a, b in zip((243, 248, 255), (253, 253, 255)))
        draw.line([(x0, y), (x1, y)], fill=v)


def finish(im, key, zone, book, tab, width=None, label_bottom=LABEL[3], face=None):
    """im is at source resolution; rectangles are in its pixels. Returns the manifest entry."""
    w, h = im.size
    title_ink = ink(im, (zone[0], zone[1], zone[2] - 40, zone[1] + (zone[3] - zone[1]) * 5 // 9), zone)
    tag_ink = ink(im, (zone[0], zone[1] + (zone[3] - zone[1]) * 6 // 9, zone[2] - 40, zone[3]), zone)
    if tab:
        clear_tab(im, *tab)
    fill_patch(im, *zone)
    if face:
        blank_face(im, face)
    if label_bottom:
        fill_patch(im, book[0] + LABEL[0], book[1] + LABEL[2], book[2] - LABEL[1], book[1] + label_bottom, halo=(3, 3, 3))
    bx = (book[0] - BOOK_PAD[0], book[1] - BOOK_PAD[1], book[2] + BOOK_PAD[0], book[3] + BOOK_PAD[1])
    hue, sat = background_colour(im)
    target = (int(width), round(width * h / w)) if width else (round(w * SCALE), round(h * SCALE))
    im = im.resize(target, Image.LANCZOS).filter(ImageFilter.UnsharpMask(radius=1.4, percent=55, threshold=2))
    os.makedirs(OUT, exist_ok=True)
    im.save(os.path.join(OUT, key + '.jpg'), quality=90, optimize=True, subsampling=0)
    return {
        'file': key + '.jpg', 'width': target[0], 'height': target[1],
        'zone': frac(zone, w, h), 'book': frac(bx, w, h),
        'ink': list(title_ink), 'tagline': list(tag_ink), 'hue': hue, 'saturation': sat,
    }


def main(sheet_path, master_path):
    sheet = Image.open(sheet_path).convert('RGB')
    manifest = {}
    for key, c in CELLS.items():
        cell = sheet.crop(c['cell'])
        im, dx, dy = trim_gutter(cell)
        sh = lambda r: (r[0] - dx, r[1] - dy, r[2] - dx, r[3] - dy)
        manifest[key] = finish(im, key, sh(c['zone']), sh(c['book']), sh(c['tab']), label_bottom=LABEL_TALL.get(key, LABEL[3]))
    master = Image.open(master_path).convert('RGB')
    manifest[MASTER['key']] = finish(master, MASTER['key'], MASTER['zone'], MASTER['book'], None, MASTER['width'], label_bottom=0, face=MASTER['face'])

    lines = ["<?php", "", "// Generated by tools/prepare_templates.py: do not edit by hand.", "// zone: headline + tagline area, book: where the product cover goes; both as fractions of the plate.", "", "return ["]
    for key, m in manifest.items():
        lines.append("    '%s' => %s," % (key, json.dumps(m).replace('{', '[').replace('}', ']').replace('": ', '" => ').replace('"', "'")))
    lines += ["];", ""]
    with open(os.path.join(OUT, 'manifest.php'), 'w') as f:
        f.write('\n'.join(lines))
    print('wrote %d templates to %s' % (len(manifest), os.path.abspath(OUT)))


if __name__ == '__main__':
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    main(sys.argv[1], sys.argv[2])
