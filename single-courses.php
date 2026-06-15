<?php
get_header();

$course_id = get_the_ID();
$price     = get_post_meta( $course_id, '_price', true );
$price_khr = get_post_meta( $course_id, '_price_khr', true );
$duration  = get_post_meta( $course_id, '_duration', true );
$level     = get_post_meta( $course_id, '_level', true );

// Get lessons list in order
$lessons_order = get_post_meta( $course_id, '_lessons_order', true );
if ( empty( $lessons_order ) || ! is_array( $lessons_order ) ) {
    $lessons_order = array();
}
$total_lessons = count( $lessons_order );

// Get enrollment status
$enroll_status = false;
$start_lesson_url = '';
if ( is_user_logged_in() ) {
    $user_id = get_current_user_id();
    $enroll_status = reandaily_lms_is_enrolled( $user_id, $course_id );
    
    // Find the first lesson URL
    if ( ! empty( $lessons_order ) ) {
        $first_lesson_id = $lessons_order[0];
        $start_lesson_url = add_query_arg( 'course_id', $course_id, get_permalink( $first_lesson_id ) );
    }
}
?>

<!-- Course Hero -->
<section style="padding: 80px 0 60px 0; background: linear-gradient(135deg, rgba(21,27,44,0.9), #0b0f19), url('<?php echo esc_url( get_the_post_thumbnail_url( $course_id, 'full' ) ); ?>') center/cover; border-bottom: 1px solid var(--border-color);">
    <div class="container" style="display: grid; grid-template-columns: 1fr; gap: 24px;">
        <span style="background: rgba(229, 47, 46, 0.15); color: var(--color-primary); padding: 6px 14px; border-radius: 4px; font-weight: 600; font-size: 13px; border: 1px solid rgba(229, 47, 46, 0.2); width: fit-content; text-transform: uppercase;">🎓 Course Details</span>
        <h1 style="font-size: 40px; font-weight: 800; font-family: var(--font-khmer); line-height: 1.3; color:#ffffff; max-width: 800px;"><?php the_title(); ?></h1>
        
        <div style="display: flex; gap: 24px; color: var(--text-muted); font-size: 14px; flex-wrap: wrap;">
            <span><i class="fa-solid fa-signal" style="color: var(--color-primary); margin-right: 6px;"></i><?php echo esc_html( $level ? $level : 'All Levels' ); ?></span>
            <span><i class="fa-regular fa-clock" style="color: var(--color-secondary); margin-right: 6px;"></i><?php echo esc_html( $duration ? $duration : 'Self-Paced' ); ?></span>
            <span><i class="fa-solid fa-book-open" style="color: var(--color-success); margin-right: 6px;"></i><?php echo $total_lessons; ?> មេរៀន</span>
        </div>
    </div>
</section>

