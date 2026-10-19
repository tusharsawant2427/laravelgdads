<?php

namespace Packitifotech\Laragdads\Ads\Layouts;

use Packitifotech\Laragdads\Ads\AdSpec;
use Packitifotech\Laragdads\Ads\Support\Canvas;
use Packitifotech\Laragdads\Ads\Support\Typography;
use Packitifotech\Laragdads\Ads\Theme;

interface Layout
{
    /** Draw the foreground (images, text, CTA, badge) onto an already-painted background. */
    public function render(Canvas $canvas, AdSpec $spec, Theme $theme, Typography $headingFont, Typography $bodyFont): void;
}
