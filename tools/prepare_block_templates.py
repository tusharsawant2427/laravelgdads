#!/usr/bin/env python3
"""
Build the block (300x250) banner plates in resources/templates/ from the designers' block sheet.

    python3 tools/prepare_block_templates.py BLOCKS.webp

BLOCKS is the 5x4 sheet of block templates (Entrance Exam, Mathematics, Science ... Defence Studies). For each cell this

  * cuts it out of the sheet,
  * removes the baked-in logo, the heading + tagline and the three feature rows (the layout draws its own),
  * wipes the "BOOK COVER HERE" text of the placeholder book,
  * stores the plate at 600x500 (the cells are 300x250 or 300x267, the latter stretched a little to the same proportions),

and adds block-<name> entries to resources/templates/manifest.php (run prepare_templates.py first: it rewrites the manifest).
Positions are fractions of the plate. Needs Pillow only.
"""
import json
import os
import re
import sys

from PIL import Image

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import prepare_templates as T   # noqa: E402

SIZE = (600, 500)

COLS = [(0, 310), (313, 616), (619, 921), (924, 1226), (1229, 1536)]
ROWS = [(0, 251), (253, 503), (505, 754), (757, 1024)]

# name: (title text end x, feature label end x, book left, book top, book right). Cell pixels; the book bottom is the cell bottom.
CELLS = {
    'entrance':       (196, 135, 167, 118, 256),
    'mathematics':    (198, 136, 202, 114, 288),
    'science':        (192, 138, 195, 116, 274),
    'english':        (186, 164, 200, 115, 282),
    'hindi':          (118, 125, 190, 120, 270),
    'marathi':        (156, 128, 192, 102, 282),
    'social-science': (216, 112, 192, 108, 276),
    'commerce':       (194, 134, 196, 108, 274),
    'computer':       (176, 146, 188, 96, 278),
    'physics':        (150, 142, 187, 102, 276),
    'chemistry':      (188, 142, 195, 92, 284),
    'biology':        (152, 152, 193, 102, 281),
    'geography':      (186, 140, 186, 104, 272),
    'history':        (156, 140, 190, 99, 276),
    'economics':      (174, 143, 190, 92, 280),
    'primary':        (176, 154, 206, 120, 292),
    'pre-primary':    (194, 166, 203, 120, 281),
    'art-craft':      (200, 148, 203, 118, 288),
    'gk':             (240, 139, 207, 118, 288),
    'defence':        (208, 155, 205, 118, 284),
}
ORDER = list(CELLS)

LOGO_ERASE = (4, 6, 152, 40)
LOGO_AT = (22, 13, 118)         # x, y, width of the drawn logo (cell pixels)
TITLE_Y = (41, 134)             # rows of the heading + tagline (the artwork's first icon starts at 138)
FEATURE_Y = (138, 213)          # rows of the feature list
BUTTON_Y = 216                  # the button is below this


