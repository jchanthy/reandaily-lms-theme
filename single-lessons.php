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

// Gather course details
$course_title = get_the_title( $course_id );
$lessons_order = get_post_meta( $course_id, '_lessons_order', true );
if ( empty( $lessons_order ) || ! is_array( $lessons_order ) ) {
    $lessons_order = array();
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

// Drip / Start Date validation: lock lesson if start date/time is in the future
$start_date = get_post_meta( $lesson_id, '_lesson_start_date', true );
$start_time = get_post_meta( $lesson_id, '_lesson_start_time', true );
$start_ampm = get_post_meta( $lesson_id, '_lesson_start_ampm', true );

$is_locked = false;
$lock_message = '';

if ( ! empty( $start_date ) && ! current_user_can( 'edit_posts' ) ) {
    $time_str = ! empty( $start_time ) ? $start_time . ' ' . $start_ampm : '12:00 AM';
    $datetime_str = $start_date . ' ' . $time_str;
    $start_timestamp = strtotime( $datetime_str );
    $current_timestamp = current_time( 'timestamp' ); // local WP time

    if ( $start_timestamp && $current_timestamp < $start_timestamp ) {
        $is_locked = true;
        $formatted_lock_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_timestamp );
        $lock_message = sprintf( __( 'មេរៀននេះនឹងចាប់ផ្តើមនៅ %s។ (This activity is locked and will start on %s.)', 'reandaily-lms-theme' ), $formatted_lock_date, $formatted_lock_date );
    }
}

// Check sequential drip content lock
if ( ! $is_locked && ! current_user_can( 'manage_options' ) && ! $is_preview ) {
    $drip_dependencies = get_post_meta( $course_id, '_drip_dependencies', true );
    if ( is_array( $drip_dependencies ) && ! empty( $drip_dependencies ) ) {
        foreach ( $drip_dependencies as $group ) {
            if ( isset( $group['parent_id'] ) && isset( $group['dependents'] ) && is_array( $group['dependents'] ) ) {
                if ( in_array( $lesson_id, $group['dependents'] ) ) {
                    $parent_id = intval( $group['parent_id'] );
                    if ( ! in_array( $parent_id, $completed_lessons ) ) {
                        $is_locked = true;
                        $parent_post = get_post( $parent_id );
                        $parent_title = $parent_post ? $parent_post->post_title : 'preceding lesson';
                        $lock_message = sprintf( __( 'មេរៀននេះត្រូវបានចាក់សោ។ អ្នកត្រូវតែបញ្ចប់មេរៀន "%s" ជាមុនសិន។ (This lesson is locked. You must complete "%s" first.)', 'reandaily-lms-theme' ), $parent_title, $parent_title );
                        break;
                    }
                }
            }
        }
    } else {
        $lock_lessons_order = get_post_meta( $course_id, '_lock_lessons_order', true );
        if ( $lock_lessons_order === '1' ) {
            $current_lesson_index = array_search( $lesson_id, $lessons_order );
            if ( $current_lesson_index !== false && $current_lesson_index > 0 ) {
                for ( $i = 0; $i < $current_lesson_index; $i++ ) {
                    $prev_id = $lessons_order[$i];
                    if ( ! in_array( $prev_id, $completed_lessons ) ) {
                        $is_locked = true;
                        $lock_message = __( 'មេរៀននេះត្រូវបានចាក់សោ។ អ្នកត្រូវតែបញ្ចប់មេរៀនមុនៗជាមុនសិន។ (This lesson is locked. You must complete the preceding lessons first.)', 'reandaily-lms-theme' );
                        break;
                    }
                }
            }
        }
    }
}

