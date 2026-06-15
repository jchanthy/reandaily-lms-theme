<?php
/**
 * Template Name: Student Dashboard
 *
 * Displays active courses, completion percentages, and status alerts.
 */
get_header();

// Security Check: Redirect to login if user is logged out
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();

// Fetch enrollments from custom table
global $wpdb;
$table_name = $wpdb->prefix . 'reandaily_lms';
$enrollments = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
    $user_id
) );
?>

<style>
    .dashboard-wrap {
        padding: 60px 24px;
        min-height: 70vh;
    }

    .welcome-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: var(--border-radius-md);
        padding: 32px;
        display: flex;
        align-items: center;
        gap: 24px;
        margin-bottom: 48px;
        box-shadow: var(--shadow-md);
    }

    .welcome-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--color-primary);
    }

    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 32px;
    }

    .dashboard-course-card {
        background-color: var(--bg-card);
        border: 1px solid transparent;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: var(--shadow-sm);
        transition: var(--transition-normal);
    }

    .dashboard-course-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .badge-status {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 4px;
        text-transform: uppercase;
        width: fit-content;
    }

    .badge-active {
        background: rgba(16, 185, 129, 0.15);
        color: var(--color-success);
        border: 1px solid rgba(16, 185, 129, 0.25);
    }

    .badge-pending {
        background: rgba(245, 158, 11, 0.15);
        color: var(--color-warning);
        border: 1px solid rgba(245, 158, 11, 0.25);
    }

    .badge-completed {
        background: rgba(56, 189, 248, 0.15);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.25);
    }

    .progress-bar-bg {
        background: rgba(255, 255, 255, 0.05);
        height: 8px;
        border-radius: 50px;
        overflow: hidden;
        margin-top: 6px;
    }

    .progress-bar-fill {
        background: linear-gradient(90deg, var(--color-success), #10b981);
        height: 100%;
        transition: width 0.5s ease;
    }

    .btn-dashboard-action {
        display: block;
        text-align: center;
        width: 100%;
        padding: 12px;
        font-weight: 700;
        font-size: 14.5px;
        border-radius: var(--border-radius-sm);
        transition: var(--transition-fast);
        margin-top: 24px;
    }

    .btn-resume {
        background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(229, 47, 46, 0.15);
    }

    .btn-resume:hover {
        box-shadow: 0 6px 16px rgba(229, 47, 46, 0.25);
    }

    .btn-disabled {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        border: 1px solid var(--border-color);
        cursor: not-allowed;
    }
</style>

<div class="container dashboard-wrap">
    
    <!-- Welcome Profile Banner -->
    <div class="welcome-card">
        <img src="<?php echo esc_url( get_avatar_url( $user_id ) ); ?>" alt="Avatar" class="welcome-avatar">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: #ffffff;"><?php echo esc_html( $current_user->display_name ); ?></h1>
            <p style="color: #94a3b8; font-size: 14px; margin-top: 4px; font-family: var(--font-khmer);">
                សមាជិកសិក្សា ReanDaily តាំងពី៖ <?php echo date_i18n( get_option( 'date_format' ), strtotime( $current_user->user_registered ) ); ?>
            </p>
        </div>
    </div>

    <!-- Active Courses Section -->
    <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 28px; border-bottom: 2px solid var(--border-color); padding-bottom: 12px;">
        <i class="fa-solid fa-graduation-cap" style="color: var(--color-primary); margin-right: 10px;"></i> វគ្គសិក្សារបស់ខ្ញុំ (My Courses)
    </h2>

    <div class="course-grid">
        <?php if ( ! empty( $enrollments ) ) : ?>
            <?php foreach ( $enrollments as $enroll ) : 
                $course_id = intval( $enroll->course_id );
                $course    = get_post( $course_id );
                if ( ! $course || $course->post_status !== 'publish' ) continue;

                $progress = reandaily_lms_get_progress( $user_id, $course_id );
                $lessons_order = get_post_meta( $course_id, '_lessons_order', true );
                
                // Get URL of first or next incomplete lesson
                $resume_url = '';
                if ( ! empty( $lessons_order ) && is_array( $lessons_order ) ) {
                    $resume_url = add_query_arg( 'course_id', $course_id, get_permalink( $lessons_order[0] ) );
                    
                    // Attempt to locate first incomplete lesson to resume
                    $completed_lessons = json_decode( $enroll->completed_lessons, true );
                    if ( is_array( $completed_lessons ) ) {
                        foreach ( $lessons_order as $lesson_id ) {
                            if ( ! in_array( $lesson_id, $completed_lessons ) ) {
                                $resume_url = add_query_arg( 'course_id', $course_id, get_permalink( $lesson_id ) );
                                break;
                            }
                        }
                    }
                }
                ?>
                <div class="dashboard-course-card">
                    <!-- Thumbnail -->
                    <div style="position: relative; padding-top: 56.25%; background-color: #0b0f19;">
                        <?php if ( has_post_thumbnail( $course_id ) ) : ?>
                            <img src="<?php echo esc_url( get_the_post_thumbnail_url( $course_id, 'medium_large' ) ); ?>" alt="Course Thumbnail" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;">
                        <?php else : ?>
                            <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#1e2640; color:var(--text-muted);">
                                <i class="fa-solid fa-graduation-cap" style="font-size: 48px;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Enrollment Status Badge -->
                        <div style="position: absolute; top: 16px; left: 16px;">
                            <?php if ( $enroll->status === 'active' ) : ?>
                                <span class="badge-status badge-active">Active Learning</span>
                            <?php elseif ( $enroll->status === 'pending' ) : ?>
                                <span class="badge-status badge-pending">Pending Approval</span>
                            <?php elseif ( $enroll->status === 'completed' ) : ?>
                                <span class="badge-status badge-completed">Completed</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Content -->
                    <div style="padding: 24px; display: flex; flex-direction: column; flex-grow: 1;">
                        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px; font-family: var(--font-khmer-heading); line-height: 1.4;">
                            <a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" style="color: #ffffff;"><?php echo esc_html( $course->post_title ); ?></a>
                        </h3>
                        
                        <!-- Progress Tracking block -->
                        <?php if ( $enroll->status === 'active' || $enroll->status === 'completed' ) : ?>
                            <div style="margin-top: auto;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted);">
                                    <span>មេរៀនសិក្សារួច</span>
                                    <span><?php echo $progress; ?>% Completed</span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                                
                                <a href="<?php echo esc_url( $resume_url ); ?>" class="btn-dashboard-action btn-resume">
                                    <i class="fa-solid fa-play" style="margin-right: 8px;"></i> ចូលរៀនបន្ត (Resume Study)
                                </a>
                            </div>
                        <?php elseif ( $enroll->status === 'pending' ) : ?>
                            <div style="margin-top: auto;">
                                <div style="display: flex; align-items: center; gap: 10px; background: rgba(245, 158, 11, 0.05); padding: 12px; border-radius: var(--border-radius-sm); border: 1px solid rgba(245, 158, 11, 0.1); color: var(--color-warning); font-size: 13px;">
                                    <i class="fa-regular fa-clock" style="font-size: 16px;"></i>
                                    <span style="font-family: var(--font-khmer); line-height: 1.5;">កំពុងពិនិត្យវិក្កយបត្របង់ប្រាក់។ ការសិក្សានឹងបើកជូនឆាប់ៗនេះ។</span>
                                </div>
                                
                                <span class="btn-dashboard-action btn-disabled">
                                    <i class="fa-solid fa-lock" style="margin-right: 8px;"></i> រង់ចាំការផ្ទៀងផ្ទាត់
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else : ?>
            <!-- Empty state -->
            <div style="grid-column: 1 / -1; background-color: var(--bg-card); border: 1px dashed #e2e8f0; border-radius: var(--border-radius-md); padding: 80px 24px; text-align: center; color: var(--text-muted);">
                <i class="fa-regular fa-folder-open" style="font-size: 64px; color: var(--color-primary); margin-bottom: 20px;"></i>
                <h3 style="font-size: 22px; color: #ffffff; margin-bottom: 8px;"><?php _e( 'មិនទាន់មានវគ្គសិក្សានៅឡើយទេ', 'reandaily-lms-theme' ); ?></h3>
                <p style="font-size: 15px; margin-bottom: 30px;"><?php _e( 'You haven\'t enrolled in any courses yet. Browse catalog to start learning!', 'reandaily-lms-theme' ); ?></p>
                <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover)); color: #ffffff; padding: 14px 32px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 15px; display: inline-block;">
                    រុករកវគ្គសិក្សាទាំងអស់
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
