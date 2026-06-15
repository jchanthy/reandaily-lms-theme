<?php
/**
 * Template for CPT Lessons - ReanDaily Classroom Player
 */
get_header();

$lesson_id = get_the_ID();
$user_id   = get_current_user_id();

// 1. Determine which course this lesson belongs to
$course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : 0;
if ( ! $course_id ) {
    // Attempt fallback lookup: find a course containing this lesson in its curriculum order
    global $wpdb;
    $course_meta_results = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_lessons_order'"
    );
    foreach ( $course_meta_results as $meta ) {
        $lessons_list = maybe_unserialize( $meta->meta_value );
        if ( is_array( $lessons_list ) && in_array( $lesson_id, $lessons_list ) ) {
            $course_id = intval( $meta->post_id );
            break;
        }
    }
}

// 2. Security Check: Redirect if not logged in
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

// 3. Security Check: Verify enrollment or free preview permissions
$enroll_status = reandaily_lms_is_enrolled( $user_id, $course_id );
$is_preview    = get_post_meta( $lesson_id, '_is_preview', true );

if ( $enroll_status !== 'active' && $enroll_status !== 'completed' && ! $is_preview && ! current_user_can( 'manage_options' ) ) {
    // Not enrolled, not a free preview, and not an admin -> redirect to course overview page
    wp_redirect( get_permalink( $course_id ) );
    exit;
}

// 4. Gather course details
$course_title = get_the_title( $course_id );
$lessons_order = get_post_meta( $course_id, '_lessons_order', true );
if ( empty( $lessons_order ) || ! is_array( $lessons_order ) ) {
    $lessons_order = array();
}

// Find Current Lesson Index, Previous, and Next Lesson
$current_index = array_search( $lesson_id, $lessons_order );
$prev_lesson_url = '';
$next_lesson_url = '';

if ( $current_index !== false ) {
    if ( $current_index > 0 ) {
        $prev_lesson_id  = $lessons_order[ $current_index - 1 ];
        $prev_lesson_url = add_query_arg( 'course_id', $course_id, get_permalink( $prev_lesson_id ) );
    }
    if ( $current_index < count( $lessons_order ) - 1 ) {
        $next_lesson_id  = $lessons_order[ $current_index + 1 ];
        $next_lesson_url = add_query_arg( 'course_id', $course_id, get_permalink( $next_lesson_id ) );
    }
}

// Get completed lessons list for progress tracking
global $wpdb;
$table_name = $wpdb->prefix . 'reandaily_lms';
$completed_raw = $wpdb->get_var( $wpdb->prepare(
    "SELECT completed_lessons FROM $table_name WHERE user_id = %d AND course_id = %d LIMIT 1",
    $user_id,
    $course_id
) );
$completed_lessons = ! empty( $completed_raw ) ? json_decode( $completed_raw, true ) : array();
if ( ! is_array( $completed_lessons ) ) {
    $completed_lessons = array();
}
$is_completed = in_array( $lesson_id, $completed_lessons );

// Lesson Meta Fields
$video_url = get_post_meta( $lesson_id, '_video_url', true );
$duration  = get_post_meta( $lesson_id, '_duration', true );
?>