if ( $enroll_status !== 'active' && $enroll_status !== 'completed' && ! $is_preview && ! current_user_can( 'manage_options' ) ) {
    // Not enrolled, not a free preview, and not an admin -> redirect to course overview page
    wp_redirect( get_permalink( $course_id ) );
    exit;
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

// Lesson Meta Fields
$video_url = get_post_meta( $lesson_id, '_video_url', true );
$duration  = get_post_meta( $lesson_id, '_duration', true );

$quiz_questions      = get_post_meta( $lesson_id, '_quiz_questions', true ) ?: '[]';
$quiz_time_limit     = intval( get_post_meta( $lesson_id, '_quiz_time_limit', true ) ?: 0 );
$quiz_passing_grade  = intval( get_post_meta( $lesson_id, '_quiz_passing_grade', true ) ?: 70 );
$quiz_retakes        = intval( get_post_meta( $lesson_id, '_quiz_retakes', true ) ?: 0 );
?>

<style>
    .classroom-wrap {
        display: grid;
        grid-template-columns: 2.5fr 1fr;
        gap: 32px;
        padding: 40px 24px;
        font-family: var(--font-primary);
        align-items: start;
        transition: grid-template-columns 0.4s cubic-bezier(0.4, 0, 0.2, 1), gap 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .classroom-sidebar-column {
        transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 1;
        transform: translateX(0);
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
        <?php 
        $lesson_info = reandaily_lms_get_lesson_type_and_icon( $lesson_id );
        $lesson_type = $lesson_info['type'];
        ?>
        <!-- Video Player -->
        <div class="video-container" style="<?php if ( $is_locked || ( $lesson_type !== 'video' && $lesson_type !== 'stream' ) ) echo 'padding-top: 70%;'; ?>">
            <?php if ( $is_locked ) : ?>
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#0f172a; color:#fff; padding: 20px; text-align: center; font-family: var(--font-khmer);">
                    <div style="background: rgba(239, 68, 68, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fa-solid fa-lock" style="font-size: 36px; color: #ef4444;"></i>
                    </div>
                    <h3 style="font-family: var(--font-khmer-heading); margin-bottom: 8px; font-size: 20px; color: #fff;"><?php _e('មេរៀននេះត្រូវបានចាក់សោ (Lesson Locked)', 'reandaily-lms-theme'); ?></h3>
                    <p style="font-family: var(--font-khmer); color: #94a3b8; font-size: 14.5px; max-width: 450px; line-height: 1.6; margin: 0;"><?php echo esc_html( $lock_message ); ?></p>
                </div>
            <?php elseif ( $lesson_type === 'zoom' ) : ?>
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#0f172a; color:#fff; padding: 20px; text-align: center;">
                    <div style="background: rgba(59, 130, 246, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fa-solid fa-video" style="font-size: 36px; color: #2d8cff;"></i>
                    </div>
                    <h3 style="font-family: var(--font-khmer-heading); margin-bottom: 8px; font-size: 20px;">ការបង្រៀនផ្ទាល់តាម Zoom (Live Zoom Class)</h3>
                    <p style="font-family: var(--font-khmer); color: #94a3b8; font-size: 14px; margin-bottom: 24px; max-width: 450px;">សូមចូលរួមថ្នាក់រៀនផ្ទាល់តាមរយៈប៊ូតុងខាងក្រោម។ ប្រសិនបើទាមទារលេខសំងាត់ សូមទាក់ទងមកកាន់គ្រូឧទ្ទេស។</p>
                    <?php if ( ! empty( $video_url ) ) : ?>
                        <a href="<?php echo esc_url( $video_url ); ?>" target="_blank" style="background: #2d8cff; color: #fff; text-decoration: none; padding: 12px 28px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 14.5px; transition: background 0.15s ease;" onmouseover="this.style.background='#1a73e8'" onmouseout="this.style.background='#2d8cff'">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> ចូលរួមប្រជុំ Zoom (Join Zoom Meeting)
                        </a>
                    <?php else : ?>
                        <span style="background: #334155; color: #94a3b8; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-family: var(--font-khmer);"><?php _e('មិនទាន់មានលីងប្រជុំនៅឡើយទេ', 'reandaily-lms-theme'); ?></span>
                    <?php endif; ?>
                </div>
            <?php elseif ( $lesson_type === 'quiz' ) : ?>
                <div class="lms-quiz-player-container" style="position: absolute; top:0; left:0; width:100%; height:100%; background: var(--bg-card); color: var(--text-main); display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 24px; box-sizing: border-box; overflow-y: auto; font-family: var(--font-primary);">
                    
                    <!-- Start Quiz Screen -->
                    <div id="lms-quiz-start-screen" style="text-align: center; max-width: 500px;">
                        <div style="background: rgba(16, 185, 129, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                            <i class="fa-solid fa-circle-question" style="font-size: 36px; color: #10b981;"></i>
                        </div>
                        <h3 style="font-family: var(--font-khmer-heading); margin-bottom: 12px; font-size: 22px;"><?php the_title(); ?></h3>
                        <p style="font-family: var(--font-khmer); color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6;">
                            សូមស្វាគមន៍មកកាន់ការធ្វើតេស្តសមត្ថភាព! (Welcome to the quiz!)<br>
                            - ពិន្ទុជាប់៖ <strong><?php echo $quiz_passing_grade; ?>%</strong><br>
                            - រយៈពេល៖ <strong><?php echo $quiz_time_limit > 0 ? $quiz_time_limit . ' នាទី (mins)' : 'មិនកំណត់ពេល (No limit)'; ?></strong>
                        </p>
                        <button id="lms-btn-start-quiz" style="background: #10b981; border: none; color: #fff; padding: 12px 32px; font-weight: 700; border-radius: 6px; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.15s ease;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            <i class="fa-solid fa-play"></i> ចាប់ផ្តើមធ្វើតេស្ត (Start Quiz)
                        </button>
                    </div>

                    <!-- Active Quiz Screen (Hidden initially) -->
                    <div id="lms-quiz-active-screen" style="display: none; width: 100%; max-width: 650px; text-align: left; height: 100%; flex-direction: column; justify-content: space-between;">
                        <div style="width: 100%;">
                            <!-- Header: Timer & Progress -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; width: 100%;">
                                <span style="font-size: 14px; font-weight: 600; color: var(--text-muted);">
                                    សំណួរទី <span id="lms-quiz-current-q-num">1</span>/<span id="lms-quiz-total-q-num">0</span>
                                </span>
                                <?php if ($quiz_time_limit > 0): ?>
                                    <span style="font-size: 14px; font-weight: 700; color: #e52f2e; display: inline-flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-clock"></i> <span id="lms-quiz-timer">00:00</span>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Progress Bar -->
                            <div style="background: rgba(255,255,255,0.05); height: 6px; border-radius: 10px; overflow: hidden; margin-bottom: 24px; width: 100%;">
                                <div id="lms-quiz-progress-bar" style="background: #10b981; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                            </div>

                            <!-- Question Panel -->
                            <div id="lms-quiz-question-box" style="margin-bottom: 24px; width: 100%;">
                                <h4 id="lms-quiz-question-title" style="font-family: var(--font-khmer-heading); font-size: 18px; margin-bottom: 16px; line-height: 1.5; color: var(--text-main);"></h4>
                                <div id="lms-quiz-question-image-wrapper" style="margin-bottom: 20px; display: none; text-align: left;"></div>
                                <div id="lms-quiz-options-container" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                                    <!-- Options populated by JS -->
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Footer -->
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: auto; width: 100%;">
                            <button id="lms-btn-quiz-prev" style="background: rgba(15,23,42,0.03); border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 20px; font-weight: 600; border-radius: 4px; cursor: pointer; transition: all 0.15s ease;" disabled>ត្រឡប់ក្រោយ (Previous)</button>
                            <button id="lms-btn-quiz-next" style="background: #10b981; border: none; color: #fff; padding: 10px 24px; font-weight: 600; border-radius: 4px; cursor: pointer; transition: all 0.15s ease;">បន្ទាប់ (Next)</button>
                        </div>
                    </div>

                    <!-- Results Screen (Hidden initially) -->
                    <div id="lms-quiz-results-screen" style="display: none; text-align: center; max-width: 500px;">
                        <div id="lms-quiz-result-icon-container" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                            <!-- icon dynamically set -->
                        </div>
                        <h3 id="lms-quiz-result-title" style="font-family: var(--font-khmer-heading); margin-bottom: 8px; font-size: 22px;"></h3>
                        <p id="lms-quiz-result-score-text" style="font-size: 18px; font-weight: 700; margin-bottom: 12px; color: var(--text-main);"></p>
                        <p id="lms-quiz-result-desc" style="font-family: var(--font-khmer); color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.6;"></p>
                        <div style="display: flex; gap: 12px; justify-content: center;">
                            <button id="lms-btn-quiz-retry" style="background: rgba(15,23,42,0.03); border: 1px solid var(--border-color); color: var(--text-main); padding: 12px 24px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; display: none;">ព្យាយាមម្តងទៀត (Retry)</button>
                            <button id="lms-btn-quiz-continue" style="background: var(--color-primary); color: #fff; border: none; padding: 12px 28px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.15s ease; display: none;">បន្តទៅមុខទៀត (Continue)</button>
                        </div>
                    </div>

                </div>

                <!-- Pass quiz variables to JS safely -->
                <script>
                    window.LMS_QUIZ_DATA = {
                        questions: <?php echo $quiz_questions; ?>,
                        timeLimit: <?php echo $quiz_time_limit; ?>,
                        passingGrade: <?php echo $quiz_passing_grade; ?>,
                        retakes: <?php echo $quiz_retakes; ?>
                    };
                </script>
            <?php elseif ( $lesson_type === 'assignment' ) : ?>
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f8fafc; color:#1e293b; padding: 20px; text-align: center;">
                    <div style="background: rgba(245, 158, 11, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fa-solid fa-clipboard-list" style="font-size: 36px; color: #f59e0b;"></i>
                    </div>
                    <h3 style="font-family: var(--font-khmer-heading); margin-bottom: 8px; font-size: 20px;">កិច្ចការផ្ទះ / សន្លឹកកិច្ចការ (Assignment File Submission)</h3>
                    <p style="font-family: var(--font-khmer); color: #64748b; font-size: 14px; margin-bottom: 24px; max-width: 450px;">សូមទាញយកឯកសារកិច្ចការ បំពេញរួចផ្ញើត្រឡប់មកគ្រូបង្គោលវិញ។</p>
                    <div style="display: flex; gap: 12px;">
                        <?php if (!empty($video_url)): ?>
                            <a href="<?php echo esc_url($video_url); ?>" download style="background: #475569; color: #fff; text-decoration: none; padding: 12px 24px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; transition: background 0.15s ease;" onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#475569'">
                                <i class="fa-solid fa-download"></i> ទាញយកសន្លឹកកិច្ចការ (Download Sheet)
                            </a>
                        <?php endif; ?>
                        <button onclick="alert('<?php _e('ប្រព័ន្ធបញ្ជូនកិច្ចការនឹងបើកក្នុងពេលឆាប់ៗនេះ!', 'reandaily-lms-theme'); ?>')" style="background: #f59e0b; border: none; color: #fff; text-decoration: none; padding: 12px 24px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; transition: background 0.15s ease;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                            <i class="fa-solid fa-upload"></i> បញ្ជូនកិច្ចការ (Submit Assignment)
                        </button>
                    </div>
                </div>
            <?php else : ?>
                <?php if ( ! empty( $video_url ) ) : ?>
                    <?php 
                    if ( $lesson_type === 'video' || $lesson_type === 'stream' ) {
                        // YouTube embed parsing helper supporting standard links, short URLs, and embed URLs
                        if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube-nocookie\.com\/embed\/)([^"&?\/ ]{11})/i', $video_url, $match ) ) {
                            $youtube_id = $match[1];
                            echo '<iframe id="lms-youtube-player" src="https://www.youtube-nocookie.com/embed/' . esc_attr( $youtube_id ) . '?modestbranding=1&rel=0&controls=1&showinfo=0&iv_load_policy=3&enablejsapi=1" allowfullscreen></iframe>';
                        } // Vimeo embed parsing helper
                        elseif ( preg_match( '/vimeo\.com\/(?:video\/)?([0-9]+)/i', $video_url, $match ) ) {
                            $vimeo_id = $match[1];
                            echo '<iframe id="lms-vimeo-player" src="https://player.vimeo.com/video/' . esc_attr( $vimeo_id ) . '" allowfullscreen></iframe>';
                        } else {
                            // Fallback direct HTML5 video tag
                            echo '<video id="lms-native-player" src="' . esc_url( $video_url ) . '" controls style="position: absolute; top:0; left:0; width:100%; height:100%;"></video>';
                        }
                    } elseif ( $lesson_type === 'pdf' ) {
                        echo '<iframe src="' . esc_url( $video_url ) . '" style="position: absolute; top:0; left:0; width:100%; height:100%; border:none;"></iframe>';
                    } elseif ( in_array( $lesson_type, array( 'docx', 'pptx', 'xlsx' ) ) ) {
                        $embed_url = 'https://docs.google.com/gview?url=' . urlencode( $video_url ) . '&embedded=true';
                        echo '<iframe src="' . esc_url( $embed_url ) . '" style="position: absolute; top:0; left:0; width:100%; height:100%; border:none;"></iframe>';
                    } else {
                        ?>
                        <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#1e293b; color:#fff; padding: 20px; text-align: center;">
                            <i class="<?php echo esc_attr( $lesson_info['fa_icon'] ); ?>" style="font-size: 64px; color: var(--color-primary); margin-bottom: 16px;"></i>
                            <h3 style="font-family: var(--font-khmer-heading); margin-bottom: 8px; font-size: 18px;">ឯកសារភ្ជាប់មេរៀន (Lesson Attachment)</h3>
                            <p style="font-family: var(--font-khmer); color: #94a3b8; font-size: 14px; margin-bottom: 20px; max-width: 400px;">មេរៀននេះមានឯកសារភ្ជាប់សម្រាប់ទាញយក។ សូមចុចប៊ូតុងខាងក្រោមដើម្បីទាញយក។</p>
                            <a href="<?php echo esc_url( $video_url ); ?>" download style="background: var(--color-primary); color: #fff; text-decoration: none; padding: 12px 24px; font-weight: 600; border-radius: 4px; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; transition: background 0.15s ease;" onmouseover="this.style.background='var(--color-primary-hover, #ef4444)'" onmouseout="this.style.background='var(--color-primary)'">
                                <i class="fa-solid fa-download"></i> ទាញយកឯកសារ (Download File)
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                <?php else : ?>
                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#151b2c; color:var(--text-muted);">
                        <i class="fa-regular fa-image" style="font-size: 48px; margin-bottom: 8px;"></i>
                        <p style="font-family: var(--font-khmer);">មិនមានវីដេអូ ឬឯកសារសម្រាប់មេរៀននេះទេ</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php 
            if ( $lesson_type === 'video' || $lesson_type === 'stream' ) {
                $video_questions = get_post_meta( $lesson_id, '_video_questions', true ) ?: '[]';
                ?>
                <style>
                    .lms-vq-option {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        background: #1e293b;
                        border: 1px solid #334155;
                        border-radius: 8px;
                        padding: 14px 16px;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        user-select: none;
                    }
                    .lms-vq-option:hover {
                        background: #334155 !important;
                        border-color: #3b82f6 !important;
                        transform: translateY(-1px);
                    }
                    .lms-vq-option.selected {
                        background: #1e3a8a !important;
                        border-color: #3b82f6 !important;
                    }
                    .lms-vq-option.correct {
                        background: #064e3b !important;
                        border-color: #10b981 !important;
                    }
                    .lms-vq-option.incorrect {
                        background: #7f1d1d !important;
                        border-color: #ef4444 !important;
                    }
                </style>
                <div id="lms-video-question-overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.95); z-index: 100; color: #fff; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box; overflow-y: auto; font-family: var(--font-khmer), sans-serif;">
                    <div class="lms-vq-card" style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 28px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2); transform: scale(0.9); transition: transform 0.3s ease;">
                        <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.05em; font-family: var(--font-khmer-heading);"><?php _e('សំណួរវីដេអូ (Video Question)', 'reandaily-lms-theme'); ?></h4>
                        <div id="lms-vq-title" style="font-size: 18px; font-weight: 600; margin-bottom: 20px; line-height: 1.5; color: #f8fafc;"></div>
                        <div id="lms-vq-options" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;"></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div id="lms-vq-feedback" style="font-size: 14px; font-weight: 600; display: none;"></div>
                            <button type="button" id="lms-vq-submit" style="background: #3b82f6; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.15s ease; font-family: var(--font-khmer-heading);"><?php _e('បញ្ជូន (Submit)', 'reandaily-lms-theme'); ?></button>
                        </div>
                    </div>
                </div>
                <script>
                    var lmsVideoQuestions = <?php echo is_string($video_questions) ? $video_questions : json_encode($video_questions); ?>;
                </script>
                <?php
            }
            ?>
        </div>

        <?php if ( ! empty( $video_url ) && $lesson_type !== 'video' ) : ?>
            <div style="margin-top: -10px; margin-bottom: 24px; background: rgba(15, 23, 42, 0.02); border: 1px dashed var(--border-color); border-radius: var(--border-radius-sm); padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="<?php echo esc_attr( $lesson_info['fa_icon'] ); ?>" style="font-size: 24px; color: var(--color-primary);"></i>
                    <div>
                        <strong style="display: block; font-size: 14px; color: var(--text-main); font-family: var(--font-khmer-heading);">ឯកសារជំនួយស្មារតី (Lesson Resource File)</strong>
                        <span style="font-size: 12px; color: var(--text-muted); font-family: monospace;"><?php echo esc_html( basename( parse_url( $video_url, PHP_URL_PATH ) ) ); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url( $video_url ); ?>" download class="btn-nav" style="background: var(--color-primary); color: #fff; border-color: var(--color-primary); text-decoration: none; font-size: 13px; font-weight: 600;">
                    <i class="fa-solid fa-download"></i> ទាញយក (Download)
                </a>
            </div>
        <?php endif; ?>

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

        <!-- Tabs for Lesson Content and Q&A -->
        <div class="lms-frontend-tabs" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; margin-bottom: 24px; font-family: var(--font-primary); padding-bottom: 0;">
            <div style="display: flex; gap: 20px;">
                <button class="lms-frontend-tab active" data-target="#lms-front-tab-content" style="background: none; border: none; color: var(--color-primary); padding: 12px 0; font-weight: 600; font-size: 15px; cursor: pointer; border-bottom: 3px solid var(--color-primary); outline: none; position: relative; bottom: -1.5px; transition: all 0.15s ease;">Lesson Content</button>
                <button class="lms-frontend-tab" data-target="#lms-front-tab-qa" style="background: none; border: none; color: var(--text-muted); padding: 12px 0; font-weight: 600; font-size: 15px; cursor: pointer; border-bottom: 3px solid transparent; outline: none; position: relative; bottom: -1.5px; transition: all 0.15s ease;">Q&A</button>
            </div>
            
            <!-- Control Buttons (Sidebar Toggle & Theme Toggle) -->
            <div style="display: flex; gap: 10px; align-items: center;">
                <button id="lms-lesson-sidebar-toggle" type="button">
                    <i class="fa-solid fa-columns" style="font-size: 14px;"></i>
                    <span>Hide Sidebar</span>
                </button>
                <button id="lms-lesson-theme-toggle" type="button">
                    <i class="fa-regular fa-moon" style="font-size: 14px;"></i>
                    <span>Dark Mode</span>
                </button>
            </div>
        </div>

        <!-- Theme Mode Styles -->
        <style>
            #lms-lesson-theme-toggle,
            #lms-lesson-sidebar-toggle {
                background: rgba(15, 23, 42, 0.05);
                border: none;
                border-radius: 20px;
                padding: 6px 14px;
                font-size: 13px;
                font-weight: 600;
                color: var(--text-main);
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
                outline: none;
            }
            #lms-lesson-theme-toggle:hover,
            #lms-lesson-sidebar-toggle:hover {
                background: rgba(15, 23, 42, 0.08);
            }
            body.lms-dark-mode #lms-lesson-theme-toggle,
            body.lms-dark-mode #lms-lesson-sidebar-toggle {
                background: rgba(255, 255, 255, 0.1) !important;
                color: #ffffff !important;
            }
            body.lms-dark-mode #lms-lesson-theme-toggle:hover,
            body.lms-dark-mode #lms-lesson-sidebar-toggle:hover {
                background: rgba(255, 255, 255, 0.15) !important;
            }
            body.lms-dark-mode {
                background-color: #0f172a !important;
                color: #e2e8f0 !important;
                --text-main: #ffffff !important;
                --text-muted: #94a3b8 !important;
                --border-color: #334155 !important;
            }
            body.lms-dark-mode .site-header,
            body.lms-dark-mode .curriculum-sidebar,
            body.lms-dark-mode .card,
            body.lms-dark-mode .frontend-question-card {
                background-color: #1e293b !important;
                border-color: #334155 !important;
                color: #e2e8f0 !important;
            }
            body.lms-dark-mode h1, 
            body.lms-dark-mode h2, 
            body.lms-dark-mode h3, 
            body.lms-dark-mode h4, 
            body.lms-dark-mode h5, 
            body.lms-dark-mode strong,
            body.lms-dark-mode .sidebar-lesson-item.active,
            body.lms-dark-mode .sidebar-lesson-item:hover {
                color: #ffffff !important;
            }
            body.lms-dark-mode .sidebar-lesson-item.active {
                background: rgba(255, 184, 0, 0.15) !important;
            }
            body.lms-dark-mode .sidebar-lesson-item {
                color: #94a3b8 !important;
            }
            body.lms-dark-mode .sidebar-lesson-item:hover:not(.active) {
                background: rgba(255, 255, 255, 0.03) !important;
            }
            body.lms-dark-mode .lms-frontend-tabs {
                border-bottom-color: #334155 !important;
            }
            body.lms-dark-mode .lms-frontend-tab:not(.active) {
                color: #94a3b8 !important;
            }
            body.lms-dark-mode .lms-lesson-short-description {
                background: rgba(255, 255, 255, 0.03) !important;
                color: #cbd5e1 !important;
            }
            body.lms-dark-mode #lms-front-tab-content div {
                color: #cbd5e1 !important;
            }
            body.lms-dark-mode .curriculum-sidebar-header {
                color: #ffffff !important;
                background: rgba(255, 255, 255, 0.03) !important;
            }
            .classroom-wrap.sidebar-hidden {
                grid-template-columns: 2.5fr 0fr;
                gap: 0;
            }
            .classroom-wrap.sidebar-hidden .classroom-sidebar-column {
                opacity: 0;
                transform: translateX(50px);
                pointer-events: none;
                overflow: hidden;
            }
            #lms-student-question-textarea {
                background-color: #f8fafc !important;
                color: var(--text-main) !important;
                border-color: var(--border-color) !important;
                transition: all 0.2s ease;
            }
            #lms-student-question-textarea:focus {
                background-color: #ffffff !important;
                border-color: var(--color-primary) !important;
                box-shadow: 0 0 0 3px rgba(229, 47, 46, 0.15);
            }
            body.lms-dark-mode #lms-student-question-textarea {
                background-color: #0f172a !important;
                color: #ffffff !important;
                border-color: #334155 !important;
            }
            body.lms-dark-mode #lms-student-question-textarea:focus {
                background-color: #0b0f19 !important;
                border-color: var(--color-primary) !important;
                box-shadow: 0 0 0 3px rgba(229, 47, 46, 0.25);
            }
        </style>

        <!-- Tab 1: Lesson Content -->
        <div id="lms-front-tab-content" class="lms-front-tab-pane">
            <h1 style="font-size: 28px; font-weight: 800; font-family: var(--font-khmer-heading); margin-bottom: 16px; color: var(--text-main);"><?php the_title(); ?></h1>
            
            <?php if ( $is_locked ) : ?>
                <div class="lms-lesson-locked-notice" style="background: rgba(239, 68, 68, 0.03); border: 1px solid rgba(239, 68, 68, 0.15); border-left: 4px solid #ef4444; padding: 20px; border-radius: var(--border-radius-sm); margin-bottom: 24px; font-family: var(--font-khmer);">
                    <h4 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 700; color: var(--text-main);"><?php _e( 'សេចក្តីជូនដំណឹង (Access Restricted)', 'reandaily-lms-theme' ); ?></h4>
                    <p style="margin: 0; font-size: 14px; color: var(--text-muted); line-height: 1.6;"><?php echo esc_html( $lock_message ); ?></p>
                </div>
            <?php else : ?>
                <?php 
                $lesson_desc = get_post_meta( get_the_ID(), '_lesson_description', true );
                if ( ! empty( $lesson_desc ) ) : 
                ?>
                    <div class="lms-lesson-short-description" style="font-family: var(--font-khmer); font-size: 15px; color: var(--text-main); background: rgba(255,255,255,0.02); border-left: 4px solid var(--color-primary); padding: 16px; border-radius: var(--border-radius-sm); margin-bottom: 24px; line-height: 1.7;"><?php echo wp_kses_post( $lesson_desc ); ?></div>
                <?php endif; ?>

                <div style="color: var(--text-muted); line-height: 1.8; font-size: 15.5px; font-family: var(--font-khmer);">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Q&A -->
        <div id="lms-front-tab-qa" class="lms-front-tab-pane" style="display: none; font-family: var(--font-primary);">
            <h3 style="font-family: var(--font-khmer-heading); margin-bottom: 20px; font-size: 20px; color: var(--text-main);">សួរ និង ឆ្លើយ (Questions & Answers)</h3>
            
            <!-- Question Submission Form -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--border-radius-md); margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px 0; font-size: 14px; font-family: var(--font-khmer-heading); color: var(--text-main);">សួរសំណួរថ្មី (Ask a new question)</h4>
                <textarea id="lms-student-question-textarea" placeholder="សរសេរសំណួររបស់អ្នកនៅទីនេះ... (Write your question here...)" rows="3" style="width: 100%; padding: 12px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color); background: rgba(0,0,0,0.1); color: var(--text-main); font-family: var(--font-khmer); font-size: 14px; outline: none; margin-bottom: 12px; resize: vertical; box-sizing: border-box;"></textarea>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" id="lms-btn-submit-student-question" style="background: var(--color-primary); color: #fff; border: none; padding: 10px 24px; font-weight: 600; font-size: 14px; border-radius: 4px; cursor: pointer; transition: background 0.15s ease;">បញ្ជូនសំណួរ (Submit Question)</button>
                </div>
            </div>
            
            <!-- Questions List -->
            <div id="lms-frontend-qa-list" style="display: flex; flex-direction: column; gap: 20px;">
                <?php
                $comments = get_comments( array(
                    'post_id' => $lesson_id,
                    'parent'  => 0,
                    'status'  => 'approve',
                    'order'   => 'DESC',
                ) );
                
                if ( empty( $comments ) ) :
                    echo '<p id="lms-no-questions-notice" style="color: var(--text-muted); font-family: var(--font-khmer); text-align: center; padding: 30px 0;">មិនទាន់មានសំណួរនៅឡើយទេ។ (No questions posted yet.)</p>';
                else :
                    foreach ( $comments as $comment ) :
                        $replies = get_comments( array(
                            'post_id' => $lesson_id,
                            'parent'  => $comment->comment_ID,
                            'status'  => 'approve',
                            'order'   => 'ASC',
                        ) );
                        ?>
                        <div class="frontend-question-card" style="background: var(--bg-card); border-radius: var(--border-radius-md); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px;">
                            <div style="display: flex; gap: 14px; align-items: flex-start;">
                                <img src="<?php echo esc_url( get_avatar_url( $comment->comment_author_email, array( 'size' => 48 ) ) ); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; align-items: center;">
                                        <strong style="color: var(--text-main); font-size: 14.5px;"><?php echo esc_html( $comment->comment_author ); ?></strong>
                                        <span style="color: var(--text-muted); font-size: 11px;"><?php echo get_comment_date( 'M j, Y g:i a', $comment->comment_ID ); ?></span>
                                    </div>
                                    <p style="color: var(--text-main); font-family: var(--font-khmer); line-height: 1.6; font-size: 14px; margin: 0 0 16px 0; white-space: pre-wrap;"><?php echo esc_html( $comment->comment_content ); ?></p>
                                    
                                    <!-- Answers/Replies -->
                                    <div class="frontend-replies-list" style="padding-top: 16px; display: flex; flex-direction: column; gap: 12px;">
                                        <?php if ( empty( $replies ) ) : ?>
                                            <span style="color: var(--text-muted); font-size: 12px; font-style: italic; display: block;"><i class="fa-regular fa-clock"></i> រង់ចាំគ្រូឆ្លើយតប (Awaiting instructor response...)</span>
                                        <?php else : ?>
                                            <?php foreach ( $replies as $reply ) : ?>
                                                <div style="display: flex; gap: 12px; background: rgba(255,255,255,0.02); padding: 12px; border-radius: var(--border-radius-sm); border-left: 3px solid var(--color-success);">
                                                    <img src="<?php echo esc_url( get_avatar_url( $reply->comment_author_email, array( 'size' => 32 ) ) ); ?>" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
                                                    <div style="flex: 1;">
                                                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; align-items: center;">
                                                            <strong style="color: var(--text-main); font-size: 13px;"><?php echo esc_html( $reply->comment_author ); ?> <span style="background: rgba(16,185,129,0.1); color: var(--color-success); padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; margin-left: 4px;">Teacher</span></strong>
                                                            <span style="color: var(--text-muted); font-size: 10.5px;"><?php echo get_comment_date( 'M j, Y g:i a', $reply->comment_ID ); ?></span>
                                                        </div>
                                                        <p style="color: var(--text-main); font-family: var(--font-khmer); line-height: 1.5; font-size: 13px; margin: 0; white-space: pre-wrap;"><?php echo esc_html( $reply->comment_content ); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar Outline & Progress -->
    <div class="classroom-sidebar-column" style="position: sticky; top: 30px;">
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

        <div class="curriculum-sidebar">
            <div class="curriculum-sidebar-header" style="padding: 16px 20px; font-weight: 700; font-size: 15px; color: var(--text-main); background: rgba(15,23,42,0.01);">
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
                        $sidebar_is_preview = get_post_meta( $sid, '_is_preview', true );
                        
                        $sidebar_is_locked = false;
                        if ( ! current_user_can( 'manage_options' ) && ! $sidebar_is_preview ) {
                            $drip_dependencies = get_post_meta( $course_id, '_drip_dependencies', true );
                            if ( is_array( $drip_dependencies ) && ! empty( $drip_dependencies ) ) {
                                foreach ( $drip_dependencies as $group ) {
                                    if ( isset( $group['parent_id'] ) && isset( $group['dependents'] ) && is_array( $group['dependents'] ) ) {
                                        if ( in_array( $sid, $group['dependents'] ) ) {
                                            $parent_id = intval( $group['parent_id'] );
                                            if ( ! in_array( $parent_id, $completed_lessons ) ) {
                                                $sidebar_is_locked = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $lock_lessons_order = get_post_meta( $course_id, '_lock_lessons_order', true );
                                if ( $lock_lessons_order === '1' ) {
                                    $sidebar_lesson_index = array_search( $sid, $lessons_order );
                                    if ( $sidebar_lesson_index !== false && $sidebar_lesson_index > 0 ) {
                                        for ( $i = 0; $i < $sidebar_lesson_index; $i++ ) {
                                            $prev_id = $lessons_order[$i];
                                            if ( ! in_array( $prev_id, $completed_lessons ) ) {
                                                $sidebar_is_locked = true;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $lurl = $sidebar_is_locked ? '#' : add_query_arg( 'course_id', $course_id, get_permalink( $sid ) );
                        $item_style = $sidebar_is_locked ? 'opacity: 0.6; cursor: not-allowed;' : '';
                        ?>
                        <a href="<?php echo $sidebar_is_locked ? '#' : esc_url( $lurl ); ?>" class="sidebar-lesson-item <?php echo $active ? 'active' : ''; ?>" style="<?php echo esc_attr( $item_style ); ?>">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php
                                $sidebar_lesson_info = reandaily_lms_get_lesson_type_and_icon( $sid );
                                ?>
                                <span style="font-family: var(--font-khmer); font-weight: <?php echo $active ? '600' : '400'; ?>; color: <?php echo $active ? 'var(--text-main)' : 'var(--text-muted)'; ?>; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="<?php echo esc_attr( $sidebar_lesson_info['fa_icon'] ); ?>" style="color: <?php echo $active ? 'var(--color-primary)' : 'var(--text-muted)'; ?>; width: 14px; text-align: center;"></i>
                                    <?php echo esc_html( $spost->post_title ); ?>
                                </span>
                            </div>
                            
                            <div>
                                <?php if ( $sidebar_is_locked ) : ?>
                                    <i class="fa-solid fa-lock" style="color: var(--text-muted); font-size: 14px;"></i>
                                <?php elseif ( $comp ) : ?>
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
    if (!btn || !txt) return;
    
    isLessonCompleted = !isLessonCompleted;

    if (isLessonCompleted) {
        btn.style.background = 'var(--color-success)';
        btn.style.borderColor = 'var(--color-success)';
        btn.style.color = '#ffffff';
        const icon = btn.querySelector('i');
        if (icon) icon.className = 'fa-solid fa-circle-check';
        txt.textContent = 'បានរៀនរួចរាល់';
    } else {
        btn.style.background = 'rgba(15,23,42,0.03)';
        btn.style.borderColor = 'var(--border-color)';
        btn.style.color = 'var(--text-main)';
        const icon = btn.querySelector('i');
        if (icon) icon.className = 'fa-regular fa-circle';
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
            const progressBar = document.getElementById('lms-progress-bar');
            const progressPercent = document.getElementById('lms-progress-percent');
            if (progressBar) progressBar.style.width = data.data.progress + '%';
            if (progressPercent) progressPercent.textContent = data.data.progress + '%';
            
            const sidebarItems = document.querySelectorAll('.sidebar-lesson-item');
            sidebarItems.forEach(item => {
                if (item.href.includes(`post_id=${CONFIG.lessonId}`) || item.href.includes(window.location.pathname)) {
                    const icon = item.querySelector('i');
                    if (icon) {
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
                }
            });
        }
    })
    .catch(err => console.error('Error updating progress:', err));
}

// Video Questions Player Tracking Integration
var ytPlayer = null;
var vimeoPlayer = null;
var nativeVideo = null;
var currentQuestions = typeof lmsVideoQuestions !== 'undefined' ? lmsVideoQuestions : [];
var triggeredQuestions = {};
var activeQuestionIndex = -1;

function initPlayerTracking() {
    nativeVideo = document.getElementById('lms-native-player');
    var ytIframe = document.getElementById('lms-youtube-player');
    var vimeoIframe = document.getElementById('lms-vimeo-player');

    if (nativeVideo) {
        nativeVideo.addEventListener('timeupdate', function() {
            checkVideoTime(nativeVideo.currentTime, function() {
                nativeVideo.pause();
            });
        });
    } else if (ytIframe) {
        if (!window.YT) {
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }

        function checkYT() {
            if (window.YT && window.YT.Player) {
                ytPlayer = new YT.Player('lms-youtube-player', {
                    events: {
                        'onStateChange': function(event) {
                            // YT state change
                        }
                    }
                });
                setInterval(function() {
                    if (ytPlayer && typeof ytPlayer.getPlayerState === 'function' && ytPlayer.getPlayerState() === 1) {
                        checkVideoTime(ytPlayer.getCurrentTime(), function() {
                            ytPlayer.pauseVideo();
                        });
                    }
                }, 500);
            } else {
                setTimeout(checkYT, 200);
            }
        }
        checkYT();
    } else if (vimeoIframe) {
        if (typeof Vimeo !== 'undefined' && Vimeo.Player) {
            vimeoPlayer = new Vimeo.Player(vimeoIframe);
            vimeoPlayer.on('timeupdate', function(data) {
                checkVideoTime(data.seconds, function() {
                    vimeoPlayer.pause();
                });
            });
        } else {
            var tag = document.createElement('script');
            tag.src = "https://player.vimeo.com/api/player.js";
            tag.onload = function() {
                vimeoPlayer = new Vimeo.Player(vimeoIframe);
                vimeoPlayer.on('timeupdate', function(data) {
                    checkVideoTime(data.seconds, function() {
                        vimeoPlayer.pause();
                    });
                });
            };
            document.head.appendChild(tag);
        }
    }
}

function checkVideoTime(currentTime, pauseCallback) {
    if (!currentQuestions || currentQuestions.length === 0) return;
    for (var i = 0; i < currentQuestions.length; i++) {
        var q = currentQuestions[i];
        var qTime = parseInt(q.time, 10);
        if (Math.abs(currentTime - qTime) <= 1 && !triggeredQuestions[i]) {
            triggeredQuestions[i] = true;
            activeQuestionIndex = i;
            pauseCallback();
            showVideoQuestion(q, i);
            break;
        }
    }
}

function showVideoQuestion(q, index) {
    var overlay = document.getElementById('lms-video-question-overlay');
    if (!overlay) return;
    var title = document.getElementById('lms-vq-title');
    var optionsContainer = document.getElementById('lms-vq-options');
    var feedback = document.getElementById('lms-vq-feedback');
    var submitBtn = document.getElementById('lms-vq-submit');

    title.textContent = q.question;
    optionsContainer.innerHTML = '';
    feedback.style.display = 'none';
    submitBtn.textContent = 'បញ្ជូន (Submit)';
    submitBtn.style.background = '#3b82f6';
    submitBtn.disabled = false;

    if (q.type === 'truefalse') {
        optionsContainer.innerHTML = `
            <label class="lms-vq-option">
                <input type="radio" name="vq_opt" value="true" style="margin: 0; width: 18px; height: 18px; accent-color: #3b82f6;">
                <span style="font-size: 15px; color: #e2e8f0; line-height: 1.4;">True</span>
            </label>
            <label class="lms-vq-option">
                <input type="radio" name="vq_opt" value="false" style="margin: 0; width: 18px; height: 18px; accent-color: #3b82f6;">
                <span style="font-size: 15px; color: #e2e8f0; line-height: 1.4;">False</span>
            </label>
        `;
    } else {
        var opts = q.options || [];
        opts.forEach(function(opt, optIdx) {
            var inputType = q.type === 'multi' ? 'checkbox' : 'radio';
            optionsContainer.innerHTML += `
                <label class="lms-vq-option">
                    <input type="${inputType}" name="vq_opt" value="${optIdx}" style="margin: 0; width: 18px; height: 18px; accent-color: #3b82f6;">
                    <span style="font-size: 15px; color: #e2e8f0; line-height: 1.4;">${opt}</span>
                </label>
            `;
        });
    }

    var optionLabels = optionsContainer.querySelectorAll('.lms-vq-option');
    optionLabels.forEach(function(lbl) {
        lbl.addEventListener('click', function() {
            if (q.type !== 'multi') {
                optionLabels.forEach(l => l.classList.remove('selected'));
            }
            var input = lbl.querySelector('input');
            if (input.type === 'radio') {
                lbl.classList.add('selected');
            } else {
                setTimeout(function() {
                    if (input.checked) {
                        lbl.classList.add('selected');
                    } else {
                        lbl.classList.remove('selected');
                    }
                }, 50);
            }
        });
    });

    overlay.style.display = 'flex';
    setTimeout(function() {
        overlay.querySelector('.lms-vq-card').style.transform = 'scale(1)';
    }, 50);
}

function resumeVideo() {
    if (nativeVideo) {
        nativeVideo.play();
    } else if (ytPlayer && typeof ytPlayer.playVideo === 'function') {
        ytPlayer.playVideo();
    } else if (vimeoPlayer && typeof vimeoPlayer.play === 'function') {
        vimeoPlayer.play();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initPlayerTracking();

    var vqSubmit = document.getElementById('lms-vq-submit');
    if (vqSubmit) {
        vqSubmit.addEventListener('click', function() {
            var q = currentQuestions[activeQuestionIndex];
            if (!q) return;

            var overlay = document.getElementById('lms-video-question-overlay');
            var feedback = document.getElementById('lms-vq-feedback');
            var submitBtn = this;

            if (submitBtn.textContent.includes('Continue') || submitBtn.textContent.includes('បន្ត')) {
                overlay.style.display = 'none';
                overlay.querySelector('.lms-vq-card').style.transform = 'scale(0.9)';
                resumeVideo();
                return;
            }

            var selected = [];
            var inputs = document.querySelectorAll('input[name="vq_opt"]:checked');
            inputs.forEach(function(input) {
                selected.push(input.value);
            });

            if (selected.length === 0) {
                alert('សូមជ្រើសរើសចម្លើយមួយ (Please select an answer)');
                return;
            }

            var isCorrect = false;
            if (q.type === 'truefalse') {
                var ansVal = q.answer === 'true' || q.answer === true;
                var selectedVal = selected[0] === 'true';
                isCorrect = (selectedVal === ansVal);
            } else if (q.type === 'single') {
                isCorrect = (parseInt(selected[0], 10) === parseInt(q.answer, 10));
            } else if (q.type === 'multi') {
                var correctAns = Array.isArray(q.answer) ? q.answer : [q.answer];
                var correctParsed = correctAns.map(v => parseInt(v, 10));
                var selectedParsed = selected.map(v => parseInt(v, 10));
                isCorrect = (correctParsed.length === selectedParsed.length && correctParsed.every(val => selectedParsed.indexOf(val) !== -1));
            }

            var optionLabels = document.querySelectorAll('.lms-vq-option');
            optionLabels.forEach(function(lbl) {
                var input = lbl.querySelector('input');
                var val = input.value;
                if (q.type === 'truefalse') {
                    var ansVal = q.answer === 'true' || q.answer === true;
                    var inputVal = val === 'true';
                    if (inputVal === ansVal) {
                        lbl.classList.add('correct');
                    } else if (input.checked) {
                        lbl.classList.add('incorrect');
                    }
                } else {
                    var valInt = parseInt(val, 10);
                    var isAns = false;
                    if (q.type === 'single') {
                        isAns = (valInt === parseInt(q.answer, 10));
                    } else {
                        var correctAns = Array.isArray(q.answer) ? q.answer : [q.answer];
                        isAns = correctAns.map(v => parseInt(v, 10)).indexOf(valInt) !== -1;
                    }
                    if (isAns) {
                        lbl.classList.add('correct');
                    } else if (input.checked) {
                        lbl.classList.add('incorrect');
                    }
                }
            });

            feedback.style.display = 'block';
            if (isCorrect) {
                feedback.innerHTML = '<span style="color: #10b981; font-family: var(--font-khmer-heading);">✓ ត្រឹមត្រូវ! (Correct!)</span>';
            } else {
                var correctLabel = '';
                if (q.type === 'truefalse') {
                    correctLabel = (q.answer === 'true' || q.answer === true) ? 'True' : 'False';
                } else if (q.type === 'single') {
                    correctLabel = q.options[parseInt(q.answer, 10)] || '';
                } else {
                    var correctAns = Array.isArray(q.answer) ? q.answer : [q.answer];
                    correctLabel = correctAns.map(idx => q.options[parseInt(idx, 10)]).join(', ');
                }
                feedback.innerHTML = '<span style="color: #ef4444; font-family: var(--font-khmer-heading);">✗ មិនត្រឹមត្រូវទេ! (Incorrect!)</span><br><span style="font-size:12px; color:#94a3b8;">ចម្លើយត្រឹមត្រូវគឺ៖ ' + correctLabel + '</span>';
            }

            submitBtn.textContent = 'បន្តទស្សនា (Continue)';
            submitBtn.style.background = '#10b981';
        });
    }

    // Tab switching
    const frontTabs = document.querySelectorAll('.lms-frontend-tab');
    frontTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            frontTabs.forEach(t => {
                t.classList.remove('active');
                t.style.color = 'var(--text-muted)';
                t.style.borderBottomColor = 'transparent';
            });
            this.classList.add('active');
            this.style.color = 'var(--color-primary)';
            this.style.borderBottomColor = 'var(--color-primary)';
            
            const target = this.getAttribute('data-target');
            document.querySelectorAll('.lms-front-tab-pane').forEach(p => p.style.display = 'none');
            const targetPane = document.querySelector(target);
            if (targetPane) targetPane.style.display = 'block';
        });
    });

    // Question Submission
    const submitQuestionBtn = document.getElementById('lms-btn-submit-student-question');
    if (submitQuestionBtn) {
        submitQuestionBtn.addEventListener('click', function() {
            const textarea = document.getElementById('lms-student-question-textarea');
            if (!textarea) return;
            const content = textarea.value.trim();
            
            if (content === '') {
                alert('សូមសរសេរសំណួររបស់អ្នកជាមុនសិន។ (Please type your question first.)');
                textarea.focus();
                return;
            }
            
            submitQuestionBtn.textContent = 'កំពុងបញ្ជូន... (Submitting...)';
            submitQuestionBtn.disabled = true;
            
            const fd = new FormData();
            fd.append('action', 'reandaily_lms_post_student_question');
            fd.append('lesson_id', CONFIG.lessonId);
            fd.append('content', content);
            fd.append('security', CONFIG.nonce);
            
            fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(response => {
                submitQuestionBtn.textContent = 'បញ្ជូនសំណួរ (Submit Question)';
                submitQuestionBtn.disabled = false;
                
                if (response.success) {
                    textarea.value = '';
                    const notice = document.getElementById('lms-no-questions-notice');
                    if (notice) notice.remove();
                    
                    const newQ = `
                        <div class="frontend-question-card" style="background: var(--bg-card); border-radius: var(--border-radius-md); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px;">
                            <div style="display: flex; gap: 14px; align-items: flex-start;">
                                <img src="${response.data.avatar_url}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; align-items: center;">
                                        <strong style="color: var(--text-main); font-size: 14.5px;">${response.data.author}</strong>
                                        <span style="color: var(--text-muted); font-size: 11px;">${response.data.date}</span>
                                    </div>
                                    <p style="color: var(--text-main); font-family: var(--font-khmer); line-height: 1.6; font-size: 14px; margin: 0 0 16px 0; white-space: pre-wrap;">${response.data.content}</p>
                                    
                                    <div class="frontend-replies-list" style="padding-top: 16px; display: flex; flex-direction: column; gap: 12px;">
                                        <span style="color: var(--text-muted); font-size: 12px; font-style: italic; display: block;"><i class="fa-regular fa-clock"></i> រង់ចាំគ្រូឆ្លើយតប (Awaiting instructor response...)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    const container = document.getElementById('lms-frontend-qa-list');
                    if (container) {
                        container.insertAdjacentHTML('afterbegin', newQ);
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(response.data || 'Failed to submit question.');
                }
            })
            .catch(err => {
                submitQuestionBtn.textContent = 'បញ្ជូនសំណួរ (Submit Question)';
                submitQuestionBtn.disabled = false;
                console.error(err);
                alert('Error submitting question.');
            });
        });
    }

    // Theme Toggle
    const themeToggleBtn = document.getElementById('lms-lesson-theme-toggle');
    if (themeToggleBtn) {
        let savedTheme = 'light';
        try {
            savedTheme = localStorage.getItem('lms_lesson_theme') || 'light';
        } catch (e) {
            console.warn('localStorage is not accessible');
        }

        if (savedTheme === 'dark') {
            document.body.classList.add('lms-dark-mode');
            const icon = themeToggleBtn.querySelector('i');
            const span = themeToggleBtn.querySelector('span');
            if (icon) icon.className = 'fa-regular fa-sun';
            if (span) span.textContent = 'Light Mode';
        }

        themeToggleBtn.addEventListener('click', function() {
            const isDark = document.body.classList.toggle('lms-dark-mode');
            try {
                localStorage.setItem('lms_lesson_theme', isDark ? 'dark' : 'light');
            } catch (e) {
                console.warn('localStorage set failed');
            }
            
            const icon = themeToggleBtn.querySelector('i');
            const label = themeToggleBtn.querySelector('span');
            if (icon && label) {
                if (isDark) {
                    icon.className = 'fa-regular fa-sun';
                    label.textContent = 'Light Mode';
                } else {
                    icon.className = 'fa-regular fa-moon';
                    label.textContent = 'Dark Mode';
                }
            }
        });
    }

    // Sidebar Hide/Show Toggle
    const sidebarToggleBtn = document.getElementById('lms-lesson-sidebar-toggle');
    const classroomWrap = document.querySelector('.classroom-wrap');
    if (sidebarToggleBtn && classroomWrap) {
        let savedSidebarState = 'visible';
        try {
            savedSidebarState = localStorage.getItem('lms_lesson_sidebar') || 'visible';
        } catch (e) {
            console.warn('localStorage is not accessible');
        }

        if (savedSidebarState === 'hidden') {
            classroomWrap.classList.add('sidebar-hidden');
            const icon = sidebarToggleBtn.querySelector('i');
            const span = sidebarToggleBtn.querySelector('span');
            if (icon) icon.className = 'fa-solid fa-columns';
            if (span) span.textContent = 'Show Sidebar';
        }

        sidebarToggleBtn.addEventListener('click', function() {
            const isHidden = classroomWrap.classList.toggle('sidebar-hidden');
            try {
                localStorage.setItem('lms_lesson_sidebar', isHidden ? 'hidden' : 'visible');
            } catch (e) {
                console.warn('localStorage set failed');
            }
            
            const icon = sidebarToggleBtn.querySelector('i');
            const label = sidebarToggleBtn.querySelector('span');
            if (icon && label) {
                if (isHidden) {
                    label.textContent = 'Show Sidebar';
                } else {
                    label.textContent = 'Hide Sidebar';
                }
            }
        });
    }

    // Interactive Quiz Player
    const quizData = window.LMS_QUIZ_DATA;
    if (quizData && quizData.questions && quizData.questions.length > 0) {
        const startScreen = document.getElementById('lms-quiz-start-screen');
        const activeScreen = document.getElementById('lms-quiz-active-screen');
        const resultsScreen = document.getElementById('lms-quiz-results-screen');
        const btnStart = document.getElementById('lms-btn-start-quiz');
        const btnPrev = document.getElementById('lms-btn-quiz-prev');
        const btnNext = document.getElementById('lms-btn-quiz-next');
        const btnRetry = document.getElementById('lms-btn-quiz-retry');
        const btnContinue = document.getElementById('lms-btn-quiz-continue');
        
        let currentQuestionIdx = 0;
        let userAnswers = {}; // { qIdx: selectedOptionIdx or [optIdxs] or bool }
        let timerInterval = null;
        let timeLeft = 0; // in seconds
        let retakesCount = 0;

        // Try load retakes count from localStorage
        try {
            retakesCount = parseInt(localStorage.getItem('lms_quiz_retakes_' + <?php echo $lesson_id; ?>) || '0');
        } catch (e) {}

        if (btnStart) btnStart.addEventListener('click', startQuiz);
        if (btnPrev) btnPrev.addEventListener('click', prevQuestion);
        if (btnNext) btnNext.addEventListener('click', nextQuestion);
        if (btnRetry) btnRetry.addEventListener('click', retryQuiz);
        if (btnContinue) {
            btnContinue.addEventListener('click', function() {
                const completeBtn = document.getElementById('lms-btn-complete-lesson');
                if (completeBtn) {
                    completeBtn.click();
                }
            });
        }

        function startQuiz() {
            startScreen.style.display = 'none';
            activeScreen.style.display = 'flex';
            resultsScreen.style.display = 'none';
            
            currentQuestionIdx = 0;
            userAnswers = {};
            
            // Set total count
            document.getElementById('lms-quiz-total-q-num').textContent = quizData.questions.length;
            
            // Start Timer if configured
            if (quizData.timeLimit > 0) {
                timeLeft = quizData.timeLimit * 60;
                updateTimerDisplay();
                clearInterval(timerInterval);
                timerInterval = setInterval(function() {
                    timeLeft--;
                    updateTimerDisplay();
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        submitQuiz();
                    }
                }, 1000);
            }
            
            loadQuestion();
        }

        function updateTimerDisplay() {
            const timerSpan = document.getElementById('lms-quiz-timer');
            if (!timerSpan) return;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerSpan.textContent = 
                (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        }

        function loadQuestion() {
            const q = quizData.questions[currentQuestionIdx];
            document.getElementById('lms-quiz-current-q-num').textContent = currentQuestionIdx + 1;
            
            // Update Progress Bar
            const percent = ((currentQuestionIdx) / quizData.questions.length) * 100;
            document.getElementById('lms-quiz-progress-bar').style.width = percent + '%';
            
            // Title
            document.getElementById('lms-quiz-question-title').textContent = q.question;

            // Question Image
            const imgWrapper = document.getElementById('lms-quiz-question-image-wrapper');
            if (imgWrapper) {
                imgWrapper.innerHTML = '';
                if (q.image) {
                    const img = document.createElement('img');
                    img.src = q.image;
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '300px';
                    img.style.objectFit = 'contain';
                    img.style.borderRadius = '8px';
                    img.style.border = '1px solid var(--border-color)';
                    imgWrapper.appendChild(img);
                    imgWrapper.style.display = 'block';
                } else {
                    imgWrapper.style.display = 'none';
                }
            }
            
            // Options
            const container = document.getElementById('lms-quiz-options-container');
            container.innerHTML = '';
            
            if (q.type === 'truefalse') {
                const options = [
                    { label: 'True (ពិត)', value: true },
                    { label: 'False (មិនពិត)', value: false }
                ];
                options.forEach(opt => {
                    const isSelected = userAnswers[currentQuestionIdx] === opt.value;
                    const card = createOptionCard(opt.label, 'tf_opt', opt.value, isSelected);
                    container.appendChild(card);
                });
            } else {
                q.options.forEach((optVal, optIdx) => {
                    let isSelected = false;
                    if (q.type === 'single') {
                        isSelected = parseInt(userAnswers[currentQuestionIdx]) === optIdx;
                    } else if (q.type === 'multi') {
                        isSelected = Array.isArray(userAnswers[currentQuestionIdx]) && userAnswers[currentQuestionIdx].includes(optIdx);
                    }
                    const inputType = q.type === 'single' ? 'radio' : 'checkbox';
                    const card = createOptionCard(optVal, inputType, optIdx, isSelected);
                    container.appendChild(card);
                });
            }
            
            // Update Nav buttons
            btnPrev.disabled = currentQuestionIdx === 0;
            if (currentQuestionIdx === quizData.questions.length - 1) {
                btnNext.textContent = 'បញ្ចប់ការធ្វើតេស្ត (Submit)';
            } else {
                btnNext.textContent = 'បន្ទាប់ (Next)';
            }
        }

        function createOptionCard(opt, inputType, value, isSelected) {
            const label = document.createElement('label');
            label.style.display = 'flex';
            label.style.alignItems = 'center';
            label.style.gap = '12px';
            label.style.padding = '14px 18px';
            label.style.borderRadius = '8px';
            label.style.border = isSelected ? '2px solid var(--color-secondary)' : '1px solid var(--border-color)';
            label.style.background = isSelected ? 'rgba(255, 184, 0, 0.05)' : 'rgba(255, 255, 255, 0.02)';
            label.style.cursor = 'pointer';
            label.style.transition = 'all 0.15s ease';
            label.style.fontSize = '14.5px';
            label.style.fontFamily = 'var(--font-khmer)';
            
            const input = document.createElement('input');
            input.type = inputType === 'tf_opt' ? 'radio' : inputType;
            input.name = 'quiz_opt';
            input.value = value;
            input.checked = isSelected;
            input.style.margin = '0';
            input.style.accentColor = 'var(--color-secondary)';
            
            input.addEventListener('change', function() {
                const q = quizData.questions[currentQuestionIdx];
                if (q.type === 'single') {
                    userAnswers[currentQuestionIdx] = parseInt(value);
                } else if (q.type === 'multi') {
                    if (!Array.isArray(userAnswers[currentQuestionIdx])) {
                        userAnswers[currentQuestionIdx] = [];
                    }
                    if (this.checked) {
                        userAnswers[currentQuestionIdx].push(parseInt(value));
                    } else {
                        const idx = userAnswers[currentQuestionIdx].indexOf(parseInt(value));
                        if (idx !== -1) userAnswers[currentQuestionIdx].splice(idx, 1);
                    }
                } else if (q.type === 'truefalse') {
                    userAnswers[currentQuestionIdx] = (value === 'true' || value === true);
                }
                
                // Refresh selection borders
                Array.from(label.parentNode.children).forEach(child => {
                    const isChildSelected = child.querySelector('input').checked;
                    child.style.border = isChildSelected ? '2px solid var(--color-secondary)' : '1px solid var(--border-color)';
                    child.style.background = isChildSelected ? 'rgba(255, 184, 0, 0.05)' : 'rgba(255, 255, 255, 0.02)';
                });
            });
            
            label.appendChild(input);

            // Handle option content (text or image)
            const contentContainer = document.createElement('div');
            contentContainer.style.display = 'flex';
            contentContainer.style.alignItems = 'center';
            contentContainer.style.gap = '10px';
            contentContainer.style.flex = '1';

            if (typeof opt === 'object' && opt !== null && opt.is_image) {
                if (opt.image) {
                    const img = document.createElement('img');
                    img.src = opt.image;
                    img.style.maxWidth = '150px';
                    img.style.maxHeight = '150px';
                    img.style.objectFit = 'contain';
                    img.style.borderRadius = '4px';
                    contentContainer.appendChild(img);
                } else {
                    const noImgText = document.createElement('span');
                    noImgText.textContent = '(No image selected)';
                    noImgText.style.fontStyle = 'italic';
                    noImgText.style.color = 'var(--text-muted)';
                    contentContainer.appendChild(noImgText);
                }
            } else {
                const textVal = (typeof opt === 'object' && opt !== null) ? opt.text : opt;
                const textSpan = document.createElement('span');
                textSpan.textContent = textVal;
                contentContainer.appendChild(textSpan);
            }

            label.appendChild(contentContainer);
            return label;
        }

        function prevQuestion() {
            if (currentQuestionIdx > 0) {
                currentQuestionIdx--;
                loadQuestion();
            }
        }

        function nextQuestion() {
            if (currentQuestionIdx < quizData.questions.length - 1) {
                currentQuestionIdx++;
                loadQuestion();
            } else {
                submitQuiz();
            }
        }

        function submitQuiz() {
            clearInterval(timerInterval);
            activeScreen.style.display = 'none';
            resultsScreen.style.display = 'block';
            
            // Calculate Score
            let correctCount = 0;
            quizData.questions.forEach((q, qIdx) => {
                const userAns = userAnswers[qIdx];
                if (q.type === 'truefalse') {
                    if (userAns === q.answer) correctCount++;
                } else if (q.type === 'single') {
                    if (parseInt(userAns) === parseInt(q.answer)) correctCount++;
                } else if (q.type === 'multi') {
                    const sortedUser = Array.isArray(userAns) ? [...userAns].sort() : [];
                    const sortedCorrect = Array.isArray(q.answer) ? [...q.answer].sort() : [];
                    if (JSON.stringify(sortedUser) === JSON.stringify(sortedCorrect)) correctCount++;
                }
            });
            
            const scorePercentage = Math.round((correctCount / quizData.questions.length) * 100);
            const passed = scorePercentage >= quizData.passingGrade;
            
            // Render Result
            const iconContainer = document.getElementById('lms-quiz-result-icon-container');
            const resultTitle = document.getElementById('lms-quiz-result-title');
            const scoreText = document.getElementById('lms-quiz-result-score-text');
            const desc = document.getElementById('lms-quiz-result-desc');
            
            scoreText.textContent = 'ពិន្ទុរបស់អ្នក (Your Score): ' + scorePercentage + '%';
            
            if (passed) {
                iconContainer.style.background = 'rgba(16, 185, 129, 0.1)';
                iconContainer.innerHTML = '<i class="fa-solid fa-circle-check" style="font-size: 36px; color: #10b981;"></i>';
                resultTitle.textContent = ' អបអរសាទរ! អ្នកបានជាប់តេស្តហើយ! (Passed!)';
                resultTitle.style.color = '#10b981';
                desc.textContent = 'អបអរសាទរ! អ្នកបានឆ្លងកាត់ការធ្វើតេស្តនេះដោយជោគជ័យ។ (Congratulations, you successfully passed this quiz.)';
                
                btnRetry.style.display = 'none';
                btnContinue.style.display = 'inline-flex';
                
                triggerCompletion();
            } else {
                iconContainer.style.background = 'rgba(239, 68, 68, 0.1)';
                iconContainer.innerHTML = '<i class="fa-solid fa-circle-xmark" style="font-size: 36px; color: #ef4444;"></i>';
                resultTitle.textContent = 'សោកស្តាយ! អ្នកមិនបានជាប់ទេ! (Failed!)';
                resultTitle.style.color = '#ef4444';
                
                retakesCount++;
                try {
                    localStorage.setItem('lms_quiz_retakes_' + <?php echo $lesson_id; ?>, retakesCount.toString());
                } catch (e) {}
                
                const remainingRetakes = quizData.retakes > 0 ? (quizData.retakes - retakesCount) : null;
                
                if (remainingRetakes === null || remainingRetakes > 0) {
                    desc.innerHTML = 'អ្នកមិនបានឆ្លងកាត់ពិន្ទុជាប់ ' + quizData.passingGrade + '% ទេ។ សូមព្យាយាមម្តងទៀត។<br>' + 
                        (remainingRetakes !== null ? '(ឱកាសព្យាយាមនៅសល់៖ <strong>' + remainingRetakes + '</strong> ដង)' : '(ព្យាយាមម្តងទៀតបានមិនកំណត់)');
                    btnRetry.style.display = 'inline-flex';
                } else {
                    desc.textContent = 'អ្នកមិនបានឆ្លងកាត់ពិន្ទុជាប់ ' + quizData.passingGrade + '% ទេ ហើយអ្នកបានអស់ឱកាសព្យាយាមហើយ។ សូមទាក់ទងគ្រូបង្គោលរបស់អ្នក។';
                    btnRetry.style.display = 'none';
                }
                btnContinue.style.display = 'none';
            }
        }

        function triggerCompletion() {
            const completeBtn = document.getElementById('lms-btn-complete-lesson');
            if (completeBtn && !completeBtn.classList.contains('completed')) {
                completeBtn.click();
            }
        }

        function retryQuiz() {
            startQuiz();
        }
    }
});
</script>

<?php
get_footer();
?>
