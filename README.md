Laravel GD Ads
==============

A PHP class for **Creating ads banner** from an image. Uses PHP and GD, Imagick or Gmagick, color-thief-php libraries to make it happen.

![example image](https://github.com/tusharsawant2427/laravelgdads/example.png)

## Requirements

- PHP >= 7.2 or >= PHP 8.0
- Fileinfo extension
- One or more PHP extensions for image processing:
  - GD >= 2.0
  - Imagick >= 2.0 (but >= 3.0 for CMYK images)
  - Gmagick >= 1.0
- Supports JPEG, PNG.

## How to use


### Install via Composer
The recommended way to install Laravel Gd Ads is through
[Composer](http://getcomposer.org):
```bash
composer require packitifotech/laragdads
```

### Get the dominant color from an image
```php Laravel
use Packitifotech\Laragdads\Laragdads;
$image1 = public_path('fruits.jpg');
$image2 = public_path('orange.jpg');
$fontPath = public_path('FjallaOne-Regular.ttf');
$text = "The tree that bears the most fruit gets the most attention. Allow the fruit to fall and rot";
$gdImage = Laragdads::createHorizontalAdsBanner(fImagepath: $image1, sImagePath:$image2, fontPath:$fontPath, text:$text, resizeWidth:null, resizeHeight:null);
Laragdads::saveAdsBannerPng($horizontalAds, Storage::path('public/ads'));

```
The `$image1` and `$image2` variable must contain absolute path of the image on the server.
The `$fontPath` variable must contain absolute path of the image on the server.

```php
ColorThief::saveAdsBannerPng(gdImage: $gdImage, storagePath: $storagePath)
```

You can save banner gd instance image in png by calling `saveAdsBannerPng` method

## Credits

### Author
by Tushar Sawant
[itinfotech.in](http://www.itinfotech.in)

Based on the fabulous work done by Lokesh Dhakar
[itinfotech.in](http://itinfotech.in)

### Thanks
* Kevin Subileau - For creating the [https://github.com/ksubileau/color-thief-php]).