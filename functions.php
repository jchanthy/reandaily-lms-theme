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
        // 1. Create Free Course
        $free_course = get_page_by_path( 'introduction-to-web-development-free', OBJECT, 'courses' );
        if ( ! $free_course ) {
            $course_id = wp_insert_post( array(
                'post_title'   => 'Introduction to Web Development (Free)',
                'post_name'    => 'introduction-to-web-development-free',
                'post_content' => 'Welcome to the free Web Development course! In this course, you will learn the foundational building blocks of the web: HTML and CSS.',
                'post_status'  => 'publish',
                'post_type'    => 'courses'
            ) );

            if ( ! is_wp_error( $course_id ) ) {
                update_post_meta( $course_id, '_price', '0' );
                update_post_meta( $course_id, '_price_khr', '0' );
                update_post_meta( $course_id, '_duration', '2 Hours' );
                update_post_meta( $course_id, '_level', 'Beginner' );

                // Create lessons for Free Course
                $lesson_ids = array();
                $lessons_data = array(
                    array(
                        'title' => 'Lesson 1: What is HTML?',
                        'content' => 'HTML stands for HyperText Markup Language. It is the standard markup language for documents designed to be displayed in a web browser.',
                        'video' => 'https://www.youtube.com/watch?v=kUMe1FH4CHE',
                        'duration' => '10 mins',
                        'preview' => '1'
                    ),
                    array(
                        'title' => 'Lesson 2: CSS Styles and Layouts',
                        'content' => 'Cascading Style Sheets (CSS) is a style sheet language used for describing the presentation of a document written in a markup language like HTML.',
                        'video' => 'https://www.youtube.com/watch?v=1Rs2ND1ryYc',
                        'duration' => '15 mins',
                        'preview' => '0'
                    )
                );

                foreach ( $lessons_data as $data ) {
                    $lesson_id = wp_insert_post( array(
                        'post_title'   => $data['title'],
                        'post_content' => $data['content'],
                        'post_status'  => 'publish',
                        'post_type'    => 'lessons'
                    ) );
                    if ( ! is_wp_error( $lesson_id ) ) {
                        update_post_meta( $lesson_id, '_duration', $data['duration'] );
                        update_post_meta( $lesson_id, '_video_url', $data['video'] );
                        update_post_meta( $lesson_id, '_is_preview', $data['preview'] );
                        $lesson_ids[] = $lesson_id;
                    }
                }
                update_post_meta( $course_id, '_lessons_order', $lesson_ids );
            }
        }

        // 2. Create Paid Course
        $paid_course = get_page_by_path( 'mastering-wordpress-lms-theme-design-paid', OBJECT, 'courses' );
        if ( ! $paid_course ) {
            $course_id = wp_insert_post( array(
                'post_title'   => 'Mastering WordPress LMS Theme Design (Paid)',
                'post_name'    => 'mastering-wordpress-lms-theme-design-paid',
                'post_content' => 'Take your WordPress development skills to the next level. Learn how to design custom course builders, payment QR integration, and distraction-free classroom players.',
                'post_status'  => 'publish',
                'post_type'    => 'courses'
            ) );

            if ( ! is_wp_error( $course_id ) ) {
                update_post_meta( $course_id, '_price', '15.00' );
                update_post_meta( $course_id, '_price_khr', '60000' );
                update_post_meta( $course_id, '_duration', '5 Hours' );
                update_post_meta( $course_id, '_level', 'Intermediate' );

                // Create lessons for Paid Course
                $lesson_ids = array();
                $lessons_data = array(
                    array(
                        'title' => 'Lesson 1: Theme Architecture & File Layout',
                        'content' => 'In this lesson, we will cover how a lightweight LMS theme structures its templates, headers, footers, and dashboard layouts.',
                        'video' => 'https://www.youtube.com/watch?v=mU6an7qxkTo',
                        'duration' => '20 mins',
                        'preview' => '1' // Free Preview
                    ),
                    array(
                        'title' => 'Lesson 2: Custom Post Types and Meta Fields',
                        'content' => 'Learn how to register custom post types for courses and lessons, and how to build tabbed editor dashboards to control values.',
                        'video' => 'https://www.youtube.com/watch?v=8339H0k723Q',
                        'duration' => '25 mins',
                        'preview' => '0'
                    ),
                    array(
                        'title' => 'Lesson 3: Customizing Video Player and Sidebar',
                        'content' => 'Let us explore the classroom template to customize the side navigation panel and style the video player iframe elements.',
                        'video' => 'https://www.youtube.com/watch?v=Sqzr-O_Gg1U',
                        'duration' => '30 mins',
                        'preview' => '0'
                    )
                );

                foreach ( $lessons_data as $data ) {
                    $lesson_id = wp_insert_post( array(
                        'post_title'   => $data['title'],
                        'post_content' => $data['content'],
                        'post_status'  => 'publish',
                        'post_type'    => 'lessons'
                    ) );
                    if ( ! is_wp_error( $lesson_id ) ) {
                        update_post_meta( $lesson_id, '_duration', $data['duration'] );
                        update_post_meta( $lesson_id, '_video_url', $data['video'] );
                        update_post_meta( $lesson_id, '_is_preview', $data['preview'] );
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
            wp_localize_script( 'jquery', 'reandaily_lms_admin_vars', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'reandaily_lms_ajax_nonce' ),
            ) );
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
    
    // Retrieve curriculum sections hierarchy
    $sections = get_post_meta( $post->ID, '_course_sections', true );
    if ( ! is_array( $sections ) || empty( $sections ) ) {
        // Fallback to flat _lessons_order
        $lessons_order = get_post_meta( $post->ID, '_lessons_order', true );
        if ( ! is_array( $lessons_order ) ) {
            $lessons_order = array();
        }
        $sections = array(
            array(
                'id' => 'sec_default',
                'title' => 'Section 1: Lectures',
                'lessons' => $lessons_order
            )
        );
    }

    $course_description = get_post_field( 'post_content', $post->ID );
    ?>
    <style>
        /* Banner when fullscreen is deactivated */
        .lms-fullscreen-toggle-banner {
            background: #1e293b;
            color: #fff;
            padding: 16px 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .lms-fullscreen-toggle-banner h4 {
            margin: 0 0 4px 0;
            color: #fff;
            font-size: 15px;
        }
        .lms-fullscreen-toggle-banner p {
            margin: 0;
            color: #94a3b8;
            font-size: 13px;
        }

        /* Fullscreen distraction-free modern builder overlay */
        .lms-fullscreen-builder {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 99999;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
        }
        .lms-top-nav {
            background: #0f172a;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            border-bottom: 1px solid #1e293b;
            color: #f1f5f9;
            flex-shrink: 0;
        }
        .lms-nav-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .lms-back-link {
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.15s ease;
        }
        .lms-back-link:hover {
            color: #f1f5f9;
        }
        .lms-nav-divider {
            width: 1px;
            height: 20px;
            background: #334155;
        }
        #lms-course-top-title {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            width: 250px;
            padding: 4px 8px;
            outline: none;
            border-bottom: 1px dashed transparent;
            transition: border-color 0.15s ease;
        }
        #lms-course-top-title:focus {
            border-bottom-color: #3b82f6;
            box-shadow: none;
        }
        .lms-nav-center {
            display: flex;
            gap: 4px;
            height: 100%;
        }
        .lms-nav-tab {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 0 20px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
            outline: none;
            transition: color 0.15s ease;
        }
        .lms-nav-tab:hover {
            color: #f1f5f9;
        }
        .lms-nav-tab.active {
            color: #3b82f6;
        }
        .lms-nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #3b82f6;
        }
        .lms-nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .lms-btn-toggle-old {
            background: transparent;
            border: 1px solid #334155;
            color: #94a3b8;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lms-btn-toggle-old:hover {
            color: #f1f5f9;
            border-color: #475569;
        }
        .lms-btn-publish {
            background: #3b82f6;
            border: none;
            color: #fff;
            padding: 8px 18px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .lms-btn-publish:hover {
            background: #2563eb;
        }
        .lms-btn-view {
            background: #334155;
            border: none;
            color: #fff;
            padding: 8px 18px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .lms-btn-view:hover {
            background: #475569;
        }

        /* Workspace Panels container */
        .lms-workspace-content {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        .lms-workspace-panel {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: none;
            overflow-y: auto;
        }
        .lms-workspace-panel.active {
            display: block;
        }

        /* Curriculum grid matching the layout in screenshot */
        .lms-curriculum-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            height: 100%;
            background: #f1f5f9;
            overflow: hidden;
        }
        .lms-curriculum-left {
            border-right: 1px solid #cbd5e1;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding: 24px;
        }
        .lms-curriculum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .lms-curriculum-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .lms-sections-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .lms-section-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        .lms-section-card-header {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
        }
        .lms-section-title-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        .lms-section-title-input {
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 14px;
            color: #1e293b;
            padding: 2px 4px;
            width: 100%;
            outline: none;
        }
        .lms-section-title-input:focus {
            background: #fff;
            box-shadow: 0 0 0 1px #3b82f6;
            border-radius: 3px;
        }
        .lms-section-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lms-section-action-btn {
            background: transparent;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 2px;
            border-radius: 3px;
        }
        .lms-section-action-btn:hover {
            color: #0f172a;
            background: #e2e8f0;
        }
        .lms-section-lessons-list {
            padding: 8px 12px;
            margin: 0;
            list-style: none;
            min-height: 40px;
            background: #fff;
        }
        .lms-section-lesson-item {
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: grab;
            font-size: 13px;
            transition: all 0.15s ease;
        }
        .lms-section-lesson-item:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }
        .lms-section-lesson-item.active {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 500;
        }
        .lms-lesson-item-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .lms-lesson-item-actions {
            display: flex;
            gap: 4px;
        }
        .lms-lesson-action-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 2px;
            border-radius: 3px;
        }
        .lms-lesson-action-btn:hover {
            color: #ef4444;
            background: #fee2e2;
        }
        .lms-section-footer-btns {
            padding: 8px 16px 12px 16px;
            display: flex;
            gap: 8px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .lms-btn-add-lesson {
            background: #fff;
            border: 1px dashed #cbd5e1;
            color: #2563eb;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }
        .lms-btn-add-lesson:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
        .lms-btn-new-section {
            background: #fff;
            border: 1px solid #3b82f6;
            color: #2563eb;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            transition: all 0.15s ease;
        }
        .lms-btn-new-section:hover {
            background: #eff6ff;
        }

        /* Right column: Lesson details editor */
        .lms-curriculum-right {
            background: #f8fafc;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-sizing: border-box;
        }
        .lms-empty-editor-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: #64748b;
        }
        .lms-lesson-editor-form {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
            display: flex;
            flex-direction: column;
        }
        .lms-lesson-editor-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .lms-editor-title-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        #lms-edit-lesson-title {
            border: none;
            border-bottom: 1px solid #cbd5e1;
            font-size: 18px;
            font-weight: 600;
            color: #0f172a;
            padding: 4px 0;
            flex: 1;
            outline: none;
        }
        #lms-edit-lesson-title:focus {
            border-bottom-color: #3b82f6;
            box-shadow: none;
        }
        .lms-btn-save-lesson {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .lms-btn-save-lesson:hover {
            background: #1d4ed8;
        }
        .lms-lesson-editor-tabs {
            display: flex;
            gap: 16px;
            border-bottom: 1px solid #e2e8f0;
            margin-top: 12px;
        }
        .lms-lesson-tab {
            background: transparent;
            border: none;
            color: #3b82f6;
            padding: 8px 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 2px solid #3b82f6;
            outline: none;
        }
        .lms-lesson-editor-body {
            padding: 24px;
        }
        .lms-field-group {
            margin-bottom: 20px;
        }
        .lms-field-group label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            margin-bottom: 8px;
        }
        .lms-field-group input[type="text"],
        .lms-field-group select,
        .lms-field-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .lms-field-group input[type="text"]:focus,
        .lms-field-group select:focus,
        .lms-field-group textarea:focus {
            border-color: #3b82f6;
        }

        /* Settings Panels */
        .lms-panel-inner {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
        }
        .lms-panel-inner h3 {
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .lms-settings-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
    </style>

    <!-- Banner to switch back to Fullscreen mode -->
    <div class="lms-fullscreen-toggle-banner" id="lms-fullscreen-toggle-banner" style="display: none;">
        <div>
            <h4><?php _e('Modern Fullscreen Builder Available', 'reandaily-lms-theme'); ?></h4>
            <p><?php _e('You are currently editing this course in the classic editor layout. Switch to the interactive dashboard for a better experience.', 'reandaily-lms-theme'); ?></p>
        </div>
        <button type="button" class="button button-primary button-large" id="lms-btn-enter-fullscreen" style="background:#2563eb; border-color:#2563eb;"><?php _e('Use Fullscreen Builder', 'reandaily-lms-theme'); ?></button>
    </div>

    <!-- The Fullscreen Overlay -->
    <div class="lms-fullscreen-builder" id="lms-fullscreen-builder-overlay" style="display: none;">
        <!-- Top Navigation -->
        <div class="lms-top-nav">
            <div class="lms-nav-left">
                <a href="<?php echo admin_url( 'edit.php?post_type=courses' ); ?>" class="lms-back-link">
                    <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Back to courses', 'reandaily-lms-theme'); ?>
                </a>
                <span class="lms-nav-divider"></span>
                <input type="text" id="lms-course-top-title" value="<?php echo esc_attr( $post->post_title ); ?>" placeholder="<?php _e('Course Title', 'reandaily-lms-theme'); ?>">
            </div>
            
            <div class="lms-nav-center">
                <button type="button" class="lms-nav-tab active" data-panel="course-syllabus"><?php _e('Curriculum', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-general"><?php _e('Settings', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-description"><?php _e('Description', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-pricing"><?php _e('Pricing', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-media"><?php _e('Media', 'reandaily-lms-theme'); ?></button>
            </div>
            
            <div class="lms-nav-right">
                <button type="button" class="lms-btn-toggle-old"><?php _e('Switch to old builder', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-btn-publish" onclick="jQuery('#publish').click();"><?php _e('Publish', 'reandaily-lms-theme'); ?></button>
                <?php if ( get_post_status( $post->ID ) === 'publish' ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank" class="lms-btn-view"><?php _e('View', 'reandaily-lms-theme'); ?></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panels Container -->
        <div class="lms-workspace-content">
            <!-- 1. Curriculum Workspace -->
            <div id="course-syllabus" class="lms-workspace-panel active">
                <div class="lms-curriculum-grid">
                    <!-- Left column sections manager -->
                    <div class="lms-curriculum-left">
                        <div class="lms-curriculum-header">
                            <h3><?php _e('Curriculum', 'reandaily-lms-theme'); ?></h3>
                        </div>
                        
                        <div class="lms-sections-container" id="lms-sections-sortable">
                            <!-- Injected by JS -->
                        </div>
                        
                        <button type="button" class="lms-btn-new-section" id="lms-add-new-section-btn">
                            <span class="dashicons dashicons-plus"></span> <?php _e('New section', 'reandaily-lms-theme'); ?>
                        </button>
                    </div>
                    
                    <!-- Right column lesson settings editor -->
                    <div class="lms-curriculum-right" id="lms-lesson-editor-panel">
                        <div class="lms-empty-editor-state">
                            <span class="dashicons dashicons-edit" style="font-size: 48px; width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 12px;"></span>
                            <p><?php _e('Select a lesson on the left to configure its details.', 'reandaily-lms-theme'); ?></p>
                        </div>
                        
                        <div class="lms-lesson-editor-form" style="display: none;">
                            <div class="lms-lesson-editor-header">
                                <div class="lms-editor-title-row">
                                    <span class="dashicons dashicons-video-alt3" style="color: #64748b;"></span>
                                    <input type="text" id="lms-edit-lesson-title" placeholder="<?php _e('Lesson Title', 'reandaily-lms-theme'); ?>">
                                    <button type="button" class="lms-btn-save-lesson" id="lms-save-lesson-btn"><?php _e('Save', 'reandaily-lms-theme'); ?></button>
                                </div>
                                <div class="lms-lesson-editor-tabs">
                                    <button type="button" class="lms-lesson-tab active"><?php _e('Lesson Settings', 'reandaily-lms-theme'); ?></button>
                                </div>
                            </div>
                            
                            <div class="lms-lesson-editor-body">
                                <input type="hidden" id="lms-edit-lesson-id">
                                
                                <div class="lms-field-group">
                                    <label><?php _e('Lesson Video URL', 'reandaily-lms-theme'); ?></label>
                                    <input type="text" id="lms-edit-lesson-video-url" placeholder="e.g. YouTube or Vimeo video URL, or direct MP4 URL">
                                </div>
                                
                                <div class="lms-field-group">
                                    <label><?php _e('Lesson Duration', 'reandaily-lms-theme'); ?></label>
                                    <input type="text" id="lms-edit-lesson-duration" placeholder="e.g. 15m, 1h 20m">
                                </div>
                                
                                <div class="lms-field-group" style="display: flex; align-items: center; gap: 8px; margin-top: 24px;">
                                    <input type="checkbox" id="lms-edit-lesson-preview" value="1">
                                    <label for="lms-edit-lesson-preview" style="margin-bottom: 0; font-weight: normal; cursor: pointer;">
                                        <?php _e('Enable free preview (students can watch before paying)', 'reandaily-lms-theme'); ?>
                                    </label>
                                </div>
                                
                                <div class="lms-field-group" style="margin-top: 24px;">
                                    <label><?php _e('Lesson Content / Text', 'reandaily-lms-theme'); ?></label>
                                    <textarea id="lms-edit-lesson-content" rows="10" placeholder="<?php _e('Add text instructions or notes for this lesson...', 'reandaily-lms-theme'); ?>"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Settings Workspace -->
            <div id="course-general" class="lms-workspace-panel">
                <div class="lms-panel-inner">
                    <h3><?php _e('General Course Settings', 'reandaily-lms-theme'); ?></h3>
                    <div class="lms-settings-form">
                        <div class="lms-field-group">
                            <label for="lms_duration_fs"><?php _e('Course Duration', 'reandaily-lms-theme'); ?></label>
                            <input type="text" id="lms_duration_fs" value="<?php echo esc_attr( $duration ); ?>" placeholder="e.g. 10 Hours, 4 Weeks">
                        </div>
                        <div class="lms-field-group">
                            <label for="lms_level_fs"><?php _e('Difficulty Level', 'reandaily-lms-theme'); ?></label>
                            <select id="lms_level_fs">
                                <option value="All Levels" <?php selected( $level, 'All Levels' ); ?>><?php _e('All Levels', 'reandaily-lms-theme'); ?></option>
                                <option value="Beginner" <?php selected( $level, 'Beginner' ); ?>><?php _e('Beginner', 'reandaily-lms-theme'); ?></option>
                                <option value="Intermediate" <?php selected( $level, 'Intermediate' ); ?>><?php _e('Intermediate', 'reandaily-lms-theme'); ?></option>
                                <option value="Advanced" <?php selected( $level, 'Advanced' ); ?>><?php _e('Advanced', 'reandaily-lms-theme'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Description Workspace -->
            <div id="course-description" class="lms-workspace-panel">
                <div class="lms-panel-inner">
                    <h3><?php _e('Course Description / Syllabus', 'reandaily-lms-theme'); ?></h3>
                    <?php
                    wp_editor( $course_description, 'lms_course_description_fs', array(
                        'textarea_name' => 'lms_course_description',
                        'media_buttons' => true,
                        'textarea_rows' => 15,
                        'tinymce'       => true
                    ) );
                    ?>
                </div>
            </div>

            <!-- 4. Pricing Workspace -->
            <div id="course-pricing" class="lms-workspace-panel">
                <div class="lms-panel-inner">
                    <h3><?php _e('Pricing Settings', 'reandaily-lms-theme'); ?></h3>
                    <div class="lms-settings-form">
                        <div class="lms-field-group">
                            <label for="lms_price_fs"><?php _e('USD Price ($)', 'reandaily-lms-theme'); ?></label>
                            <input type="text" id="lms_price_fs" value="<?php echo esc_attr( $price ); ?>" placeholder="e.g. 19.99 (Set 0 for free)">
                        </div>
                        <div class="lms-field-group">
                            <label for="lms_price_khr_fs"><?php _e('KHR Price (៛)', 'reandaily-lms-theme'); ?></label>
                            <input type="text" id="lms_price_khr_fs" value="<?php echo esc_attr( $price_khr ); ?>" placeholder="e.g. 80000">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Media Workspace -->
            <div id="course-media" class="lms-workspace-panel">
                <div class="lms-panel-inner">
                    <h3><?php _e('Promo Media Settings', 'reandaily-lms-theme'); ?></h3>
                    <div class="lms-settings-form">
                        <div class="lms-field-group">
                            <label for="lms_trailer_url_fs"><?php _e('Promo/Trailer Video URL', 'reandaily-lms-theme'); ?></label>
                            <input type="text" id="lms_trailer_url_fs" value="<?php echo esc_url( $trailer_url ); ?>" placeholder="e.g. YouTube or Vimeo trailer video URL">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden input storing sections data JSON structure -->
        <input type="hidden" id="lms-course-sections-input" name="lms_course_sections" value="<?php echo esc_attr( json_encode( $sections ) ); ?>">
    </div>

    <!-- Hidden Default inputs for saving standard WordPress data -->
    <div style="display:none !important;">
        <input type="text" id="lms_duration" name="lms_duration" value="<?php echo esc_attr( $duration ); ?>">
        <select id="lms_level" name="lms_level">
            <option value="All Levels" <?php selected( $level, 'All Levels' ); ?>>All Levels</option>
            <option value="Beginner" <?php selected( $level, 'Beginner' ); ?>>Beginner</option>
            <option value="Intermediate" <?php selected( $level, 'Intermediate' ); ?>>Intermediate</option>
            <option value="Advanced" <?php selected( $level, 'Advanced' ); ?>>Advanced</option>
        </select>
        <input type="text" id="lms_price" name="lms_price" value="<?php echo esc_attr( $price ); ?>">
        <input type="text" id="lms_price_khr" name="lms_price_khr" value="<?php echo esc_attr( $price_khr ); ?>">
        <input type="text" id="lms_trailer_url" name="lms_trailer_url" value="<?php echo esc_url( $trailer_url ); ?>">
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Parse sections hierarchy
            var sections = <?php echo json_encode( $sections ); ?>;
            var activeLessonId = null;

            // Load view state
            if (localStorage.getItem('lms_use_old_builder') === 'true') {
                $('#lms-fullscreen-toggle-banner').show();
                $('#lms-fullscreen-builder-overlay').hide();
            } else {
                $('#lms-fullscreen-builder-overlay').show();
                $('body').addClass('lms-fullscreen-active');
            }

            // Enter Fullscreen mode
            $('#lms-btn-enter-fullscreen').on('click', function() {
                localStorage.setItem('lms_use_old_builder', 'false');
                $('#lms-fullscreen-toggle-banner').hide();
                $('#lms-fullscreen-builder-overlay').show();
                $('body').addClass('lms-fullscreen-active');
            });

            // Toggle back to old/classic editor
            $('.lms-btn-toggle-old').on('click', function() {
                localStorage.setItem('lms_use_old_builder', 'true');
                $('#lms-fullscreen-builder-overlay').hide();
                $('#lms-fullscreen-toggle-banner').show();
                $('body').removeClass('lms-fullscreen-active');
            });

            // Top Bar tabs switching
            $('.lms-nav-tab').on('click', function() {
                var targetPanel = $(this).data('panel');
                $('.lms-nav-tab').removeClass('active');
                $(this).addClass('active');

                $('.lms-workspace-panel').removeClass('active');
                $('#' + targetPanel).addClass('active');

                // If editing tinymce in description panel, refresh it
                if (targetPanel === 'course-description' && typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }
            });

            // Keep top bar title field and original WP post title input in sync
            $('#lms-course-top-title').on('input', function() {
                $('#title').val($(this).val());
            });

            // Keep settings panel inputs in sync with default hidden inputs
            $('#lms_duration_fs').on('input', function() { $('#lms_duration').val($(this).val()); });
            $('#lms_level_fs').on('change', function() { $('#lms_level').val($(this).val()); });
            $('#lms_price_fs').on('input', function() { $('#lms_price').val($(this).val()); });
            $('#lms_price_khr_fs').on('input', function() { $('#lms_price_khr').val($(this).val()); });
            $('#lms_trailer_url_fs').on('input', function() { $('#lms_trailer_url').val($(this).val()); });

            // Initialize Sortable on sections and lesson lists
            function initSyllabusSortables() {
                // Drag sections
                $("#lms-sections-sortable").sortable({
                    handle: ".lms-section-card-header",
                    placeholder: "ui-state-highlight",
                    update: function(event, ui) {
                        saveStateFromDOM();
                    }
                });

                // Drag lessons across sections
                $(".lms-section-lessons-list").sortable({
                    connectWith: ".lms-section-lessons-list",
                    placeholder: "ui-state-highlight",
                    update: function(event, ui) {
                        saveStateFromDOM();
                    }
                }).disableSelection();
            }

            // Sync JS state from sorted DOM
            function saveStateFromDOM() {
                var updatedSections = [];
                $("#lms-sections-sortable .lms-section-card").each(function() {
                    var secId = $(this).data('id');
                    var secTitle = $(this).find('.lms-section-title-input').val();
                    var lessons = [];
                    $(this).find('.lms-section-lesson-item').each(function() {
                        lessons.push($(this).data('id'));
                    });
                    updatedSections.push({
                        id: secId,
                        title: secTitle,
                        lessons: lessons
                    });
                });
                sections = updatedSections;
                $('#lms-course-sections-input').val(JSON.stringify(sections));
            }

            // Render Sections & Lessons
            function renderCurriculum() {
                var container = $('#lms-sections-sortable');
                container.empty();

                sections.forEach(function(sec, secIndex) {
                    var sectionHtml = `
                        <div class="lms-section-card" data-id="${sec.id}">
                            <div class="lms-section-card-header">
                                <div class="lms-section-title-wrap">
                                    <span class="dashicons dashicons-menu" style="color: #94a3b8; cursor: move;"></span>
                                    <input type="text" class="lms-section-title-input" value="${sec.title}">
                                </div>
                                <div class="lms-section-actions">
                                    <button type="button" class="lms-section-action-btn lms-delete-section" title="Delete Section">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                            <ul class="lms-section-lessons-list" data-section-index="${secIndex}">
                    `;

                    // Render lessons inside this section
                    if (sec.lessons && sec.lessons.length > 0) {
                        sec.lessons.forEach(function(lessonId) {
                            var activeClass = (activeLessonId === lessonId) ? 'active' : '';
                            
                            // Find the lesson details locally or fetch via Ajax
                            sectionHtml += `
                                <li class="lms-section-lesson-item ${activeClass}" data-id="${lessonId}">
                                    <div class="lms-lesson-item-left">
                                        <span class="dashicons dashicons-video-alt3"></span>
                                        <span class="lms-lesson-item-title-label" id="lbl-lesson-${lessonId}">Lesson ID: ${lessonId}</span>
                                    </div>
                                    <div class="lms-lesson-item-actions">
                                        <button type="button" class="lms-lesson-action-btn lms-edit-lesson" title="Edit Settings">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="lms-lesson-action-btn lms-remove-lesson" title="Remove from Course">
                                            <span class="dashicons dashicons-dismiss"></span>
                                        </button>
                                    </div>
                                </li>
                            `;
                        });
                    }

                    sectionHtml += `
                            </ul>
                            <div class="lms-section-footer-btns">
                                <button type="button" class="lms-btn-add-lesson" data-section-index="${secIndex}">
                                    <span class="dashicons dashicons-plus-alt"></span> Add lesson
                                </button>
                            </div>
                        </div>
                    `;

                    container.append(sectionHtml);

                    // Pull title from server for lessons dynamically
                    if (sec.lessons) {
                        sec.lessons.forEach(function(lessonId) {
                            $.post(reandaily_lms_admin_vars.ajaxurl, {
                                action: 'reandaily_lms_get_lesson_settings',
                                nonce: reandaily_lms_admin_vars.nonce,
                                lesson_id: lessonId
                            }, function(res) {
                                if (res.success) {
                                    $(`#lbl-lesson-${lessonId}`).text(res.data.title);
                                }
                            });
                        });
                    }
                });

                initSyllabusSortables();
            }

            // Add new section
            $('#lms-add-new-section-btn').on('click', function() {
                var title = prompt("Enter section title:", "Section " + (sections.length + 1));
                if (title) {
                    sections.push({
                        id: 'sec_' + Date.now(),
                        title: title,
                        lessons: []
                    });
                    $('#lms-course-sections-input').val(JSON.stringify(sections));
                    renderCurriculum();
                }
            });

            // Update section title input directly
            $(document).on('change', '.lms-section-title-input', function() {
                saveStateFromDOM();
            });

            // Delete section
            $(document).on('click', '.lms-delete-section', function() {
                if (confirm("Are you sure you want to delete this section? Lessons inside won't be deleted but will be detached from the course.")) {
                    var card = $(this).closest('.lms-section-card');
                    card.remove();
                    saveStateFromDOM();
                    renderCurriculum();
                }
            });

            // Add Lesson inside a section via Ajax
            $(document).on('click', '.lms-btn-add-lesson', function() {
                var secIndex = $(this).data('section-index');
                var title = prompt("Enter lesson title:");
                if (title) {
                    var btn = $(this);
                    btn.prop('disabled', true).text('Creating...');

                    $.post(reandaily_lms_admin_vars.ajaxurl, {
                        action: 'reandaily_lms_create_lesson',
                        nonce: reandaily_lms_admin_vars.nonce,
                        title: title
                    }, function(res) {
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt"></span> Add lesson');
                        if (res.success) {
                            sections[secIndex].lessons.push(res.data.id);
                            $('#lms-course-sections-input').val(JSON.stringify(sections));
                            renderCurriculum();
                            // Select the newly created lesson immediately
                            loadLessonEditor(res.data.id);
                        } else {
                            alert('Failed to create lesson: ' + res.data);
                        }
                    });
                }
            });

            // Remove lesson from section
            $(document).on('click', '.lms-remove-lesson', function() {
                if (confirm('Detach this lesson from the course?')) {
                    var item = $(this).closest('.lms-section-lesson-item');
                    item.remove();
                    saveStateFromDOM();
                    renderCurriculum();
                    
                    // Hide editor if we deleted the currently active lesson
                    var id = item.data('id');
                    if (activeLessonId === id) {
                        $('.lms-lesson-editor-form').hide();
                        $('.lms-empty-editor-state').show();
                        activeLessonId = null;
                    }
                }
            });

            // Select lesson to edit
            $(document).on('click', '.lms-section-lesson-item, .lms-edit-lesson', function(e) {
                // If clicking trash icon, return
                if ($(e.target).closest('.lms-remove-lesson').length > 0) return;
                
                var id = $(this).closest('.lms-section-lesson-item').data('id');
                loadLessonEditor(id);
            });

            // Fetch lesson data and display on the right
            function loadLessonEditor(id) {
                activeLessonId = id;
                $('.lms-section-lesson-item').removeClass('active');
                $(`.lms-section-lesson-item[data-id="${id}"]`).addClass('active');

                $('.lms-empty-editor-state').hide();
                $('.lms-lesson-editor-form').hide();

                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_get_lesson_settings',
                    nonce: reandaily_lms_admin_vars.nonce,
                    lesson_id: id
                }, function(res) {
                    if (res.success) {
                        $('#lms-edit-lesson-id').val(res.data.id);
                        $('#lms-edit-lesson-title').val(res.data.title);
                        $('#lms-edit-lesson-video-url').val(res.data.video_url);
                        $('#lms-edit-lesson-duration').val(res.data.duration);
                        $('#lms-edit-lesson-preview').prop('checked', res.data.is_preview === 1);
                        $('#lms-edit-lesson-content').val(res.data.content);

                        $('.lms-lesson-editor-form').fadeIn(150);
                    } else {
                        alert('Could not load lesson data.');
                    }
                });
            }

            // Save lesson settings
            $('#lms-save-lesson-btn').on('click', function() {
                var id = $('#lms-edit-lesson-id').val();
                var btn = $(this);
                btn.text('Saving...').prop('disabled', true);

                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_save_lesson_settings',
                    nonce: reandaily_lms_admin_vars.nonce,
                    lesson_id: id,
                    title: $('#lms-edit-lesson-title').val(),
                    video_url: $('#lms-edit-lesson-video-url').val(),
                    duration: $('#lms-edit-lesson-duration').val(),
                    is_preview: $('#lms-edit-lesson-preview').is(':checked') ? 1 : 0,
                    content: $('#lms-edit-lesson-content').val()
                }, function(res) {
                    btn.text('Save').prop('disabled', false);
                    if (res.success) {
                        $(`#lbl-lesson-${id}`).text($('#lms-edit-lesson-title').val());
                    } else {
                        alert('Failed to save settings: ' + res.data);
                    }
                });
            });

            // Initial curriculum render
            renderCurriculum();
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
        /* Force fullwidth edit page layout */
        #poststuff {
            max-width: 100% !important;
        }
        #post-body.columns-2 {
            margin-right: 0 !important;
        }
        #postbox-container-1 {
            float: none !important;
            width: 100% !important;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        #postbox-container-1 .postbox {
            flex: 1;
            min-width: 280px;
            margin-bottom: 0 !important;
        }
        #postbox-container-2 {
            width: 100% !important;
            float: none !important;
        }
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
        if ( isset( $_POST['lms_course_sections'] ) ) {
            $sections_json = stripslashes( $_POST['lms_course_sections'] );
            $sections_data = json_decode( $sections_json, true );
            if ( is_array( $sections_data ) ) {
                update_post_meta( $post_id, '_course_sections', $sections_data );
                
                // Reconstruct and update flat _lessons_order list for backward compatibility
                $flat_lessons = array();
                foreach ( $sections_data as $section ) {
                    if ( isset( $section['lessons'] ) && is_array( $section['lessons'] ) ) {
                        foreach ( $section['lessons'] as $lid ) {
                            $flat_lessons[] = intval( $lid );
                        }
                    }
                }
                update_post_meta( $post_id, '_lessons_order', $flat_lessons );
            }
        } elseif ( isset( $_POST['lms_lessons_order'] ) ) {
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


// ── 16. ENROLLMENT MANAGER ADMIN PANEL ───────────────────────────────────────

function reandaily_lms_register_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=courses',
        __( 'LMS Enrollments', 'reandaily-lms-theme' ),
        __( 'Enrollments', 'reandaily-lms-theme' ),
        'manage_options',
        'reandaily-lms-enrollments',
        'reandaily_lms_admin_enrollments_page'
    );
}
add_action( 'admin_menu', 'reandaily_lms_register_admin_menu' );

