<?php
// Custom template for handling our 'event pages' made by Timetable.  As it stood they were BLECH!
get_header();
?>
<div class="content" id="bfg-mp-event">
    <div class="content__news-article">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                <?php
                global $withcomments;
                $withcomments = true;
                $date_format  = get_option('date_format');
                $categories   = get_the_category();
                ?>
                <article <?php post_class('entry'); ?>>
                    <div class="content__row grey">
                        <!-- content__item -->
                        <div class="content__item">
                            <h1><?php the_title(); ?>
                                <span class="tt-back"><a href="<?= get_site_url(null, "/timetable") ?>">« Back to Schedule</a></span>
                            </h1>
                        </div>
                        <!-- /content__item -->
                    </div>
                    <div class="content__row entry__content">
                        <!-- content__item -->
                        <div class="content__item columns">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </article>
            <?php
            endwhile;
        endif;
        ?>
    </div>
</div>
<?php
get_footer();