def cell_box(i):
    return (COLS[i % 5][0], ROWS[i // 5][0], COLS[i % 5][1], ROWS[i // 5][1])


def row_fill(im, x0, y0, x1, y1, feather=10):
    """Erase a rectangle of text over a smooth background: each row takes the colour at the clean left margin (x < 8), smoothed over
    neighbouring rows, and moves to the colour just right of the rectangle when that is a close match (otherwise stays), feathered at the edge."""
    px = im.load()
    w, h = im.size

    def col(x, y, half=4):
        pts = [px[x, yy] for yy in range(max(0, y - half), min(h, y + half + 1))]
        pts.sort(key=lambda p: sum(p))
        mid = pts[len(pts) // 4: 3 * len(pts) // 4 + 1]   # drop the extremes (text, specks)
        return tuple(sum(p[i] for p in mid) / len(mid) for i in range(3))

    xr = min(w - 1, x1 + 12)
    for y in range(y0, y1 + 1):
        left = tuple(sum(col(x, y)[i] for x in (3, 5, 7)) / 3 for i in range(3))
        right = col(xr, y, 6)
        close = sum(abs(left[i] - right[i]) for i in range(3)) < 95
        for x in range(x0, x1 + 1):
            t = (x - x0) / max(1, x1 - x0)
            c = tuple(left[i] + (right[i] - left[i]) * t if close else left[i] for i in range(3))
            edge = min(1.0, (x1 - x) / feather)   # feather the right side into the artwork
            old = px[x, y]
            px[x, y] = tuple(int(round(c[i] * edge + old[i] * (1 - edge))) if x > x1 - feather else int(round(c[i])) for i in range(3))


def smooth_fill(im, x0, y0, x1, y1, top_rows, bottom_rows, radius=9):
    """Fill a rectangle with a vertical blend between two clean strips of the artwork just outside it.

    Each strip is the median of a few rows, then blurred sideways, so text edges or icons in the strip cannot streak down the region.
    """
    px = im.load()
    w, h = im.size

    def strip(rows):
        cols = []
        for x in range(x0, x1 + 1):
            vals = [px[x, y] for y in rows]
            cols.append(tuple(sorted(v[i] for v in vals)[len(vals) // 2] for i in range(3)))
        out = []
        for k in range(len(cols)):
            lo, hi = max(0, k - radius), min(len(cols), k + radius + 1)
            out.append(tuple(sum(c[i] for c in cols[lo:hi]) / (hi - lo) for i in range(3)))
        return out

    top, bottom = strip(top_rows), strip(bottom_rows)
    for k, x in enumerate(range(x0, x1 + 1)):
        for y in range(y0, y1 + 1):
            t = (y - y0) / max(1, y1 - y0)
            px[x, y] = tuple(int(round(top[k][i] + (bottom[k][i] - top[k][i]) * t)) for i in range(3))


def button(im):
    """Bounding box of the red Shop Now pill: strongly red pixels in the lower left, below the feature rows."""
    w, h = im.size
    hsv = im.convert('HSV').load()
    xs, ys = [], []
    for y in range(BUTTON_Y, h):
        for x in range(0, int(w * 0.55)):
            h8, s8, v8 = hsv[x, y]
            hue = h8 * 360 / 255
            if (hue >= 345 or hue <= 10) and s8 / 255 > 0.75 and v8 / 255 > 0.7:
                xs.append(x)
                ys.append(y)
    xs.sort()
    ys.sort()
    lo, hi = len(xs) // 100, len(xs) - 1 - len(xs) // 100
    return (xs[lo] - 2, ys[lo] - 2, xs[hi] + 3, ys[hi] + 3)


def main(path):
    sheet = Image.open(path).convert('RGB')
    entries = {}
    for i, name in enumerate(ORDER):
        title_end, feat_end, bl, bt, br = CELLS[name]
        im = sheet.crop(cell_box(i))
        w, h = im.size
        bb = h - 4

        # ink colours, before anything is wiped: the heading's and the feature labels'.
        heading = (24, TITLE_Y[0] + 6, min(title_end, 200) - 30, TITLE_Y[0] + 52)
        labels = (50, FEATURE_Y[0] + 4, feat_end - 8, FEATURE_Y[1] - 6)
        title_ink = T.ink(im, heading, (20, TITLE_Y[0], title_end, TITLE_Y[1]))
        label_ink = T.ink(im, labels, (18, FEATURE_Y[0], feat_end, FEATURE_Y[1]))
        dark = sum(sum(p) for p in im.crop((LOGO_ERASE[0], LOGO_ERASE[1], LOGO_ERASE[0] + 4, LOGO_ERASE[3])).getdata()) / (3 * 4 * (LOGO_ERASE[3] - LOGO_ERASE[1])) < 110

        btn0 = button(im)
        # Logo + heading + tagline, then the feature rows: each region is filled from the clean artwork to its left and right only
        # (rows above and below are text), so nothing streaks.
        row_fill(im, 8, 4, max(title_end + 6, 164), TITLE_Y[1])
        row_fill(im, 8, TITLE_Y[1] + 1, feat_end + 8, btn0[1] - 5)
        bh = bb - bt
        T.fill_patch(im, bl + 8, bt + int(bh * 0.26), br - 22, bt + int(bh * 0.80), halo=(3, 3, 3))
        btn = btn0

        hue, sat = T.background_colour(im)
        out = im.resize(SIZE, Image.LANCZOS).filter(T.ImageFilter.UnsharpMask(radius=1.2, percent=45, threshold=2))
        lightness = round(sum(out.convert('L').resize((64, 32)).getdata()) / (64 * 32 * 255), 3)
        key = 'block-' + name
        out.save(os.path.join(T.OUT, key + '.jpg'), quality=90, optimize=True, subsampling=0)

        lw = LOGO_AT[2]
        lh = lw / T.LOGO_ASPECT
        book_right = min(w, br + 4)
        entries[key] = {
            'file': key + '.jpg', 'width': SIZE[0], 'height': SIZE[1],
            'zone': T.frac((20, TITLE_Y[0], min(bl - 16, 204), TITLE_Y[1]), w, h),
            'book': T.frac((bl - 2, bt, book_right, bb), w, h),
            'ink': list(title_ink), 'tagline': list(label_ink), 'hue': hue, 'saturation': sat, 'lightness': lightness,
            'button': T.frac(btn, w, h), 'fixed': False,
            'featureRow': T.frac((20, FEATURE_Y[0] + 2, min(bl - 18, 200), FEATURE_Y[1]), w, h),
            'logo': T.frac((LOGO_AT[0], LOGO_AT[1], LOGO_AT[0] + lw, LOGO_AT[1] + lh), w, h), 'logoDark': dark,
            'block': True,
        }

    manifest = os.path.join(T.OUT, 'manifest.php')
    text = open(manifest).read()
    text = re.sub(r"^    'block-[a-z-]+' => .*\n", '', text, flags=re.M)
    add = ''.join("    '%s' => %s,\n" % (k, json.dumps(v).replace('{', '[').replace('}', ']').replace('": ', '" => ').replace('"', "'").replace('true', 'true').replace('false', 'false')) for k, v in entries.items())
    text = text.replace('\n];', '\n' + add + '];')
    open(manifest, 'w').write(text)
    print('wrote %d block templates' % len(entries))


if __name__ == '__main__':
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    main(sys.argv[1])