function reandaily_lms_admin_enrollments_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'reandaily_lms';

    // Handle Actions (Approve/Pending/Delete)
    if ( isset( $_GET['action'] ) && isset( $_GET['id'] ) && current_user_can( 'manage_options' ) ) {
        $id = intval( $_GET['id'] );
        $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
        
        if ( wp_verify_nonce( $nonce, 'lms_enrollment_action_' . $id ) ) {
            if ( $_GET['action'] === 'approve' ) {
                $wpdb->update( $table_name, array( 'status' => 'active' ), array( 'id' => $id ) );
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Enrollment approved successfully!', 'reandaily-lms-theme' ) . '</p></div>';
            } elseif ( $_GET['action'] === 'pending' ) {
                $wpdb->update( $table_name, array( 'status' => 'pending' ), array( 'id' => $id ) );
                echo '<div class="notice notice-warning is-dismissible"><p>' . __( 'Enrollment status set to pending.', 'reandaily-lms-theme' ) . '</p></div>';
            } elseif ( $_GET['action'] === 'delete' ) {
                $wpdb->delete( $table_name, array( 'id' => $id ) );
                echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Enrollment request deleted.', 'reandaily-lms-theme' ) . '</p></div>';
            }
        }
    }

    // Fetch Enrollments
    $enrollments = $wpdb->get_results( "
        SELECT e.*, u.user_login, u.user_email, p.post_title 
        FROM $table_name e 
        LEFT JOIN $wpdb->users u ON e.user_id = u.ID 
        LEFT JOIN $wpdb->posts p ON e.course_id = p.ID 
        ORDER BY e.created_at DESC
    " );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e( 'LMS Enrollment Requests', 'reandaily-lms-theme' ); ?></h1>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php _e( 'Student Details', 'reandaily-lms-theme' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Course', 'reandaily-lms-theme' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Bill # / Payment', 'reandaily-lms-theme' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Receipt / Slip', 'reandaily-lms-theme' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Status', 'reandaily-lms-theme' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Date', 'reandaily-lms-theme' ); ?></th>
                    <th scope="col" class="manage-column"><?php _e( 'Actions', 'reandaily-lms-theme' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $enrollments ) ) : ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #646970;">
                            <?php _e( 'No enrollment requests found.', 'reandaily-lms-theme' ); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $enrollments as $e ) : 
                        $nonce_url = 'lms_enrollment_action_' . $e->id;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $e->user_login ); ?></strong><br>
                                <span style="font-size: 12px; color: #646970;"><?php echo esc_html( $e->user_email ); ?></span>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $e->post_title ? $e->post_title : 'Course ID: ' . $e->course_id ); ?></strong>
                            </td>
                            <td>
                                <code><?php echo esc_html( $e->bill_number ); ?></code><br>
                                <span style="font-size: 11px; text-transform: uppercase; padding: 2px 6px; background: #e2e8f0; border-radius: 4px; font-weight: 600;">
                                    <?php echo esc_html( $e->payment_method ); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $receipt_url = get_user_meta( $e->user_id, '_last_receipt_' . $e->course_id, true );
                                if ( $receipt_url ) : ?>
                                    <a href="<?php echo esc_url( $receipt_url ); ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-media-document" style="font-size:18px;"></span>
                                        <?php _e( 'View Receipt Slip', 'reandaily-lms-theme' ); ?>
                                    </a>
                                <?php else : ?>
                                    <span style="color: #8c8f94; font-style: italic;"><?php _e( 'No slip uploaded', 'reandaily-lms-theme' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $e->status === 'active' ) : ?>
                                    <span style="color: #10b981; font-weight: bold; background: rgba(16,185,129,0.1); padding: 4px 8px; border-radius: 4px;"><?php _e( 'Active', 'reandaily-lms-theme' ); ?></span>
                                <?php elseif ( $e->status === 'pending' ) : ?>
                                    <span style="color: #f59e0b; font-weight: bold; background: rgba(245,158,11,0.1); padding: 4px 8px; border-radius: 4px;"><?php _e( 'Pending Approval', 'reandaily-lms-theme' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #ef4444; font-weight: bold; background: rgba(239,68,68,0.1); padding: 4px 8px; border-radius: 4px;"><?php echo esc_html( $e->status ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $e->created_at ) ); ?>
                            </td>
                            <td>
                                <?php if ( $e->status !== 'active' ) : ?>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=courses&page=reandaily-lms-enrollments&action=approve&id=' . $e->id ), $nonce_url ); ?>" class="button button-primary button-small" style="background:#10b981; border-color:#10b981;"><?php _e( 'Approve', 'reandaily-lms-theme' ); ?></a>
                                <?php else : ?>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=courses&page=reandaily-lms-enrollments&action=pending&id=' . $e->id ), $nonce_url ); ?>" class="button button-secondary button-small"><?php _e( 'Make Pending', 'reandaily-lms-theme' ); ?></a>
                                <?php endif; ?>
                                <a href="<?php echo wp_nonce_url( admin_url( 'edit.php?post_type=courses&page=reandaily-lms-enrollments&action=delete&id=' . $e->id ), $nonce_url ); ?>" class="button button-link-delete button-small" style="color:#ef4444; margin-left: 8px;" onclick="return confirm('<?php _e( 'Are you sure you want to delete this registration request?', 'reandaily-lms-theme' ); ?>')"><?php _e( 'Delete', 'reandaily-lms-theme' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ── 17. AJAX ACTIONS FOR MODERN COURSE BUILDER ───────────────────────────────

add_action( 'wp_ajax_reandaily_lms_create_lesson', 'reandaily_lms_ajax_create_lesson' );
function reandaily_lms_ajax_create_lesson() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    if ( empty( $title ) ) {
        wp_send_json_error( 'Lesson title is required' );
    }

    $lesson_id = wp_insert_post( array(
        'post_title'  => $title,
        'post_type'   => 'lessons',
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $lesson_id ) ) {
        wp_send_json_error( $lesson_id->get_error_message() );
    }

    wp_send_json_success( array(
        'id'    => $lesson_id,
        'title' => $title,
    ) );
}

add_action( 'wp_ajax_reandaily_lms_get_lesson_settings', 'reandaily_lms_ajax_get_lesson_settings' );
function reandaily_lms_ajax_get_lesson_settings() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    if ( ! $lesson_id ) {
        wp_send_json_error( 'Invalid lesson' );
    }

    $video_url  = get_post_meta( $lesson_id, '_video_url', true );
    $duration   = get_post_meta( $lesson_id, '_duration', true );
    $is_preview = get_post_meta( $lesson_id, '_is_preview', true );
    $title      = get_the_title( $lesson_id );
    $content    = get_post_field( 'post_content', $lesson_id );

    wp_send_json_success( array(
        'id'         => $lesson_id,
        'title'      => $title,
        'video_url'  => $video_url,
        'duration'   => $duration,
        'is_preview' => ( $is_preview === '1' || $is_preview === true ) ? 1 : 0,
        'content'    => $content,
    ) );
}

add_action( 'wp_ajax_reandaily_lms_save_lesson_settings', 'reandaily_lms_ajax_save_lesson_settings' );
function reandaily_lms_ajax_save_lesson_settings() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    if ( ! $lesson_id ) {
        wp_send_json_error( 'Invalid lesson' );
    }

    $title      = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    $video_url  = isset( $_POST['video_url'] ) ? esc_url_raw( $_POST['video_url'] ) : '';
    $duration   = isset( $_POST['duration'] ) ? sanitize_text_field( $_POST['duration'] ) : '';
    $is_preview = isset( $_POST['is_preview'] ) ? '1' : '0';
    $content    = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';

    // Update title and content
    wp_update_post( array(
        'ID'           => $lesson_id,
        'post_title'  => $title,
        'post_content' => $content,
    ) );

    update_post_meta( $lesson_id, '_video_url', $video_url );
    update_post_meta( $lesson_id, '_duration', $duration );
    update_post_meta( $lesson_id, '_is_preview', $is_preview );

    wp_send_json_success( 'Saved successfully' );
}



