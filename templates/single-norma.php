<?php
/**
 * Plantilla para la ficha individual de una norma
 */

get_header();

while (have_posts()) : the_post();
    $shortcodes = new ULL_Normativa_Shortcodes();
    echo $shortcodes->ficha_shortcode(['id' => get_the_ID()]);
endwhile;

get_footer();
