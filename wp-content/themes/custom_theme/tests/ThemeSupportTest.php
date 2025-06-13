<?php
use PHPUnit\Framework\TestCase;

class ThemeSupportTest extends TestCase
{
    /**
     * Test that post thumbnails support is enabled.
     */
    public function testPostThumbnailsSupport()
    {
        // Simula la acción 'after_setup_theme' para invocar el soporte de imágenes destacadas.
        do_action('after_setup_theme');

        // Comprueba que el soporte para las imágenes destacadas está habilitado.
        $this->assertTrue(current_theme_supports('post-thumbnails'));
    }
}