<?php
get_header();
?>

<main class="container" style="padding: 80px 24px; min-height: 60vh;">
    <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 48px; text-align: center;">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> style="margin-bottom: 40px; text-align: left;">
                    <h2 style="font-size: 28px; margin-bottom: 16px;"><a href="<?php the_permalink(); ?>" style="color: var(--text-main);"><?php the_title(); ?></a></h2>
                    <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
                        <span><i class="fa-regular fa-calendar" style="margin-right: 6px;"></i><?php echo get_the_date(); ?></span>
                    </div>
                    <div class="entry-content" style="color: var(--text-muted); line-height: 1.8;">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
            
            <div class="pagination">
                <?php the_posts_pagination(); ?>
            </div>
        <?php else : ?>
            <span style="font-size: 60px;">🔍</span>
            <h2 style="font-size: 24px; margin: 20px 0 10px; color: var(--text-main);"><?php _e( 'No Content Found', 'reandaily-lms-theme' ); ?></h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;"><?php _e( 'It seems we can\'t find what you\'re looking for.', 'reandaily-lms-theme' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover)); color: #ffffff; padding: 12px 28px; border-radius: var(--border-radius-sm); font-weight: 600; display: inline-block;">
                <?php _e( 'Back to Home', 'reandaily-lms-theme' ); ?>
            </a>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
