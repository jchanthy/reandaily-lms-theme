<?php
get_header();
?>

<!-- Banner -->
<section style="padding: 60px 0; background: radial-gradient(circle at top, rgba(0, 123, 255, 0.08), transparent 70%); text-align: center;">
    <div class="container">
        <span style="color: var(--color-primary); font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">🎓 REANDAILY CATALOG</span>
        <h1 style="font-size: 38px; font-weight: 800; margin-top: 10px;"><?php _e( 'វគ្គសិក្សាទាំងអស់', 'reandaily-lms-theme' ); ?></h1>
        <p style="color: var(--text-muted); font-size: 15px; margin-top: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
            រុករកវគ្គសិក្សាជំនាញបច្ចេកវិទ្យាដ៏សម្បូរបែប និងជ្រើសរើសវគ្គសិក្សាដែលសាកសមសម្រាប់អ្នក។
        </p>
    </div>
</section>

<!-- Main Grid -->
<main class="container" style="padding: 60px 24px; min-height: 50vh;">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 32px;">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); 
                $course_id = get_the_ID();
                $price     = get_post_meta( $course_id, '_price', true );
                $price_khr = get_post_meta( $course_id, '_price_khr', true );
                $duration  = get_post_meta( $course_id, '_duration', true );
                $level     = get_post_meta( $course_id, '_level', true );
                ?>
                <div style="background-color: var(--bg-card); border: 1px solid transparent; border-radius: var(--border-radius-md); overflow: hidden; display: flex; flex-direction: column; transition: var(--transition-normal); box-shadow: var(--shadow-sm);" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='var(--shadow-md)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)';">
                    <!-- Image -->
                    <div style="position: relative; padding-top: 56.25%; background-color: #0b0f19;">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <img src="<?php echo esc_url( get_the_post_thumbnail_url( $course_id, 'medium_large' ) ); ?>" alt="Course Thumbnail" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;">
                        <?php else : ?>
                            <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#1e2640; color:var(--text-muted);">
                                <i class="fa-solid fa-graduation-cap" style="font-size: 48px;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badges -->
                        <div style="position: absolute; top: 16px; left: 16px; display: flex; gap: 8px;">
                            <span style="background: rgba(11, 15, 25, 0.85); backdrop-filter: blur(4px); color: #ffffff; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1);"><i class="fa-solid fa-signal" style="margin-right: 5px; color: var(--color-primary);"></i><?php echo esc_html( $level ? $level : 'All Levels' ); ?></span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div style="padding: 24px; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; font-family: var(--font-khmer-heading); line-height: 1.4;">
                            <a href="<?php the_permalink(); ?>" style="color: var(--text-main);"><?php the_title(); ?></a>
                        </h3>
                        
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px; font-family: var(--font-khmer); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?php echo wp_strip_all_tags( get_the_excerpt() ); ?>
                        </p>

                        <!-- Meta -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; margin-top: auto; font-size: 13.5px; color: var(--text-muted);">
                            <span><i class="fa-regular fa-clock" style="margin-right: 6px; color: var(--color-secondary);"></i><?php echo esc_html( $duration ? $duration : 'Self-Paced' ); ?></span>
                            
                            <div style="text-align: right;">
                                <?php if ( empty( $price ) || $price <= 0 ) : ?>
                                    <span style="font-size: 18px; font-weight: 800; color: var(--color-success);">FREE</span>
                                <?php else : ?>
                                    <span style="font-size: 18px; font-weight: 800; color: var(--text-main);">$<?php echo number_format( $price, 2 ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <div style="grid-column: 1 / -1; margin-top: 40px; display: flex; justify-content: center;">
                <?php the_posts_pagination(); ?>
            </div>

        <?php else : ?>
            <div style="grid-column: 1 / -1; background-color: var(--bg-card); border: 1px dashed var(--border-color); border-radius: var(--border-radius-md); padding: 60px; text-align: center; color: var(--text-muted);">
                <i class="fa-regular fa-folder-open" style="font-size: 56px; color: var(--color-primary); margin-bottom: 16px;"></i>
                <h3 style="font-size: 22px; color: var(--text-main); margin-bottom: 8px;"><?php _e( 'No Courses Found', 'reandaily-lms-theme' ); ?></h3>
                <p style="font-size: 15px;"><?php _e( 'We couldn\'t find any courses matching the archive requirements.', 'reandaily-lms-theme' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
