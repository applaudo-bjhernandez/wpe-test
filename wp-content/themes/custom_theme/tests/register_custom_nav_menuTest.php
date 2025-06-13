<?php
use PHPUnit\Framework\TestCase;

class ThemeFunctionsTest extends TestCase
{
    /**
     * Test that custom navigation menus are registered correctly.
     */
    public function testRegisterCustomNavMenus()
    {
        // Simula la acción 'after_setup_theme' para invocar el registro de menús.
        do_action('after_setup_theme');

        // Comprueba que los menús se han registrado correctamente.
        $this->assertTrue(has_nav_menu('top_menu'));
        $this->assertTrue(has_nav_menu('primary_menu'));
        $this->assertTrue(has_nav_menu('footer_menu'));
        $this->assertTrue(has_nav_menu('watch_now'));
    }
}