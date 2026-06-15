<?php
/**
 * ReanDaily LMS Theme Functions
 *
 * @package ReanDaily_LMS
 */

// ── 1. THEME SUPPORT & ASSETS ────────────────────────────────────────────────
function reandaily_lms_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
}
add_action( 'after_setup_theme', 'reandaily_lms_setup' );

function reandaily_lms_enqueue_assets() {
    // Google Fonts (Inter, Outfit, Kantumruy & Kantumruy Pro for Khmer)
    wp_enqueue_style( 'reandaily-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;700;800&family=Kantumruy:wght@300;400;700&family=Kantumruy+Pro:wght@500;700&display=swap', array(), null );
    
    // Main Stylesheet
    wp_enqueue_style( 'reandaily-style', get_stylesheet_uri(), array(), '1.1.6' );

    // FontAwesome for UI icons
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );

    // Load QRCode.js library for checkout page
    if ( is_page_template( 'page-enroll.php' ) || is_page( 'enroll' ) ) {
        wp_enqueue_script( 'qrcode-js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', array(), '1.0.0', true );
    }
}
add_action( 'wp_enqueue_scripts', 'reandaily_lms_enqueue_assets' );


// ── 2. DATABASE AUTO-SETUP (theme switch) ──────────────────────────────────
function reandaily_lms_create_db_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reandaily_lms';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        course_id bigint(20) NOT NULL,
        status varchar(50) DEFAULT 'pending' NOT NULL,
        completed_lessons text DEFAULT '' NOT NULL,
        bill_number varchar(100) DEFAULT '' NOT NULL,
        payment_method varchar(50) DEFAULT '' NOT NULL,
        receipt_img varchar(255) DEFAULT '' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_course (user_id, course_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
add_action( 'after_switch_theme', 'reandaily_lms_create_db_table' );

function reandaily_lms_create_required_pages() {
    // Enroll Page
    $enroll_page = get_page_by_path( 'enroll' );
    if ( ! $enroll_page ) {
        $enroll_id = wp_insert_post( array(
            'post_title'   => 'Enroll',
            'post_name'    => 'enroll',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
        if ( $enroll_id ) {
            update_post_meta( $enroll_id, '_wp_page_template', 'page-enroll.php' );
        }
    } else {
        update_post_meta( $enroll_page->ID, '_wp_page_template', 'page-enroll.php' );
    }

    // Dashboard Page
    $dashboard_page = get_page_by_path( 'dashboard' );
    if ( ! $dashboard_page ) {
        $dashboard_id = wp_insert_post( array(
            'post_title'   => 'Dashboard',
            'post_name'    => 'dashboard',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
        if ( $dashboard_id ) {
            update_post_meta( $dashboard_id, '_wp_page_template', 'page-dashboard.php' );
        }
    } else {
        update_post_meta( $dashboard_page->ID, '_wp_page_template', 'page-dashboard.php' );
    }
}
add_action( 'admin_init', 'reandaily_lms_create_required_pages' );

function reandaily_lms_install_demo_data() {
    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['reandaily_install_demo'] ) ) {
        // Create Bakong Test Course
        $existing = get_page_by_path( 'bakong-test-course-real-bank-money', OBJECT, 'courses' );
        if ( ! $existing ) {
            $course_id = wp_insert_post( array(
                'post_title'   => 'Bakong Test Course (Real Bank Money)',
                'post_content' => 'This course is created for testing real money transactions using Bakong KHQR. It is priced at $0.10 (USD) or 400 Riels (KHR).',
                'post_status'  => 'publish',
                'post_type'    => 'courses'
            ) );

            if ( ! is_wp_error( $course_id ) ) {
                update_post_meta( $course_id, '_price', '0.10' );
                update_post_meta( $course_id, '_price_khr', '400' );
                update_post_meta( $course_id, '_duration', '1 Hour' );
                update_post_meta( $course_id, '_level', 'Beginner' );

                // Create lessons
                $lesson_ids = array();
                for ( $i = 1; $i <= 2; $i++ ) {
                    $lesson_id = wp_insert_post( array(
                        'post_title'   => "Lesson $i: Introduction to KHQR Payment",
                        'post_content' => "This is lesson $i content. In this lesson, we will cover how to scan and verify payment.",
                        'post_status'  => 'publish',
                        'post_type'    => 'lessons'
                    ) );
                    if ( ! is_wp_error( $lesson_id ) ) {
                        update_post_meta( $lesson_id, '_duration', '15 mins' );
                        if ( $i == 1 ) {
                            update_post_meta( $lesson_id, '_is_preview', '1' );
                        }
                        $lesson_ids[] = $lesson_id;
                    }
                }
                update_post_meta( $course_id, '_lessons_order', $lesson_ids );
            }
        }
        
        wp_redirect( admin_url( 'edit.php?post_type=courses' ) );
        exit;
    }
}
add_action( 'admin_init', 'reandaily_lms_install_demo_data' );


// ── 3. REGISTER CUSTOM POST TYPES ───────────────────────────────────────────
function reandaily_lms_register_cpts() {
    // Courses Custom Post Type
    register_post_type( 'courses', array(
        'labels' => array(
            'name'          => __( 'Courses', 'reandaily-lms-theme' ),
            'singular_name' => __( 'Course', 'reandaily-lms-theme' ),
            'add_new'       => __( 'Add New Course', 'reandaily-lms-theme' ),
            'edit_item'     => __( 'Edit Course', 'reandaily-lms-theme' ),
            'all_items'     => __( 'All Courses', 'reandaily-lms-theme' ),
        ),
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'menu_icon'   => 'dashicons-welcome-learn-more',
        'rewrite'     => array( 'slug' => 'courses' ),
        'show_in_rest'=> true,
    ) );

    // Lessons Custom Post Type
    register_post_type( 'lessons', array(
        'labels' => array(
            'name'          => __( 'Lessons', 'reandaily-lms-theme' ),
            'singular_name' => __( 'Lesson', 'reandaily-lms-theme' ),
            'add_new'       => __( 'Add New Lesson', 'reandaily-lms-theme' ),
            'edit_item'     => __( 'Edit Lesson', 'reandaily-lms-theme' ),
            'all_items'     => __( 'All Lessons', 'reandaily-lms-theme' ),
        ),
        'public'      => true,
        'has_archive' => false,
        'supports'    => array( 'title', 'editor', 'thumbnail' ),
        'menu_icon'   => 'dashicons-playlist-video',
        'rewrite'     => array( 'slug' => 'lessons' ),
        'show_in_rest'=> true,
    ) );
}
add_action( 'init', 'reandaily_lms_register_cpts' );


// ── 4. CUSTOMIZER REGISTER SETTINGS ──────────────────────────────────────────
function reandaily_lms_customize_register( $wp_customize ) {
    // Add Payment Gateway Section
    $wp_customize->add_section( 'reandaily_lms_payment_section', array(
        'title'    => __( 'LMS KHQR & Bank Settings', 'reandaily-lms-theme' ),
        'priority' => 30,
    ) );

    // ABA PayWay Link
    $wp_customize->add_setting( 'reandaily_aba_payway_link', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( 'reandaily_aba_payway_link_control', array(
        'label'    => __( 'ABA PayWay Link', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_aba_payway_link',
        'type'     => 'url'
    ) );

    // ABA Bakong ID
    $wp_customize->add_setting( 'reandaily_aba_bakong_id', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'reandaily_aba_bakong_id_control', array(
        'label'    => __( 'ABA Bakong ID (e.g. name@aba)', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_aba_bakong_id',
        'type'     => 'text'
    ) );

    // Merchant Name
    $wp_customize->add_setting( 'reandaily_aba_merchant_name', array( 'default' => 'ReanDaily', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'reandaily_aba_merchant_name_control', array(
        'label'    => __( 'Merchant Name (On QR)', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_aba_merchant_name',
        'type'     => 'text'
    ) );

    // Merchant City
    $wp_customize->add_setting( 'reandaily_aba_merchant_city', array( 'default' => 'Phnom Penh', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'reandaily_aba_merchant_city_control', array(
        'label'    => __( 'Merchant City', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_aba_merchant_city',
        'type'     => 'text'
    ) );

    // Manual Bank Details
    $wp_customize->add_setting( 'reandaily_manual_bank_name', array( 'default' => 'Advanced Bank of Asia (ABA)', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'reandaily_manual_bank_name_control', array(
        'label'    => __( 'Manual Bank Name', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_manual_bank_name',
        'type'     => 'text'
    ) );

    $wp_customize->add_setting( 'reandaily_manual_account_name', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'reandaily_manual_account_name_control', array(
        'label'    => __( 'Manual Account Name', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_manual_account_name',
        'type'     => 'text'
    ) );

    $wp_customize->add_setting( 'reandaily_manual_account_no', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( 'reandaily_manual_account_no_control', array(
        'label'    => __( 'Manual Account Number', 'reandaily-lms-theme' ),
        'section'  => 'reandaily_lms_payment_section',
        'settings' => 'reandaily_manual_account_no',
        'type'     => 'text'
    ) );

    // Custom QR Code Center Logo upload
    $wp_customize->add_setting( 'reandaily_qr_code_logo', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'reandaily_qr_code_logo_control', array(
        'label'       => __( 'QR Code Center Logo', 'reandaily-lms-theme' ),
        'description' => __( 'Upload an image to display in the center of checkout QR codes. Recommended: square PNG with solid background.', 'reandaily-lms-theme' ),
        'section'     => 'reandaily_lms_payment_section',
        'settings'    => 'reandaily_qr_code_logo',
    ) ) );
}
add_action( 'customize_register', 'reandaily_lms_customize_register' );


// ── 5. CORE LMS USER & HELPERS FUNCTIONS ──────────────────────────────────────
function reandaily_lms_is_enrolled( $user_id, $course_id ) {
    global $wpdb;
    if ( ! $user_id || ! $course_id ) return false;

    $table_name = $wpdb->prefix . 'reandaily_lms';
    $status = $wpdb->get_var( $wpdb->prepare(
        "SELECT status FROM $table_name WHERE user_id = %d AND course_id = %d ORDER BY id DESC LIMIT 1",
        $user_id,
        $course_id
    ) );
    
    return $status ? $status : false;
}

function reandaily_lms_get_progress( $user_id, $course_id ) {
    global $wpdb;
    if ( ! $user_id || ! $course_id ) return 0;

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

    $lessons_order = get_post_meta( $course_id, '_lessons_order', true );
    if ( empty( $lessons_order ) || ! is_array( $lessons_order ) ) {
        return 0;
    }

    $total_lessons = count( $lessons_order );
    if ( $total_lessons === 0 ) return 0;

    // Filter to count valid completed lessons that belong to this course
    $valid_completed = count( array_intersect( $completed_lessons, $lessons_order ) );

    return round( ( $valid_completed / $total_lessons ) * 100 );
}


// ── 6. AJAX LMS HANDLERS ────────────────────────────────────────────────────

// Check & Save Enrollment on Checkout
function ajax_reandaily_lms_enroll_student() {
    check_ajax_referer( 'reandaily_lms_nonce', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Please log in to continue.' ) );
    }

    $user_id   = get_current_user_id();
    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
    $method    = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : 'khqr';

    if ( ! $course_id ) {
        wp_send_json_error( array( 'message' => 'Invalid course.' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'reandaily_lms';

    // Check if already active
    $existing = reandaily_lms_is_enrolled( $user_id, $course_id );
    if ( $existing === 'active' ) {
        wp_send_json_success( array( 'redirect' => get_permalink( $course_id ) ) );
    }

    $bill_number = 'RD-' . $course_id . '-' . time();

    // Insert or update pending enrollment
    $exists = $wpdb->get_row( $wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d AND course_id = %d LIMIT 1",
        $user_id,
        $course_id
    ) );

    if ( $exists ) {
        $wpdb->update(
            $table_name,
            array(
                'status'         => 'pending',
                'bill_number'    => $bill_number,
                'payment_method' => $method,
                'created_at'     => current_time( 'mysql' )
            ),
            array( 'id' => $exists->id )
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'user_id'           => $user_id,
                'course_id'         => $course_id,
                'status'            => 'pending',
                'completed_lessons' => wp_json_encode( array() ),
                'bill_number'       => $bill_number,
                'payment_method'    => $method,
                'created_at'        => current_time( 'mysql' )
            )
        );
    }

    wp_send_json_success( array( 'bill_number' => $bill_number ) );
}
add_action( 'wp_ajax_reandaily_lms_enroll_student', 'ajax_reandaily_lms_enroll_student' );


// Upload Receipt Handler
function ajax_reandaily_lms_submit_receipt() {
    check_ajax_referer( 'reandaily_lms_nonce', 'security' );

    if ( ! is_user_logged_in() || empty( $_FILES['receipt_file'] ) ) {
        wp_send_json_error( array( 'message' => 'Invalid request.' ) );
    }

    $user_id   = get_current_user_id();
    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;

    if ( ! $course_id ) {
        wp_send_json_error( array( 'message' => 'Course ID is missing.' ) );
    }

    // Handle File Upload safely
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    $uploadedfile = $_FILES['receipt_file'];
    $upload_overrides = array(
        'test_form' => false,
        'mimes'     => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'webp'         => 'image/webp',
        ),
    );
    $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

    if ( $movefile && ! isset( $movefile['error'] ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'reandaily_lms';

        $wpdb->update(
            $table_name,
            array(
                'status'      => 'pending',
                'receipt_img' => $movefile['url']
            ),
            array( 'user_id' => $user_id, 'course_id' => $course_id )
        );

        wp_send_json_success( array( 'message' => 'Receipt uploaded successfully! Waiting for Admin verification.' ) );
    } else {
        wp_send_json_error( array( 'message' => $movefile['error'] ) );
    }
}
add_action( 'wp_ajax_reandaily_lms_submit_receipt', 'ajax_reandaily_lms_submit_receipt' );


// Progress update handler
function ajax_reandaily_lms_update_progress() {
    check_ajax_referer( 'reandaily_lms_nonce', 'security' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Logged out.' ) );
    }

    $user_id   = get_current_user_id();
    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    $completed = isset( $_POST['completed'] ) && $_POST['completed'] === 'true' ? true : false;

    if ( ! $course_id || ! $lesson_id ) {
        wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
    }

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

    if ( $completed ) {
        if ( ! in_array( $lesson_id, $completed_lessons ) ) {
            $completed_lessons[] = $lesson_id;
        }
    } else {
        $completed_lessons = array_diff( $completed_lessons, array( $lesson_id ) );
    }

    $wpdb->update(
        $table_name,
        array( 'completed_lessons' => wp_json_encode( array_values( $completed_lessons ) ) ),
        array( 'user_id' => $user_id, 'course_id' => $course_id )
    );

    // Calculate overall course completion status
    $progress = reandaily_lms_get_progress( $user_id, $course_id );
    if ( $progress >= 100 ) {
        $wpdb->update(
            $table_name,
            array( 'status' => 'completed' ),
            array( 'user_id' => $user_id, 'course_id' => $course_id )
        );
    } else {
        $wpdb->update(
            $table_name,
            array( 'status' => 'active' ),
            array( 'user_id' => $user_id, 'course_id' => $course_id )
        );
    }

    wp_send_json_success( array( 'progress' => $progress ) );
}
add_action( 'wp_ajax_reandaily_lms_update_progress', 'ajax_reandaily_lms_update_progress' );


// Check Poll Status
function ajax_reandaily_lms_check_status() {
    check_ajax_referer( 'reandaily_lms_nonce', 'security' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Logged out.' ) );
    }

    $user_id   = get_current_user_id();
    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
    
    $status = reandaily_lms_is_enrolled( $user_id, $course_id );
    
    wp_send_json_success( array( 'status' => $status ) );
}
add_action( 'wp_ajax_reandaily_lms_check_status', 'ajax_reandaily_lms_check_status' );


// ── 7. SELF-CONTAINED GITHUB THEME UPDATER ───────────────────────────────────
class ReanDaily_LMS_Theme_Updater {
    private $theme_slug;
    private $version;
    private $repo;

    public function __construct() {
        $this->theme_slug = get_template();
        $theme            = wp_get_theme( $this->theme_slug );
        $this->version    = $theme->exists() ? $theme->get( 'Version' ) : '1.0.0';
        $this->repo       = 'jchanthy/reandaily-lms-theme';

        add_filter( 'site_transient_update_themes', array( $this, 'check_update' ) );
        add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );

        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'clear_transient' ) );
            add_action( 'admin_notices', array( $this, 'debug_notice' ) );
        }
    }

    public function clear_transient() {
        global $pagenow;
        if ( $pagenow === 'update-core.php' && isset( $_GET['force-check'] ) && $_GET['force-check'] == '1' ) {
            delete_site_transient( 'update_themes' );
        }
    }

    public function debug_notice() {
        if ( isset( $_GET['debug_updater'] ) ) {
            $headers = array( 'User-Agent' => 'WordPress/' . get_bloginfo('version') );
            if ( defined( 'GITHUB_API_TOKEN' ) && GITHUB_API_TOKEN ) {
                $headers['Authorization'] = 'token ' . GITHUB_API_TOKEN;
            }

            $response = wp_remote_get( "https://api.github.com/repos/{$this->repo}/releases/latest", array(
                'headers' => $headers
            ) );
            $remote = 'Error/Unknown';
            $extra = '';
            if ( ! is_wp_error( $response ) ) {
                $body = wp_remote_retrieve_body( $response );
                $release = json_decode( $body, true );
                if ( isset( $release['tag_name'] ) ) {
                    $remote = $release['tag_name'];
                } elseif ( isset( $release['message'] ) ) {
                    $remote = 'API Error';
                    $extra = ' - Message: ' . esc_html( $release['message'] );
                } else {
                    $remote = 'No tag';
                    $extra = ' - Response Code: ' . wp_remote_retrieve_response_code( $response );
                }
            } else {
                $extra = ' - WP Error: ' . $response->get_error_message();
            }
            echo "<div class='notice notice-info'><p><strong>Git Updater Debug:</strong> Local: {$this->version} | Remote: {$remote}{$extra} | Slug: {$this->theme_slug} | Repo: {$this->repo}</p></div>";
        }
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $headers = array( 'User-Agent' => 'WordPress/' . get_bloginfo('version') );
        if ( defined( 'GITHUB_API_TOKEN' ) && GITHUB_API_TOKEN ) {
            $headers['Authorization'] = 'token ' . GITHUB_API_TOKEN;
        }

        // Fetch latest release details from GitHub API
        $response = wp_remote_get( "https://api.github.com/repos/{$this->repo}/releases/latest", array(
            'headers' => $headers
        ) );

        if ( is_wp_error( $response ) ) {
            return $transient;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $release ) || empty( $release['tag_name'] ) ) {
            return $transient;
        }

        $remote_version = ltrim( $release['tag_name'], 'v' );

        // If newer version is available, push notification variables to transient
        if ( version_compare( $this->version, $remote_version, '<' ) ) {
            $transient->response[ $this->theme_slug ] = array(
                'theme'       => $this->theme_slug,
                'new_version' => $remote_version,
                'url'         => $release['html_url'],
                'package'     => $release['zipball_url'],
            );
        }

        return $transient;
    }

    public function post_install( $true, $hook_extra, $result ) {
        // Only run this cleanup on our theme
        if ( isset( $hook_extra['theme'] ) && $hook_extra['theme'] === $this->theme_slug ) {
            global $wp_filesystem;
            $destination = $result['destination'];
            $correct_dir = trailingslashit( dirname( $destination ) ) . $this->theme_slug;

            if ( $destination !== $correct_dir ) {
                if ( $wp_filesystem->exists( $correct_dir ) ) {
                    $wp_filesystem->delete( $correct_dir, true );
                }
                if ( $wp_filesystem->move( $destination, $correct_dir ) ) {
                    $result['destination'] = $correct_dir;
                }
            }
        }
        return $result;
    }
}
new ReanDaily_LMS_Theme_Updater();


// ── 8. DYNAMIC KHQR GENERATOR (EMVCO STANDARDS) ──────────────────────────────
function reandaily_lms_generate_khqr( $bakong_id, $merchant_name, $merchant_city, $amount, $currency = 'USD' ) {
    $bakong_id     = sanitize_text_field( $bakong_id );
    $merchant_name = preg_replace( '/[^a-zA-Z0-9 ]/', '', $merchant_name );
    $merchant_name = substr( $merchant_name, 0, 25 );
    if ( empty( $merchant_name ) ) {
        $merchant_name = 'ReanDaily';
    }

    $merchant_city = preg_replace( '/[^a-zA-Z0-9 ]/', '', $merchant_city );
    $merchant_city = substr( $merchant_city, 0, 15 );
    if ( empty( $merchant_city ) ) {
        $merchant_city = 'Phnom Penh';
    }

    // Tag 00: Payload Format Indicator
    $payload = '000201'; 
    // Tag 01: Point of Initiation (12 = Dynamic QR with Amount)
    $payload .= '010212'; 

    // Tag 29: Merchant Account Information (Bakong ID)
    $guid_subtag = '0017kh.gov.nbc.bakong';
    $account_subtag = '01' . sprintf( '%02d', strlen( $bakong_id ) ) . $bakong_id;
    $tag29_value = $guid_subtag . $account_subtag;
    $payload .= '29' . sprintf( '%02d', strlen( $tag29_value ) ) . $tag29_value;

    // Tag 52: Merchant Category Code (5999 = General Merchant)
    $payload .= '52045999'; 

    // Tag 53: Transaction Currency (840 = USD, 116 = KHR)
    $curr_code = ( strtoupper( $currency ) === 'KHR' ) ? '116' : '840';
    $payload .= '5303' . $curr_code;

    // Tag 54: Transaction Amount
    $amount_str = number_format( $amount, 2, '.', '' );
    if ( $curr_code === '116' ) {
        $amount_str = (string) round( $amount );
    }
    $payload .= '54' . sprintf( '%02d', strlen( $amount_str ) ) . $amount_str;

    // Tag 58: Country Code (KH)
    $payload .= '5802KH'; 
    // Tag 59: Merchant Name
    $payload .= '59' . sprintf( '%02d', strlen( $merchant_name ) ) . $merchant_name; 
    // Tag 60: Merchant City
    $payload .= '60' . sprintf( '%02d', strlen( $merchant_city ) ) . $merchant_city; 

    // Tag 63: CRC Header
    $payload .= '6304';
    
    // Calculate CRC16 CCITT (polynomial 0x1021, seed 0xFFFF)
    $crc = 0xFFFF;
    for ( $i = 0; $i < strlen( $payload ); $i++ ) {
        $x = ( ( $crc >> 8 ) ^ ord( $payload[ $i ] ) ) & 0xFF;
        $x ^= $x >> 4;
        $crc = ( ( $crc << 8 ) ^ ( $x << 12 ) ^ ( $x << 5 ) ^ $x ) & 0xFFFF;
    }
    $crc_str = sprintf( '%04X', $crc );

    return $payload . $crc_str;
}

function reandaily_lms_get_enroll_url( $course_id = 0 ) {
    $page = get_page_by_path( 'enroll' );
    if ( $page ) {
        $url = get_permalink( $page->ID );
    } else {
        $url = home_url( '/enroll/' );
    }
    if ( $course_id ) {
        $url = add_query_arg( 'course_id', $course_id, $url );
    }
    return $url;
}

function reandaily_lms_get_dashboard_url() {
    $page = get_page_by_path( 'dashboard' );
    if ( $page ) {
        return get_permalink( $page->ID );
    }
    return home_url( '/dashboard/' );
}


