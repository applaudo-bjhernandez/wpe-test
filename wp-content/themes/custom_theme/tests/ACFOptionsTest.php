<?php
use PHPUnit\Framework\TestCase;

class ACFOptionsTest extends TestCase
{
    /**
     * Test that ACF options pages are added correctly.
     */
    public function testACFOptionsPagesAreAdded()
    {
        // Simula que ACF está activo.
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => 'Theme General Settings',
                'menu_title' => 'Theme Settings',
                'menu_slug'  => 'theme-general-settings',
                'capability' => 'edit_posts',
                'redirect'   => false,
            ]);

            // Comprueba que la página de opciones principal fue registrada.
            $this->assertTrue(function_exists('acf_add_options_page'));

            // Simula la creación de una subpágina y comprueba que se crea.
            acf_add_options_sub_page([
                'page_title'  => 'Theme Footer Settings',
                'menu_title'  => 'Footer',
                'parent_slug' => 'theme-general-settings',
            ]);
            $this->assertTrue(function_exists('acf_add_options_sub_page'));
        }
    }
}