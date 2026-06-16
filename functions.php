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
    wp_enqueue_style( 'reandaily-style', get_stylesheet_uri(), array(), '1.2.0' );

    // FontAwesome for UI icons
    wp_enqueue_style( 'font-awesome', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css', array(), '6.4.0' );

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
        'supports'    => array( 'title', 'thumbnail' ),
        'menu_icon'   => 'dashicons-welcome-learn-more',
        'rewrite'     => array( 'slug' => 'courses' ),
        'show_in_rest'=> false,
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
        'supports'    => array( 'title' ),
        'menu_icon'   => 'dashicons-playlist-video',
        'rewrite'     => array( 'slug' => 'lessons' ),
        'show_in_rest'=> false,
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


// ── 15. COURSE & LESSON METABOXES & TABBED BUILDERS ─────────────────────────

// Enqueue Admin Scripts for Course/Lesson Manager
function reandaily_lms_admin_enqueue( $hook ) {
    global $post;
    if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
        if ( $post && ( 'courses' === get_post_type( $post ) || 'lessons' === get_post_type( $post ) ) ) {
            wp_enqueue_script( 'jquery-ui-sortable' );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'reandaily_lms_admin_enqueue' );

// Register Metaboxes
function reandaily_lms_register_metaboxes() {
    add_meta_box(
        'reandaily_lms_course_builder',
        __( 'Course Builder & Settings', 'reandaily-lms-theme' ),
        'reandaily_lms_course_builder_html',
        'courses',
        'normal',
        'high'
    );

    add_meta_box(
        'reandaily_lms_lesson_builder',
        __( 'Lesson Settings & Content', 'reandaily-lms-theme' ),
        'reandaily_lms_lesson_builder_html',
        'lessons',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'reandaily_lms_register_metaboxes' );

// Course Builder Metabox HTML (Tabbed)
function reandaily_lms_course_builder_html( $post ) {
    wp_nonce_field( 'reandaily_lms_save_course_meta', 'reandaily_lms_course_meta_nonce' );

    $price = get_post_meta( $post->ID, '_price', true );
    $price_khr = get_post_meta( $post->ID, '_price_khr', true );
    $duration = get_post_meta( $post->ID, '_duration', true );
    $level = get_post_meta( $post->ID, '_level', true );
    $trailer_url = get_post_meta( $post->ID, '_trailer_url', true );
    
    $lessons_order = get_post_meta( $post->ID, '_lessons_order', true );
    if ( ! is_array( $lessons_order ) ) {
        $lessons_order = array();
    }

    $lessons = get_posts( array(
        'post_type'      => 'lessons',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ) );

    $course_description = get_post_field( 'post_content', $post->ID );
    ?>
    <style>
        .lms-builder-container {
            display: flex;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .lms-builder-tabs {
            width: 220px;
            background: #f6f7f7;
            border-right: 1px solid #ccd0d4;
            display: flex;
            flex-direction: column;
        }
        .lms-builder-tab-btn {
            padding: 16px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #444;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
            border-bottom: 1px solid #e5e5e5;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            outline: none;
        }
        .lms-builder-tab-btn:hover {
            background: #f0f0f1;
            color: #2271b1;
        }
        .lms-builder-tab-btn.active {
            background: #fff;
            color: #2271b1;
            border-left: 4px solid #2271b1;
            padding-left: 16px;
        }
        .lms-builder-tab-btn .dashicons {
            color: #646970;
        }
        .lms-builder-tab-btn.active .dashicons {
            color: #2271b1;
        }
        .lms-builder-panels {
            flex: 1;
            padding: 28px;
            min-height: 450px;
            background: #fff;
        }
        .lms-builder-panel {
            display: none;
        }
        .lms-builder-panel.active {
            display: block;
        }
        .lms-builder-row {
            margin-bottom: 24px;
        }
        .lms-builder-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13.5px;
            color: #1d2327;
        }
        .lms-builder-row input[type="text"],
        .lms-builder-row select {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 14px;
        }
        .lms-builder-row input[type="text"]:focus,
        .lms-builder-row select:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: 2px solid transparent;
        }
        .lms-syllabus-columns {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        .lms-syllabus-col {
            flex: 1;
            background: #f8f9fa;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            padding: 16px;
            min-height: 300px;
        }
        .lms-syllabus-col h4 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-weight: 700;
            color: #1d2327;
            padding-bottom: 8px;
            border-bottom: 1px solid #dcdcde;
        }
        .lms-lesson-list {
            list-style: none;
            margin: 0;
            padding: 0;
            min-height: 250px;
        }
        .lms-lesson-item {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 8px;
            cursor: move;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            font-size: 13px;
        }
        .lms-lesson-item .dashicons-menu {
            color: #8c8f94;
        }
        .ui-state-highlight {
            border: 2px dashed #2271b1;
            background: #f0f6fc;
            min-height: 40px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
    </style>

    <div class="lms-builder-container">
        <!-- Vertical Tab List -->
        <div class="lms-builder-tabs">
            <button type="button" class="lms-builder-tab-btn active" data-target="course-general">
                <span class="dashicons dashicons-admin-generic"></span> <?php _e( 'General', 'reandaily-lms-theme' ); ?>
            </button>
            <button type="button" class="lms-builder-tab-btn" data-target="course-description">
                <span class="dashicons dashicons-editor-paragraph"></span> <?php _e( 'Description', 'reandaily-lms-theme' ); ?>
            </button>
            <button type="button" class="lms-builder-tab-btn" data-target="course-pricing">
                <span class="dashicons dashicons-cart"></span> <?php _e( 'Pricing', 'reandaily-lms-theme' ); ?>
            </button>
            <button type="button" class="lms-builder-tab-btn" data-target="course-syllabus">
                <span class="dashicons dashicons-playlist-video"></span> <?php _e( 'Curriculum', 'reandaily-lms-theme' ); ?>
            </button>
            <button type="button" class="lms-builder-tab-btn" data-target="course-media">
                <span class="dashicons dashicons-format-video"></span> <?php _e( 'Media Settings', 'reandaily-lms-theme' ); ?>
            </button>
        </div>

        <!-- Panels Container -->
        <div class="lms-builder-panels">
            <!-- 1. General Panel -->
            <div id="course-general" class="lms-builder-panel active">
                <h3><?php _e( 'General Course Information', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">
                
                <div class="lms-builder-row">
                    <label for="lms_duration"><?php _e( 'Course Duration', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_duration" name="lms_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="e.g. 10 Hours, 4 Weeks">
                </div>

                <div class="lms-builder-row">
                    <label for="lms_level"><?php _e( 'Difficulty Level', 'reandaily-lms-theme' ); ?></label>
                    <select id="lms_level" name="lms_level">
                        <option value="All Levels" <?php selected( $level, 'All Levels' ); ?>><?php _e( 'All Levels', 'reandaily-lms-theme' ); ?></option>
                        <option value="Beginner" <?php selected( $level, 'Beginner' ); ?>><?php _e( 'Beginner', 'reandaily-lms-theme' ); ?></option>
                        <option value="Intermediate" <?php selected( $level, 'Intermediate' ); ?>><?php _e( 'Intermediate', 'reandaily-lms-theme' ); ?></option>
                        <option value="Advanced" <?php selected( $level, 'Advanced' ); ?>><?php _e( 'Advanced', 'reandaily-lms-theme' ); ?></option>
                    </select>
                </div>
            </div>

            <!-- 2. Description Panel (Rich Editor) -->
            <div id="course-description" class="lms-builder-panel">
                <h3><?php _e( 'Course Syllabus / Description', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">
                
                <?php
                wp_editor( $course_description, 'lms_course_description', array(
                    'textarea_name' => 'lms_course_description',
                    'media_buttons' => true,
                    'textarea_rows' => 15,
                    'tinymce'       => true
                ) );
                ?>
            </div>

            <!-- 3. Pricing Panel -->
            <div id="course-pricing" class="lms-builder-panel">
                <h3><?php _e( 'Course Pricing Settings', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">

                <div class="lms-builder-row">
                    <label for="lms_price"><?php _e( 'USD Price ($)', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_price" name="lms_price" value="<?php echo esc_attr( $price ); ?>" placeholder="e.g. 19.99 (Set 0 for free)">
                </div>

                <div class="lms-builder-row">
                    <label for="lms_price_khr"><?php _e( 'KHR Price (៛)', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_price_khr" name="lms_price_khr" value="<?php echo esc_attr( $price_khr ); ?>" placeholder="e.g. 80000">
                </div>
            </div>

            <!-- 4. Curriculum Builder -->
            <div id="course-syllabus" class="lms-builder-panel">
                <h3><?php _e( 'Drag & Drop Course Curriculum', 'reandaily-lms-theme' ); ?></h3>
                <p class="description"><?php _e( 'Drag lessons from the left list to the right list to include and sequence them in the course syllabus.', 'reandaily-lms-theme' ); ?></p>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 20px 0;">

                <div class="lms-syllabus-columns">
                    <div class="lms-syllabus-col">
                        <h4><?php _e( 'Available Lessons', 'reandaily-lms-theme' ); ?></h4>
                        <ul id="lms-unassigned-lessons" class="lms-lesson-list">
                            <?php
                            foreach ( $lessons as $lesson ) {
                                if ( in_array( $lesson->ID, $lessons_order ) ) {
                                    continue;
                                }
                                echo '<li class="lms-lesson-item" data-id="' . esc_attr( $lesson->ID ) . '">';
                                echo '<span class="dashicons dashicons-menu"></span>';
                                echo '<span>' . esc_html( $lesson->post_title ) . '</span>';
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    </div>

                    <div class="lms-syllabus-col">
                        <h4><?php _e( 'Course Syllabus (Syllabus)', 'reandaily-lms-theme' ); ?></h4>
                        <ul id="lms-course-lessons" class="lms-lesson-list">
                            <?php
                            foreach ( $lessons_order as $lesson_id ) {
                                $lesson_post = get_post( $lesson_id );
                                if ( $lesson_post && $lesson_post->post_status === 'publish' ) {
                                    echo '<li class="lms-lesson-item" data-id="' . esc_attr( $lesson_id ) . '">';
                                    echo '<span class="dashicons dashicons-menu"></span>';
                                    echo '<span>' . esc_html( $lesson_post->post_title ) . '</span>';
                                    echo '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>

                <input type="hidden" id="lms_lessons_order_input" name="lms_lessons_order" value="<?php echo esc_attr( implode( ',', $lessons_order ) ); ?>">
            </div>

            <!-- 5. Media Panel -->
            <div id="course-media" class="lms-builder-panel">
                <h3><?php _e( 'Course Promotion Media', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">

                <div class="lms-builder-row">
                    <label for="lms_trailer_url"><?php _e( 'Promo/Trailer Video URL', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_trailer_url" name="lms_trailer_url" value="<?php echo esc_url( $trailer_url ); ?>" placeholder="e.g. YouTube or Vimeo trailer video URL">
                    <p class="description"><?php _e( 'This video will appear as the course preview video for prospective students.', 'reandaily-lms-theme' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Tab switching logic
            $('.lms-builder-tab-btn').on('click', function() {
                var target = $(this).data('target');
                $('.lms-builder-tab-btn').removeClass('active');
                $('.lms-builder-panel').removeClass('active');
                
                $(this).addClass('active');
                $('#' + target).addClass('active');
            });

            // Initialize Sortable syllabus Columns
            $("#lms-unassigned-lessons, #lms-course-lessons").sortable({
                connectWith: ".lms-lesson-list",
                placeholder: "ui-state-highlight",
                update: function(event, ui) {
                    var order = [];
                    $("#lms-course-lessons .lms-lesson-item").each(function() {
                        order.push($(this).data('id'));
                    });
                    $("#lms_lessons_order_input").val(order.join(','));
                }
            }).disableSelection();
        });
    </script>
    <?php
}

// Lesson Settings & Content Builder HTML (Tabbed)
function reandaily_lms_lesson_builder_html( $post ) {
    wp_nonce_field( 'reandaily_lms_save_lesson_meta', 'reandaily_lms_lesson_meta_nonce' );

    $video_url = get_post_meta( $post->ID, '_video_url', true );
    $duration = get_post_meta( $post->ID, '_duration', true );
    $is_preview = get_post_meta( $post->ID, '_is_preview', true );
    $lesson_content = get_post_field( 'post_content', $post->ID );
    ?>
    <style>
        .lms-builder-container {
            display: flex;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .lms-builder-tabs {
            width: 220px;
            background: #f6f7f7;
            border-right: 1px solid #ccd0d4;
            display: flex;
            flex-direction: column;
        }
        .lms-builder-tab-btn {
            padding: 16px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #444;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
            border-bottom: 1px solid #e5e5e5;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            outline: none;
        }
        .lms-builder-tab-btn:hover {
            background: #f0f0f1;
            color: #2271b1;
        }
        .lms-builder-tab-btn.active {
            background: #fff;
            color: #2271b1;
            border-left: 4px solid #2271b1;
            padding-left: 16px;
        }
        .lms-builder-tab-btn .dashicons {
            color: #646970;
        }
        .lms-builder-tab-btn.active .dashicons {
            color: #2271b1;
        }
        .lms-builder-panels {
            flex: 1;
            padding: 28px;
            min-height: 450px;
            background: #fff;
        }
        .lms-builder-panel {
            display: none;
        }
        .lms-builder-panel.active {
            display: block;
        }
        .lms-builder-row {
            margin-bottom: 24px;
        }
        .lms-builder-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13.5px;
            color: #1d2327;
        }
        .lms-builder-row input[type="text"],
        .lms-builder-row input[type="checkbox"] {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            font-size: 14px;
        }
        .lms-builder-row input[type="checkbox"] {
            width: auto;
            margin-top: 0;
        }
    </style>

    <div class="lms-builder-container">
        <!-- Vertical Tab List -->
        <div class="lms-builder-tabs">
            <button type="button" class="lms-builder-tab-btn active" data-target="lesson-content">
                <span class="dashicons dashicons-editor-paragraph"></span> <?php _e( 'Lesson Content', 'reandaily-lms-theme' ); ?>
            </button>
            <button type="button" class="lms-builder-tab-btn" data-target="lesson-settings">
                <span class="dashicons dashicons-admin-generic"></span> <?php _e( 'Video & Rules', 'reandaily-lms-theme' ); ?>
            </button>
        </div>

        <!-- Panels Container -->
        <div class="lms-builder-panels">
            <!-- 1. Content Panel (Rich Editor) -->
            <div id="lesson-content" class="lms-builder-panel active">
                <h3><?php _e( 'Lesson Content description', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">
                
                <?php
                wp_editor( $lesson_content, 'lms_lesson_description', array(
                    'textarea_name' => 'lms_lesson_description',
                    'media_buttons' => true,
                    'textarea_rows' => 15,
                    'tinymce'       => true
                ) );
                ?>
            </div>

            <!-- 2. Settings Panel -->
            <div id="lesson-settings" class="lms-builder-panel">
                <h3><?php _e( 'Lesson Media & Preview Settings', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">

                <div class="lms-builder-row">
                    <label for="lms_video_url"><?php _e( 'Video URL', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_video_url" name="lms_video_url" value="<?php echo esc_url( $video_url ); ?>" placeholder="e.g. YouTube, Vimeo or MP4 file link">
                    <p class="description"><?php _e( 'Video file or hosting platform link for this lesson.', 'reandaily-lms-theme' ); ?></p>
                </div>

                <div class="lms-builder-row">
                    <label for="lms_lesson_duration"><?php _e( 'Lesson Duration', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_lesson_duration" name="lms_lesson_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="e.g. 15 mins, 45 mins">
                </div>

                <div class="lms-builder-row" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="lms_is_preview" name="lms_is_preview" value="1" <?php checked( $is_preview, '1' ); ?>>
                    <label for="lms_is_preview" style="margin-bottom:0; font-weight: normal; font-size: 13.5px;"><?php _e( 'Is Preview (Free lesson before enrollment)', 'reandaily-lms-theme' ); ?></label>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.lms-builder-tab-btn').on('click', function() {
                var target = $(this).data('target');
                $('.lms-builder-tab-btn').removeClass('active');
                $('.lms-builder-panel').removeClass('active');
                
                $(this).addClass('active');
                $('#' + target).addClass('active');
            });
        });
    </script>
    <?php
}

// Save Metabox Data
function reandaily_lms_save_metaboxes_data( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save Course Builder fields
    if ( isset( $_POST['lms_course_meta_nonce'] ) && wp_verify_nonce( $_POST['lms_course_meta_nonce'], 'reandaily_lms_save_course_meta' ) ) {
        if ( isset( $_POST['lms_price'] ) ) {
            update_post_meta( $post_id, '_price', sanitize_text_field( $_POST['lms_price'] ) );
        }
        if ( isset( $_POST['lms_price_khr'] ) ) {
            update_post_meta( $post_id, '_price_khr', sanitize_text_field( $_POST['lms_price_khr'] ) );
        }
        if ( isset( $_POST['lms_duration'] ) ) {
            update_post_meta( $post_id, '_duration', sanitize_text_field( $_POST['lms_duration'] ) );
        }
        if ( isset( $_POST['lms_level'] ) ) {
            update_post_meta( $post_id, '_level', sanitize_text_field( $_POST['lms_level'] ) );
        }
        if ( isset( $_POST['lms_trailer_url'] ) ) {
            update_post_meta( $post_id, '_trailer_url', esc_url_raw( $_POST['lms_trailer_url'] ) );
        }
        if ( isset( $_POST['lms_lessons_order'] ) ) {
            $order_string = sanitize_text_field( $_POST['lms_lessons_order'] );
            $order_array = array_filter( array_map( 'intval', explode( ',', $order_string ) ) );
            update_post_meta( $post_id, '_lessons_order', $order_array );
        }
        
        // Sync custom description editor back to post_content field
        if ( isset( $_POST['lms_course_description'] ) ) {
            remove_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );
            wp_update_post( array(
                'ID'           => $post_id,
                'post_content' => wp_kses_post( $_POST['lms_course_description'] ),
            ) );
            add_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );
        }
    }

    // Save Lesson Builder fields
    if ( isset( $_POST['lms_lesson_meta_nonce'] ) && wp_verify_nonce( $_POST['lms_lesson_meta_nonce'], 'reandaily_lms_save_lesson_meta' ) ) {
        if ( isset( $_POST['lms_video_url'] ) ) {
            update_post_meta( $post_id, '_video_url', esc_url_raw( $_POST['lms_video_url'] ) );
        }
        if ( isset( $_POST['lms_lesson_duration'] ) ) {
            update_post_meta( $post_id, '_duration', sanitize_text_field( $_POST['lms_lesson_duration'] ) );
        }
        
        $is_preview = isset( $_POST['lms_is_preview'] ) ? '1' : '0';
        update_post_meta( $post_id, '_is_preview', $is_preview );

        // Sync custom lesson editor back to post_content field
        if ( isset( $_POST['lms_lesson_description'] ) ) {
            remove_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );
            wp_update_post( array(
                'ID'           => $post_id,
                'post_content' => wp_kses_post( $_POST['lms_lesson_description'] ),
            ) );
            add_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );
        }
    }
}
add_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );


