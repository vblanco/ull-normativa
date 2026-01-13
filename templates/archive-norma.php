<?php
/**
 * Plantilla para el archivo de normativa
 */

get_header();
?>

<div class="ull-archive-container">
    <header class="ull-archive-header">
        <h1><?php post_type_archive_title(); ?></h1>
        <?php if (get_the_archive_description()): ?>
            <div class="ull-archive-description"><?php echo get_the_archive_description(); ?></div>
        <?php endif; ?>
    </header>

    <?php echo do_shortcode('[ull_normativa_listado]'); ?>
</div>

<?php
get_footer();
