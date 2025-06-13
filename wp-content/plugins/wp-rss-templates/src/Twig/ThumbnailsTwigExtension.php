<?php

namespace RebelCode\Wpra\Templates\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extends Twig with features relating to thumbnails.
 *
 * @since 0.3
 */
class ThumbnailsTwigExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     *
     * @since 0.3
     */
    public function getFunctions()
    {
        return [
            $this->getPostThumbnailFunction(),
        ];
    }

    /**
     * Retrieves the "get_the_post_thumbnail" Twig function.
     *
     * @since 0.3
     *
     * @return TwigFunction The function instance.
     */
    protected function getPostThumbnailFunction()
    {
        return new TwigFunction('get_the_post_thumbnail', 'get_the_post_thumbnail');
    }
}
