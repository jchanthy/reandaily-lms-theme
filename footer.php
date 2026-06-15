<footer class="site-footer" style="background-color: #080c14; border-top: 1px solid var(--border-color); padding: 48px 0; margin-top: 60px; font-family: var(--font-primary);">
    <div class="container" style="display: flex; flex-direction: column; gap: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
            <div>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="font-family: var(--font-heading); font-size: 20px; font-weight: 800; color: #ffffff;">
                    <i class="fa-solid fa-graduation-cap" style="color: var(--color-primary); margin-right: 8px;"></i><?php bloginfo( 'name' ); ?>
                </a>
                <p style="color: var(--text-muted); font-size: 14px; margin-top: 8px; max-width: 350px;">
                    <?php bloginfo( 'description' ); ?>
                </p>
            </div>
            
            <div style="display: flex; gap: 40px; flex-wrap: wrap;">
                <div>
                    <h5 style="color: #ffffff; font-size: 15px; margin-bottom: 16px; font-weight: 600;"><?php _e( 'Explore', 'reandaily-lms-theme' ); ?></h5>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 10px; font-size: 14px;">
                        <li><a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>" style="color: var(--text-muted);"><?php _e( 'All Courses', 'reandaily-lms-theme' ); ?></a></li>
                        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color: var(--text-muted);"><?php _e( 'Privacy Policy', 'reandaily-lms-theme' ); ?></a></li>
                        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color: var(--text-muted);"><?php _e( 'Terms & Conditions', 'reandaily-lms-theme' ); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; font-size: 13.5px; color: var(--text-muted);">
            <p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
            <p>Built with <i class="fa-solid fa-heart" style="color: var(--color-primary);"></i> by Antigravity</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