<style>
    .classroom-wrap {
        display: grid;
        grid-template-columns: 2.5fr 1fr;
        gap: 32px;
        padding: 40px 24px;
        font-family: var(--font-primary);
        align-items: start;
    }

    .video-container {
        position: relative;
        padding-top: 56.25%; /* 16:9 Aspect Ratio */
        background: #000000;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        border: 1px solid var(--border-color);
        margin-bottom: 24px;
        box-shadow: var(--shadow-lg);
    }

    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }

    .lesson-meta-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
    }

    .progress-container {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-sm);
        padding: 24px;
        margin-bottom: 24px;
    }

    .progress-bar-bg {
        background: rgba(255, 255, 255, 0.05);
        height: 8px;
        border-radius: 50px;
        overflow: hidden;
        margin-top: 8px;
    }

    .progress-bar-fill {
        background: linear-gradient(90deg, var(--color-success), #10b981);
        height: 100%;
        width: 0%;
        transition: width 0.5s ease;
    }

    .curriculum-sidebar {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .sidebar-lesson-item {
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        color: var(--text-muted);
        transition: var(--transition-fast);
    }

    .sidebar-lesson-item.active {
        background: rgba(229, 47, 46, 0.08);
        border-left: 3px solid var(--color-primary);
        color: var(--text-main);
    }

    .sidebar-lesson-item:hover:not(.active) {
        background: rgba(15, 23, 42, 0.02);
        color: var(--text-main);
    }

    .btn-nav {
        background: rgba(15, 23, 42, 0.03);
        border: 1px solid var(--border-color);
        color: var(--text-main);
        padding: 10px 18px;
        border-radius: var(--border-radius-sm);
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: var(--transition-fast);
    }

    .btn-nav:hover {
        background: rgba(15, 23, 42, 0.06);
    }
</style>

<div class="container classroom-wrap">
    
    <!-- Left Column: Video & Lesson Info -->
    <div>
        <!-- Video Player -->
        <div class="video-container">
            <?php if ( ! empty( $video_url ) ) : ?>
                <?php 
                // YouTube embed parsing helper
                if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $video_url, $match ) ) {
                    $youtube_id = $match[1];
                    echo '<iframe src="https://www.youtube.com/embed/' . esc_attr( $youtube_id ) . '?rel=0&modestbranding=1" allowfullscreen></iframe>';
                } // Vimeo embed parsing helper
                elseif ( preg_match( '/vimeo\.com\/(?:video\/)?([0-9]+)/i', $video_url, $match ) ) {
                    $vimeo_id = $match[1];
                    echo '<iframe src="https://player.vimeo.com/video/' . esc_attr( $vimeo_id ) . '" allowfullscreen></iframe>';
                } else {
                    // Fallback direct HTML5 video tag
                    echo '<video src="' . esc_url( $video_url ) . '" controls style="position: absolute; top:0; left:0; width:100%; height:100%;"></video>';
                }
                ?>
            <?php else : ?>
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#151b2c; color:var(--text-muted);">
                    <i class="fa-regular fa-image" style="font-size: 48px; margin-bottom: 8px;"></i>
                    <p style="font-family: var(--font-khmer);">មិនមានវីដេអូសម្រាប់មេរៀននេះទេ</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Meta Navigation Row -->
        <div class="lesson-meta-bar">
            <div style="display: flex; gap: 12px;">
                <?php if ( $prev_lesson_url ) : ?>
                    <a href="<?php echo esc_url( $prev_lesson_url ); ?>" class="btn-nav">
                        <i class="fa-solid fa-chevron-left"></i> មុន (Prev)
                    </a>
                <?php endif; ?>
                <?php if ( $next_lesson_url ) : ?>
                    <a href="<?php echo esc_url( $next_lesson_url ); ?>" class="btn-nav">
                        បន្ទាប់ (Next) <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Complete Toggle -->
            <div>
                <button onclick="toggleLessonComplete()" id="btn-complete-lesson" class="btn" style="width:auto; padding: 10px 24px; background: <?php echo $is_completed ? 'var(--color-success)' : 'rgba(15,23,42,0.03)'; ?>; color: <?php echo $is_completed ? '#ffffff' : 'var(--text-main)'; ?>; border: 1px solid <?php echo $is_completed ? 'var(--color-success)' : 'var(--border-color)'; ?>;">
                    <i class="fa-solid <?php echo $is_completed ? 'fa-circle-check' : 'fa-circle'; ?>" style="margin-right: 8px;"></i>
                    <span id="btn-complete-text"><?php echo $is_completed ? 'បានរៀនរួចរាល់' : 'សម្គាល់ថាបានរៀន'; ?></span>
                </button>
            </div>
        </div>

        <!-- Lesson Title & Description -->
        <div>
            <h1 style="font-size: 28px; font-weight: 800; font-family: var(--font-khmer-heading); margin-bottom: 16px; color: var(--text-main);"><?php the_title(); ?></h1>
            <div style="color: var(--text-muted); line-height: 1.8; font-size: 15.5px; font-family: var(--font-khmer);">
                <?php the_content(); ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar Outline & Progress -->
    <div>
        <!-- Course Title -->
        <div class="card" style="padding: 24px; margin-bottom: 24px; background-color: var(--bg-card);">
            <h4 style="font-size: 13px; color: var(--color-primary); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">COURSE PROGRESS</h4>
            <h3 style="font-size: 18px; margin-top: 6px; font-family: var(--font-khmer-heading); line-height: 1.4; color: var(--text-main);"><?php echo esc_html( $course_title ); ?></h3>
            
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" id="lms-progress-bar" style="width: <?php echo reandaily_lms_get_progress( $user_id, $course_id ); ?>%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: var(--text-muted); margin-top: 8px;">
                <span>វឌ្ឍនភាពសិក្សា (Progress)</span>
                <span id="lms-progress-percent"><?php echo reandaily_lms_get_progress( $user_id, $course_id ); ?>%</span>
            </div>
        </div>

        <!-- Curriculum Sidebar -->
        <div class="curriculum-sidebar">
            <div style="padding: 16px 20px; font-weight: 700; font-size: 15px; color: var(--text-main); background: rgba(15,23,42,0.01);">
                <i class="fa-solid fa-list-ol" style="margin-right: 8px; color: var(--color-secondary);"></i> មាតិកាវគ្គសិក្សា
            </div>
            <div style="display: flex; flex-direction: column;">
                <?php
                if ( ! empty( $lessons_order ) ) :
                    $idx = 1;
                    foreach ( $lessons_order as $sid ) :
                        $spost = get_post( $sid );
                        if ( ! $spost ) continue;

                        $active = ( $sid === $lesson_id );
                        $comp = in_array( $sid, $completed_lessons );
                        $lurl = add_query_arg( 'course_id', $course_id, get_permalink( $sid ) );
                        ?>
                        <a href="<?php echo esc_url( $lurl ); ?>" class="sidebar-lesson-item <?php echo $active ? 'active' : ''; ?>">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size:12.5px; font-weight:600; width:15px; color: <?php echo $active ? 'var(--text-main)' : 'var(--text-muted)'; ?>"><?php echo $idx++; ?></span>
                                <span style="font-family: var(--font-khmer); font-weight: <?php echo $active ? '600' : '400'; ?>; color: <?php echo $active ? 'var(--text-main)' : 'var(--text-muted)'; ?>;"><?php echo esc_html( $spost->post_title ); ?></span>
                            </div>
                            
                            <div>
                                <?php if ( $comp ) : ?>
                                    <i class="fa-solid fa-circle-check" style="color: var(--color-success); font-size: 16px;"></i>
                                <?php else : ?>
                                    <i class="fa-regular fa-circle" style="color: var(--border-color); font-size: 14px;"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </div>