<!-- Content Area -->
<div class="container" style="padding: 60px 24px; display: grid; grid-template-columns: 2fr 1fr; gap: 48px; align-items: start;">
    
    <!-- Left Column: Details & Curriculum -->
    <div>
        <!-- About/Description -->
        <div style="margin-bottom: 40px;">
            <h2 style="font-size: 24px; border-bottom: 1.5px solid var(--color-primary); width: fit-content; padding-bottom: 8px; margin-bottom: 20px;">ព័ត៌មានលម្អិតអំពីវគ្គសិក្សា</h2>
            <div style="color: var(--text-muted); line-height: 1.8; font-size: 15.5px; font-family: var(--font-khmer);">
                <?php the_content(); ?>
            </div>
        </div>

        <!-- Curriculum -->
        <div>
            <h2 style="font-size: 24px; border-bottom: 1.5px solid var(--color-primary); width: fit-content; padding-bottom: 8px; margin-bottom: 20px;">មាតិកាមេរៀន (Curriculum)</h2>
            <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); overflow: hidden;">
                <?php
                if ( ! empty( $lessons_order ) ) :
                    $index = 1;
                    foreach ( $lessons_order as $lesson_id ) :
                        $lesson_post = get_post( $lesson_id );
                        if ( ! $lesson_post ) continue;

                        $is_preview = get_post_meta( $lesson_id, '_is_preview', true );
                        $lesson_duration = get_post_meta( $lesson_id, '_duration', true );
                        
                        // Determine if lesson can be viewed
                        $can_view = ( $enroll_status === 'active' || $is_preview );
                        $lesson_url = $can_view ? add_query_arg( 'course_id', $course_id, get_permalink( $lesson_id ) ) : '#';
                        ?>
                        <div style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); transition: var(--transition-fast);" <?php if ($can_view): ?>onmouseover="this.style.background='rgba(15,23,42,0.02)';" onmouseout="this.style.background='transparent';"<?php endif; ?>>
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <span style="color: var(--text-muted); font-size: 14px; font-weight: 600; width: 20px;"><?php echo $index++; ?>.</span>
                                <div style="display: flex; flex-direction: column;">
                                    <?php if ( $can_view ) : ?>
                                        <a href="<?php echo esc_url( $lesson_url ); ?>" style="color: var(--text-main); font-weight: 500; font-family: var(--font-khmer);"><?php echo esc_html( $lesson_post->post_title ); ?></a>
                                    <?php else : ?>
                                        <span style="color: var(--text-muted); font-weight: 500; font-family: var(--font-khmer); cursor: not-allowed;"><i class="fa-solid fa-lock" style="font-size: 12px; margin-right: 8px; color: var(--text-muted);"></i><?php echo esc_html( $lesson_post->post_title ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 16px; font-size: 13px; color: var(--text-muted);">
                                <?php if ( $lesson_duration ) : ?>
                                    <span><i class="fa-regular fa-clock" style="margin-right: 6px;"></i><?php echo esc_html( $lesson_duration ); ?></span>
                                <?php endif; ?>
                                
                                <?php if ( $is_preview && $enroll_status !== 'active' ) : ?>
                                    <span style="background: rgba(16,185,129,0.15); color: var(--color-success); border: 1px solid rgba(16,185,129,0.2); border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 600;">PREVIEW</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                    endforeach;
                else : ?>
                    <div style="padding: 32px; text-align: center; color: var(--text-muted);">
                        <i class="fa-regular fa-folder-open" style="font-size: 32px; margin-bottom: 12px;"></i>
                        <p>មិនទាន់មានមេរៀនត្រូវបានបញ្ចូលនៅឡើយទេ។</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar Checkout Card -->
    <div style="position: sticky; top: 100px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 32px; box-shadow: var(--shadow-md);">
        <!-- Price Display -->
        <div style="margin-bottom: 24px; text-align: center;">
            <?php if ( empty( $price ) || $price <= 0 ) : ?>
                <h3 style="font-size: 36px; font-weight: 800; color: var(--color-success);">FREE</h3>
            <?php else : ?>
                <h3 style="font-size: 38px; font-weight: 800; color: var(--text-main);">$<?php echo number_format( $price, 2 ); ?></h3>
                <?php if ( ! empty( $price_khr ) ) : ?>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px; font-family: var(--font-khmer);"><?php echo number_format( $price_khr ); ?>៛</p>
                <?php else : ?>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px; font-family: var(--font-khmer);"><?php echo number_format( $price * 4100 ); ?>៛</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Course Meta Bullet List -->
        <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; border-top: 1px solid var(--border-color); padding-top: 24px; font-size: 14px; color: var(--text-muted);">
            <div style="display: flex; justify-content: space-between;">
                <span><i class="fa-regular fa-clock" style="margin-right: 8px;"></i>រយៈពេលសិក្សា</span>
                <strong style="color: var(--text-main);"><?php echo esc_html( $duration ? $duration : 'Self-Paced' ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span><i class="fa-solid fa-layer-group" style="margin-right: 8px;"></i>កម្រិតមេរៀន</span>
                <strong style="color: var(--text-main);"><?php echo esc_html( $level ? $level : 'All Levels' ); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span><i class="fa-solid fa-play" style="margin-right: 8px;"></i>មេរៀនសរុប</span>
                <strong style="color: var(--text-main);"><?php echo $total_lessons; ?> Lectures</strong>
            </div>
        </div>

        <!-- Call To Action Button -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if ( ! is_user_logged_in() ) : 
                // Redirect back after login
                $login_url = wp_login_url( get_permalink() );
                ?>
                <a href="<?php echo esc_url( $login_url ); ?>" style="text-align: center; background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover)); color: #ffffff; padding: 14px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(229,47,46,0.2);">
                    ចូលគណនីដើម្បីចូលរៀន
                </a>
            <?php elseif ( $enroll_status === 'active' || $enroll_status === 'completed' ) : ?>
                <a href="<?php echo esc_url( $start_lesson_url ); ?>" style="text-align: center; background: linear-gradient(135deg, var(--color-success), #059669); color: #ffffff; padding: 14px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(16,185,129,0.2);">
                    ចូលរៀនឥឡូវនេះ (Classroom)
                </a>
            <?php elseif ( $enroll_status === 'pending' ) : ?>
                <a href="<?php echo esc_url( home_url( '/enroll/?course_id=' . $course_id ) ); ?>" style="text-align: center; background: var(--color-warning); color: #ffffff; padding: 14px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(245,158,11,0.2);">
                    រង់ចាំការផ្ទៀងផ្ទាត់ (Pending)
                </a>
            <?php else : 
                $enroll_url = home_url( '/enroll/?course_id=' . $course_id );
                ?>
                <a href="<?php echo esc_url( $enroll_url ); ?>" style="text-align: center; background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover)); color: #ffffff; padding: 14px 24px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(229,47,46,0.2);">
                    ចុះឈ្មោះចូលរៀនឥឡូវនេះ
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
get_footer();
