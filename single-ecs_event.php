<?php
// Custom template for handling our 'event pages' made by our plugin.
get_header();
?>
<div class="content" id="bfg-event">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <?php
        global $withcomments;
        $withcomments = true;
        $date_format  = get_option('date_format');
        $categories   = get_the_category();
        ?>
        <div <?php post_class('entry'); ?>>

            <div class="content__row heading ">
                <div class="content__item">
                    <h1>
                        <?php the_title(); ?>
                        <span class="tt-back"><a href="<?= get_site_url(null, "/con-schedule") ?>">« Back to Schedule</a></span>
                    </h1>
                </div>
            </div>

            <div class="columns html-text row clearfix content__row entry__content">
                <?php the_content(); ?>
            </div>

            <div class="columns html-text row clearfix content__row entry__content">
                <?php
                // Let's figure out the room:
                $rooms = wp_get_post_terms($post->ID, 'ecs_room');
                $room = $rooms[0]; // Assuming 1 room per event, so just grabbing the 1st response.
        
                // Grab the 'type(s)'
                $wp_types = wp_get_post_terms($post->ID, 'ecs_type');
                $types = [];
                $recursefunc = function ($term) use (&$types, &$recursefunc) {
                    $types[$term->term_id] = $term;
                    if ($term->parent) {
                        $recursefunc(get_term($term->parent, 'ecs_type')); 
                    }
                };
                array_walk($wp_types, $recursefunc);
                
                $user = wp_get_current_user();
                // Putting this here, should be AJAX in future, but just testing it works:
                if (!empty($_POST['action']) && $user->exists()) {
                    if ($_POST['action'] == 'drop_attendee') {
                        // Drop this user:
                        ecs_drop_attendee($post->ID, $user->ID);
                    } elseif ($_POST['action'] == 'add_attendee') {
                        ecs_add_attendee($post->ID, $user->ID);
                    }
                }

                // Grab other details:
                $meta = get_post_meta($post->ID, 'ecs_event_details', true);
                if ($meta['slots']) {
                    $a_ids = ecs_fetch_attendee_ids($post->ID);
                    $attendees = empty($a_ids) ? [] : get_users(['include' => $a_ids]);
                    $taken = count($a_ids);
                }
                ?>
                <dl>
                    <dt>Room:</dt>
                    <dd><?= $room->name ?></dd>
                    <dt>Start:</dt>
                    <dd><?= $meta['start'] ?></dd>
                    <dt>End:</dt>
                    <dd><?= $meta['end'] ?></dd>
                    <dt>Spaces:</dt>
                    <dd><?= $meta['slots'] ?: 'Not Limited' ?></dd>
                    <dt>Type of Event:</dt>
                    <dd><?= implode(", ", array_map(function($x) {
                        return $x->name; }, $types)); ?></dd>
                </dl>
            </div>

            <div class="columns html-text row clearfix content__row entry__content">
                <?php if ($attendees): ?>
                    <h3>Players: (<?= $taken ?>/<?= $meta['slots'] ?: '∞' ?>)</h3>
                    <ul class="ecs-attendees">
                    <?php foreach ($attendees as $a): ?>
                        <li><?= get_avatar($a, 36, 'retro') ?> <?= $a->data->display_name ?></li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php
                // ADD NONCE PROTECTION?  MAKE AJAX?  ALL THE THINGS!
                if ($user->exists()): if (in_array($user->ID, $a_ids)): ?>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="drop_attendee" />
                        <input type="submit" value="Drop this event from your schedule" />
                    </form>
                <?php else: // Logged in but not joined ?>
                    <form action="" method="post">
                        <input type="hidden" name="action" value="add_attendee" />
                        <input type="submit" value="Join this event" />
                    </form>
                <?php endif; // Either way: ?>
                    <p><a href="<?= wp_logout_url(get_permalink()) ?>">Log out</a></p>
                <?php else: // Not logged in ?>
                    <p>You'll need to buy a ticket and then <a href="<?= wp_login_url(get_permalink()) ?>">log in</a> to your account you receive to change your participation</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; endif; ?>
</div>
<?php
get_footer();