</div>

<script>
    const CONFIG = {
        courseId  : <?php echo (int) $course_id; ?>,
        lessonId  : <?php echo (int) $lesson_id; ?>,
        ajaxUrl   : <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
        nonce     : <?php echo wp_json_encode( wp_create_nonce( 'reandaily_lms_nonce' ) ); ?>,
        completed : <?php echo $is_completed ? 'true' : 'false'; ?>
    };

    let isLessonCompleted = CONFIG.completed;

    function toggleLessonComplete() {
        const btn = document.getElementById('btn-complete-lesson');
        const txt = document.getElementById('btn-complete-text');
        
        isLessonCompleted = !isLessonCompleted;

        // Visual toggle instantly
        if (isLessonCompleted) {
            btn.style.background = 'var(--color-success)';
            btn.style.borderColor = 'var(--color-success)';
            btn.style.color = '#ffffff';
            btn.querySelector('i').className = 'fa-solid fa-circle-check';
            txt.textContent = 'បានរៀនរួចរាល់';
        } else {
            btn.style.background = 'rgba(15,23,42,0.03)';
            btn.style.borderColor = 'var(--border-color)';
            btn.style.color = 'var(--text-main)';
            btn.querySelector('i').className = 'fa-regular fa-circle';
            txt.textContent = 'សម្គាល់ថាបានរៀន';
        }

        const fd = new FormData();
        fd.append('action', 'reandaily_lms_update_progress');
        fd.append('course_id', CONFIG.courseId);
        fd.append('lesson_id', CONFIG.lessonId);
        fd.append('completed', isLessonCompleted ? 'true' : 'false');
        fd.append('security', CONFIG.nonce);

        fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update Sidebar Progress Bar & Percentage
                document.getElementById('lms-progress-bar').style.width = data.data.progress + '%';
                document.getElementById('lms-progress-percent').textContent = data.data.progress + '%';
                
                // Relocate checkmarks on sidebar if matching
                const sidebarItems = document.querySelectorAll('.sidebar-lesson-item');
                sidebarItems.forEach(item => {
                    if (item.href.includes(`post_id=${CONFIG.lessonId}`) || item.href.includes(window.location.pathname)) {
                        const icon = item.querySelector('i');
                        if (isLessonCompleted) {
                            icon.className = 'fa-solid fa-circle-check';
                            icon.style.color = 'var(--color-success)';
                            icon.style.fontSize = '16px';
                        } else {
                            icon.className = 'fa-regular fa-circle';
                            icon.style.color = 'var(--border-color)';
                            icon.style.fontSize = '14px';
                        }
                    }
                });
            }
        });
    }
</script>

<?php
get_footer();
