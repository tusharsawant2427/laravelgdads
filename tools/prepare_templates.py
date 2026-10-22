#!/usr/bin/env python3
"""
Build the banner template plates in resources/templates/ from the designers' artwork.

    python3 tools/prepare_templates.py SUBJECTS.webp TOPICS.webp VARIANTS.webp ENTRANCE.png

SUBJECTS is the 10-up contact sheet of subject templates (Mathematics ... EVS / GK), TOPICS the 10-up sheet of topic templates
(entrance exam, pre-primary, kids activity ...) VARIANTS the 10 colour variants of the generic layout (the "general" family) and ENTRANCE the sheet of entrance-exam templates
(NEET, MHT-CET, CUET, all entrance exams) and the two category layouts ("[SUBJECT NAME] PREPARATION BOOKS").
For every template this

  * cuts it out of the sheet and trims the white gutter,
  * removes the "N. SUBJECT TEMPLATE" label,
  * removes the baked-in logo (the layout draws the corporate logo crisply at the recorded position: the baked-in one was small, blurry and cut by the artwork),
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
LOGO_ASPECT = Image.open(os.path.join(OUT, '..', 'logo', 'target-logo.png')).size
LOGO_ASPECT = LOGO_ASPECT[0] / LOGO_ASPECT[1]   # resources/logo/target-logo.png
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

# Topic templates (second sheet, cells about 1000x262): label tab, copy zone and placeholder book.
TOPICS = {
    'pre-primary':    dict(tab=(0, 0, 288), zone=(60, 77, 520, 151), book=(672, 28, 872, 250)),
    'kids-activity':  dict(tab=(0, 0, 300), zone=(60, 77, 520, 151), book=(700, 25, 890, 250)),
    'primary':        dict(tab=(0, 0, 322), zone=(68, 77, 520, 151), book=(715, 20, 900, 250)),
    'foundation':     dict(tab=(0, 0, 390), zone=(60, 77, 505, 151), book=(705, 25, 895, 250)),
    # no single book here: a shelf of four. The cover stands in front of the middle two.
    'multi-subject':  dict(tab=(0, 0, 402), zone=(68, 77, 520, 151), book=(690, 40, 830, 250), label=False),
    'early-learning': dict(tab=(0, 0, 372), zone=(64, 77, 520, 151), book=(733, 22, 905, 250)),
    'kindergarten':   dict(tab=(0, 0, 398), zone=(72, 77, 520, 151), book=(700, 28, 880, 250)),
    'handwriting':    dict(tab=(0, 0, 430), zone=(68, 77, 520, 151), book=(690, 25, 880, 250)),
    'gk-kids':        dict(tab=(0, 0, 442), zone=(72, 77, 540, 151), book=(705, 18, 895, 250)),
}
TOPIC_LABEL = (16, 12, 40, 108)

def _grid(rows, cols, keys):
    return {keys[i]: (c[0], r[0], c[1], r[1]) for i, (r, c) in enumerate((r, c) for r in rows for c in cols)}


TOPIC_CELLS = _grid([(0, 265), (270, 529), (535, 799), (805, 1065), (1070, 1336)], [(0, 1000), (1001, 2000)],
                    ['entrance-exam', 'pre-primary', 'kids-activity', 'primary', 'foundation', 'multi-subject',
                     'early-learning', 'kindergarten', 'handwriting', 'gk-kids'])
VARIANT_CELLS = _grid([(0, 262), (268, 527), (533, 797), (802, 1064), (1069, 1337)], [(0, 995), (999, 2000)],
                      ['general', 'general-orange', 'general-purple', 'general-green', 'general-navy', 'general-pink',
                       'general-yellow', 'general-teal', 'general-cream', 'general-lavender'])

# Colour variants of the generic layout (third sheet): a numbered circle instead of a tab, title + class pill + tagline zone.
VARIANT_ZONE = (118, 52, 452, 157)
VARIANT_CIRCLE = (4, 4, 62, 60)
VARIANT_LABEL = (16, 12, 50, 122)
VARIANTS = {
    'general':          (667, 5, 843, 252),
    'general-orange':   (669, 14, 840, 249),
    'general-purple':   (671, 20, 837, 241),
    'general-green':    (660, 21, 832, 248),
    'general-navy':     (674, 21, 846, 253),
    'general-pink':     (712, 26, 887, 243),
    'general-yellow':   (671, 22, 839, 249),
    'general-teal':     (705, 24, 882, 257),
    'general-cream':    (682, 25, 856, 227),
    'general-lavender': (706, 30, 882, 252),
}
VARIANT_SIZE = (990, 260)   # every variant is stored at the same size, so the whole family renders at one proportion
TOPIC_SCALE = 1.0

# Entrance sheet (about 1085x250 cells): rectangles are in the cell, before the gutter trim.
# NEET / MHT-CET / CUET keep their own headline, tagline, icons and button; only the logo and the book are replaced.
# "entrance-exams" has three finished books instead of a placeholder, so it takes no cover. The two category layouts take a subject
# name, a tagline and four feature labels.
ENTRANCE_ROWS = [(0, 256), (260, 501), (502, 724)]
ENTRANCE_COLS = [(0, 1085), (1089, 2172)]
ENTRANCE = {
    'neet':           dict(cell=(0, 0), logo=(98, 7, 290, 48, 105, 12, 200), book=(703, 3, 880, 252), label=(24, 34, 68, 170), fixed=True, row=(98, 160, 598, 203)),
    'mht-cet':        dict(cell=(0, 1), logo=(96, 5, 290, 48, 103, 10, 200), book=(693, 12, 868, 250), label=(40, 45, 68, 155), fixed=True, row=(96, 160, 504, 202)),
    'cuet':           dict(cell=(1, 0), logo=(98, 5, 290, 48, 105, 10, 200), book=(702, 2, 880, 232), label=(36, 46, 70, 160), fixed=True, row=(100, 154, 580, 194),
                      wipes=[(344, 127, 355, 149)]),   # a stray "]" after "Universities" in the artwork's tagline,
    'entrance-exams': dict(cell=(1, 1), logo=(92, 5, 290, 44, 100, 10, 200), book=None, label=None, fixed=True, row=(98, 157, 572, 194)),
    'category':        dict(cell=(2, 0), logo=(100, 6, 300, 46, 110, 12, 200), book=(712, 3, 892, 205), label=(50, 40, 65, 142),
                            zone=(106, 44, 462, 84), tagline=(106, 110, 400, 130), row=(104, 138, 648, 172)),
    'category-orange': dict(cell=(2, 1), logo=(98, 6, 290, 46, 108, 12, 200), book=(698, 3, 882, 205), label=(50, 60, 67, 142),
                            zone=(104, 44, 450, 84), tagline=(104, 110, 395, 130), row=(102, 138, 610, 172)),
}


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


def fill_patch(im, x0, y0, x1, y1, halo=None, smooth_top=False, smooth_bottom=False):
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
    if smooth_top:
        # The title's brackets and the logo's small print reach the top edge: take it from the sides, not from the row above.
        top = {x: lerp(lef[y0], rig[y0], (x - x0) / max(1, x1 - x0)) for x in range(x0, x1 + 1)}
    if smooth_bottom:
        bot = {x: lerp(lef[y1], rig[y1], (x - x0) / max(1, x1 - x0)) for x in range(x0, x1 + 1)}
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


def fill_vertical(im, x0, y0, x1, y1):
    """Replace a rectangle by a vertical blend of the rows just above and below it (for text with an icon on its left)."""
    w, h = im.size
    px = im.load()
    for x in range(max(3, x0), min(w - 3, x1) + 1):
        top = [sum(px[xx, y0 - 3][i] for xx in range(x - 2, x + 3)) / 5 for i in range(3)]
        bot = [sum(px[xx, y1 + 3][i] for xx in range(x - 2, x + 3)) / 5 for i in range(3)]
        for y in range(y0, y1 + 1):
            t = (y - y0) / max(1, y1 - y0)
            px[x, y] = tuple(int(round(top[i] + (bot[i] - top[i]) * t)) for i in range(3))
    box = (x0 - 2, y0 - 2, x1 + 3, y1 + 3)
    region = im.crop(box).filter(ImageFilter.GaussianBlur(1.2))
    mask = Image.new('L', region.size, 0)
    mp = mask.load()
    for y in range(region.size[1]):
        for x in range(region.size[0]):
            d = min(x, y, region.size[0] - 1 - x, region.size[1] - 1 - y)
            mp[x, y] = 255 if d >= 4 else int(255 * d / 4)
    im.paste(region, box[:2], mask)


def find_button(im):
    """The red "Shop Now" pill: bounding box of the strongly red pixels in the lower left of the artwork (rows below the icons)."""
    w, h = im.size
    hsv = im.convert('HSV')
    px = hsv.load()
    xs, ys = [], []
    for y in range(int(h * 0.76), h):
        for x in range(0, int(w * 0.40)):
            h8, s8, v8 = px[x, y]
            hue = h8 * 360 / 255
            if (hue >= 345 or hue <= 10) and s8 / 255 > 0.75 and v8 / 255 > 0.7:
                xs.append(x)
                ys.append(y)
    if len(xs) < 200:
        raise SystemExit('no Shop Now button found')
    xs.sort()
    ys.sort()
    lo, hi = len(xs) // 100, len(xs) - 1 - len(xs) // 100
    return (xs[lo] - 3, ys[lo] - 3, xs[hi] + 4, ys[hi] + 4)


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


def finish(im, key, zone, book, tab, label=None, scale=SCALE, size=None, smooth_top=False, smooth_bottom=False, logo=None,
           extra=None, fixed=False):
    """im is at source resolution; rectangles are in its pixels. Returns the manifest entry.

    zone: where the headline goes (None when the artwork's own headline stays, as on the NEET banner); book: the placeholder
    book (None when there is none). label: (left, right, top, bottom) inside the book to wipe, or None. size: store the plate
    at exactly this size. extra: {'tagline': rect, 'features': [rect, ...]}: more text to wipe and redraw. fixed: the artwork is
    tied to one subject (its colours are not matched to the cover).
    """
    w, h = im.size
    title_ink = tag_ink = (0, 0, 0)
    if zone:
        title_ink = ink(im, (zone[0], zone[1], zone[2] - 40, zone[1] + (zone[3] - zone[1]) * 5 // 9), zone)
        tag_ink = ink(im, (zone[0], zone[1] + (zone[3] - zone[1]) * 6 // 9, zone[2] - 40, zone[3]), zone)
    extra = extra or {}
    for r in extra.get('wipes', []):
        fill_vertical(im, *r)
    if extra.get('tagline'):
        tag_ink = ink(im, extra['tagline'], extra['tagline'])
    if tab:
        clear_tab(im, *tab)
    logo_entry = {}
    if logo:
        erase, (lx, ly, lw) = logo
        dark = sum(sum(p) for p in im.crop((erase[0] - 6, erase[1] + 2, erase[0], erase[3])).getdata()) / (3 * max(1, 6 * (erase[3] - erase[1] - 2))) < 110
        fill_patch(im, *erase, halo=(3, 5, 2), smooth_top=True, smooth_bottom=True)
        logo_entry = {'logo': frac((lx, ly, lx + lw, ly + lw / LOGO_ASPECT), w, h), 'logoDark': dark}
    if zone:
        fill_patch(im, *zone, smooth_top=smooth_top, smooth_bottom=smooth_bottom)
    entry_extra = {}
    if extra.get('tagline'):
        fill_patch(im, *extra['tagline'], halo=(3, 6, 2), smooth_top=True, smooth_bottom=True)
        entry_extra['taglineZone'] = frac(extra['tagline'], w, h)
    button = find_button(im)
    row = extra.get('row')
    if row == 'auto':
        # The icon + label row sits between the headline zone and the button, left of the book: wipe it, the layout draws its own.
        row = (zone[0] - 4, zone[3] + 3, min(book[0] - int(0.05 * w), int(extra.get('rowCap', 0.58) * w)), button[1] - 3)
    if row:
        fill_vertical(im, *row)
        entry_extra['featureRow'] = frac(row, w, h)
    bx = None
    if book:
        if label:
            fill_patch(im, book[0] + label[0], book[1] + label[2], book[2] - label[1], book[1] + label[3], halo=(3, 3, 3))
        bx = frac((book[0] - BOOK_PAD[0], book[1] - BOOK_PAD[1], book[2] + BOOK_PAD[0], book[3] + BOOK_PAD[1]), w, h)
    hue, sat = background_colour(im)
    target = size or (round(w * scale), round(h * scale))
    im = im.resize(target, Image.LANCZOS).filter(ImageFilter.UnsharpMask(radius=1.4, percent=55, threshold=2))
    lightness = round(sum(im.convert('L').resize((64, 32)).getdata()) / (64 * 32 * 255), 3)
    os.makedirs(OUT, exist_ok=True)
    im.save(os.path.join(OUT, key + '.jpg'), quality=90, optimize=True, subsampling=0)
    return {
        'file': key + '.jpg', 'width': target[0], 'height': target[1],
        'zone': frac(zone, w, h) if zone else None, 'book': bx,
        'ink': list(title_ink), 'tagline': list(tag_ink), 'hue': hue, 'saturation': sat, 'lightness': lightness,
        'button': frac(button, w, h), 'fixed': fixed,
        **entry_extra,
        **logo_entry,
    }


def cut(sheet, box):
    """Cut a cell out of a sheet and trim its white gutter; returns (image, rect shifter)."""
    im, dx, dy = trim_gutter(sheet.crop(box))
    return im, (lambda r: (r[0] - dx, r[1] - dy, r[2] - dx, r[3] - dy))


def main(subjects_path, topics_path, variants_path, entrance_path):
    manifest = {}

    sheet = Image.open(subjects_path).convert('RGB')
    for key, c in CELLS.items():
        im, sh = cut(sheet, c['cell'])
        label = (LABEL[0], LABEL[1], LABEL[2], LABEL_TALL.get(key, LABEL[3]))
        zx = c['zone'][0]
        wide = c['cell'][2] - c['cell'][0] > 550   # biology and EVS cells are wider and their logo a little smaller
        lw = 145 if wide else 150
        manifest[key] = finish(im, key, sh(c['zone']), sh(c['book']), sh(c['tab']), label=label, extra={'row': 'auto'},
                               logo=(sh((zx - 6, 36, zx + 124, c['zone'][1] - 5)), sh((zx, 42, 0, 0))[:2] + (lw,)))

    sheet = Image.open(topics_path).convert('RGB')
    for key, box in TOPIC_CELLS.items():
        if key not in TOPICS:
            continue   # 'entrance-exam' (first cell) was replaced by the entrance sheet
        c = TOPICS[key]
        im, sh = cut(sheet, box)
        tab = sh((c['tab'][0], 0, c['tab'][2] + 14, 33))   # the label's shadow runs past the measured edge
        zx = c['zone'][0]
        manifest[key] = finish(im, key, sh(c['zone']), sh(c['book']), tab,
                               label=None if c.get('label') is False else TOPIC_LABEL, scale=TOPIC_SCALE, smooth_top=True, extra={'row': 'auto', 'rowCap': 0.63},
                               logo=(sh((zx - 10, 38, zx + 200, 76)), sh((zx, 42, 0, 0))[:2] + (230,)))

    sheet = Image.open(variants_path).convert('RGB')
    for key, box in VARIANT_CELLS.items():
        im, sh = cut(sheet, box)
        manifest[key] = finish(im, key, sh(VARIANT_ZONE), sh(VARIANTS[key]), sh((0, 0, VARIANT_CIRCLE[2], VARIANT_CIRCLE[3] + 2)),
                               label=VARIANT_LABEL, size=VARIANT_SIZE, smooth_top=True, smooth_bottom=True, extra={'row': 'auto', 'rowCap': 0.6},
                               logo=(sh((108, 2, 330, 51)), sh((118, 8, 0, 0))[:2] + (250,)))

    sheet = Image.open(entrance_path).convert('RGB')
    for key, c in ENTRANCE.items():
        r, col = c['cell']
        im, sh = cut(sheet, (ENTRANCE_COLS[col][0], ENTRANCE_ROWS[r][0], ENTRANCE_COLS[col][1], ENTRANCE_ROWS[r][1]))
        ex0, ey0, ex1, ey1, lx, ly, lw = c['logo']
        extra = {'wipes': [sh(r) for r in c.get('wipes', [])], 'row': sh(c['row'])}
        if c.get('tagline'):
            extra['tagline'] = sh(c['tagline'])
        manifest[key] = finish(im, key, sh(c['zone']) if c.get('zone') else None, sh(c['book']) if c.get('book') else None, None,
                               label=c['label'], scale=1.0, logo=(sh((ex0, ey0, ex1, ey1)), sh((lx, ly, 0, 0))[:2] + (lw,)),
                               extra=extra, fixed=c.get('fixed', False))

    lines = ["<?php", "", "// Generated by tools/prepare_templates.py: do not edit by hand.", "// zone: headline + tagline area, book: where the product cover goes; both as fractions of the plate.",
             "// hue, saturation, lightness: the background colour family of the artwork (what is recoloured to match a cover, or picks a colour variant).", "", "return ["]
    for key, m in manifest.items():
        lines.append("    '%s' => %s," % (key, json.dumps(m).replace('{', '[').replace('}', ']').replace('": ', '" => ').replace('"', "'")))
    lines += ["];", ""]
    with open(os.path.join(OUT, 'manifest.php'), 'w') as f:
        f.write('\n'.join(lines))
    print('wrote %d templates to %s' % (len(manifest), os.path.abspath(OUT)))


if __name__ == '__main__':
    if len(sys.argv) != 5:
        sys.exit(__doc__)
    main(*sys.argv[1:])
