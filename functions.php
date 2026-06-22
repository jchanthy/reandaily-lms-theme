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
            'name'                  => __( 'Courses', 'reandaily-lms-theme' ),
            'singular_name'         => __( 'Course', 'reandaily-lms-theme' ),
            'add_new'               => __( 'Add New Course', 'reandaily-lms-theme' ),
            'add_new_item'          => __( 'Add New Course', 'reandaily-lms-theme' ),
            'edit_item'             => __( 'Edit Course', 'reandaily-lms-theme' ),
            'new_item'              => __( 'New Course', 'reandaily-lms-theme' ),
            'view_item'             => __( 'View Course', 'reandaily-lms-theme' ),
            'search_items'          => __( 'Search Courses', 'reandaily-lms-theme' ),
            'not_found'             => __( 'No courses found', 'reandaily-lms-theme' ),
            'not_found_in_trash'    => __( 'No courses found in trash', 'reandaily-lms-theme' ),
            'all_items'             => __( 'All Courses', 'reandaily-lms-theme' ),
            'name_admin_bar'        => __( 'Course', 'reandaily-lms-theme' ),
        ),
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'thumbnail' ),
        'menu_icon'   => 'dashicons-welcome-learn-more',
        'rewrite'     => array( 'slug' => 'courses' ),
        'show_in_rest'=> false,
        'map_meta_cap'=> true,
        'show_in_menu'=> false,
    ) );

    // Lessons Custom Post Type
    register_post_type( 'lessons', array(
        'labels' => array(
            'name'                  => __( 'Lessons', 'reandaily-lms-theme' ),
            'singular_name'         => __( 'Lesson', 'reandaily-lms-theme' ),
            'add_new'               => __( 'Add New Lesson', 'reandaily-lms-theme' ),
            'add_new_item'          => __( 'Add New Lesson', 'reandaily-lms-theme' ),
            'edit_item'             => __( 'Edit Lesson', 'reandaily-lms-theme' ),
            'new_item'              => __( 'New Lesson', 'reandaily-lms-theme' ),
            'view_item'             => __( 'View Lesson', 'reandaily-lms-theme' ),
            'search_items'          => __( 'Search Lessons', 'reandaily-lms-theme' ),
            'not_found'             => __( 'No lessons found', 'reandaily-lms-theme' ),
            'not_found_in_trash'    => __( 'No lessons found in trash', 'reandaily-lms-theme' ),
            'all_items'             => __( 'All Lessons', 'reandaily-lms-theme' ),
            'name_admin_bar'        => __( 'Lesson', 'reandaily-lms-theme' ),
        ),
        'public'      => true,
        'has_archive' => false,
        'supports'    => array( 'title', 'editor', 'comments' ),
        'menu_icon'   => 'dashicons-playlist-video',
        'rewrite'     => array( 'slug' => 'lessons' ),
        'show_in_rest'=> false,
        'map_meta_cap'=> true,
        'show_in_menu'=> false,
    ) );

    // Submissions Custom Post Type (for assignments grading)
    register_post_type( 'submissions', array(
        'labels' => array(
            'name'                  => __( 'Submissions', 'reandaily-lms-theme' ),
            'singular_name'         => __( 'Submission', 'reandaily-lms-theme' ),
            'add_new'               => __( 'Add New Submission', 'reandaily-lms-theme' ),
            'add_new_item'          => __( 'Add New Submission', 'reandaily-lms-theme' ),
            'edit_item'             => __( 'View Submission', 'reandaily-lms-theme' ),
            'new_item'              => __( 'New Submission', 'reandaily-lms-theme' ),
            'view_item'             => __( 'View Submission', 'reandaily-lms-theme' ),
            'search_items'          => __( 'Search Submissions', 'reandaily-lms-theme' ),
            'not_found'             => __( 'No submissions found', 'reandaily-lms-theme' ),
            'not_found_in_trash'    => __( 'No submissions found in trash', 'reandaily-lms-theme' ),
            'all_items'             => __( 'All Submissions', 'reandaily-lms-theme' ),
            'name_admin_bar'        => __( 'Submission', 'reandaily-lms-theme' ),
        ),
        'public'      => false,
        'show_ui'     => true,
        'show_in_menu'=> false,
        'supports'    => array( 'title', 'editor' ),
        'map_meta_cap'=> true,
    ) );

    // Questions Bank CPT
    register_post_type( 'lms_questions', array(
        'labels' => array(
            'name'                  => __( 'Questions Bank', 'reandaily-lms-theme' ),
            'singular_name'         => __( 'Question', 'reandaily-lms-theme' ),
            'add_new'               => __( 'Add New Question', 'reandaily-lms-theme' ),
            'add_new_item'          => __( 'Add New Question to Bank', 'reandaily-lms-theme' ),
            'edit_item'             => __( 'Edit Question', 'reandaily-lms-theme' ),
            'new_item'              => __( 'New Question', 'reandaily-lms-theme' ),
            'view_item'             => __( 'View Question', 'reandaily-lms-theme' ),
            'search_items'          => __( 'Search Questions Bank', 'reandaily-lms-theme' ),
            'not_found'             => __( 'No questions found', 'reandaily-lms-theme' ),
            'not_found_in_trash'    => __( 'No questions found in trash', 'reandaily-lms-theme' ),
            'all_items'             => __( 'All Questions', 'reandaily-lms-theme' ),
        ),
        'public'      => false,
        'show_ui'     => true,
        'supports'    => array( 'title' ),
        'menu_icon'   => 'dashicons-editor-help',
        'show_in_menu'=> false,
    ) );

    // Course Categories Taxonomy
    register_taxonomy( 'course_category', 'courses', array(
        'labels' => array(
            'name'              => _x( 'Course Categories', 'taxonomy general name', 'reandaily-lms-theme' ),
            'singular_name'     => _x( 'Course Category', 'taxonomy singular name', 'reandaily-lms-theme' ),
            'search_items'      => __( 'Search Course Categories', 'reandaily-lms-theme' ),
            'all_items'         => __( 'All Course Categories', 'reandaily-lms-theme' ),
            'parent_item'       => __( 'Parent Course Category', 'reandaily-lms-theme' ),
            'parent_item_colon' => __( 'Parent Course Category:', 'reandaily-lms-theme' ),
            'edit_item'         => __( 'Edit Course Category', 'reandaily-lms-theme' ),
            'update_item'       => __( 'Update Course Category', 'reandaily-lms-theme' ),
            'add_new_item'      => __( 'Add New Course Category', 'reandaily-lms-theme' ),
            'new_item_name'     => __( 'New Course Category Name', 'reandaily-lms-theme' ),
            'menu_name'         => __( 'Course Categories', 'reandaily-lms-theme' ),
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'course-category' ),
    ) );

    // Question Categories Taxonomy
    register_taxonomy( 'question_category', 'lms_questions', array(
        'labels' => array(
            'name'              => _x( 'Question Categories', 'taxonomy general name', 'reandaily-lms-theme' ),
            'singular_name'     => _x( 'Question Category', 'taxonomy singular name', 'reandaily-lms-theme' ),
            'search_items'      => __( 'Search Question Categories', 'reandaily-lms-theme' ),
            'all_items'         => __( 'All Question Categories', 'reandaily-lms-theme' ),
            'parent_item'       => __( 'Parent Question Category', 'reandaily-lms-theme' ),
            'parent_item_colon' => __( 'Parent Question Category:', 'reandaily-lms-theme' ),
            'edit_item'         => __( 'Edit Question Category', 'reandaily-lms-theme' ),
            'update_item'       => __( 'Update Question Category', 'reandaily-lms-theme' ),
            'add_new_item'      => __( 'Add New Question Category', 'reandaily-lms-theme' ),
            'new_item_name'     => __( 'New Question Category Name', 'reandaily-lms-theme' ),
            'menu_name'         => __( 'Question Categories', 'reandaily-lms-theme' ),
        ),
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => false,
    ) );

    // Default categories to create
    $default_cats = array( 'Development', 'Design', 'Marketing', 'Business' );
    foreach ( $default_cats as $cat ) {
        if ( ! term_exists( $cat, 'course_category' ) ) {
            wp_insert_term( $cat, 'course_category' );
        }
    }
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
    wp_enqueue_media();
    global $post;
    $post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : '';
    if ( ! $post_type && $post ) {
        $post_type = get_post_type( $post );
    }
    if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
        if ( $post_type === 'courses' || $post_type === 'lessons' ) {
            wp_enqueue_style( 'flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.13' );
            wp_enqueue_script( 'flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', array(), '4.6.13', false );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-droppable' );
            wp_enqueue_media();
            wp_enqueue_editor();
            wp_localize_script( 'jquery-ui-sortable', 'reandaily_lms_admin_vars', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'reandaily_lms_ajax_nonce' ),
                'post_id' => $post ? $post->ID : 0,
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

    add_meta_box(
        'reandaily_lms_question_editor',
        __( 'Question Data & Setup', 'reandaily-lms-theme' ),
        'reandaily_lms_question_editor_html',
        'lms_questions',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'reandaily_lms_register_metaboxes' );

function reandaily_lms_question_editor_html( $post ) {
    $q_type = get_post_meta( $post->ID, '_q_type', true ) ?: 'single';
    $q_data_json = get_post_meta( $post->ID, '_q_data', true ) ?: '{"type":"single","question":"","options":["Option A","Option B"],"answer":0}';
    
    wp_nonce_field( 'lms_save_question_editor_nonce', 'lms_question_editor_nonce' );
    ?>
    <div style="padding: 12px;">
        <p style="margin-top:0;">
            <label style="font-weight:600; display:block; margin-bottom:8px;"><?php _e( 'Question Data (JSON Format)', 'reandaily-lms-theme' ); ?></label>
            <textarea name="lms_q_data" style="width:100%; font-family:monospace; min-height: 200px; padding:10px; border-radius:6px; border:1px solid #cbd5e1;"><?php echo esc_textarea( $q_data_json ); ?></textarea>
        </p>
        <p style="color:#64748b; font-size:12.5px; margin-top:-4px;">
            <?php _e( 'This question will be available to import in any quiz. We recommend managing questions directly inside quizzes and clicking "Save to Bank".', 'reandaily-lms-theme' ); ?>
        </p>
    </div>
    <?php
}

function reandaily_lms_save_question_meta( $post_id ) {
    if ( ! isset( $_POST['lms_question_editor_nonce'] ) || ! wp_verify_nonce( $_POST['lms_question_editor_nonce'], 'lms_save_question_editor_nonce' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['lms_q_data'] ) ) {
        $q_data_json = stripslashes( $_POST['lms_q_data'] );
        $q_data = json_decode( $q_data_json, true );
        if ( is_array( $q_data ) ) {
            update_post_meta( $post_id, '_q_type', sanitize_text_field( $q_data['type'] ) );
            update_post_meta( $post_id, '_q_data', $q_data_json );
        }
    }
}
add_action( 'save_post', 'reandaily_lms_save_question_meta' );

// Course Builder Metabox HTML (Tabbed)
function reandaily_lms_course_builder_html( $post ) {
    wp_nonce_field( 'reandaily_lms_save_course_meta', 'reandaily_lms_course_meta_nonce' );

    $price = get_post_meta( $post->ID, '_price', true );
    $price_khr = get_post_meta( $post->ID, '_price_khr', true );
    $duration = get_post_meta( $post->ID, '_duration', true );
    $video_duration = get_post_meta( $post->ID, '_video_duration', true );
    $preview_description = get_post_meta( $post->ID, '_preview_description', true );
    $featured_course = get_post_meta( $post->ID, '_featured_course', true );
    $lock_lessons = get_post_meta( $post->ID, '_lock_lessons_order', true );
    $access_duration = get_post_meta( $post->ID, '_access_duration', true );
    $access_device_types = get_post_meta( $post->ID, '_access_device_types', true );
    $certification_info = get_post_meta( $post->ID, '_certification_info', true );
    $level = get_post_meta( $post->ID, '_level', true );
    $trailer_url = get_post_meta( $post->ID, '_trailer_url', true );
    
    // Retrieve course FAQ data
    $price_faq = get_post_meta( $post->ID, '_price_faq', true );
    $price_khr_faq = get_post_meta( $post->ID, '_price_khr_faq', true );
    $price_type_faq = get_post_meta( $post->ID, '_price_type_faq', true ) ?: 'free';

    // Retrieve course notice data and set default placeholder text if empty
    $course_notice = get_post_meta( $post->ID, '_course_notice', true );
    if ( empty( $course_notice ) ) {
        $course_notice = '<h3>Productivity Hacks to Get More Done</h3><ol><li><strong>Facebook News Feed Eradicator (free chrome extension)</strong> Stay focused by removing your Facebook newsfeed and replacing it with an inspirational quote. Disable the tool anytime you want to see what friends are up to!</li><li><strong>Hide My Inbox (free chrome extension for Gmail)</strong> Stay focused by hiding your inbox. Click "show your inbox" at a scheduled time and batch processs everything one go.</li><li><strong>Habitica (free mobile + web app)</strong> Gamify your to do list. Treat your life like a game and earn gold goins for getting stuff done!</li></ol>';
    }
    
    // Get assigned category
    $course_terms = wp_get_post_terms( $post->ID, 'course_category', array( 'fields' => 'ids' ) );
    $selected_category_id = ! empty( $course_terms ) && ! is_wp_error( $course_terms ) ? intval( $course_terms[0] ) : 0;

    // Get all categories for selection
    $course_categories = get_terms( array(
        'taxonomy'   => 'course_category',
        'hide_empty' => false,
    ) );
    // Retrieve FAQ list
    $faq = get_post_meta( $post->ID, '_course_faq', true );
    if ( ! is_array( $faq ) || empty( $faq ) ) {
        $faq = array(
            array(
                'question' => __('តើវគ្គសិក្សានេះរៀបចំឡើងសម្រាប់អ្នកណា? (Who is this course for?)', 'reandaily-lms-theme'),
                'answer'   => __('វគ្គសិក្សានេះត្រូវបានបង្កើតឡើងសម្រាប់សិស្ស និស្សិត និងអ្នកដែលចង់ចាប់ផ្តើមរៀនពីមូលដ្ឋានគ្រឹះរហូតដល់កម្រិតខ្ពស់ ដោយមិនតម្រូវឱ្យមានបទពិសោធន៍ពីមុនមកនោះទេ។ (This course is designed for students and beginners, requiring no prior experience.)', 'reandaily-lms-theme')
            ),
            array(
                'question' => __('តើខ្ញុំអាចចូលរៀនបានរយៈពេលប៉ុន្មាន? (How long do I have access?)', 'reandaily-lms-theme'),
                'answer'   => __('បន្ទាប់ពីចុះឈ្មោះរួច អ្នកនឹងទទួលបានសិទ្ធិចូលរៀនមួយជីវិត ដោយគ្មានកំណត់ពេលវេលា ឬចំនួនដងនោះទេ។ (Once enrolled, you get lifetime access with no time limits.)', 'reandaily-lms-theme')
            ),
            array(
                'question' => __('តើវគ្គសិក្សានេះមានការផ្ដល់ជូនវិញ្ញាបនបត្រដែរឬទេ? (Is a certificate included?)', 'reandaily-lms-theme'),
                'answer'   => __('បាទ/ចាស! បន្ទាប់ពីសិក្សាចប់គ្រប់មេរៀន និងបំពេញកិច្ចការងារវាយតម្លៃរួចរាល់ អ្នកនឹងទទួលបានវិញ្ញាបនបត្របញ្ជាក់ការសិក្សាជាផ្លូវការ។ (Yes! You will receive an official certificate upon course completion.)', 'reandaily-lms-theme')
            )
        );
    }
    
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

        body.lms-fullscreen-active {
            overflow: hidden !important;
            height: 100vh !important;
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
        .lms-btn-save-draft {
            background: transparent;
            border: 1px solid #475569;
            color: #cbd5e1;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .lms-btn-save-draft:hover {
            background: #1e293b;
            color: #fff;
            border-color: #64748b;
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
        .lms-btn-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2.5px solid rgba(255,255,255,0.35);
            border-radius: 50%;
            border-top-color: #ffffff;
            animation: lms-button-spin 0.75s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes lms-button-spin {
            to { transform: rotate(360deg); }
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
        #course-syllabus.lms-workspace-panel {
            overflow: hidden !important;
        }
        #course-syllabus.lms-workspace-panel.active,
        #course-pricing.lms-workspace-panel.active,
        #course-faq.lms-workspace-panel.active,
        #course-notice.lms-workspace-panel.active {
            display: flex !important;
            flex-direction: column;
            overflow: hidden !important;
        }
        #course-general.lms-workspace-panel.active {
            display: flex !important;
            flex-direction: row;
            overflow: hidden !important;
        }
        .lms-settings-sidebar {
            width: 240px;
            background: #fff;
            border-right: 1px solid #cbd5e1;
            display: flex;
            flex-direction: column;
            padding: 24px 0 0 0;
            box-sizing: border-box;
            flex-shrink: 0;
        }
        .lms-settings-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
        }
        .lms-settings-menu-item:hover {
            background: #f8fafc;
            color: #0f172a;
        }
        .lms-settings-menu-item.active {
            background: #f1f5f9;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 600;
        }
        .lms-settings-menu-item .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit;
        }
        .lms-settings-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            scrollbar-gutter: stable;
            background: #f8fafc;
            box-sizing: border-box;
        }
        .lms-settings-card {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .lms-settings-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .lms-settings-card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .lms-settings-card-body {
            padding: 24px;
        }
        .lms-settings-sub-panel {
            display: none;
        }
        .lms-settings-sub-panel.active {
            display: block;
        }

        /* Curriculum grid matching the layout in screenshot */
        .lms-curriculum-grid {
            display: grid;
            grid-template-columns: 420px 1fr;
            height: 100%;
            background: #f1f5f9;
            overflow: hidden;
        }
        .lms-curriculum-left {
            border-right: 1px solid #cbd5e1;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            padding: 12px;
            height: 100%;
            box-sizing: border-box;
        }
        .lms-curriculum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-shrink: 0;
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
            overflow-y: auto;
            scrollbar-gutter: stable;
            flex: 1;
            padding-right: 4px;
            min-height: 0; /* Enable scrolling container inside flex */
        }
        .lms-section-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            flex-shrink: 0; /* Prevent section card squishing */
        }
        .lms-section-card-header {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .lms-section-card-header .lms-section-actions {
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        .lms-section-card-header:hover .lms-section-actions {
            opacity: 1;
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
            color: #ef4444;
            background: #f1f5f9;
        }
        .lms-section-action-btn.lms-edit-section-title:hover {
            color: #3b82f6;
            background: #f1f5f9;
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
            justify-content: center;
            gap: 4px;
            height: 32px;
            width: 130px;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .lms-btn-add-lesson:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }
        .lms-btn-search-material {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #64748b;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-left: auto;
            height: 32px;
            width: 130px;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .lms-btn-search-material:hover {
            color: #3b82f6;
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
            flex-shrink: 0;
            transition: all 0.15s ease;
        }
        .lms-btn-new-section:hover {
            background: #eff6ff;
        }

        /* Right column: Lesson details editor */
        .lms-curriculum-right {
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            overflow: hidden scroll;
            padding: 30px 30px 0px;
            height: calc(-60px + 100vh);
            width: 100%;
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
            height: 100%;
            overflow: hidden;
        }
        .lms-lesson-editor-form[style*="display: block"] {
            display: flex !important;
        }
        .lms-lesson-editor-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        .lms-editor-title-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }
        #lms-edit-lesson-title {
            border: none;
            font-size: 14.5px;
            font-weight: normal;
            color: #334155;
            padding: 10px 14px;
            flex: 1;
            outline: none;
            margin: 0;
            box-shadow: none;
            background: transparent;
        }
        #lms-edit-lesson-title:focus {
            box-shadow: none;
            background: transparent;
        }
        .lms-btn-save-lesson {
            background: #1d6bf3;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.15s ease;
            white-space: nowrap;
        }
        .lms-btn-save-lesson:hover {
            background: #1557c7;
        }
        .lms-lesson-editor-tabs {
            display: inline-flex;
            background: #e2e8f0;
            padding: 4px;
            border-radius: 8px;
            margin-top: 16px;
            gap: 4px;
        }
        .lms-lesson-tab {
            background: transparent;
            border: none;
            color: #64748b;
            padding: 8px 32px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 6px;
            outline: none;
            transition: all 0.2s ease;
            min-width: 120px;
            border-bottom: none !important;
        }
        .lms-lesson-tab.active {
            background: #fff;
            color: #334155;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .lms-lesson-editor-body {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
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
        
        /* Modal General Styles */
        .lms-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lms-modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 680px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            animation: lmsModalFadeIn 0.2s ease-out;
        }
        @keyframes lmsModalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .lms-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .lms-modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .lms-modal-close {
            color: #64748b;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.15s ease;
            line-height: 1;
        }
        .lms-modal-close:hover {
            color: #0f172a;
        }
        .lms-modal-body {
            padding: 24px;
        }
        .lms-modal-section-title {
            margin: 0 0 16px 0;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.5px;
        }
        .lms-activity-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        @media (max-width: 600px) {
            .lms-activity-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .lms-activity-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        .lms-activity-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1), 0 4px 6px -4px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }
        .lms-activity-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            transition: all 0.2s ease;
        }
        .lms-activity-card:hover .lms-activity-icon {
            border-color: rgba(59, 130, 246, 0.3);
            background: rgba(59, 130, 246, 0.05);
        }
        .lms-activity-icon .dashicons {
            font-size: 28px;
            width: 28px;
            height: 28px;
        }
        .lms-activity-name {
            font-size: 13.5px;
            font-weight: 500;
            color: #334155;
        }

        /* Material Upload Box */
        .lms-upload-box {
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            padding: 32px 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-top: 8px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .lms-upload-box:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.02);
        }

        /* Toggle Switch Styling */
        .lms-switch {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 22px;
            margin: 0;
        }
        .lms-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .lms-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .2s;
            border-radius: 34px;
        }
        .lms-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .2s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .lms-switch input:checked + .lms-slider {
            background-color: #3b82f6;
        }
        .lms-switch input:checked + .lms-slider:before {
            transform: translateX(20px);
        }
        /* Modern Flatpickr Calendar Override (MasterStudy LMS Style) */
        .flatpickr-calendar {
            z-index: 999999 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            border-radius: 10px !important;
            background: #ffffff !important;
            padding: 4px !important;
        }
        .flatpickr-months {
            padding: 8px 4px 4px !important;
        }
        .flatpickr-current-month {
            font-weight: 600 !important;
            font-size: 14px !important;
            color: #1e293b !important;
        }
        .flatpickr-months .flatpickr-prev-month, 
        .flatpickr-months .flatpickr-next-month {
            color: #64748b !important;
            padding: 8px !important;
            top: 4px !important;
        }
        .flatpickr-months .flatpickr-prev-month:hover, 
        .flatpickr-months .flatpickr-next-month:hover {
            color: #3b82f6 !important;
        }
        span.flatpickr-weekday {
            font-weight: 600 !important;
            color: #94a3b8 !important;
            font-size: 11px !important;
            text-transform: uppercase;
        }
        .flatpickr-day {
            border-radius: 50% !important;
            color: #334155 !important;
            font-weight: 500 !important;
            height: 36px !important;
            line-height: 36px !important;
            max-width: 36px !important;
            margin: 2px auto !important;
        }
        .flatpickr-day.today {
            border-color: #e2e8f0 !important;
            color: #3b82f6 !important;
            font-weight: 700 !important;
        }
        .flatpickr-day.selected, 
        .flatpickr-day.selected:focus, 
        .flatpickr-day.selected:hover,
        .flatpickr-day.selected.prevMonthDay,
        .flatpickr-day.selected.nextMonthDay {
            background: #3b82f6 !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        .flatpickr-day:hover {
            background: #f1f5f9 !important;
        }
        .flatpickr-day.flatpickr-disabled, 
        .flatpickr-day.flatpickr-disabled:hover {
            color: #cbd5e1 !important;
            background: transparent !important;
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
                <button type="button" class="lms-nav-tab" data-panel="course-drip"><?php _e('Drip', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-general"><?php _e('Settings', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-pricing"><?php _e('Pricing', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-faq"><?php _e('FAQ', 'reandaily-lms-theme'); ?></button>
                <button type="button" class="lms-nav-tab" data-panel="course-notice"><?php _e('Notice', 'reandaily-lms-theme'); ?></button>
            </div>
            
            <div class="lms-nav-right">
                <button type="button" class="lms-btn-toggle-old"><?php _e('Switch to old builder', 'reandaily-lms-theme'); ?></button>
                <?php if ( get_post_status( $post->ID ) !== 'publish' ) : ?>
                    <button type="button" class="lms-btn-save-draft"><?php _e('Save Draft', 'reandaily-lms-theme'); ?></button>
                <?php endif; ?>
                <button type="button" class="lms-btn-publish"><?php 
                    echo ( get_post_status( $post->ID ) === 'publish' ) ? __('Update', 'reandaily-lms-theme') : __('Publish', 'reandaily-lms-theme'); 
                ?></button>
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
                        <div class="lms-curriculum-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;"><?php _e('Curriculum', 'reandaily-lms-theme'); ?></h3>
                            <button type="button" id="lms-curriculum-toggle-all" style="background: transparent; border: none; cursor: pointer; color: #2563eb; display: flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; outline: none; padding: 4px 8px; border-radius: 4px; transition: background 0.15s ease;" data-expanded="true">
                                <span class="dashicons dashicons-arrow-up-alt2" id="lms-toggle-all-icon" style="font-size: 16px; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;"></span>
                                <span id="lms-toggle-all-text"><?php _e('Collapse All', 'reandaily-lms-theme'); ?></span>
                            </button>
                        </div>
                        
                        <div class="lms-sections-container" id="lms-sections-sortable">
                            <!-- Injected by JS -->
                        </div>
                        
                        <div class="lms-add-section-bar" style="display: flex; gap: 8px; margin-top: 20px; flex-shrink: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; align-items: center; box-sizing: border-box; height: 44px;">
                            <span class="dashicons dashicons-plus" style="color: #3b82f6; font-size: 18px; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;"></span>
                            <input type="text" id="lms-new-section-title-input" placeholder="<?php _e('Enter section title...', 'reandaily-lms-theme'); ?>" style="flex: 1; border: none; outline: none; font-size: 13px; font-weight: 600; color: #1e293b; background: transparent; padding: 0; box-shadow: none;">
                            <button type="button" id="lms-add-new-section-submit" style="background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 12px; font-weight: 600; padding: 6px 12px; cursor: pointer; transition: background 0.15s ease; height: 28px; line-height: 28px; display: flex; align-items: center; justify-content: center;"><?php _e('Add', 'reandaily-lms-theme'); ?></button>
                        </div>
                    </div>
                    
                    <!-- Right column lesson settings editor -->
                    <div class="lms-curriculum-right" id="lms-lesson-editor-panel">
                        <div class="lms-empty-editor-state">
                            <span class="dashicons dashicons-edit" style="font-size: 48px; width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 12px;"></span>
                            <p><?php _e('Select a lesson on the left to configure its details.', 'reandaily-lms-theme'); ?></p>
                        </div>
                        
                        <div class="lms-lesson-editor-form" style="display: none;">
                            <div class="lms-lesson-editor-body">
                                <div class="lms-lesson-editor-header" style="padding: 0 0 16px 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 24px;">
                                    <div class="lms-editor-title-row" style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                                        <div style="display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 6px; flex: 1; overflow: hidden; background: #fff;">
                                            <div class="lms-editor-type-badge" id="lms-editor-type-badge" style="display: flex; align-items: center; gap: 6px; background: #f1f5f9; padding: 10px 16px; border-right: 1px solid #cbd5e1; font-weight: 600; font-size: 13.5px; color: #475569; white-space: nowrap; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                                                <span class="dashicons dashicons-video-alt3" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #64748b;"></span>
                                                <span class="lms-type-badge-text">Video lesson</span>
                                            </div>
                                            <input type="text" id="lms-edit-lesson-title" placeholder="<?php _e('Lesson Title', 'reandaily-lms-theme'); ?>">
                                        </div>
                                    </div>
                                    <div class="lms-lesson-editor-tabs">
                                        <button type="button" class="lms-lesson-tab active" data-target="#lms-lesson-tab-content-settings"><?php _e('Lesson', 'reandaily-lms-theme'); ?></button>
                                        <button type="button" class="lms-lesson-tab lms-tab-quiz-only" data-target="#lms-lesson-tab-content-quiz" style="display: none;"><?php _e('Quiz Settings', 'reandaily-lms-theme'); ?></button>
                                        <button type="button" class="lms-lesson-tab lms-tab-quiz-only" data-target="#lms-lesson-tab-content-questions" style="display: none;"><?php _e('Quiz Questions', 'reandaily-lms-theme'); ?></button>
                                        <button type="button" class="lms-lesson-tab lms-tab-video-only" data-target="#lms-lesson-tab-content-video-questions" style="display: none;"><?php _e('Video Questions', 'reandaily-lms-theme'); ?></button>
                                        <button type="button" class="lms-lesson-tab" data-target="#lms-lesson-tab-content-qa"><?php _e('Q&A', 'reandaily-lms-theme'); ?></button>
                                    </div>
                                </div>
                                <input type="hidden" id="lms-edit-lesson-id">
                                <input type="hidden" id="lms-edit-video-questions-data">
                                
                                <!-- Tab 1: Settings content -->
                                <div id="lms-lesson-tab-content-settings" class="lms-tab-content-pane">
                                    <div class="lms-field-group" id="lms-group-video-url">
                                        <label><?php _e('Lesson Video / Resource URL', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms-edit-lesson-video-url" placeholder="e.g. YouTube or Vimeo video URL, or direct PDF/MP4 URL">
                                    </div>
                                    
                                    <div class="lms-field-group">
                                        <label><?php _e('Lesson Duration', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms-edit-lesson-duration" placeholder="e.g. 15m, 1h 20m">
                                    </div>
                                    
                                    <!-- Lesson Preview Toggle -->
                                    <div class="lms-field-group" style="display: flex; align-items: center; justify-content: flex-start; gap: 12px; margin-top: 16px;">
                                        <label class="lms-switch" style="margin: 0; flex-shrink: 0;">
                                            <input type="checkbox" id="lms-edit-lesson-preview" value="1">
                                            <span class="lms-slider"></span>
                                        </label>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 600; font-size: 13.5px; color: #334155;"><?php _e('Lesson preview', 'reandaily-lms-theme'); ?></span>
                                            <span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px; color: #3b82f6; cursor: pointer; margin: 0;" title="<?php _e('Students can watch before paying', 'reandaily-lms-theme'); ?>"></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Unlock lesson drip Toggle -->
                                    <div class="lms-field-group" style="display: flex; align-items: center; justify-content: flex-start; gap: 12px; margin-top: 12px; margin-bottom: 16px;">
                                        <label class="lms-switch" style="margin: 0; flex-shrink: 0;">
                                            <input type="checkbox" id="lms-edit-lesson-unlock-drip" value="1">
                                            <span class="lms-slider"></span>
                                        </label>
                                        <span style="font-weight: 600; font-size: 13.5px; color: #334155;"><?php _e('Unlock the lesson after a certain time after the purchase', 'reandaily-lms-theme'); ?></span>
                                    </div>
                                    
                                    <!-- Date and Time Row -->
                                    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 16px; margin-top: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 24px;">
                                        <div class="lms-field-group">
                                            <label style="font-weight: 600; font-size: 13px; color: #475569; display: block; margin-bottom: 6px;"><?php _e('Lesson start date', 'reandaily-lms-theme'); ?></label>
                                            <div style="position: relative; display: flex; align-items: center;">
                                                <input type="text" id="lms-edit-lesson-start-date" placeholder="June 17, 2026" style="width: 100%; padding: 0 36px 0 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; box-sizing: border-box; height: 38px; line-height: 38px; margin: 0;">
                                                <span class="dashicons dashicons-calendar-alt" style="position: absolute; right: 10px; color: #3b82f6; pointer-events: none;"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="lms-field-group">
                                            <label style="font-weight: 600; font-size: 13px; color: #475569; display: block; margin-bottom: 6px;"><?php _e('Lesson start time', 'reandaily-lms-theme'); ?></label>
                                            <div style="display: flex; gap: 8px;">
                                                <div style="position: relative; display: flex; align-items: center; flex: 1; min-width: 120px;">
                                                    <input type="text" id="lms-edit-lesson-start-time" placeholder="10:45" style="width: 100%; padding: 0 36px 0 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; box-sizing: border-box; height: 38px; line-height: 38px; margin: 0;">
                                                    <span class="dashicons dashicons-clock" style="position: absolute; right: 10px; color: #3b82f6; pointer-events: none;"></span>
                                                </div>
                                                <select id="lms-edit-lesson-start-ampm" style="width: 80px; padding: 0 24px 0 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; background-color: #fff; cursor: pointer; box-sizing: border-box; height: 38px; line-height: 36px; flex-shrink: 0; min-width: unset; vertical-align: middle; margin: 0;">
                                                    <option value="AM">AM</option>
                                                    <option value="PM">PM</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="lms-field-group" style="margin-top: 24px;">
                                        <label><?php _e('Short description of the lesson', 'reandaily-lms-theme'); ?></label>
                                        <textarea id="lms-edit-lesson-description" rows="4" placeholder="<?php _e('Add a short description or summary for this lesson...', 'reandaily-lms-theme'); ?>"></textarea>
                                    </div>

                                    <div class="lms-field-group" style="margin-top: 24px;">
                                        <label><?php _e('Lesson Content / Text', 'reandaily-lms-theme'); ?></label>
                                        <textarea id="lms-edit-lesson-content" rows="10" placeholder="<?php _e('Add text instructions or notes for this lesson...', 'reandaily-lms-theme'); ?>"></textarea>
                                    </div>

                                    <div class="lms-field-group" style="margin-top: 24px;">
                                        <label><?php _e('Lesson materials', 'reandaily-lms-theme'); ?></label>
                                        <div class="lms-upload-box" id="lms-material-upload-area">
                                            <p style="margin: 0 0 12px 0; color: #64748b; font-size: 13.5px; font-weight: normal;"><?php _e('Drag & drop files here or browse files from your computer', 'reandaily-lms-theme'); ?></p>
                                            <button type="button" class="button button-secondary button-large" id="lms-btn-browse-material" style="background: #3b82f6; border-color: #3b82f6; color: #fff; text-shadow: none; font-weight: 600; font-size: 13px;"><?php _e('Browse files', 'reandaily-lms-theme'); ?></button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 2: Q&A content -->
                                <div id="lms-lesson-tab-content-qa" class="lms-tab-content-pane" style="display: none;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                                        <h4 style="margin: 0; font-size: 14px; font-weight: 600; color: #334155;"><?php _e('Discussion / Q&A Comments', 'reandaily-lms-theme'); ?></h4>
                                        <button type="button" class="button button-secondary button-small" id="lms-btn-refresh-qa" style="background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px;"><?php _e('Refresh', 'reandaily-lms-theme'); ?></button>
                                    </div>
                                    
                                    <!-- Teacher Post Question Form -->
                                    <div class="lms-teacher-question-form" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 6px; margin-bottom: 16px;">
                                        <h5 style="margin: 0 0 8px 0; font-size: 12.5px; font-weight: 600; color: #475569;"><?php _e('Post a new question/announcement to students:', 'reandaily-lms-theme'); ?></h5>
                                        <textarea id="lms-teacher-new-question-text" placeholder="Type a question or discussion topic..." rows="2" style="width: 100%; font-size: 13px; border-radius: 6px; padding: 8px; border: 1px solid #cbd5e1; outline: none; resize: vertical; box-sizing: border-box; margin-bottom: 8px;"></textarea>
                                        <div style="display: flex; justify-content: flex-end;">
                                            <button type="button" id="lms-btn-teacher-post-question" style="background: #3b82f6; border: none; color: #fff; padding: 6px 14px; border-radius: 4px; font-weight: 600; font-size: 11.5px; cursor: pointer; transition: background 0.15s ease;"><?php _e('Post Question', 'reandaily-lms-theme'); ?></button>
                                        </div>
                                    </div>
                                    
                                    <div id="lms-qa-list-container">
                                        <!-- Loaded Dynamically -->
                                        <p style="color: #64748b; font-size: 13px; text-align: center; padding: 20px;"><?php _e('No questions posted yet.', 'reandaily-lms-theme'); ?></p>
                                    </div>
                                </div>

                                <input type="hidden" id="lms-edit-quiz-questions-data">

                                <!-- Tab 3: Quiz settings -->
                                <div id="lms-lesson-tab-content-quiz" class="lms-tab-content-pane" style="display: none;">
                                    <div class="lms-field-group">
                                        <label><?php _e('Passing Grade (%)', 'reandaily-lms-theme'); ?></label>
                                        <input type="number" id="lms-edit-quiz-passing-grade" min="0" max="100" placeholder="70" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    </div>
                                    <div class="lms-field-group" style="margin-top: 16px;">
                                        <label><?php _e('Time Limit (Minutes)', 'reandaily-lms-theme'); ?></label>
                                        <input type="number" id="lms-edit-quiz-time-limit" min="0" placeholder="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    </div>
                                    <div class="lms-field-group" style="margin-top: 16px;">
                                        <label><?php _e('Allowed Retakes', 'reandaily-lms-theme'); ?></label>
                                        <input type="number" id="lms-edit-quiz-retakes" min="0" placeholder="0" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    </div>
                                </div>

                                <!-- Tab 4: Quiz questions -->
                                <div id="lms-lesson-tab-content-questions" class="lms-tab-content-pane" style="display: none;">
                                    <div id="lms-modal-quiz-questions-list" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                                        <!-- Questions dynamic list -->
                                    </div>
                                    <button type="button" class="button button-primary button-large" id="lms-modal-btn-add-question" style="display: inline-flex; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-plus" style="margin-top: 3px;"></span> <?php _e( 'Add Question', 'reandaily-lms-theme' ); ?>
                                    </button>
                                </div>
                                
                                <!-- Tab 5: Video questions -->
                                <div id="lms-lesson-tab-content-video-questions" class="lms-tab-content-pane" style="display: none;">
                                    <div id="lms-modal-video-questions-list" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px;">
                                        <!-- Video Questions dynamic list -->
                                    </div>
                                    <button type="button" class="button button-primary button-large" id="lms-modal-btn-add-video-question" style="display: inline-flex; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-plus" style="margin-top: 3px;"></span> <?php _e( 'Add Video Question', 'reandaily-lms-theme' ); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Bottom Footer with Save Button -->
                            <div class="lms-lesson-editor-footer" style="background: #fff; border-top: 1px solid #e2e8f0; padding: 12px 20px; display: flex; justify-content: flex-end; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.03); z-index: 10;">
                                <button type="button" class="lms-btn-save-lesson-sticky" id="lms-save-lesson-btn-sticky" style="background: #3b82f6; border: none; color: #fff; padding: 10px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.15s ease; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);"><?php _e('Save Settings', 'reandaily-lms-theme'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Settings Workspace (Split layout: sidebar + main card) -->
            <div id="course-general" class="lms-workspace-panel">
                <div class="lms-settings-sidebar">
                    <div class="lms-settings-menu-item active" data-subpanel="main">
                        <span class="dashicons dashicons-admin-generic"></span> <?php _e('Main', 'reandaily-lms-theme'); ?>
                    </div>
                    <div class="lms-settings-menu-item" data-subpanel="access">
                        <span class="dashicons dashicons-lock"></span> <?php _e('Access', 'reandaily-lms-theme'); ?>
                    </div>
                    <div class="lms-settings-menu-item" data-subpanel="prerequisites">
                        <span class="dashicons dashicons-admin-links"></span> <?php _e('Prerequisites', 'reandaily-lms-theme'); ?>
                    </div>
                    <div class="lms-settings-menu-item" data-subpanel="files">
                        <span class="dashicons dashicons-document"></span> <?php _e('Course files', 'reandaily-lms-theme'); ?>
                    </div>
                    <div class="lms-settings-menu-item" data-subpanel="certificate">
                        <span class="dashicons dashicons-awards"></span> <?php _e('Certificate', 'reandaily-lms-theme'); ?>
                    </div>
                    <div class="lms-settings-menu-item" data-subpanel="page">
                        <span class="dashicons dashicons-layout"></span> <?php _e('Course Page', 'reandaily-lms-theme'); ?>
                    </div>
                </div>
                
                <div class="lms-settings-content">
                    <div class="lms-settings-card">
                        <div class="lms-settings-card-header">
                            <h3 id="lms-settings-card-title"><?php _e('Main Settings', 'reandaily-lms-theme'); ?></h3>
                        </div>
                        <div class="lms-settings-card-body">
                            <!-- Subpanel 1: Main -->
                            <div id="lms-settings-subpanel-main" class="lms-settings-sub-panel active">
                                <div class="lms-field-group">
                                    <label for="lms_course_name_fs"><?php _e('Course name', 'reandaily-lms-theme'); ?></label>
                                    <input type="text" id="lms_course_name_fs" value="<?php echo esc_attr( $post->post_title ); ?>" placeholder="e.g. Introduction to Programming">
                                </div>
                                <div class="lms-field-group">
                                    <label><?php _e('Url', 'reandaily-lms-theme'); ?></label>
                                    <div style="display: flex; align-items: center; background: #f1f5f9; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; color: #475569;">
                                        <span style="word-break: break-all; flex: 1;"><?php echo esc_url( get_permalink( $post->ID ) ); ?></span>
                                        <span class="dashicons dashicons-edit" style="font-size: 16px; cursor: pointer; margin-left: 8px;" title="Edit Permalink"></span>
                                    </div>
                                </div>
                                <div class="lms-field-group">
                                    <label for="lms_course_category_fs"><?php _e('Course Category', 'reandaily-lms-theme'); ?></label>
                                    <select id="lms_course_category_fs">
                                        <option value=""><?php _e('Select Category', 'reandaily-lms-theme'); ?></option>
                                        <?php
                                        if ( ! empty( $course_categories ) && ! is_wp_error( $course_categories ) ) {
                                            foreach ( $course_categories as $cat ) {
                                                echo '<option value="' . esc_attr( $cat->term_id ) . '" ' . selected( $selected_category_id, $cat->term_id, false ) . '>' . esc_html( $cat->name ) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
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
                                <style>
                                    .lms-custom-select-option:hover { background: #f1f5f9; }
                                    .lms-custom-multiselect-option:hover { background: #f1f5f9; }
                                    .lms-co-teacher-pill-remove:hover { color: #ef4444 !important; }
                                </style>
                                <div class="lms-teacher-row" style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px; width: 100%; box-sizing: border-box;">
                                    <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                        <label><?php _e('Primary Teacher', 'reandaily-lms-theme'); ?></label>
                                        <div style="display: flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; background: #f8fafc; height: 42px; box-sizing: border-box; user-select: none;">
                                            <?php
                                            $all_users = get_users( array( 'capability' => 'edit_posts' ) );
                                            $current_author_id = $post->post_author;
                                            $current_author = get_userdata( $current_author_id );
                                            if ( $current_author ) {
                                                $author_name = ! empty( $current_author->display_name ) ? $current_author->display_name : $current_author->user_login;
                                                $author_avatar = get_avatar_url( $current_author_id, array( 'size' => 32 ) );
                                                echo '<img src="' . esc_url( $author_avatar ) . '" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">';
                                                echo '<span style="font-size: 14px; color: #0f172a; font-weight: 500;">' . esc_html( $author_name ) . '</span>';
                                            }
                                            ?>
                                        </div>
                                        <input type="hidden" id="lms_course_author_fs" value="<?php echo esc_attr( $current_author_id ); ?>">
                                    </div>
                                    <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                        <label><?php _e('Co-teachers', 'reandaily-lms-theme'); ?></label>
                                        
                                        <div class="lms-custom-multiselect" id="lms-co-teachers-custom-multiselect" style="position: relative; width: 100%;">
                                            <div class="lms-custom-select-trigger" style="display: flex; align-items: center; justify-content: space-between; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; background: #fff; cursor: pointer; user-select: none;">
                                                <span style="font-size: 13.5px; color: #94a3b8; font-weight: normal;"><?php _e('Select co-teachers...', 'reandaily-lms-theme'); ?></span>
                                                <span style="font-size: 10px; color: #64748b;">▼</span>
                                            </div>
                                            <div class="lms-custom-select-dropdown" style="display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 1000; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-top: 4px; max-height: 220px; overflow-y: auto; padding: 8px; box-sizing: border-box;">
                                                <input type="text" class="lms-custom-select-search" placeholder="<?php esc_attr_e('Search co-teachers...', 'reandaily-lms-theme'); ?>" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; font-size: 13.5px; box-sizing: border-box; outline: none; background: #f8fafc;">
                                                <div class="lms-custom-select-options" style="display: flex; flex-direction: column; gap: 4px;">
                                                    <?php
                                                    $selected_co_teachers = get_post_meta( $post->ID, '_lms_co_teachers', true );
                                                    if ( ! is_array( $selected_co_teachers ) ) {
                                                        $selected_co_teachers = array();
                                                    }
                                                    foreach ( $all_users as $u ) {
                                                        $display_name = ! empty( $u->display_name ) ? $u->display_name : $u->user_login;
                                                        $avatar = get_avatar_url( $u->ID, array( 'size' => 32 ) );
                                                        $is_checked = in_array( $u->ID, $selected_co_teachers ) ? 'true' : 'false';
                                                        $style = ( $u->ID == $current_author_id ) ? 'display: none;' : '';
                                                        echo '<div class="lms-custom-multiselect-option" data-value="' . esc_attr( $u->ID ) . '" data-selected="' . $is_checked . '" style="' . $style . ' display: flex; align-items: center; justify-content: space-between; padding: 8px; border-radius: 6px; cursor: pointer; transition: background 0.2s;">';
                                                        echo '<div style="display: flex; align-items: center; gap: 8px;">';
                                                        echo '<img src="' . esc_url( $avatar ) . '" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">';
                                                        echo '<span style="font-size: 13.5px; color: #334155; font-weight: 500;">' . esc_html( $display_name ) . '</span>';
                                                        echo '</div>';
                                                        echo '<span class="lms-check-indicator" style="font-size: 14px; color: #0284c7; font-weight: bold; ' . ( $is_checked === 'true' ? '' : 'display: none;' ) . '">✓</span>';
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Visual List of selected Co-teachers -->
                                        <div class="lms-co-teachers-selected-list" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;"></div>

                                        <!-- Hidden inputs simulated checkboxes for form compatibility -->
                                        <div id="lms-co-teachers-checkboxes-hidden" style="display: none;">
                                            <?php
                                            foreach ( $all_users as $u ) {
                                                $checked = in_array( $u->ID, $selected_co_teachers ) ? 'checked' : '';
                                                echo '<input type="checkbox" class="lms-co-teacher-checkbox" value="' . esc_attr( $u->ID ) . '" ' . $checked . '>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="lms-durations-row" style="display: flex; gap: 20px; align-items: flex-start; margin-top: 20px; margin-bottom: 20px; width: 100%; box-sizing: border-box;">
                                    <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                        <label for="lms_duration_fs" style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 6px; display: block;"><?php _e('Course duration', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms_duration_fs" value="<?php echo esc_attr( $duration ); ?>" placeholder="<?php esc_attr_e('e.g. 9 hours', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; height: 42px;">
                                    </div>
                                    <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                        <label for="lms_video_duration_fs" style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 6px; display: block;"><?php _e('Video duration', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms_video_duration_fs" value="<?php echo esc_attr( $video_duration ); ?>" placeholder="<?php esc_attr_e('e.g. 5 hours', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; height: 42px;">
                                    </div>
                                </div>
                                <div class="lms-field-group" style="margin-top: 20px; margin-bottom: 20px; position: relative; width: 100%; box-sizing: border-box;">
                                    <label style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 8px; display: block;"><?php _e('Course image', 'reandaily-lms-theme'); ?></label>
                                    <div id="lms-course-image-preview-container" style="width: 100%; height: 240px; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden; position: relative; box-sizing: border-box;">
                                        <?php
                                        $thumbnail_id = get_post_thumbnail_id( $post->ID );
                                        $img_url = '';
                                        if ( $thumbnail_id ) {
                                            $img_url = wp_get_attachment_image_url( $thumbnail_id, 'large' );
                                        }
                                        ?>
                                        <img id="lms-course-image-preview" src="<?php echo esc_url( $img_url ); ?>" style="width: 100%; height: 100%; object-fit: cover; display: <?php echo $img_url ? 'block' : 'none'; ?>;">
                                        
                                        <div id="lms-course-image-placeholder-wrapper" style="display: <?php echo $img_url ? 'none' : 'flex'; ?>; flex-direction: column; align-items: center; justify-content: center; gap: 12px;">
                                            <span id="lms-course-image-placeholder" style="font-size: 14px; color: #94a3b8; text-align: center; font-weight: 500;"><?php _e('No Image Selected', 'reandaily-lms-theme'); ?></span>
                                            <button type="button" id="lms-browse-course-image-btn" class="button" style="background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; height: 36px; display: flex; align-items: center; justify-content: center;"><?php _e('Browse Image', 'reandaily-lms-theme'); ?></button>
                                        </div>

                                        <button type="button" id="lms-remove-course-image-btn" class="button" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); z-index: 10; margin: 0; background: #ef4444; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; height: 36px; display: <?php echo $img_url ? 'flex' : 'none'; ?>; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);"><?php _e('Remove Image', 'reandaily-lms-theme'); ?></button>
                                    </div>
                                    <input type="hidden" id="lms_course_image_id" name="_thumbnail_id" value="<?php echo esc_attr( $thumbnail_id ); ?>">
                                </div>
                                <div class="lms-field-group" style="margin-top: 24px;">
                                    <label><?php _e('Course Description / Syllabus', 'reandaily-lms-theme'); ?></label>
                                    <?php
                                    wp_editor( $course_description, 'lms_course_description_fs', array(
                                        'textarea_name' => 'lms_course_description',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'tinymce'       => true
                                    ) );
                                    ?>
                                </div>
                                <div class="lms-field-group" style="margin-top: 24px;">
                                    <label for="lms_preview_description_fs"><?php _e('Course Preview Description', 'reandaily-lms-theme'); ?></label>
                                    <textarea id="lms_preview_description_fs" rows="4" placeholder="<?php esc_attr_e('Enter a brief preview description for the course list card...', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; resize: vertical;"><?php echo esc_textarea( $preview_description ); ?></textarea>
                                </div>

                                <!-- Featured & Lock settings -->
                                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 16px;">
                                    <!-- Featured course toggle -->
                                    <div style="display: flex; align-items: center; gap: 12px; position: relative;">
                                        <label class="lms-switch" style="margin: 0; flex-shrink: 0;">
                                            <input type="checkbox" id="lms_featured_course_fs" value="1" <?php checked( $featured_course, '1' ); ?>>
                                            <span class="lms-slider"></span>
                                        </label>
                                        <span style="font-size: 14.5px; font-weight: 600; color: #1e293b;"><?php _e('Featured course', 'reandaily-lms-theme'); ?></span>
                                        <span class="lms-info-icon" data-tooltip="lms-featured-tooltip" style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 1.5px solid #3b82f6; border-radius: 50%; color: #3b82f6; font-size: 12px; font-weight: 700; cursor: pointer; user-select: none;">!</span>
                                        
                                        <!-- Tooltip Popover -->
                                        <div id="lms-featured-tooltip" class="lms-tooltip-popover" style="display: none; position: absolute; left: 160px; bottom: 30px; z-index: 1000; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 220px; box-sizing: border-box;">
                                            <span class="lms-tooltip-close" style="position: absolute; right: 8px; top: 6px; cursor: pointer; color: #94a3b8; font-weight: bold; font-size: 12px;">✕</span>
                                            <p style="margin: 0; font-size: 13px; color: #2563eb; line-height: 1.5; font-weight: 500;"><?php _e('Enable this to add a "Featured" badge to the course', 'reandaily-lms-theme'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Quota warning box -->
                                    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 12px 16px; display: flex; align-items: center; gap: 10px; box-sizing: border-box; max-width: 600px;">
                                        <span style="font-size: 16px; color: #d97706;">⚠️</span>
                                        <span style="font-size: 13.5px; color: #b45309; font-weight: 600;"><?php _e('You have reached your featured courses quota limit!', 'reandaily-lms-theme'); ?></span>
                                    </div>

                                    <!-- Lock lessons in order toggle -->
                                    <div style="display: flex; align-items: center; gap: 12px; position: relative;">
                                        <label class="lms-switch" style="margin: 0; flex-shrink: 0;">
                                            <input type="checkbox" id="lms_lock_lessons_fs" value="1" <?php checked( $lock_lessons, '1' ); ?>>
                                            <span class="lms-slider"></span>
                                        </label>
                                        <span style="font-size: 14.5px; font-weight: 600; color: #1e293b;"><?php _e('Lock lessons in order', 'reandaily-lms-theme'); ?></span>
                                        <span class="lms-info-icon" data-tooltip="lms-lock-lessons-tooltip" style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border: 1.5px solid #3b82f6; border-radius: 50%; color: #3b82f6; font-size: 12px; font-weight: 700; cursor: pointer; user-select: none;">!</span>
                                        
                                        <!-- Tooltip Popover -->
                                        <div id="lms-lock-lessons-tooltip" class="lms-tooltip-popover" style="display: none; position: absolute; left: 200px; bottom: 30px; z-index: 1000; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 220px; box-sizing: border-box;">
                                            <span class="lms-tooltip-close" style="position: absolute; right: 8px; top: 6px; cursor: pointer; color: #94a3b8; font-weight: bold; font-size: 12px;">✕</span>
                                            <p style="margin: 0; font-size: 13px; color: #2563eb; line-height: 1.5; font-weight: 500;"><?php _e('Students must complete lessons in chronological sequence.', 'reandaily-lms-theme'); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional information section -->
                                <div style="margin-top: 36px; border-top: 1px solid #e2e8f0; padding-top: 24px;">
                                    <h4 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 700; color: #0f172a;"><?php _e('Additional information', 'reandaily-lms-theme'); ?></h4>
                                    <div class="lms-additional-info-row" style="display: flex; gap: 20px; align-items: flex-start; width: 100%; box-sizing: border-box; margin-bottom: 20px;">
                                        <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                            <label for="lms_access_duration_fs" style="font-weight: 600; font-size: 13.5px; color: #1e293b; margin-bottom: 6px; display: block;"><?php _e('Access duration', 'reandaily-lms-theme'); ?></label>
                                            <input type="text" id="lms_access_duration_fs" value="<?php echo esc_attr( $access_duration ); ?>" placeholder="<?php esc_attr_e('Enter access duration', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; height: 42px;">
                                        </div>
                                        <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                            <label for="lms_access_device_types_fs" style="font-weight: 600; font-size: 13.5px; color: #1e293b; margin-bottom: 6px; display: block;"><?php _e('Access device types', 'reandaily-lms-theme'); ?></label>
                                            <input type="text" id="lms_access_device_types_fs" value="<?php echo esc_attr( $access_device_types ); ?>" placeholder="<?php esc_attr_e('Enter access device types', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; height: 42px;">
                                        </div>
                                        <div class="lms-field-group" style="position: relative; flex: 1; min-width: 0; margin-bottom: 0;">
                                            <label for="lms_certification_info_fs" style="font-weight: 600; font-size: 13.5px; color: #1e293b; margin-bottom: 6px; display: block;"><?php _e('Certification info', 'reandaily-lms-theme'); ?></label>
                                            <input type="text" id="lms_certification_info_fs" value="<?php echo esc_attr( $certification_info ); ?>" placeholder="<?php esc_attr_e('Enter certification info', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; background: #fff; box-sizing: border-box; height: 42px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Subpanel 2: Access -->
                            <div id="lms-settings-subpanel-access" class="lms-settings-sub-panel">
                                <div class="lms-field-group">
                                    <label for="lms_enroll_limit_fs"><?php _e('Enrolment Limit', 'reandaily-lms-theme'); ?></label>
                                    <input type="number" id="lms_enroll_limit_fs" placeholder="e.g. 100 (Leave empty for unlimited)">
                                </div>
                                <div class="lms-field-group" style="display: flex; align-items: center; gap: 12px; margin-top: 20px;">
                                    <label class="lms-switch" style="margin: 0; flex-shrink: 0;">
                                        <input type="checkbox" id="lms_enroll_lock_fs" value="1">
                                        <span class="lms-slider"></span>
                                    </label>
                                    <span style="font-size: 13.5px; font-weight: 600; color: #475569;"><?php _e('Lock enrollment after course start date', 'reandaily-lms-theme'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Subpanel 3: Prerequisites -->
                            <div id="lms-settings-subpanel-prerequisites" class="lms-settings-sub-panel">
                                <div class="lms-field-group">
                                    <label><?php _e('Course Prerequisites', 'reandaily-lms-theme'); ?></label>
                                    <p style="color: #64748b; font-size: 13px; margin-bottom: 12px;"><?php _e('Select courses that students must complete before enrolling in this course.', 'reandaily-lms-theme'); ?></p>
                                    <select style="width: 100%; height: 42px;">
                                        <option value=""><?php _e('None', 'reandaily-lms-theme'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Subpanel 4: Course Files -->
                            <div id="lms-settings-subpanel-files" class="lms-settings-sub-panel">
                                <div class="lms-field-group">
                                    <label><?php _e('Attached Course Files', 'reandaily-lms-theme'); ?></label>
                                    <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 40px; text-align: center; color: #64748b;">
                                        <span class="dashicons dashicons-cloud-upload" style="font-size: 32px; width: 32px; height: 32px; margin-bottom: 8px;"></span>
                                        <p style="margin: 0 0 12px 0; font-size: 13px;"><?php _e('Upload PDF, ZIP, or doc files for the course curriculum.', 'reandaily-lms-theme'); ?></p>
                                        <button type="button" class="button" style="font-weight: 600; font-size: 13px;"><?php _e('Select Files', 'reandaily-lms-theme'); ?></button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Subpanel 5: Certificate -->
                            <div id="lms-settings-subpanel-certificate" class="lms-settings-sub-panel">
                                <div class="lms-field-group">
                                    <label for="lms_certificate_template_fs"><?php _e('Completion Certificate', 'reandaily-lms-theme'); ?></label>
                                    <select id="lms_certificate_template_fs">
                                        <option value=""><?php _e('No Certificate', 'reandaily-lms-theme'); ?></option>
                                        <option value="default"><?php _e('Default Certificate Template', 'reandaily-lms-theme'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Subpanel 6: Course Page Layout -->
                            <div id="lms-settings-subpanel-page" class="lms-settings-sub-panel">
                                <div class="lms-field-group">
                                    <label for="lms_trailer_url_fs"><?php _e('Promo/Trailer Video URL', 'reandaily-lms-theme'); ?></label>
                                    <input type="text" id="lms_trailer_url_fs" value="<?php echo esc_url( $trailer_url ); ?>" placeholder="e.g. YouTube or Vimeo trailer video URL">
                                </div>
                                <div class="lms-field-group">
                                    <label for="lms_course_page_layout"><?php _e('Sidebar Position', 'reandaily-lms-theme'); ?></label>
                                    <select id="lms_course_page_layout">
                                        <option value="right"><?php _e('Right Sidebar', 'reandaily-lms-theme'); ?></option>
                                        <option value="left"><?php _e('Left Sidebar', 'reandaily-lms-theme'); ?></option>
                                        <option value="none"><?php _e('No Sidebar (Full Width)', 'reandaily-lms-theme'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="lms-settings-card-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; background: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; box-sizing: border-box; width: 100%;">
                            <button type="button" class="lms-btn-publish" style="background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: background 0.25s; font-size: 14px; outline: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box; line-height: 1;"><?php _e('Save Settings', 'reandaily-lms-theme'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Drip Workspace -->
            <div id="course-drip" class="lms-workspace-panel" style="background: #f8fafc; overflow: hidden !important;">
                <input type="hidden" name="lms_drip_dependencies" id="lms_drip_dependencies" value="<?php echo esc_attr( wp_json_encode( get_post_meta( $post->ID, '_drip_dependencies', true ) ?: array() ) ); ?>">
                <div style="display: flex; flex-direction: row; height: 100%; width: 100%; box-sizing: border-box; overflow: hidden;">
                    <!-- Left: Course Materials (Sidebar style) -->
                    <div class="lms-settings-sidebar" style="width: 260px; display: flex; flex-direction: column; height: 100%; box-sizing: border-box; overflow: hidden; padding-top: 0;">
                        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; background: #fff;">
                            <h3 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 700; color: #0f172a;"><?php _e('Course materials', 'reandaily-lms-theme'); ?></h3>
                            <p style="margin: 0; font-size: 11px; color: #64748b; line-height: 1.4;"><?php _e('Drag lessons to the right to create drip content', 'reandaily-lms-theme'); ?></p>
                        </div>
                        <div id="lms-drip-materials-list" style="flex: 1; overflow-y: auto; padding: 20px; box-sizing: border-box; display: flex; flex-direction: column; gap: 16px;">
                            <!-- Dynamically loaded from JS sections state -->
                        </div>
                    </div>

                    <!-- Right: Drip Content (Content style) -->
                    <div class="lms-settings-content">
                        <div class="lms-settings-card" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; background: #fff;">
                            <div class="lms-settings-card-header" style="padding: 20px 24px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; background: #fff; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;"><?php _e('Drip content', 'reandaily-lms-theme'); ?></h3>
                            </div>
                            <div class="lms-settings-card-body" style="padding: 24px; display: flex; flex-direction: column; gap: 24px;">
                                <div id="lms-drip-groups-container" style="display: flex; flex-direction: column; gap: 24px;">
                                    <!-- Dynamically loaded dependency groups -->
                                </div>
                                <div style="margin-top: 10px;">
                                    <button type="button" id="lms-add-drip-dependency" style="background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; font-size: 14px; outline: none; height: 38px; display: inline-flex; align-items: center; justify-content: center;"><?php _e('Add dependency', 'reandaily-lms-theme'); ?></button>
                                </div>
                            </div>
                            <div class="lms-settings-card-footer" style="padding: 16px 24px; border-top: 1px solid #cbd5e1; display: flex; justify-content: flex-end; background: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; box-sizing: border-box; width: 100%;">
                                <button type="button" class="lms-btn-publish" style="background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: background 0.25s; font-size: 14px; outline: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box; line-height: 1;"><?php _e('Save Settings', 'reandaily-lms-theme'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Pricing Workspace -->
            <div id="course-pricing" class="lms-workspace-panel">
                <div class="lms-settings-content" style="padding: 30px; background: #f8fafc; width: 100%;">
                    <div class="lms-settings-card" style="max-width: 640px; margin: 0 auto 24px auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div class="lms-settings-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;"><?php _e('Pricing', 'reandaily-lms-theme'); ?></h3>
                        </div>
                        <div class="lms-settings-card-body" style="padding: 24px;">
                            <!-- Free / Paid Selection -->
                            <div style="display: flex; gap: 24px; margin-bottom: 24px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; color: #475569; cursor: pointer;">
                                    <input type="radio" name="lms_price_type" id="lms_price_type_free" value="free" <?php checked( empty($price) || $price == 0 ); ?> style="width: 18px; height: 18px; cursor: pointer;">
                                    <?php _e('Free', 'reandaily-lms-theme'); ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; color: #475569; cursor: pointer;">
                                    <input type="radio" name="lms_price_type" id="lms_price_type_paid" value="paid" <?php checked( !empty($price) && $price > 0 ); ?> style="width: 18px; height: 18px; cursor: pointer;">
                                    <?php _e('Paid', 'reandaily-lms-theme'); ?>
                                </label>
                            </div>

                            <!-- Paid Fields Container -->
                            <div id="lms-paid-fields-container" style="display: <?php echo (!empty($price) && $price > 0) ? 'block' : 'none'; ?>; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                <!-- One-time purchase Toggle -->
                                <div class="lms-field-group" style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
                                    <label class="lms-switch" style="margin: 0; flex-shrink: 0;">
                                        <input type="checkbox" id="lms_pricing_onetime" checked>
                                        <span class="lms-slider"></span>
                                    </label>
                                    <span style="font-size: 14px; font-weight: 600; color: #1e293b;"><?php _e('One-time purchase', 'reandaily-lms-theme'); ?></span>
                                </div>

                                <!-- Pricing Fields Detail Container -->
                                <div id="lms-pricing-fields-detail-container">
                                    <!-- USD Price ($) -->
                                    <div class="lms-field-group" style="margin-bottom: 20px;">
                                        <label for="lms_price_fs" style="display: block; font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 8px;"><?php _e('Price ($)', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms_price_fs" value="<?php echo esc_attr( $price ); ?>" placeholder="e.g. 19.99" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; box-sizing: border-box;">
                                    </div>

                                    <!-- KHR Price (៛) -->
                                    <div class="lms-field-group" style="margin-bottom: 20px;">
                                        <label for="lms_price_khr_fs" style="display: block; font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 8px;"><?php _e('KHR Price (៛)', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms_price_khr_fs" value="<?php echo esc_attr( $price_khr ); ?>" placeholder="e.g. 80000" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; box-sizing: border-box;">
                                    </div>

                                    <!-- Sale Price ($) -->
                                    <div class="lms-field-group" style="margin-bottom: 20px;">
                                        <label for="lms_sale_price_fs" style="display: block; font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 8px;"><?php _e('Sale price ($)', 'reandaily-lms-theme'); ?></label>
                                        <input type="text" id="lms_sale_price_fs" placeholder="<?php _e('Enter sale price', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; box-sizing: border-box;">
                                    </div>
                                </div>
                            </div>

                            <!-- Price Info (Always visible, even on Free) -->
                            <div id="lms-price-info-container" style="border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 20px;">
                                <div class="lms-field-group" style="margin-bottom: 0;">
                                    <label for="lms_price_info_fs" style="display: block; font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 8px;"><?php _e('Price info', 'reandaily-lms-theme'); ?></label>
                                    <input type="text" id="lms_price_info_fs" placeholder="<?php _e('Enter price info', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; outline: none; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>
                        <div class="lms-settings-card-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; background: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; box-sizing: border-box; width: 100%;">
                            <button type="button" class="lms-btn-publish" style="background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: background 0.25s; font-size: 14px; outline: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box; line-height: 1;"><?php _e('Save Pricing', 'reandaily-lms-theme'); ?></button>
                        </div>
                    </div>

                    <!-- Premium Addons Card -->
                    <div class="lms-settings-card" style="max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div class="lms-settings-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;"><?php _e('Add-on Services', 'reandaily-lms-theme'); ?></h3>
                        </div>
                        <div class="lms-settings-card-body" style="padding: 24px;">
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="dashicons dashicons-awards" style="color: #3b82f6; font-size: 18px; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 6px;"></span>
                                        <span style="font-size: 13px; font-weight: 600; color: #475569;"><?php _e('Point System addon is locked!', 'reandaily-lms-theme'); ?></span>
                                    </div>
                                    <span class="dashicons dashicons-lock" style="font-size: 16px; color: #94a3b8;"></span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="dashicons dashicons-groups" style="color: #3b82f6; font-size: 18px; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 6px;"></span>
                                        <span style="font-size: 13px; font-weight: 600; color: #475569;"><?php _e('Group Courses addon is locked!', 'reandaily-lms-theme'); ?></span>
                                    </div>
                                    <span class="dashicons dashicons-lock" style="font-size: 16px; color: #94a3b8;"></span>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="dashicons dashicons-calendar-alt" style="color: #3b82f6; font-size: 18px; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 6px;"></span>
                                        <span style="font-size: 13px; font-weight: 600; color: #475569;"><?php _e('Subscriptions addon is locked!', 'reandaily-lms-theme'); ?></span>
                                    </div>
                                    <span class="dashicons dashicons-lock" style="font-size: 16px; color: #94a3b8;"></span>
                                </div>
                            </div>
                            <div style="margin-top: 16px; font-weight: 700; font-size: 14.5px; color: #1e293b;">
                                <?php _e('Unlock', 'reandaily-lms-theme'); ?> <span style="color: #2563eb;"><?php _e('Premium Addons', 'reandaily-lms-theme'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. FAQ Workspace -->
            <div id="course-faq" class="lms-workspace-panel">
                <div class="lms-settings-content" style="padding: 30px; background: #f8fafc; width: 100%; overflow-y: auto;">
                    <div class="lms-settings-card" style="max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div class="lms-settings-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;"><?php _e('Frequently Asked Questions', 'reandaily-lms-theme'); ?></h3>
                        </div>
                        <div class="lms-settings-card-body" style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                            
                            <!-- Dynamic FAQ Items List -->
                            <div id="lms-faq-items-list" style="display: flex; flex-direction: column; gap: 12px;">
                                <!-- FAQ items will be rendered dynamically here by JS -->
                            </div>

                            <!-- FAQ Empty State -->
                            <div id="lms-faq-empty-state" style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 40px; text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                                <span class="dashicons dashicons-editor-help" style="font-size: 48px; width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 12px;"></span>
                                <p style="margin: 0; font-size: 14px;"><?php _e('No questions added yet. Click "Add new question" to begin.', 'reandaily-lms-theme'); ?></p>
                            </div>
                        </div>
                        <div class="lms-settings-card-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; background: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; box-sizing: border-box; width: 100%; align-items: center;">
                            <button type="button" class="lms-add-faq-btn" style="background: #eff6ff; color: #2563eb; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-size: 14px; outline: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box;">
                                <?php _e('Add new question', 'reandaily-lms-theme'); ?>
                            </button>
                            <button type="button" class="lms-btn-publish" style="background: #3b82f6; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: background 0.25s; font-size: 14px; outline: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box; line-height: 1;">
                                <?php _e('Save', 'reandaily-lms-theme'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Hidden input storing FAQ data JSON structure -->
                <input type="hidden" id="lms-course-faq-input" name="lms_course_faq" value="<?php echo esc_attr( is_array($faq) ? json_encode($faq) : '[]' ); ?>">
            </div>

            <!-- 6. Notice Workspace -->
            <div id="course-notice" class="lms-workspace-panel">
                <div class="lms-settings-content" style="padding: 30px; background: #f8fafc; width: 100%; overflow-y: auto;">
                    <div class="lms-settings-card" style="max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div class="lms-settings-card-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;"><?php _e('Course Announcements & Notices', 'reandaily-lms-theme'); ?></h3>
                        </div>
                        <div class="lms-settings-card-body" style="padding: 24px;">
                                <?php
                                wp_editor( $course_notice, 'lms_course_notice_fs', array(
                                    'textarea_name' => 'lms_course_notice',
                                    'media_buttons' => true,
                                    'textarea_rows' => 12,
                                    'tinymce'       => true,
                                    'quicktags'     => true
                                ) );
                                ?>
                        </div>
                        <div class="lms-settings-card-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; background: #f8fafc; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; box-sizing: border-box; width: 100%;">
                            <button type="button" class="lms-btn-publish" style="background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; font-weight: 600; cursor: pointer; transition: background 0.25s; font-size: 14px; outline: none; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box; line-height: 1;"><?php _e('Save Notice Settings', 'reandaily-lms-theme'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden input storing sections data JSON structure -->
        <input type="hidden" id="lms-course-sections-input" name="lms_course_sections" value="<?php echo esc_attr( json_encode( $sections ) ); ?>">
    </div>

    <!-- Add Lesson Modal -->
    <div id="lms-add-lesson-modal" class="lms-modal" style="display: none;">
        <div class="lms-modal-content">
            <div class="lms-modal-header">
                <h3><?php _e('Add New Activity', 'reandaily-lms-theme'); ?></h3>
                <span class="lms-modal-close" id="lms-close-modal-btn">&times;</span>
            </div>
            <div class="lms-modal-body">
                <div class="lms-field-group" style="margin-bottom: 24px;">
                    <label for="lms-new-lesson-title" style="font-weight: 600; font-size: 14px; margin-bottom: 8px; display: block; color: #1e293b;"><?php _e('Activity Title', 'reandaily-lms-theme'); ?></label>
                    <input type="text" id="lms-new-lesson-title" placeholder="e.g. Lesson 1: Introduction" style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                </div>
                
                <h4 class="lms-modal-section-title"><?php _e('LEARNING CONTENT', 'reandaily-lms-theme'); ?></h4>
                <div class="lms-activity-grid">
                    <div class="lms-activity-card" data-type="text">
                        <div class="lms-activity-icon">
                            <span class="dashicons dashicons-media-text"></span>
                        </div>
                        <span class="lms-activity-name"><?php _e('Text lesson', 'reandaily-lms-theme'); ?></span>
                    </div>
                    <div class="lms-activity-card" data-type="video">
                        <div class="lms-activity-icon">
                            <span class="dashicons dashicons-video-alt3"></span>
                        </div>
                        <span class="lms-activity-name"><?php _e('Video lesson', 'reandaily-lms-theme'); ?></span>
                    </div>
                    <div class="lms-activity-card" data-type="stream">
                        <div class="lms-activity-icon">
                            <span class="dashicons dashicons-rss"></span>
                        </div>
                        <span class="lms-activity-name"><?php _e('Stream lesson', 'reandaily-lms-theme'); ?></span>
                    </div>
                    <div class="lms-activity-card" data-type="zoom">
                        <div class="lms-activity-icon">
                            <span class="dashicons dashicons-welcome-teleport-reline"></span>
                        </div>
                        <span class="lms-activity-name"><?php _e('Zoom lesson', 'reandaily-lms-theme'); ?></span>
                    </div>
                </div>
                
                <h4 class="lms-modal-section-title" style="margin-top: 24px;"><?php _e('EXAM STUDENTS', 'reandaily-lms-theme'); ?></h4>
                <div class="lms-activity-grid">
                    <div class="lms-activity-card" data-type="quiz">
                        <div class="lms-activity-icon">
                            <span class="dashicons dashicons-welcome-write-blog"></span>
                        </div>
                        <span class="lms-activity-name"><?php _e('Quiz', 'reandaily-lms-theme'); ?></span>
                    </div>
                    <div class="lms-activity-card" data-type="assignment">
                        <div class="lms-activity-icon">
                            <span class="dashicons dashicons-clipboard"></span>
                        </div>
                        <span class="lms-activity-name"><?php _e('Assignment', 'reandaily-lms-theme'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Import Material Modal -->
    <div id="lms-search-material-modal" class="lms-modal" style="display: none;">
        <div class="lms-modal-content" style="max-width: 600px; padding: 24px; border-radius: 12px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <!-- Header Search Bar Row -->
            <div style="display: flex; gap: 12px; margin-bottom: 24px; align-items: center;">
                <div style="position: relative; flex: 1; display: flex; align-items: center;">
                    <input type="text" id="lms-search-material-input" placeholder="<?php _e('Search materials', 'reandaily-lms-theme'); ?>" style="width: 100%; padding: 10px 36px 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; height: 42px; outline: none; background: #f8fafc; color: #1e293b;">
                    <span class="dashicons dashicons-search" style="position: absolute; right: 12px; color: #64748b; font-size: 18px; pointer-events: none;"></span>
                </div>
                <div style="width: 160px; position: relative;">
                    <select id="lms-search-material-type" style="width: 100%; padding: 0 32px 0 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; height: 42px; outline: none; background-color: #fff; cursor: pointer; color: #1e293b; appearance: none; -webkit-appearance: none;">
                        <option value=""><?php _e('Type', 'reandaily-lms-theme'); ?></option>
                        <option value="text"><?php _e('Text lesson', 'reandaily-lms-theme'); ?></option>
                        <option value="video"><?php _e('Video lesson', 'reandaily-lms-theme'); ?></option>
                        <option value="stream"><?php _e('Stream lesson', 'reandaily-lms-theme'); ?></option>
                        <option value="quiz"><?php _e('Quiz', 'reandaily-lms-theme'); ?></option>
                        <option value="assignment"><?php _e('Assignment', 'reandaily-lms-theme'); ?></option>
                    </select>
                    <span class="dashicons dashicons-arrow-down-alt2" style="position: absolute; right: 12px; color: #64748b; font-size: 16px; top: 13px; pointer-events: none;"></span>
                </div>
            </div>

            <h4 style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;"><?php _e('RECENT MATERIALS', 'reandaily-lms-theme'); ?></h4>
            
            <!-- List Container -->
            <div id="lms-search-materials-list" style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 1px; background: #e2e8f0; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; min-height: 80px;">
                <!-- Dynamically loaded list items -->
            </div>

            <!-- Footer Action Row -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center;">
                <button type="button" id="lms-cancel-search-material-btn" style="flex: 1; max-width: 180px; height: 44px; border: 1px solid #3b82f6; background: transparent; color: #2563eb; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s ease;"><?php _e('Cancel', 'reandaily-lms-theme'); ?></button>
                <button type="button" id="lms-import-materials-btn" style="flex: 1.5; height: 44px; border: none; background: #2563eb; color: #ffffff; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s ease;" disabled><?php _e('Import 0 materials to Section', 'reandaily-lms-theme'); ?></button>
            </div>
        </div>
    </div>

    <div style="display:none !important;">
        <input type="text" id="lms_duration" name="lms_duration" value="<?php echo esc_attr( $duration ); ?>">
        <input type="text" id="lms_video_duration" name="lms_video_duration" value="<?php echo esc_attr( $video_duration ); ?>">
        <textarea id="lms_preview_description" name="lms_preview_description" style="display:none !important;"><?php echo esc_textarea( $preview_description ); ?></textarea>
        <input type="checkbox" id="lms_featured_course" name="lms_featured_course" value="1" <?php checked( $featured_course, '1' ); ?>>
        <input type="checkbox" id="lms_lock_lessons" name="lms_lock_lessons" value="1" <?php checked( $lock_lessons, '1' ); ?>>
        <input type="text" id="lms_access_duration" name="lms_access_duration" value="<?php echo esc_attr( $access_duration ); ?>">
        <input type="text" id="lms_access_device_types" name="lms_access_device_types" value="<?php echo esc_attr( $access_device_types ); ?>">
        <input type="text" id="lms_certification_info" name="lms_certification_info" value="<?php echo esc_attr( $certification_info ); ?>">
        <input type="text" id="lms_course_category" name="lms_course_category" value="<?php echo esc_attr( $selected_category_id ); ?>">
        <select id="lms_level" name="lms_level">
            <option value="All Levels" <?php selected( $level, 'All Levels' ); ?>>All Levels</option>
            <option value="Beginner" <?php selected( $level, 'Beginner' ); ?>>Beginner</option>
            <option value="Intermediate" <?php selected( $level, 'Intermediate' ); ?>>Intermediate</option>
            <option value="Advanced" <?php selected( $level, 'Advanced' ); ?>>Advanced</option>
        </select>
        <input type="text" id="lms_price" name="lms_price" value="<?php echo esc_attr( $price ); ?>">
        <input type="text" id="lms_price_khr" name="lms_price_khr" value="<?php echo esc_attr( $price_khr ); ?>">
        <input type="text" id="lms_trailer_url" name="lms_trailer_url" value="<?php echo esc_url( $trailer_url ); ?>">
        <input type="text" id="lms_course_author" name="lms_course_author" value="<?php echo esc_attr( $post->post_author ); ?>">
        <div id="lms-co-teachers-hidden-inputs">
            <?php
            $selected_co_teachers = get_post_meta( $post->ID, '_lms_co_teachers', true );
            if ( is_array( $selected_co_teachers ) ) {
                foreach ( $selected_co_teachers as $ct_id ) {
                    echo '<input type="hidden" name="lms_co_teachers[]" value="' . esc_attr( $ct_id ) . '">';
                }
            }
            ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Parse sections hierarchy
            var sections = <?php echo json_encode( $sections ); ?>;
            if (Array.isArray(sections)) {
                sections.forEach(function(sec) {
                    sec.collapsed = false;
                });
            }
            var activeLessonId = null;
            var editorId = 'lms-edit-lesson-content';
            var descEditorId = 'lms-edit-lesson-description';
            var startDatePicker = null;

            // Initialize Flatpickr for start date field
            if (typeof flatpickr !== 'undefined') {
                startDatePicker = flatpickr('#lms-edit-lesson-start-date', {
                    dateFormat: 'F j, Y',
                    allowInput: false
                });
            }

            // Initialize WP Rich Editor settings on page load
            if (typeof wp !== 'undefined' && wp.editor) {
                setTimeout(function() {
                    wp.editor.initialize(editorId, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wplink wpview',
                            toolbar1: 'formatselect | bold italic underline strikethrough | forecolor | bullist numlist | alignleft aligncenter alignright alignjustify | link unlink image media | undo redo | removeformat',
                            setup: function(editor) {
                                editor.on('change', function() {
                                    editor.save();
                                    $('#' + editorId).trigger('change');
                                });
                            }
                        },
                        quicktags: true,
                        mediaButtons: true
                    });

                    wp.editor.initialize(descEditorId, {
                        tinymce: {
                            wpautop: true,
                            plugins: 'charmap colorpicker compat3x directionality fullscreen hr image lists media paste tabfocus textcolor wordpress wpautoresize wpdialogs wpeditimage wplink wpview',
                            toolbar1: 'formatselect | bold italic underline strikethrough | forecolor | bullist numlist | alignleft aligncenter alignright alignjustify | link unlink image media | undo redo | removeformat',
                            setup: function(editor) {
                                editor.on('change', function() {
                                    editor.save();
                                    $('#' + descEditorId).trigger('change');
                                });
                            }
                        },
                        quicktags: true,
                        mediaButtons: true
                    });
                }, 500);
            }

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

                // Hide all panels inline first to override any active styles
                $('.lms-workspace-panel').removeClass('active').css('display', 'none');
                
                var panel = $('#' + targetPanel);
                panel.addClass('active');
                
                // Programmatically force correct flex/block display modes inline to prevent CSS caching bugs
                if (targetPanel === 'course-syllabus' || targetPanel === 'course-pricing' || targetPanel === 'course-faq' || targetPanel === 'course-notice' || targetPanel === 'course-drip') {
                    panel.css('display', 'flex');
                    if (targetPanel === 'course-drip') {
                        renderDripWorkspace();
                    }
                } else if (targetPanel === 'course-general') {
                    panel.css('display', 'flex');
                } else {
                    panel.css('display', 'block');
                }

                // If editing tinymce in description panel, refresh it
                if (targetPanel === 'course-general' && typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }
            });

            // Custom Drip Dependency Builder state
            var dripDependencies = [];
            var lessonDetailsCache = {};

            try {
                dripDependencies = JSON.parse($('#lms_drip_dependencies').val() || '[]');
            } catch(e) {
                dripDependencies = [];
            }

            function renderDripWorkspace() {
                // Fetch details for all lessons of the course to populate cache on demand
                var lessonsToFetch = [];
                sections.forEach(function(sec) {
                    if (sec.lessons) {
                        sec.lessons.forEach(function(lid) {
                            if (!lessonDetailsCache[lid]) {
                                lessonsToFetch.push(lid);
                            }
                        });
                    }
                });

                if (lessonsToFetch.length > 0) {
                    var fetchedCount = 0;
                    lessonsToFetch.forEach(function(lessonId) {
                        $.post(reandaily_lms_admin_vars.ajaxurl, {
                            action: 'reandaily_lms_get_lesson_settings',
                            nonce: reandaily_lms_admin_vars.nonce,
                            lesson_id: lessonId
                        }, function(res) {
                            if (res.success) {
                                lessonDetailsCache[lessonId] = {
                                    title: res.data.title,
                                    icon: res.data.icon,
                                    type: res.data.type || 'lesson'
                                };
                            }
                            fetchedCount++;
                            if (fetchedCount === lessonsToFetch.length) {
                                renderDripMaterials();
                                renderDripGroups();
                            }
                        });
                    });
                } else {
                    renderDripMaterials();
                    renderDripGroups();
                }
            }

            function renderDripMaterials() {
                var container = $('#lms-drip-materials-list');
                container.empty();
                
                sections.forEach(function(sec) {
                    if (!sec.lessons || sec.lessons.length === 0) return;
                    
                    var sectionHtml = $(`
                        <div class="lms-drip-material-section-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <div style="background: #f8fafc; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 700; font-size: 13.5px; color: #1e293b;">${sec.title || 'Section'}</span>
                                <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 16px; width: 16px; height: 16px; color: #64748b; cursor: pointer;"></span>
                            </div>
                            <div class="lms-drip-section-body" style="padding: 10px; display: flex; flex-direction: column; gap: 8px;">
                            </div>
                        </div>
                    `);
                    
                    var body = sectionHtml.find('.lms-drip-section-body');
                    
                    sec.lessons.forEach(function(lessonId) {
                        var details = lessonDetailsCache[lessonId] || { title: 'Lesson ID: ' + lessonId, icon: 'dashicons-video-alt3', type: 'lesson' };
                        var iconClass = details.icon || 'dashicons-video-alt3';
                        var iconColor = '#3b82f6'; 
                        if (iconClass.indexOf('help') !== -1 || iconClass.indexOf('question') !== -1) {
                            iconColor = '#f59e0b'; 
                        } else if (iconClass.indexOf('write') !== -1 || iconClass.indexOf('document') !== -1 || iconClass.indexOf('welcome') !== -1) {
                            iconColor = '#10b981'; 
                        }
                        
                        var item = $(`
                            <div class="lms-drip-draggable-item" data-id="${lessonId}" style="display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; cursor: grab; transition: all 0.2s;">
                                <span class="dashicons dashicons-grid" style="color: #94a3b8; font-size: 14px; width: 14px; height: 14px; cursor: grab;"></span>
                                <span class="dashicons ${iconClass}" style="color: ${iconColor}; font-size: 18px; width: 18px; height: 18px;"></span>
                                <span style="font-size: 13.5px; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;">${details.title}</span>
                            </div>
                        `);
                        
                        item.hover(
                            function() { $(this).css({ 'borderColor': '#2563eb', 'boxShadow': '0 2px 4px rgba(0,0,0,0.05)' }); },
                            function() { $(this).css({ 'borderColor': '#cbd5e1', 'boxShadow': 'none' }); }
                        );
                        
                        body.append(item);
                    });
                    
                    container.append(sectionHtml);
                });

                // Make items draggable
                $('.lms-drip-draggable-item').draggable({
                    helper: 'clone',
                    revert: 'invalid',
                    cursor: 'grabbing',
                    appendTo: 'body',
                    zIndex: 9999
                });
            }

            function renderDripGroups() {
                var container = $('#lms-drip-groups-container');
                container.empty();
                
                if (dripDependencies.length === 0) {
                    container.html(`
                        <div style="background: #ffffff; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 40px 20px; text-align: center; color: #64748b; font-size: 14px;">
                            <span class="dashicons dashicons-clock" style="font-size: 40px; width: 40px; height: 40px; color: #94a3b8; margin-bottom: 12px;"></span>
                            <p style="margin: 0;">No content drip dependencies defined. Click "Add dependency" to create one.</p>
                        </div>
                    `);
                    return;
                }
                
                // Get list of all lessons in course for select dropdown fallback
                var allLessonsOptions = '<option value="">-- Select Lesson --</option>';
                sections.forEach(function(sec) {
                    if (sec.lessons) {
                        sec.lessons.forEach(function(lid) {
                            var details = lessonDetailsCache[lid] || { title: 'Lesson ID: ' + lid };
                            allLessonsOptions += `<option value="${lid}">${details.title}</option>`;
                        });
                    }
                });
                
                dripDependencies.forEach(function(group, index) {
                    var groupIndex = index + 1;
                    var parentId = group.parent_id || '';
                    var parentDetails = parentId ? (lessonDetailsCache[parentId] || { title: 'Lesson ID: ' + parentId, icon: 'dashicons-video-alt3' }) : null;
                    
                    var groupHtml = $(`
                        <div class="lms-drip-group-card" data-group-id="${group.id}" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px; box-sizing: border-box;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                                <strong style="font-size: 14.5px; color: #0f172a;">Drip content ${groupIndex}</strong>
                                <button type="button" class="lms-delete-drip-group" data-group-id="${group.id}" style="background: transparent; border: none; cursor: pointer; color: #94a3b8; padding: 4px; display: inline-flex; align-items: center; justify-content: center; outline: none; box-shadow: none; margin: 0;">
                                    <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                </button>
                            </div>
                            
                            <div class="lms-drip-group-dashed-box" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; background: #f8fafc; display: flex; flex-direction: column; gap: 12px; min-height: 100px; box-sizing: border-box;">
                                <!-- Parent Drop Zone -->
                                <div class="lms-drip-parent-dropzone" data-group-id="${group.id}" style="box-sizing: border-box; min-height: 48px;">
                                </div>
                                
                                <!-- Dependents Container -->
                                <div class="lms-drip-dependents-list" data-group-id="${group.id}" style="display: flex; flex-direction: column; gap: 8px; box-sizing: border-box; margin-left: 20px;">
                                </div>
                            </div>
                        </div>
                    `);

                    // Delete Group handler
                    groupHtml.find('.lms-delete-drip-group').hover(
                        function() { $(this).css('color', '#ef4444'); },
                        function() { $(this).css('color', '#94a3b8'); }
                    ).on('click', function() {
                        var gid = $(this).data('group-id');
                        dripDependencies = dripDependencies.filter(function(g) { return g.id !== gid; });
                        saveDripState();
                    });

                    // Render Parent Dropzone Content
                    var parentZone = groupHtml.find('.lms-drip-parent-dropzone');
                    if (parentId && parentDetails) {
                        var iconClass = parentDetails.icon || 'dashicons-video-alt3';
                        var iconColor = '#3b82f6';
                        if (iconClass.indexOf('help') !== -1 || iconClass.indexOf('question') !== -1) {
                            iconColor = '#f59e0b';
                        } else if (iconClass.indexOf('write') !== -1 || iconClass.indexOf('document') !== -1 || iconClass.indexOf('welcome') !== -1) {
                            iconColor = '#10b981';
                        }
                        var parentBar = $(`
                            <div class="lms-drip-selected-item-bar" style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                <div style="display: flex; align-items: center; gap: 10px; overflow: hidden; flex: 1;">
                                    <span class="dashicons ${iconClass}" style="color: ${iconColor}; font-size: 18px; width: 18px; height: 18px;"></span>
                                    <span style="font-size: 13.5px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 350px;">${parentDetails.title}</span>
                                </div>
                                <button type="button" class="lms-remove-drip-parent" data-group-id="${group.id}" style="background: transparent; border: none; cursor: pointer; color: #94a3b8; outline: none; box-shadow: none;">
                                    <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                </button>
                            </div>
                        `);
                        parentBar.find('.lms-remove-drip-parent').hover(
                            function() { $(this).css('color', '#ef4444'); },
                            function() { $(this).css('color', '#94a3b8'); }
                        ).on('click', function() {
                            var gid = $(this).data('group-id');
                            var g = dripDependencies.find(function(item) { return item.id === gid; });
                            if (g) {
                                g.parent_id = '';
                                saveDripState();
                            }
                        });
                        parentZone.append(parentBar);
                    } else {
                        var parentSelector = $(`
                            <div style="border: 1px dashed #cbd5e1; border-radius: 6px; padding: 12px; background: #ffffff; text-align: center; color: #94a3b8; font-size: 12.5px; font-weight: 600;">
                                <span>Drag parent lesson here</span>
                            </div>
                        `);
                        parentZone.append(parentSelector);
                    }

                    // Render Dependents List
                    var dependentsList = groupHtml.find('.lms-drip-dependents-list');
                    if (group.dependents && group.dependents.length > 0) {
                        group.dependents.forEach(function(depId, depIndex) {
                            var depDetails = lessonDetailsCache[depId] || { title: 'Lesson ID: ' + depId, icon: 'dashicons-video-alt3' };
                            var depIconClass = depDetails.icon || 'dashicons-video-alt3';
                            var depIconColor = '#3b82f6';
                            if (depIconClass.indexOf('help') !== -1 || depIconClass.indexOf('question') !== -1) {
                                depIconColor = '#f59e0b';
                            } else if (depIconClass.indexOf('write') !== -1 || depIconClass.indexOf('document') !== -1 || depIconClass.indexOf('welcome') !== -1) {
                                depIconColor = '#10b981';
                            }
                            
                            var dependentItem = $(`
                                <div style="display: flex; align-items: center; gap: 10px; box-sizing: border-box;">
                                    <span style="font-size: 18px; color: #94a3b8; font-weight: 700; display: inline-flex; align-items: center; margin-top: -6px; line-height: 1; transform: scaleY(0.9);">↳</span>
                                    <div class="lms-drip-selected-item-bar" style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); flex: 1; overflow: hidden;">
                                        <div style="display: flex; align-items: center; gap: 10px; overflow: hidden; flex: 1;">
                                            <span class="dashicons ${depIconClass}" style="color: ${depIconColor}; font-size: 18px; width: 18px; height: 18px;"></span>
                                            <span style="font-size: 13.5px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 300px;">${depDetails.title}</span>
                                        </div>
                                        <button type="button" class="lms-remove-drip-dependent" data-group-id="${group.id}" data-index="${depIndex}" style="background: transparent; border: none; cursor: pointer; color: #94a3b8; outline: none; box-shadow: none;">
                                            <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                        </button>
                                    </div>
                                </div>
                            `);
                            dependentItem.find('.lms-remove-drip-dependent').hover(
                                function() { $(this).css('color', '#ef4444'); },
                                function() { $(this).css('color', '#94a3b8'); }
                            ).on('click', function() {
                                var gid = $(this).data('group-id');
                                var idx = $(this).data('index');
                                var g = dripDependencies.find(function(item) { return item.id === gid; });
                                if (g && g.dependents) {
                                    g.dependents.splice(idx, 1);
                                    saveDripState();
                                }
                            });
                            dependentsList.append(dependentItem);
                        });
                    }

                    // Render New Dependent dropzone
                    var childZone = $(`
                        <div class="lms-drip-child-dropzone" data-group-id="${group.id}" style="display: flex; align-items: center; gap: 10px; box-sizing: border-box; min-height: 38px;">
                            <span style="font-size: 18px; color: #cbd5e1; font-weight: 700; line-height: 1; transform: scaleY(0.9);">↳</span>
                            <div style="border: 1px dashed #cbd5e1; border-radius: 6px; padding: 8px 12px; background: #ffffff; text-align: center; color: #94a3b8; font-size: 12px; font-weight: 600; flex: 1;">
                                <span>Drag dependent lesson here</span>
                            </div>
                        </div>
                    `);
                    dependentsList.append(childZone);

                    container.append(groupHtml);
                });

                // Make dropzones droppable
                $('.lms-drip-parent-dropzone').droppable({
                    accept: '.lms-drip-draggable-item',
                    hoverClass: 'ui-state-hover',
                    drop: function(event, ui) {
                        var groupId = $(this).data('group-id');
                        var lessonId = parseInt(ui.draggable.attr('data-id'));
                        setGroupParent(groupId, lessonId);
                    }
                });

                $('.lms-drip-child-dropzone').droppable({
                    accept: '.lms-drip-draggable-item',
                    hoverClass: 'ui-state-hover',
                    drop: function(event, ui) {
                        var groupId = $(this).data('group-id');
                        var lessonId = parseInt(ui.draggable.attr('data-id'));
                        addGroupDependent(groupId, lessonId);
                    }
                });
            }

            function setGroupParent(groupId, lessonId) {
                var group = dripDependencies.find(function(g) { return g.id === groupId; });
                if (group) {
                    group.parent_id = lessonId;
                    if (group.dependents) {
                        group.dependents = group.dependents.filter(function(lid) { return lid !== lessonId; });
                    }
                    saveDripState();
                }
            }

            function addGroupDependent(groupId, lessonId) {
                var group = dripDependencies.find(function(g) { return g.id === groupId; });
                if (group) {
                    if (!group.dependents) {
                        group.dependents = [];
                    }
                    if (group.parent_id !== lessonId && !group.dependents.includes(lessonId)) {
                        group.dependents.push(lessonId);
                        saveDripState();
                    }
                }
            }

            function saveDripState() {
                $('#lms_drip_dependencies').val(JSON.stringify(dripDependencies));
                renderDripGroups();
            }

            // Click Add Dependency Button
            $(document).on('click', '#lms-add-drip-dependency', function() {
                var newId = 'drip_' + Math.random().toString(36).substr(2, 9);
                dripDependencies.push({
                    id: newId,
                    parent_id: '',
                    dependents: []
                });
                saveDripState();
            });

            // Click collapse/expand on drip material sections
            $(document).on('click', '.lms-drip-material-section-card div:first-child', function() {
                var body = $(this).next('.lms-drip-section-body');
                var icon = $(this).find('.dashicons-arrow-down-alt2, .dashicons-arrow-right-alt2');
                if (body.is(':visible')) {
                    body.slideUp(200);
                    icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                } else {
                    body.slideDown(200);
                    icon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                }
            });

            // Settings Workspace subpanel switching
            $('.lms-settings-menu-item').on('click', function() {
                var subpanel = $(this).data('subpanel');
                $('.lms-settings-menu-item').removeClass('active');
                $(this).addClass('active');

                $('.lms-settings-sub-panel').removeClass('active');
                $('#lms-settings-subpanel-' + subpanel).addClass('active');

                // Update settings card title based on menu text
                var titleText = $(this).text().trim() + ' Settings';
                $('#lms-settings-card-title').text(titleText);
            });

            // Keep top bar title field, settings course name, and original WP post title input in sync
            $('#lms_course_name_fs').on('input', function() {
                var val = $(this).val();
                $('#lms-course-top-title').val(val);
                $('#title').val(val);
            });
            $('#lms-course-top-title').on('input', function() {
                var val = $(this).val();
                $('#lms_course_name_fs').val(val);
                $('#title').val(val);
            });

            // Keep settings panel inputs in sync with default hidden inputs
            $('#lms_duration_fs').on('input', function() { $('#lms_duration').val($(this).val()); });
            $('#lms_video_duration_fs').on('input', function() { $('#lms_video_duration').val($(this).val()); });
            $('#lms_preview_description_fs').on('input', function() { $('#lms_preview_description').val($(this).val()); });
            $('#lms_featured_course_fs').on('change', function() { $('#lms_featured_course').prop('checked', $(this).is(':checked')); });
            $('#lms_lock_lessons_fs').on('change', function() { $('#lms_lock_lessons').prop('checked', $(this).is(':checked')); });
            $('#lms_access_duration_fs').on('input', function() { $('#lms_access_duration').val($(this).val()); });
            $('#lms_access_device_types_fs').on('input', function() { $('#lms_access_device_types').val($(this).val()); });
            $('#lms_certification_info_fs').on('input', function() { $('#lms_certification_info').val($(this).val()); });
            $('#lms_course_category_fs').on('change', function() { $('#lms_course_category').val($(this).val()); });
            $('#lms_level_fs').on('change', function() { $('#lms_level').val($(this).val()); });
            $('#lms_price_fs').on('input', function() { $('#lms_price').val($(this).val()); });
            $('#lms_price_khr_fs').on('input', function() { $('#lms_price_khr').val($(this).val()); });
            $('#lms_trailer_url_fs').on('input', function() { $('#lms_trailer_url').val($(this).val()); });

            // WordPress Media Library Uploader for Course Image
            var courseImageFrame;
            $('#lms-course-image-preview-container').on('click', '#lms-browse-course-image-btn', function(e) {
                e.preventDefault();
                if (courseImageFrame) {
                    courseImageFrame.open();
                    return;
                }
                courseImageFrame = wp.media({
                    title: 'Select Course Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                courseImageFrame.on('select', function() {
                    var attachment = courseImageFrame.state().get('selection').first().toJSON();
                    $('#lms_course_image_id').val(attachment.id);
                    
                    $('#lms-course-image-preview').attr('src', attachment.url).show();
                    $('#lms-course-image-placeholder-wrapper').hide();
                    $('#lms-remove-course-image-btn').css('display', 'flex');
                });
                courseImageFrame.open();
            });

            $('#lms-course-image-preview-container').on('click', '#lms-remove-course-image-btn', function(e) {
                e.preventDefault();
                $('#lms_course_image_id').val('');
                $('#lms-course-image-preview').attr('src', '').hide();
                $('#lms-course-image-placeholder-wrapper').css('display', 'flex');
                $(this).hide();
            });

            function updatePricingVisibility() {
                var priceType = $('input[name="lms_price_type"]:checked').val();
                var oneTimePurchase = $('#lms_pricing_onetime').is(':checked');

                if (priceType === 'free') {
                    $('#lms-paid-fields-container').slideUp(150);
                    $('#lms-price-info-container').slideDown(150);
                    $('#lms_price_fs').val('0').trigger('input');
                    $('#lms_price_khr_fs').val('0').trigger('input');
                } else {
                    $('#lms-paid-fields-container').slideDown(150);
                    if (oneTimePurchase) {
                        $('#lms-pricing-fields-detail-container').slideDown(150);
                        $('#lms-price-info-container').slideDown(150);
                    } else {
                        $('#lms-pricing-fields-detail-container').slideUp(150);
                        $('#lms-price-info-container').slideUp(150);
                    }
                }
            }

            $('input[name="lms_price_type"]').on('change', function() {
                updatePricingVisibility();
                if ($(this).val() === 'paid' && $('#lms_price_fs').val() === '0') {
                    $('#lms_price_fs').val('').focus();
                }
            });

            $('#lms_pricing_onetime').on('change', function() {
                updatePricingVisibility();
            });

            // Initial trigger
            updatePricingVisibility();

            function updatePricingVisibilityFAQ() {
                var priceType = $('input[name="lms_price_type_faq"]:checked').val();
                var oneTimePurchase = $('#lms_pricing_onetime_faq').is(':checked');

                if (priceType === 'free') {
                    $('#lms-paid-fields-container-faq').slideUp(150);
                    $('#lms_price_fs_faq').val('0');
                    $('#lms_price_khr_fs_faq').val('0');
                } else {
                    $('#lms-paid-fields-container-faq').slideDown(150);
                    if (oneTimePurchase) {
                        $('#lms-pricing-fields-detail-container-faq').slideDown(150);
                    } else {
                        $('#lms-pricing-fields-detail-container-faq').slideUp(150);
                    }
                }
            }

            $('input[name="lms_price_type_faq"]').on('change', function() {
                updatePricingVisibilityFAQ();
                if ($(this).val() === 'paid' && $('#lms_price_fs_faq').val() === '0') {
                    $('#lms_price_fs_faq').val('').focus();
                }
            });

            $('#lms_pricing_onetime_faq').on('change', function() {
                updatePricingVisibilityFAQ();
            });

            updatePricingVisibilityFAQ();

            // Sync primary teacher select and co-teacher checkbox states
            function syncCoTeachers() {
                var container = $('#lms-co-teachers-hidden-inputs');
                container.empty();
                $('.lms-co-teacher-checkbox:checked').each(function() {
                    container.append('<input type="hidden" name="lms_co_teachers[]" value="' + $(this).val() + '">');
                });
            }

            $(document).on('change', '.lms-co-teacher-checkbox', function() {
                syncCoTeachers();
            });

            // Toggle custom selects dropdown visibility
            $(document).on('click', '.lms-custom-select-trigger', function(e) {
                e.stopPropagation();
                var dropdown = $(this).siblings('.lms-custom-select-dropdown');
                $('.lms-custom-select-dropdown').not(dropdown).hide(); // Close all other custom dropdowns
                dropdown.toggle();
                if (dropdown.is(':visible')) {
                    dropdown.find('.lms-custom-select-search').val('').trigger('keyup').focus();
                }
            });

            // Close dropdowns on clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.lms-custom-select, .lms-custom-multiselect').length) {
                    $('.lms-custom-select-dropdown').hide();
                }
            });

            // Prevent dropdown closure when clicking search input or search container
            $(document).on('click', '.lms-custom-select-dropdown', function(e) {
                e.stopPropagation();
            });

            // Dropdown option search filter
            $(document).on('keyup', '.lms-custom-select-search', function() {
                var query = $(this).val().toLowerCase();
                $(this).siblings('.lms-custom-select-options').find('.lms-custom-select-option, .lms-custom-multiselect-option').each(function() {
                    var text = $(this).text().toLowerCase();
                    if (text.indexOf(query) > -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Primary Teacher Option Click
            $(document).on('click', '.lms-custom-select-option', function() {
                var val = $(this).data('value');
                var htmlContent = $(this).html();
                
                // Update trigger view
                var trigger = $(this).closest('.lms-custom-select').find('.lms-custom-select-selected-value');
                trigger.html(htmlContent);
                
                // Update active state in list
                $(this).siblings().removeAttr('data-selected').css('background', '');
                $(this).attr('data-selected', 'true').css('background', '#f1f5f9');
                
                // Update hidden course author field value and trigger change
                $('#lms_course_author_fs').val(val).trigger('change');
                
                // Close dropdown
                $(this).closest('.lms-custom-select-dropdown').hide();
            });

            // Co-teachers Multiselect Option Click
            $(document).on('click', '.lms-custom-multiselect-option', function() {
                var val = $(this).data('value');
                var isSelected = $(this).attr('data-selected') === 'true';
                var newSelected = !isSelected;
                
                $(this).attr('data-selected', newSelected ? 'true' : 'false');
                if (newSelected) {
                    $(this).find('.lms-check-indicator').show();
                } else {
                    $(this).find('.lms-check-indicator').hide();
                }
                
                // Sync to hidden checkbox
                $('.lms-co-teacher-checkbox[value="' + val + '"]').prop('checked', newSelected).trigger('change');
                
                renderCoTeacherPills();
            });

            // Render Co-teacher Pills function
            function renderCoTeacherPills() {
                var listContainer = $('.lms-co-teachers-selected-list');
                listContainer.empty();
                $('.lms-custom-multiselect-option[data-selected="true"]').each(function() {
                    var val = $(this).data('value');
                    var name = $(this).find('span').first().text();
                    var img = $(this).find('img').attr('src');
                    
                    var pill = $('<div class="lms-co-teacher-pill" style="display: flex; align-items: center; gap: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 20px; padding: 4px 10px 4px 6px; font-size: 13px; color: #334155; font-weight: 500; margin-bottom: 4px;">' +
                        '<img src="' + img + '" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">' +
                        '<span>' + name + '</span>' +
                        '<span class="lms-co-teacher-pill-remove" data-value="' + val + '" style="margin-left: 4px; cursor: pointer; color: #94a3b8; font-weight: bold; font-size: 12px; transition: color 0.2s;">✕</span>' +
                        '</div>');
                    listContainer.append(pill);
                });
            }

            // Remove co-teacher from badge/pill click
            $(document).on('click', '.lms-co-teacher-pill-remove', function(e) {
                e.stopPropagation();
                var val = $(this).data('value');
                
                var option = $('.lms-custom-multiselect-option[data-value="' + val + '"]');
                option.attr('data-selected', 'false');
                option.find('.lms-check-indicator').hide();
                
                $('.lms-co-teacher-checkbox[value="' + val + '"]').prop('checked', false).trigger('change');
                renderCoTeacherPills();
            });

            // Primary author change handler
            $('#lms_course_author_fs').on('change', function() {
                var newAuthor = $(this).val();
                $('#lms_course_author').val(newAuthor);

                // Check co-teachers options: hide/disable matching option
                $('.lms-custom-multiselect-option').each(function() {
                    var optVal = $(this).data('value');
                    if (optVal == newAuthor) {
                        // Unselect if it was selected
                        $(this).attr('data-selected', 'false');
                        $(this).find('.lms-check-indicator').hide();
                        $('.lms-co-teacher-checkbox[value="' + optVal + '"]').prop('checked', false);
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
                
                // Update hidden checkboxes and pills
                syncCoTeachers();
                renderCoTeacherPills();
            });

            // Initialize Co-teacher Pills & Hidden state matching current primary author on load
            var currentPrimaryAuthor = $('#lms_course_author_fs').val();
            $('.lms-custom-multiselect-option').each(function() {
                var optVal = $(this).data('value');
                if (optVal == currentPrimaryAuthor) {
                    $(this).attr('data-selected', 'false');
                    $(this).find('.lms-check-indicator').hide();
                    $('.lms-co-teacher-checkbox[value="' + optVal + '"]').prop('checked', false);
                    $(this).hide();
                }
            });
            syncCoTeachers();
            renderCoTeacherPills();

            // Info icon popover tooltips triggers
            $(document).on('click', '.lms-info-icon', function(e) {
                e.stopPropagation();
                var tooltipId = $(this).data('tooltip');
                $('.lms-tooltip-popover').not('#' + tooltipId).hide();
                $('#' + tooltipId).toggle();
            });
            $(document).on('click', '.lms-tooltip-close', function(e) {
                e.stopPropagation();
                $(this).closest('.lms-tooltip-popover').hide();
            });
            $(document).on('click', function() {
                $('.lms-tooltip-popover').hide();
            });
            $(document).on('click', '.lms-tooltip-popover', function(e) {
                e.stopPropagation();
            });

            // --- Course FAQ Management ---
            var faqs = [];
            try {
                var faqVal = $('#lms-course-faq-input').val();
                if (faqVal) {
                    faqs = JSON.parse(faqVal);
                }
            } catch(e) {
                faqs = [];
            }
            if (!Array.isArray(faqs)) {
                faqs = [];
            }

            function escapeHtml(text) {
                if (!text) return '';
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            function renderFaqs() {
                var container = $('#lms-faq-items-list');
                console.log("FAQ Render Initialized. Items count: ", faqs.length, "Container: ", container.length);
                container.empty();
                
                if (faqs.length === 0) {
                    console.log("Showing Empty State");
                    $('#lms-faq-empty-state').css('display', 'flex');
                } else {
                    console.log("Hiding Empty State");
                    $('#lms-faq-empty-state').hide();
                    
                    faqs.forEach(function(faq, index) {
                        if (faq.collapsed === undefined) {
                            faq.collapsed = true;
                        }
                        var isCollapsed = faq.collapsed;
                        var truncated = faq.question ? ': ' + (faq.question.length > 60 ? faq.question.substring(0, 60) + '...' : faq.question) : '';
                        
                        var html = '';
                        html += '<div class="lms-faq-item-card" data-index="' + index + '" style="background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; flex-direction: column; position: relative; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 4px;">';
                        
                        // Header
                        html += '    <div class="lms-faq-card-header" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; cursor: pointer; user-select: none;">';
                        html += '        <span style="font-weight: 700; color: #1e293b; font-size: 13.5px; display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0;">';
                        html += '            <span class="dashicons dashicons-menu" style="color: #94a3b8; font-size: 18px; width: 18px; height: 18px; cursor: move; flex-shrink: 0;" title="<?php _e('Drag to Reorder', 'reandaily-lms-theme'); ?>"></span>';
                        html += '            <div style="display: flex; flex-direction: column; gap: 2px; min-width: 0;">';
                        html += '                <span style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase;">Question ' + (index + 1) + '</span>';
                        html += '                <span class="lms-faq-title-text" style="font-size: 14.5px; font-weight: bold; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">' + (faq.question ? escapeHtml(faq.question) : '<?php _e('New Question', 'reandaily-lms-theme'); ?>') + '</span>';
                        html += '            </div>';
                        html += '        </span>';
                        html += '        <div style="display: flex; align-items: center; gap: 12px; flex-shrink: 0;">';
                        html += '            <button type="button" class="lms-remove-faq-btn" style="background: none; border: none; color: #94a3b8; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 6px; outline: none; transition: color 0.2s;" onmouseover="this.style.color=\'#ef4444\'" onmouseout="this.style.color=\'#94a3b8\'" title="<?php _e('Delete', 'reandaily-lms-theme'); ?>">';
                        html += '                <span class="dashicons dashicons-trash" style="font-size: 18px; width: 18px; height: 18px;"></span>';
                        html += '            </button>';
                        html += '            <span class="dashicons lms-faq-toggle-icon ' + (isCollapsed ? 'dashicons-arrow-right-alt2' : 'dashicons-arrow-down-alt2') + '" style="color: #475569; font-size: 18px; width: 18px; height: 18px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 6px; cursor: pointer;"></span>';
                        html += '        </div>';
                        html += '    </div>';
                        
                        // Collapsible Content Wrapper
                        html += '    <div class="lms-faq-card-content" style="display: ' + (isCollapsed ? 'none' : 'flex') + '; flex-direction: column; gap: 12px; padding: 20px;">';
                        html += '        <div style="display: flex; flex-direction: column; gap: 6px;">';
                        html += '            <label style="font-weight: 600; font-size: 12px; color: #475569;"><?php _e('Question', 'reandaily-lms-theme'); ?></label>';
                        html += '            <input type="text" class="lms-faq-question-input" value="' + escapeHtml(faq.question || '') + '" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; font-size: 14px; outline: none; box-sizing: border-box;" placeholder="<?php _e('e.g. Is this course for beginners?', 'reandaily-lms-theme'); ?>">';
                        html += '        </div>';
                        html += '        <div style="display: flex; flex-direction: column; gap: 6px;">';
                        html += '            <label style="font-weight: 600; font-size: 12px; color: #475569;"><?php _e('Answer', 'reandaily-lms-theme'); ?></label>';
                        html += '            <textarea class="lms-faq-answer-input" rows="4" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; font-size: 14px; resize: vertical; outline: none; box-sizing: border-box;" placeholder="<?php _e('e.g. Yes, absolutely! We start from the basics...', 'reandaily-lms-theme'); ?>">' + escapeHtml(faq.answer || '') + '</textarea>';
                        html += '        </div>';
                        html += '    </div>';
                        
                        html += '</div>';
                        container.append(html);
                    });
                }
            }

            function saveFaqsToInput() {
                var updatedFaqs = [];
                $('.lms-faq-item-card').each(function() {
                    var q = $(this).find('.lms-faq-question-input').val();
                    var a = $(this).find('.lms-faq-answer-input').val();
                    var isCollapsed = $(this).find('.lms-faq-card-content').is(':hidden');
                    updatedFaqs.push({ question: q, answer: a, collapsed: isCollapsed });
                });
                faqs = updatedFaqs;
                $('#lms-course-faq-input').val(JSON.stringify(faqs));
            }

            // Bind change events to dynamically update state on typing
            $(document).on('input change', '.lms-faq-question-input, .lms-faq-answer-input', function() {
                saveFaqsToInput();
            });

            // Update title text in header in real-time as question is typed
            $(document).on('input', '.lms-faq-question-input', function() {
                var val = $(this).val();
                var card = $(this).closest('.lms-faq-item-card');
                var titleSpan = card.find('.lms-faq-title-text');
                titleSpan.text(val ? val : 'New Question');
            });

            // Toggle card collapse state on header click
            $(document).on('click', '.lms-faq-card-header', function(e) {
                if ($(e.target).closest('.lms-remove-faq-btn, .dashicons-menu').length) {
                    return;
                }
                var card = $(this).closest('.lms-faq-item-card');
                var index = card.data('index');
                var content = card.find('.lms-faq-card-content');
                var chevron = card.find('.lms-faq-toggle-icon');
                
                if (content.is(':visible')) {
                    content.slideUp(150);
                    chevron.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                    faqs[index].collapsed = true;
                } else {
                    content.slideDown(150);
                    chevron.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    faqs[index].collapsed = false;
                }
                saveFaqsToInput();
            });

            // Add FAQ Click
            $(document).on('click', '.lms-add-faq-btn', function(e) {
                e.preventDefault();
                saveFaqsToInput();
                faqs.push({ question: '', answer: '', collapsed: false });
                renderFaqs();
                saveFaqsToInput();
            });

            // Delete FAQ Click
            $(document).on('click', '.lms-remove-faq-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var card = $(this).closest('.lms-faq-item-card');
                var index = card.data('index');
                saveFaqsToInput();
                faqs.splice(index, 1);
                renderFaqs();
                saveFaqsToInput();
            });

            // Initialize rendering
            renderFaqs();

            // Make FAQ list sortable via jQuery UI sortable
            if ($.fn.sortable) {
                $('#lms-faq-items-list').sortable({
                    handle: '.dashicons-menu',
                    update: function() {
                        saveFaqsToInput();
                        renderFaqs(); // Re-render to update the index titles (FAQ #1, #2, etc.)
                    }
                });
            }

            // Handle Publish/Update button click
            $('.lms-btn-publish').on('click', function(e) {
                e.preventDefault();

                var clickedBtn = $(this);
                if (clickedBtn.hasClass('lms-saving-active')) {
                    return;
                }
                clickedBtn.addClass('lms-saving-active');
                
                var originalText = clickedBtn.text().trim();
                var loadingText = (originalText === 'Save Settings' || originalText === 'Save Pricing') ? 'Saving...' : 'Updating...';
                
                $('.lms-btn-publish').prop('disabled', true).css({ 'opacity': '0.8', 'cursor': 'not-allowed' });
                clickedBtn.html('<span class="lms-btn-spinner"></span> ' + loadingText);

                // Explicit final sync of FAQs
                saveFaqsToInput();

                // Explicit final sync of DOM hierarchy into sections state
                saveStateFromDOM();

                // Explicit final sync of all values
                $('#title').val($('#lms-course-top-title').val());
                $('#lms_duration').val($('#lms_duration_fs').val());
                $('#lms_video_duration').val($('#lms_video_duration_fs').val());
                $('#lms_preview_description').val($('#lms_preview_description_fs').val());
                $('#lms_featured_course').prop('checked', $('#lms_featured_course_fs').is(':checked'));
                $('#lms_lock_lessons').prop('checked', $('#lms_lock_lessons_fs').is(':checked'));
                $('#lms_access_duration').val($('#lms_access_duration_fs').val());
                $('#lms_access_device_types').val($('#lms_access_device_types_fs').val());
                $('#lms_certification_info').val($('#lms_certification_info_fs').val());
                $('#lms_course_category').val($('#lms_course_category_fs').val());
                $('#lms_level').val($('#lms_level_fs').val());
                $('#lms_price').val($('#lms_price_fs').val());
                $('#lms_price_khr').val($('#lms_price_khr_fs').val());
                $('#lms_trailer_url').val($('#lms_trailer_url_fs').val());
                $('#lms_course_author').val($('#lms_course_author_fs').val());
                syncCoTeachers();

                // Sync TinyMCE editors
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }

                // Simulate clicking the WP publish/update button to pass post-status validation
                var publishBtn = $('#publish');
                if (publishBtn.length) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: publishBtn.attr('name'),
                        value: publishBtn.val()
                    }).appendTo('#post');
                }
                
                // Submit using native HTML Form element to bypass jQuery recursion/interceptors
                document.getElementById('post').submit();
            });

            // Handle Save Draft button click
            $('.lms-btn-save-draft').on('click', function(e) {
                e.preventDefault();

                // Explicit final sync of FAQs
                saveFaqsToInput();

                // Explicit final sync of DOM hierarchy into sections state
                saveStateFromDOM();

                // Explicit final sync of all values
                $('#title').val($('#lms-course-top-title').val());
                $('#lms_duration').val($('#lms_duration_fs').val());
                $('#lms_course_category').val($('#lms_course_category_fs').val());
                $('#lms_level').val($('#lms_level_fs').val());
                $('#lms_price').val($('#lms_price_fs').val());
                $('#lms_price_khr').val($('#lms_price_khr_fs').val());
                $('#lms_trailer_url').val($('#lms_trailer_url_fs').val());

                // Sync TinyMCE editors
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }

                // Simulate clicking the WP save-post button (Save Draft)
                var saveBtn = $('#save-post');
                if (saveBtn.length) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: saveBtn.attr('name'),
                        value: saveBtn.val()
                    }).appendTo('#post');
                } else {
                    // Fallback to custom save parameter if standard button not present
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'save',
                        value: 'Save Draft'
                    }).appendTo('#post');
                }
                
                // Submit using native HTML Form element
                document.getElementById('post').submit();
            });

            // Initialize Sortable on sections and lesson lists
            function initSyllabusSortables() {
                // Drag sections
                $("#lms-sections-sortable").sortable({
                    handle: ".lms-section-drag-handle",
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
                    var secId = $(this).attr('data-id');
                    var secTitle = $(this).find('.lms-section-title-input').val();
                    var lessons = [];
                    $(this).find('.lms-section-lesson-item').each(function() {
                        var lessonId = $(this).attr('data-id');
                        if (lessonId) {
                            lessons.push(parseInt(lessonId));
                        }
                    });
                    updatedSections.push({
                        id: secId,
                        title: secTitle,
                        lessons: lessons
                    });
                });
                sections = updatedSections;
                $('#lms-course-sections-input').val(JSON.stringify(sections));
                saveCurriculumToDatabase();
            }

            // Save curriculum data (sections & lesson sorting) to DB via Ajax
            function saveCurriculumToDatabase() {
                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_save_course_sections',
                    nonce: reandaily_lms_admin_vars.nonce,
                    course_id: reandaily_lms_admin_vars.post_id,
                    sections: JSON.stringify(sections)
                }, function(res) {
                    if (!res.success) {
                        console.error('Failed to auto-save curriculum:', res.data);
                    } else {
                        console.log('Curriculum auto-saved successfully');
                    }
                });
            }

            // Render Sections & Lessons
            function renderCurriculum() {
                var container = $('#lms-sections-sortable');
                container.empty();

                sections.forEach(function(sec, secIndex) {
                    var isCollapsed = sec.collapsed === true;
                    var arrowClass = isCollapsed ? 'dashicons-arrow-right-alt2' : 'dashicons-arrow-down-alt2';
                    var displayStyle = isCollapsed ? 'display: none;' : '';

                    var sectionHtml = `
                        <div class="lms-section-card" data-id="${sec.id}">
                            <div class="lms-section-card-header">
                                <div class="lms-section-title-wrap">
                                    <span class="dashicons dashicons-menu lms-section-drag-handle" style="color: #94a3b8; cursor: move; margin-right: 4px;"></span>
                                    <span class="lms-section-title-text" style="font-weight: 600; font-size: 14px; color: #1e293b; cursor: pointer;">${sec.title || 'Untitled Section'}</span>
                                    <input type="text" class="lms-section-title-input" value="${sec.title || ''}" style="display: none; font-size: 14px; font-weight: 600; color: #1e293b; border: 1px solid #3b82f6; border-radius: 4px; padding: 2px 6px; background: #fff; outline: none; width: 220px; box-sizing: border-box; height: 28px; line-height: 20px;">
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="lms-section-actions">
                                        <button type="button" class="lms-section-action-btn lms-edit-section-title" title="Edit Section Title">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                        <button type="button" class="lms-section-action-btn lms-delete-section" title="Delete Section">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </div>
                                    <span class="dashicons ${arrowClass} lms-section-toggle" style="color: #2563eb; cursor: pointer; font-size: 18px; width: 18px; height: 18px; background: #eff6ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 6px; transition: background 0.15s ease;"></span>
                                </div>
                            </div>
                            <ul class="lms-section-lessons-list" data-section-index="${secIndex}" style="${displayStyle}">
                    `;

                    // Render lessons inside this section
                    if (sec.lessons && sec.lessons.length > 0) {
                        sec.lessons.forEach(function(lessonId) {
                            var activeClass = (activeLessonId === lessonId) ? 'active' : '';
                            
                            // Find the lesson details locally or fetch via Ajax
                            sectionHtml += `
                                <li class="lms-section-lesson-item ${activeClass}" data-id="${lessonId}">
                                    <div class="lms-lesson-item-left">
                                        <span class="dashicons dashicons-video-alt3" id="icon-lesson-${lessonId}"></span>
                                        <span class="lms-lesson-item-title-label" id="lbl-lesson-${lessonId}">Lesson ID: ${lessonId}</span>
                                    </div>
                                    <div class="lms-lesson-item-actions">
                                        <button type="button" class="lms-lesson-action-btn lms-move-lesson" title="Move to Section" data-lesson-id="${lessonId}" data-section-index="${secIndex}">
                                            <span class="dashicons dashicons-migrate"></span>
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
                            <div class="lms-section-footer-btns" style="display: flex; gap: 8px; ${displayStyle}">
                                <button type="button" class="lms-btn-add-lesson" data-section-id="${sec.id}">
                                    <span class="dashicons dashicons-plus-alt"></span> Add lesson
                                </button>
                                <button type="button" class="lms-btn-search-material" data-section-id="${sec.id}" data-section-title="${sec.title || 'Section'}">
                                    <span class="dashicons dashicons-search"></span> Search lesson
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
                                    if (res.data.icon) {
                                        $(`#icon-lesson-${lessonId}`).removeClass(function (index, className) {
                                            return (className.match(/(^|\s)dashicons-\S+/g) || []).join(' ');
                                        }).addClass(res.data.icon);
                                    }
                                }
                            });
                        });
                    }
                });

                initSyllabusSortables();
            }

            function addSectionFromInput() {
                var input = $('#lms-new-section-title-input');
                var title = input.val().trim();
                
                if (!title) {
                    title = "Section " + (sections.length + 1);
                }
                
                sections.push({
                    id: 'sec_' + Date.now(),
                    title: title,
                    lessons: [],
                    collapsed: false
                });
                
                input.val(''); // Clear the input field
                $('#lms-course-sections-input').val(JSON.stringify(sections));
                renderCurriculum();
                saveCurriculumToDatabase();
            }
            
            // Add section trigger via button click or Enter keypress
            $('#lms-add-new-section-submit').on('click', function() {
                addSectionFromInput();
            });
            
            $('#lms-new-section-title-input').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    addSectionFromInput();
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

            var activeSectionIdForNewLesson = null;

            // Add Lesson inside a section via Modal Popup
            $(document).on('click', '.lms-btn-add-lesson', function() {
                activeSectionIdForNewLesson = $(this).attr('data-section-id');
                $('#lms-new-lesson-title').val('');
                $('#lms-add-lesson-modal').fadeIn(150);
                $('#lms-new-lesson-title').focus();
            });

            // Close modal
            $(document).on('click', '#lms-close-modal-btn, #lms-add-lesson-modal', function(e) {
                if (e.target === this || e.target.id === 'lms-close-modal-btn') {
                    $('#lms-add-lesson-modal').fadeOut(150);
                }
            });

            // Select Activity Card to create lesson
            $(document).on('click', '.lms-activity-card', function() {
                var title = $('#lms-new-lesson-title').val().trim();
                var type = $(this).attr('data-type');
                
                if (!title) {
                    var typeLabels = {
                        'text': 'New Text Lesson',
                        'video': 'New Video Lesson',
                        'stream': 'New Stream Lesson',
                        'zoom': 'New Zoom Lesson',
                        'quiz': 'New Quiz',
                        'assignment': 'New Assignment'
                    };
                    title = typeLabels[type] || 'New Lesson';
                }
                
                $('#lms-add-lesson-modal').fadeOut(150);
                var secId = activeSectionIdForNewLesson;

                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_create_lesson',
                    nonce: reandaily_lms_admin_vars.nonce,
                    title: title,
                    type: type
                }, function(res) {
                    if (res.success) {
                        var foundSec = sections.find(function(s) { return s.id == secId; });
                        if (foundSec) {
                            if (!foundSec.lessons) {
                                foundSec.lessons = [];
                            }
                            foundSec.lessons.push(res.data.id);
                        }
                        $('#lms-course-sections-input').val(JSON.stringify(sections));
                        renderCurriculum();
                        saveCurriculumToDatabase();
                        loadLessonEditor(res.data.id);
                    } else {
                        alert('Failed to create activity: ' + res.data);
                    }
                });
            });

            var activeSectionIdForImport = null;
            var activeSectionTitleForImport = '';

            // Open Search Material Modal
            $(document).on('click', '.lms-btn-search-material', function() {
                activeSectionIdForImport = $(this).attr('data-section-id');
                activeSectionTitleForImport = $(this).attr('data-section-title') || 'Section';
                
                $('#lms-search-material-input').val('');
                $('#lms-search-material-type').val('');
                $('#lms-import-materials-btn').prop('disabled', true).text('Import 0 materials to ' + activeSectionTitleForImport);
                
                loadMaterialsForImport('', '');
                $('#lms-search-material-modal').fadeIn(150);
            });

            // Close Search Material Modal
            $(document).on('click', '#lms-cancel-search-material-btn, #lms-search-material-modal', function(e) {
                if (e.target === this || e.target.id === 'lms-cancel-search-material-btn') {
                    $('#lms-search-material-modal').fadeOut(150);
                }
            });

            // Handle Input/Select filters
            $('#lms-search-material-input, #lms-search-material-type').on('input change', function() {
                var search = $('#lms-search-material-input').val();
                var type = $('#lms-search-material-type').val();
                loadMaterialsForImport(search, type);
            });

            // Load materials via AJAX
            function loadMaterialsForImport(search, type) {
                var listContainer = $('#lms-search-materials-list');
                listContainer.html('<div style="padding: 24px; text-align: center; color: #64748b; background: #fff;"><span class="spinner is-active" style="float: none; margin: 0 8px 0 0;"></span> Loading...</div>');
                
                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_search_materials',
                    nonce: reandaily_lms_admin_vars.nonce,
                    search: search,
                    type: type
                }, function(res) {
                    if (res.success) {
                        listContainer.empty();
                        var materials = res.data;
                        
                        if (materials.length === 0) {
                            listContainer.html('<div style="padding: 24px; text-align: center; color: #64748b; background: #fff; font-style: italic;">No materials found</div>');
                            return;
                        }
                        
                        materials.forEach(function(item) {
                            // Determine icon color based on type
                            var iconColor = '#3b82f6'; // Default blue
                            if (item.type === 'assignment') iconColor = '#ef4444'; // Red
                            else if (item.type === 'quiz') iconColor = '#f59e0b'; // Orange
                            else if (item.type === 'video') iconColor = '#2563eb'; // Dark Blue
                            else if (item.type === 'stream') iconColor = '#0ea5e9'; // Sky Blue
                            
                            var itemHtml = $(`
                                <div class="lms-material-item" data-id="${item.id}" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: #ffffff; cursor: pointer; transition: background 0.15s ease; border-bottom: 1px solid #f1f5f9;">
                                    <div style="display: flex; align-items: center; gap: 12px; pointer-events: none;">
                                        <span class="dashicons ${item.icon}" style="font-size: 20px; width: 20px; height: 20px; color: ${iconColor}; display: flex; align-items: center; justify-content: center;"></span>
                                        <span style="font-size: 14px; font-weight: 500; color: #1e293b;">${item.title}</span>
                                    </div>
                                    <input type="checkbox" class="lms-material-checkbox" value="${item.id}" style="width: 18px; height: 18px; cursor: pointer; pointer-events: none;">
                                </div>
                            `);
                            
                            // Row click toggles checkbox
                            itemHtml.on('click', function(e) {
                                var checkbox = $(this).find('.lms-material-checkbox');
                                checkbox.prop('checked', !checkbox.is(':checked'));
                                updateImportButtonState();
                            });
                            
                            listContainer.append(itemHtml);
                        });
                    } else {
                        listContainer.html('<div style="padding: 24px; text-align: center; color: #ef4444; background: #fff;">Error: ' + res.data + '</div>');
                    }
                });
            }

            // Update import button state & count
            function updateImportButtonState() {
                var checked = $('.lms-material-checkbox:checked');
                var checkedCount = checked.length;
                
                $('#lms-import-materials-btn')
                    .prop('disabled', checkedCount === 0)
                    .text('Import ' + checkedCount + ' materials to ' + activeSectionTitleForImport);
            }

            // Click Import Button to insert selected activities
            $('#lms-import-materials-btn').on('click', function() {
                var checked = $('.lms-material-checkbox:checked');
                if (checked.length === 0) return;
                
                var secId = activeSectionIdForImport;
                var foundSec = sections.find(function(s) { return s.id == secId; });
                
                if (foundSec) {
                    if (!foundSec.lessons) {
                        foundSec.lessons = [];
                    }
                    
                    checked.each(function() {
                        var lessonId = parseInt($(this).val());
                        // Avoid adding duplicates to the same section
                        if (foundSec.lessons.indexOf(lessonId) === -1) {
                            foundSec.lessons.push(lessonId);
                        }
                    });
                    
                    $('#lms-course-sections-input').val(JSON.stringify(sections));
                    renderCurriculum();
                    saveCurriculumToDatabase();
                }
                
                $('#lms-search-material-modal').fadeOut(150);
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
            $(document).on('click', '.lms-section-lesson-item', function(e) {
                // If clicking trash or move icons, return
                if ($(e.target).closest('.lms-remove-lesson, .lms-move-lesson').length > 0) return;
                
                var id = $(this).closest('.lms-section-lesson-item').data('id');
                loadLessonEditor(id);
            });

            // Move Lesson to another section dropdown action
            $(document).on('click', '.lms-move-lesson', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Remove any existing move dropdowns
                $('.lms-move-dropdown').remove();
                
                var btn = $(this);
                var lessonId = btn.data('lesson-id');
                var currentSecIndex = parseInt(btn.data('section-index'));
                
                // Create dropdown element
                var dropdown = $('<div class="lms-move-dropdown"></div>');
                dropdown.css({
                    'position': 'absolute',
                    'z-index': '999999',
                    'background': '#ffffff',
                    'border': '1px solid #cbd5e1',
                    'border-radius': '12px',
                    'box-shadow': '0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04)',
                    'padding': '16px',
                    'width': '300px',
                    'box-sizing': 'border-box',
                    'font-family': '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                });
                
                // Add header/title and close button (matching mockup)
                var header = $('<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;"></div>');
                var title = $('<span style="font-size: 16px; font-weight: 600; color: #1e293b;">Move to section</span>');
                var closeBtn = $('<span class="dashicons dashicons-no-alt" style="font-size: 18px; width: 18px; height: 18px; color: #2563eb; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: color 0.15s ease;"></span>');
                
                closeBtn.hover(
                    function() { $(this).css('color', '#1d4ed8'); },
                    function() { $(this).css('color', '#2563eb'); }
                );
                closeBtn.on('click', function(e) {
                    e.stopPropagation();
                    dropdown.remove();
                });
                
                header.append(title).append(closeBtn);
                dropdown.append(header);
                
                // Add search container and input
                var searchContainer = $('<div style="position: relative; margin-bottom: 8px; display: flex; align-items: center;"></div>');
                var searchInput = $('<input type="text" placeholder="Select..." style="width: 100%; padding: 10px 36px 10px 14px; border-radius: 8px; border: 1.5px solid #3b82f6; font-size: 14px; outline: none; background: #fff; box-shadow: none; box-sizing: border-box; height: 44px; color: #1e293b;">');
                var searchArrow = $('<span class="dashicons dashicons-arrow-down-alt2" style="position: absolute; right: 12px; color: #64748b; font-size: 14px; pointer-events: none;"></span>');
                
                searchContainer.append(searchInput).append(searchArrow);
                dropdown.append(searchContainer);
                
                // Add options list container
                var listContainer = $('<div class="lms-move-list-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-top: 6px; background: #ffffff;"></div>');
                dropdown.append(listContainer);
                
                // Populate filtered options
                function populateOptions(filterText) {
                    listContainer.empty();
                    var matchCount = 0;
                    
                    sections.forEach(function(sec, idx) {
                        if (idx === currentSecIndex) return; // Skip current section
                        
                        var secTitle = sec.title || ('Section ' + (idx + 1));
                        if (filterText && secTitle.toLowerCase().indexOf(filterText.toLowerCase()) === -1) {
                            return;
                        }
                        
                        matchCount++;
                        var option = $('<div class="lms-move-option" style="padding: 10px 14px; font-size: 14px; color: #1e293b; cursor: pointer; transition: background 0.15s ease; font-weight: 500;"></div>');
                        option.text(secTitle);
                        
                        option.hover(
                            function() { $(this).css('background', '#f1f5f9'); },
                            function() { $(this).css('background', 'transparent'); }
                        );
                        
                        option.on('click', function(e) {
                            e.stopPropagation();
                            
                            // Remove lesson from current section
                            var currentSec = sections[currentSecIndex];
                            currentSec.lessons = currentSec.lessons.filter(function(id) {
                                return parseInt(id) !== parseInt(lessonId);
                            });
                            
                            // Add to new section
                            if (!sections[idx].lessons) {
                                sections[idx].lessons = [];
                            }
                            sections[idx].lessons.push(lessonId);
                            
                            // Re-render and save
                            renderCurriculum();
                            saveCurriculumToDatabase();
                            dropdown.remove();
                        });
                        
                        listContainer.append(option);
                    });
                    
                    if (matchCount === 0) {
                        var noResults = $('<div style="padding: 12px 14px; font-size: 13px; color: #94a3b8; font-style: italic; text-align: center;">No sections found</div>');
                        listContainer.append(noResults);
                    }
                }
                
                // Initial load
                populateOptions('');
                
                // Filter items on type
                searchInput.on('input', function() {
                    populateOptions($(this).val());
                });
                
                // Append to body and position next to the clicked button
                $('body').append(dropdown);
                
                var btnOffset = btn.offset();
                var btnHeight = btn.outerHeight();
                var btnWidth = btn.outerWidth();
                var dropdownWidth = dropdown.outerWidth();
                
                dropdown.css({
                    'top': (btnOffset.top + btnHeight + 4) + 'px',
                    'left': (btnOffset.left + btnWidth - dropdownWidth) + 'px'
                });
                
                // Auto focus the input for immediate typing
                setTimeout(function() {
                    searchInput.focus();
                }, 50);
            });
            
            // Close dropdown when clicking outside (prevent closing if clicking inside the dropdown itself)
            $(document).on('click', function(e) {
                if ($(e.target).closest('.lms-move-dropdown, .lms-move-lesson').length === 0) {
                    $('.lms-move-dropdown').remove();
                }
            });

            // Expand/Collapse Section lessons (triggers on clicking the section header bar)
            $(document).on('click', '.lms-section-card-header', function(e) {
                // If clicking edit, delete, or title input field, ignore toggle
                if ($(e.target).closest('.lms-section-actions, .lms-section-title-input, .lms-section-drag-handle').length > 0) {
                    return;
                }
                
                var header = $(this);
                var toggle = header.find('.lms-section-toggle');
                var card = header.closest('.lms-section-card');
                var list = card.find('.lms-section-lessons-list');
                var footer = card.find('.lms-section-footer-btns');
                var secId = card.data('id');
                
                list.slideToggle(200);
                footer.slideToggle(200);
                
                var isCollapsed = false;
                if (toggle.hasClass('dashicons-arrow-down-alt2')) {
                    toggle.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                    isCollapsed = true;
                } else {
                    toggle.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    isCollapsed = false;
                }

                // Update local sections structure state
                sections.forEach(function(sec) {
                    if (sec.id == secId) {
                        sec.collapsed = isCollapsed;
                    }
                });

                // Update hidden input cache
                $('#lms-course-sections-input').val(JSON.stringify(sections));
            });

            // Toggle all sections expand/collapse state
            $(document).on('click', '#lms-curriculum-toggle-all', function() {
                var btn = $(this);
                var isExpanded = btn.attr('data-expanded') === 'true';
                
                if (isExpanded) {
                    // Collapse all
                    $('.lms-section-lessons-list').slideUp(200);
                    $('.lms-section-footer-btns').slideUp(200);
                    $('.lms-section-toggle')
                        .removeClass('dashicons-arrow-down-alt2')
                        .addClass('dashicons-arrow-right-alt2');
                    
                    btn.attr('data-expanded', 'false');
                    $('#lms-toggle-all-icon').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                    $('#lms-toggle-all-text').text('Expand All');

                    // Update all sections state
                    sections.forEach(function(sec) {
                        sec.collapsed = true;
                    });
                } else {
                    // Expand all
                    $('.lms-section-lessons-list').slideDown(200);
                    $('.lms-section-footer-btns').slideDown(200);
                    $('.lms-section-toggle')
                        .removeClass('dashicons-arrow-right-alt2')
                        .addClass('dashicons-arrow-down-alt2');
                    
                    btn.attr('data-expanded', 'true');
                    $('#lms-toggle-all-icon').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                    $('#lms-toggle-all-text').text('Collapse All');

                    // Update all sections state
                    sections.forEach(function(sec) {
                        sec.collapsed = false;
                    });
                }

                // Update hidden input cache
                $('#lms-course-sections-input').val(JSON.stringify(sections));
            });

            // Edit Section Title Inline
            $(document).on('click', '.lms-edit-section-title', function(e) {
                e.stopPropagation();
                var header = $(this).closest('.lms-section-card-header');
                var textSpan = header.find('.lms-section-title-text');
                var input = header.find('.lms-section-title-input');
                
                textSpan.hide();
                input.show().focus().select();
            });
            
            $(document).on('dblclick', '.lms-section-title-text', function(e) {
                var header = $(this).closest('.lms-section-card-header');
                var input = header.find('.lms-section-title-input');
                
                $(this).hide();
                input.show().focus().select();
            });
            
            // Save Section Title on blur or Enter key
            $(document).on('blur', '.lms-section-title-input', function() {
                var input = $(this);
                var header = input.closest('.lms-section-card-header');
                var card = input.closest('.lms-section-card');
                var textSpan = header.find('.lms-section-title-text');
                var secId = card.data('id');
                var newVal = input.val().trim() || 'Untitled Section';
                
                // Update local sections structure
                sections.forEach(function(sec) {
                    if (sec.id == secId) {
                        sec.title = newVal;
                    }
                });
                
                textSpan.text(newVal).show();
                input.hide();
                
                saveCurriculumToDatabase();
            });
            
            $(document).on('keypress', '.lms-section-title-input', function(e) {
                if (e.which === 13) { // Enter key
                    $(this).blur();
                }
            });

            // Fetch lesson data and display on the right
            function loadLessonEditor(id) {
                activeLessonId = id;
                $('.lms-section-lesson-item').removeClass('active');
                $(`.lms-section-lesson-item[data-id="${id}"]`).addClass('active');

                // Reset active tab to Settings content
                $('.lms-lesson-tab').removeClass('active');
                $(`.lms-lesson-tab[data-target="#lms-lesson-tab-content-settings"]`).addClass('active');
                $('.lms-tab-content-pane').hide();
                $('#lms-lesson-tab-content-settings').show();

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
                        $('#lms-edit-lesson-unlock-drip').prop('checked', res.data.unlock_drip === 1);
                        if (startDatePicker) {
                            startDatePicker.setDate(res.data.start_date || '');
                        } else {
                            $('#lms-edit-lesson-start-date').val(res.data.start_date || '');
                        }
                        $('#lms-edit-lesson-start-time').val(res.data.start_time || '');
                        $('#lms-edit-lesson-start-ampm').val(res.data.start_ampm || 'AM');
                        
                        if (typeof tinymce !== 'undefined' && tinymce.get(descEditorId)) {
                            tinymce.get(descEditorId).setContent(res.data.description || '');
                        } else {
                            $('#' + descEditorId).val(res.data.description || '');
                        }
                        
                        // Update badge label and icon dynamically
                        var badgeLabel = 'Text lesson';
                        var badgeIcon = 'dashicons-media-text';
                        
                        var lmsType = res.data.type || 'text';
                        if (lmsType === 'video') {
                            badgeLabel = 'Video lesson';
                            badgeIcon = 'dashicons-video-alt3';
                        } else if (lmsType === 'stream') {
                            badgeLabel = 'Stream lesson';
                            badgeIcon = 'dashicons-rss';
                        } else if (lmsType === 'zoom') {
                            badgeLabel = 'Zoom lesson';
                            badgeIcon = 'dashicons-welcome-teleport-reline';
                        } else if (lmsType === 'quiz') {
                            badgeLabel = 'Quiz';
                            badgeIcon = 'dashicons-welcome-write-blog';
                        } else if (lmsType === 'assignment') {
                            badgeLabel = 'Assignment';
                            badgeIcon = 'dashicons-clipboard';
                        } else {
                            badgeLabel = lmsType.charAt(0).toUpperCase() + lmsType.slice(1) + ' lesson';
                            badgeIcon = res.data.icon || 'dashicons-media-text';
                        }
                        
                        $('#lms-editor-type-badge .lms-type-badge-text').text(badgeLabel);
                        $('#lms-editor-type-badge .dashicons').removeClass(function (index, className) {
                            return (className.match(/(^|\s)dashicons-\S+/g) || []).join(' ');
                        }).addClass(badgeIcon);

                        // Show/Hide video URL based on type
                        if (lmsType === 'video' || lmsType === 'stream' || lmsType === 'zoom') {
                            $('#lms-group-video-url').show();
                        } else {
                            $('#lms-group-video-url').hide();
                        }

                        if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                            tinymce.get(editorId).setContent(res.data.content || '');
                        } else {
                            $('#' + editorId).val(res.data.content || '');
                        }

                        // Load Quiz details inside modal
                        if (lmsType === 'quiz') {
                            $('.lms-tab-quiz-only').show();
                            $('#lms-edit-quiz-passing-grade').val(res.data.quiz_passing_grade || '70');
                            $('#lms-edit-quiz-time-limit').val(res.data.quiz_time_limit || '0');
                            $('#lms-edit-quiz-retakes').val(res.data.quiz_retakes || '0');
                            $('#lms-edit-quiz-questions-data').val(res.data.quiz_questions || '[]');
                            renderModalQuestions();
                        } else {
                            $('.lms-tab-quiz-only').hide();
                        }

                        // Load Video Questions inside modal
                        if (lmsType === 'video') {
                            $('.lms-tab-video-only').show();
                            $('#lms-edit-video-questions-data').val(res.data.video_questions || '[]');
                            renderModalVideoQuestions();
                        } else {
                            $('.lms-tab-video-only').hide();
                        }

                        $('.lms-lesson-editor-form').fadeIn(150, function() {
                            // Focus & select title if it's default placeholder to allow easy customization
                            if (res.data.title.startsWith('New ') && res.data.title.includes('Lesson') || res.data.title.startsWith('New Quiz') || res.data.title.startsWith('New Assignment')) {
                                $('#lms-edit-lesson-title').select().focus();
                            }
                        });
                    } else {
                        alert('Could not load lesson data.');
                    }
                });
            }

            // Tab switching inside Lesson Editor panel
            $(document).on('click', '.lms-lesson-tab', function() {
                $('.lms-lesson-tab').removeClass('active');
                $(this).addClass('active');
                
                var targetPane = $(this).attr('data-target');
                $('.lms-tab-content-pane').hide();
                $(targetPane).show();
                
                // If switching to Q&A, load the questions
                if (targetPane === '#lms-lesson-tab-content-qa') {
                    loadLessonQuestions(activeLessonId);
                }
            });

            // Refresh Q&A
            $(document).on('click', '#lms-btn-refresh-qa', function() {
                if (activeLessonId) {
                    loadLessonQuestions(activeLessonId);
                }
            });

            // Fetch questions for active lesson
            function loadLessonQuestions(lessonId) {
                $('#lms-qa-list-container').html('<p style="color: #64748b; font-size: 13px; text-align: center; padding: 20px;">Loading questions...</p>');
                
                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_get_lesson_questions',
                    nonce: reandaily_lms_admin_vars.nonce,
                    lesson_id: lessonId
                }, function(res) {
                    if (res.success) {
                        var questions = res.data;
                        if (!questions || questions.length === 0) {
                            $('#lms-qa-list-container').html('<p style="color: #64748b; font-size: 13px; text-align: center; padding: 20px;">No questions posted yet.</p>');
                            return;
                        }
                        
                        var html = '';
                        questions.forEach(function(q) {
                            var repliesHtml = '';
                            if (q.replies && q.replies.length > 0) {
                                q.replies.forEach(function(r) {
                                    repliesHtml += `
                                        <div class="lms-qa-reply-item" style="display: flex; gap: 10px; margin-top: 12px; background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 3px solid #10b981;">
                                            <img src="${r.avatar_url}" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
                                            <div style="flex: 1;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                    <strong style="font-size: 12px; color: #1e293b;">${r.author} <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; margin-left: 4px;">Teacher</span></strong>
                                                    <span style="font-size: 11px; color: #94a3b8;">${r.date}</span>
                                                </div>
                                                <p style="margin: 0; font-size: 12.5px; color: #475569; line-height: 1.5; white-space: pre-wrap;">${r.content}</p>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            
                            html += `
                                <div class="lms-qa-question-card" data-question-id="${q.id}" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.05); margin-bottom: 12px;">
                                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                                        <img src="${q.avatar_url}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                <strong style="font-size: 13.5px; color: #0f172a;">${q.author}</strong>
                                                <span style="font-size: 11px; color: #94a3b8;">${q.date}</span>
                                            </div>
                                            <p style="margin: 0 0 12px 0; font-size: 13.5px; color: #334155; line-height: 1.6; white-space: pre-wrap;">${q.content}</p>
                                            
                                            <div class="lms-qa-replies-list" id="lms-qa-replies-for-${q.id}">
                                                ${repliesHtml}
                                            </div>
                                            
                                            <!-- Answer Reply Form -->
                                            <div style="margin-top: 16px; border-top: 1px dashed #e2e8f0; padding-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                                                <textarea class="lms-qa-reply-textarea" placeholder="Type your answer to this question..." rows="2" style="width: 100%; font-size: 12.5px; border-radius: 6px; padding: 8px 10px; border: 1px solid #cbd5e1; outline: none; resize: vertical; box-sizing: border-box;"></textarea>
                                                <div style="display: flex; justify-content: flex-end;">
                                                    <button type="button" class="lms-btn-submit-answer" data-question-id="${q.id}" style="background: #10b981; border: none; color: #fff; padding: 6px 14px; border-radius: 4px; font-weight: 600; font-size: 11.5px; cursor: pointer; transition: background 0.15s ease;">Submit Answer</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        
                        $('#lms-qa-list-container').html(html);
                    } else {
                        $('#lms-qa-list-container').html('<p style="color: #ef4444; font-size: 13px; text-align: center; padding: 20px;">Could not load questions.</p>');
                    }
                });
            }

            // Submit reply/answer
            $(document).on('click', '.lms-btn-submit-answer', function() {
                var btn = $(this);
                var questionId = btn.attr('data-question-id');
                var textarea = btn.closest('div').parent().find('.lms-qa-reply-textarea');
                var content = textarea.val().trim();
                
                if (content === '') {
                    alert('Please enter an answer first.');
                    textarea.focus();
                    return;
                }
                
                btn.text('Submitting...').prop('disabled', true);
                
                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_reply_to_question',
                    nonce: reandaily_lms_admin_vars.nonce,
                    lesson_id: activeLessonId,
                    parent_id: questionId,
                    content: content
                }, function(res) {
                    btn.text('Submit Answer').prop('disabled', false);
                    if (res.success) {
                        textarea.val('');
                        var replyHtml = `
                            <div class="lms-qa-reply-item" style="display: flex; gap: 10px; margin-top: 12px; background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 3px solid #10b981;">
                                <img src="${res.data.avatar_url}" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <strong style="font-size: 12px; color: #1e293b;">${res.data.author} <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; margin-left: 4px;">Teacher</span></strong>
                                        <span style="font-size: 11px; color: #94a3b8;">${res.data.date}</span>
                                    </div>
                                    <p style="margin: 0; font-size: 12.5px; color: #475569; line-height: 1.5; white-space: pre-wrap;">${res.data.content}</p>
                                </div>
                            </div>
                        `;
                        $(`#lms-qa-replies-for-${questionId}`).append(replyHtml);
                    } else {
                        alert('Failed to submit answer: ' + res.data);
                    }
                });
            });

            // Submit new question/topic from teacher/admin
            $(document).on('click', '#lms-btn-teacher-post-question', function() {
                var btn = $(this);
                var textarea = $('#lms-teacher-new-question-text');
                var content = textarea.val().trim();
                
                if (content === '') {
                    alert('Please enter a question or topic first.');
                    textarea.focus();
                    return;
                }
                
                btn.text('Posting...').prop('disabled', true);
                
                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_reply_to_question',
                    nonce: reandaily_lms_admin_vars.nonce,
                    lesson_id: activeLessonId,
                    parent_id: 0,
                    content: content
                }, function(res) {
                    btn.text('Post Question').prop('disabled', false);
                    if (res.success) {
                        textarea.val('');
                        loadLessonQuestions(activeLessonId);
                    } else {
                        alert('Failed to post question: ' + res.data);
                    }
                });
            });

            // Save lesson settings (directly bound to the sticky footer Save button)
            $('#lms-save-lesson-btn-sticky').on('click', function() {
                var id = $('#lms-edit-lesson-id').val();
                var btn = $(this);
                btn.text('Saving...').prop('disabled', true);

                var lessonContent = '';
                if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                    lessonContent = tinymce.get(editorId).getContent();
                } else {
                    lessonContent = $('#' + editorId).val();
                }

                var lessonDesc = '';
                if (typeof tinymce !== 'undefined' && tinymce.get(descEditorId)) {
                    lessonDesc = tinymce.get(descEditorId).getContent();
                } else {
                    lessonDesc = $('#' + descEditorId).val();
                }

                var rawVideoUrl = $('#lms-edit-lesson-video-url').val().trim();
                var matchedSrc = rawVideoUrl.match(/src=["']([^"']+)["']/i);
                var cleanVideoUrl = matchedSrc ? matchedSrc[1] : rawVideoUrl;

                $.post(reandaily_lms_admin_vars.ajaxurl, {
                    action: 'reandaily_lms_save_lesson_settings',
                    nonce: reandaily_lms_admin_vars.nonce,
                    lesson_id: id,
                    title: $('#lms-edit-lesson-title').val(),
                    video_url: cleanVideoUrl,
                    duration: $('#lms-edit-lesson-duration').val(),
                    is_preview: $('#lms-edit-lesson-preview').is(':checked') ? 1 : 0,
                    unlock_drip: $('#lms-edit-lesson-unlock-drip').is(':checked') ? 1 : 0,
                    start_date: $('#lms-edit-lesson-start-date').val(),
                    start_time: $('#lms-edit-lesson-start-time').val(),
                    start_ampm: $('#lms-edit-lesson-start-ampm').val(),
                    content: lessonContent,
                    description: lessonDesc,
                    quiz_passing_grade: $('#lms-edit-quiz-passing-grade').val(),
                    quiz_time_limit: $('#lms-edit-quiz-time-limit').val(),
                    quiz_retakes: $('#lms-edit-quiz-retakes').val(),
                    quiz_questions: $('#lms-edit-quiz-questions-data').val(),
                    video_questions: $('#lms-edit-video-questions-data').val()
                }, function(res) {
                    btn.text('Save Settings').prop('disabled', false);
                    if (res.success) {
                        $(`#lbl-lesson-${id}`).text($('#lms-edit-lesson-title').val());
                        if (res.data && res.data.icon) {
                            $(`#icon-lesson-${id}`).removeClass(function (index, className) {
                                return (className.match(/(^|\s)dashicons-\S+/g) || []).join(' ');
                            }).addClass(res.data.icon);
                        }
                    } else {
                        alert('Failed to save settings: ' + res.data);
                    }
                });
            });

            // WordPress Media Uploader for lesson materials
            $(document).on('click', '#lms-btn-browse-material', function(e) {
                e.preventDefault();
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({
                        title: 'Select or Upload Lesson Material',
                        button: {
                            text: 'Use this file'
                        },
                        multiple: false
                    });

                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#lms-edit-lesson-video-url').val(attachment.url).trigger('change');
                    });

                    frame.open();
                }
            });

            // Video Questions Builder Logic for course editor modal
            var modalVideoQuestions = [];
            
            function timeToSeconds(timeStr) {
                if (!timeStr) return 0;
                if (/^\d+$/.test(timeStr)) {
                    return parseInt(timeStr, 10);
                }
                var parts = timeStr.split(':');
                if (parts.length === 2) {
                    return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
                } else if (parts.length === 3) {
                    return parseInt(parts[0], 10) * 3600 + parseInt(parts[1], 10) * 60 + parseInt(parts[2], 10);
                }
                return parseInt(timeStr, 10) || 0;
            }

            function secondsToTime(secs) {
                var h = Math.floor(secs / 3600);
                var m = Math.floor((secs % 3600) / 60);
                var s = Math.floor(secs % 60);
                var out = '';
                if (h > 0) {
                    out += (h < 10 ? '0' + h : h) + ':';
                }
                out += (m < 10 ? '0' + m : m) + ':';
                out += (s < 10 ? '0' + s : s);
                return out;
            }

            function renderModalVideoQuestions() {
                var $dataInput = $('#lms-edit-video-questions-data');
                try {
                    modalVideoQuestions = JSON.parse($dataInput.val() || '[]');
                } catch(e) {
                    console.error("Failed to parse modal video questions JSON", e);
                    modalVideoQuestions = [];
                }

                var $container = $('#lms-modal-video-questions-list');
                $container.empty();

                if (modalVideoQuestions.length === 0) {
                    $container.append('<p style="color: #646970; font-style: italic; padding: 20px; text-align: center; border: 1px dashed #ccd0d4; background: #f6f7f7; border-radius: 4px;">No video questions added yet. Click \'Add Video Question\' to get started.</p>');
                    return;
                }

                modalVideoQuestions.forEach(function(q, qIdx) {
                    var html = '';
                    html += '<div class="lms-video-question-card" data-index="' + qIdx + '" style="border: 1px solid #ccd0d4; background: #fff; border-radius: 4px; padding: 16px; position: relative; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 12px;">';
                    
                    // Header with remove button
                    html += '  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">';
                    html += '    <h4 style="margin: 0; font-size: 14px; font-weight: 700;">Video Question #' + (qIdx + 1) + '</h4>';
                    html += '    <button type="button" class="lms-modal-btn-remove-video-question" style="background: none; border: none; color: #b32d2d; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600;" data-index="' + qIdx + '"><span class="dashicons dashicons-trash"></span> Remove</button>';
                    html += '  </div>';

                    // Time, Question Title & Type Selector
                    html += '  <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 16px; margin-bottom: 12px;">';
                    html += '    <div>';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Timestamp (MM:SS or sec)</label>';
                    html += '      <input type="text" class="lms-modal-video-question-time" value="' + (secondsToTime(q.time) || '00:00') + '" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;" placeholder="e.g. 01:30">';
                    html += '    </div>';
                    html += '    <div>';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Question Title</label>';
                    html += '      <input type="text" class="lms-modal-video-question-text" value="' + (q.question || '') + '" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;" placeholder="What is...">';
                    html += '    </div>';
                    html += '    <div>';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Type</label>';
                    html += '      <select class="lms-modal-video-question-type" style="width: 100%; height: 38px; border: 1px solid #8c8f94; border-radius: 4px;">';
                    html += '        <option value="single" ' + (q.type === 'single' ? 'selected' : '') + '>Single choice</option>';
                    html += '        <option value="multi" ' + (q.type === 'multi' ? 'selected' : '') + '>Multiple choice</option>';
                    html += '        <option value="truefalse" ' + (q.type === 'truefalse' ? 'selected' : '') + '>True-False</option>';
                    html += '      </select>';
                    html += '    </div>';
                    html += '  </div>';

                    // Options list
                    if (q.type === 'truefalse') {
                        html += '  <div style="background: #f6f7f7; padding: 12px; border-radius: 4px; border: 1px solid #e5e5e5;">';
                        html += '    <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 8px;">Correct Answer</label>';
                        html += '    <div style="display: flex; gap: 20px;">';
                        html += '      <label><input type="radio" name="modal_vtf_' + qIdx + '" class="lms-modal-video-tf-answer" value="true" ' + (q.answer === 'true' || q.answer === true ? 'checked' : '') + '> True</label>';
                        html += '      <label><input type="radio" name="modal_vtf_' + qIdx + '" class="lms-modal-video-tf-answer" value="false" ' + (q.answer === 'false' || q.answer === false ? 'checked' : '') + '> False</label>';
                        html += '    </div>';
                        html += '  </div>';
                    } else {
                        html += '  <div style="background: #f6f7f7; padding: 12px; border-radius: 4px; border: 1px solid #e5e5e5;">';
                        html += '    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
                        html += '      <label style="font-weight: 600; font-size: 12px;">Answer Options</label>';
                        html += '      <button type="button" class="lms-modal-btn-add-video-option button button-small" data-index="' + qIdx + '">Add Option</button>';
                        html += '    </div>';
                        html += '    <div class="lms-modal-video-options-list" style="display: flex; flex-direction: column; gap: 8px;">';

                        var options = q.options || [];
                        if (options.length === 0) {
                            options = ["Option 1", "Option 2"];
                            q.options = options;
                            q.answer = (q.type === 'single' ? 0 : [0]);
                        }

                        options.forEach(function(opt, optIdx) {
                            html += '      <div style="display: flex; align-items: center; gap: 10px;">';
                            
                            if (q.type === 'single') {
                                html += '        <input type="radio" name="modal_vans_' + qIdx + '" class="lms-modal-video-opt-correct-radio" data-opt-idx="' + optIdx + '" ' + (parseInt(q.answer) === optIdx ? 'checked' : '') + '>';
                            } else {
                                var isChecked = Array.isArray(q.answer) ? q.answer.indexOf(optIdx) !== -1 : false;
                                html += '        <input type="checkbox" class="lms-modal-video-opt-correct-checkbox" data-opt-idx="' + optIdx + '" ' + (isChecked ? 'checked' : '') + '>';
                            }

                            html += '        <input type="text" class="lms-modal-video-opt-text" style="flex: 1; padding: 6px; border: 1px solid #8c8f94; border-radius: 4px;" value="' + (opt || '') + '" placeholder="Enter option text">';
                            html += '        <button type="button" class="lms-modal-btn-remove-video-option" style="background:none; border:none; color:#b32d2d; cursor:pointer;" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '"><span class="dashicons dashicons-no-alt"></span></button>';
                            html += '      </div>';
                        });

                        html += '    </div>';
                        html += '  </div>';
                    }

                    html += '</div>';
                    $container.append(html);
                });

                serializeModalVideoData();
            }

            function serializeModalVideoData() {
                var $dataInput = $('#lms-edit-video-questions-data');
                $dataInput.val(JSON.stringify(modalVideoQuestions));
            }

            // Bind modal video events
            $(document).on('click', '#lms-modal-btn-add-video-question', function() {
                modalVideoQuestions.push({
                    time: 0,
                    question: '',
                    type: 'single',
                    options: ['Option 1', 'Option 2'],
                    answer: 0
                });
                renderModalVideoQuestions();
            });

            $(document).on('click', '.lms-modal-btn-remove-video-question', function() {
                var idx = $(this).data('index');
                modalVideoQuestions.splice(idx, 1);
                renderModalVideoQuestions();
            });

            $(document).on('click', '.lms-modal-btn-add-video-option', function() {
                var qIdx = $(this).data('index');
                modalVideoQuestions[qIdx].options.push('New Option');
                if (modalVideoQuestions[qIdx].type === 'multi' && !Array.isArray(modalVideoQuestions[qIdx].answer)) {
                    modalVideoQuestions[qIdx].answer = [];
                }
                renderModalVideoQuestions();
            });

            $(document).on('click', '.lms-modal-btn-remove-video-option', function() {
                var qIdx = $(this).data('q-idx');
                var optIdx = $(this).data('opt-idx');
                modalVideoQuestions[qIdx].options.splice(optIdx, 1);
                if (modalVideoQuestions[qIdx].type === 'single') {
                    if (parseInt(modalVideoQuestions[qIdx].answer) === optIdx) {
                        modalVideoQuestions[qIdx].answer = 0;
                    } else if (parseInt(modalVideoQuestions[qIdx].answer) > optIdx) {
                        modalVideoQuestions[qIdx].answer = parseInt(modalVideoQuestions[qIdx].answer) - 1;
                    }
                } else if (modalVideoQuestions[qIdx].type === 'multi') {
                    var ans = Array.isArray(modalVideoQuestions[qIdx].answer) ? modalVideoQuestions[qIdx].answer : [];
                    var newAns = [];
                    ans.forEach(function(val) {
                        if (val < optIdx) {
                            newAns.push(val);
                        } else if (val > optIdx) {
                            newAns.push(val - 1);
                        }
                    });
                    modalVideoQuestions[qIdx].answer = newAns;
                }
                renderModalVideoQuestions();
            });

            $(document).on('change', '.lms-modal-video-question-type', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                var type = $(this).val();
                modalVideoQuestions[qIdx].type = type;
                if (type === 'truefalse') {
                    modalVideoQuestions[qIdx].answer = true;
                    delete modalVideoQuestions[qIdx].options;
                } else if (type === 'multi') {
                    modalVideoQuestions[qIdx].options = modalVideoQuestions[qIdx].options || ['Option 1', 'Option 2'];
                    modalVideoQuestions[qIdx].answer = [0];
                } else {
                    modalVideoQuestions[qIdx].options = modalVideoQuestions[qIdx].options || ['Option 1', 'Option 2'];
                    modalVideoQuestions[qIdx].answer = 0;
                }
                renderModalVideoQuestions();
            });

            $(document).on('input', '.lms-modal-video-question-text', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                modalVideoQuestions[qIdx].question = $(this).val();
                serializeModalVideoData();
            });

            $(document).on('input', '.lms-modal-video-question-time', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                modalVideoQuestions[qIdx].time = timeToSeconds($(this).val());
                serializeModalVideoData();
            });

            $(document).on('input', '.lms-modal-video-opt-text', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                var optIdx = $(this).closest('div').find('.lms-modal-btn-remove-video-option').data('opt-idx');
                modalVideoQuestions[qIdx].options[optIdx] = $(this).val();
                serializeModalVideoData();
            });

            $(document).on('change', '.lms-modal-video-opt-correct-radio', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                var optIdx = $(this).data('opt-idx');
                modalVideoQuestions[qIdx].answer = optIdx;
                serializeModalVideoData();
            });

            $(document).on('change', '.lms-modal-video-opt-correct-checkbox', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                var optIdx = $(this).data('opt-idx');
                var ans = Array.isArray(modalVideoQuestions[qIdx].answer) ? modalVideoQuestions[qIdx].answer : [];
                var pos = ans.indexOf(optIdx);
                if (this.checked) {
                    if (pos === -1) ans.push(optIdx);
                } else {
                    if (pos !== -1) ans.splice(pos, 1);
                }
                modalVideoQuestions[qIdx].answer = ans;
                serializeModalVideoData();
            });

            $(document).on('change', '.lms-modal-video-tf-answer', function() {
                var qIdx = $(this).closest('.lms-video-question-card').data('index');
                modalVideoQuestions[qIdx].answer = ($(this).val() === 'true');
                serializeModalVideoData();
            });

            // Quiz Builder Logic for course editor modal
            var modalQuestions = [];
            function renderModalQuestions() {
                var $dataInput = $('#lms-edit-quiz-questions-data');
                try {
                    modalQuestions = JSON.parse($dataInput.val() || '[]');
                } catch(e) {
                    console.error("Failed to parse modal quiz questions JSON", e);
                    modalQuestions = [];
                }

                var $container = $('#lms-modal-quiz-questions-list');
                $container.empty();

                if (modalQuestions.length === 0) {
                    $container.append('<p style="color: #646970; font-style: italic; padding: 20px; text-align: center; border: 1px dashed #ccd0d4; background: #f6f7f7; border-radius: 4px;">No questions added yet. Click \'Add Question\' to get started.</p>');
                    return;
                }

                modalQuestions.forEach(function(q, qIdx) {
                    var html = '';
                    html += '<div class="lms-quiz-question-card" data-index="' + qIdx + '" style="border: 1px solid #ccd0d4; background: #fff; border-radius: 4px; padding: 16px; position: relative; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 12px;">';
                    
                    // Header with remove button
                    html += '  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">';
                    html += '    <h4 style="margin: 0; font-size: 14px; font-weight: 700;">Question #' + (qIdx + 1) + '</h4>';
                    html += '    <button type="button" class="lms-modal-btn-remove-question" style="background: none; border: none; color: #b32d2d; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600;" data-index="' + qIdx + '"><span class="dashicons dashicons-trash"></span> Remove</button>';
                    html += '  </div>';

                    // Question Text & Type Selector
                    html += '  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 12px;">';
                    html += '    <div>';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Question Title</label>';
                    html += '      <input type="text" class="lms-modal-question-text" value="' + (q.question || '') + '" style="width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px;" placeholder="What is...">';
                    html += '    </div>';
                    html += '    <div>';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Type</label>';
                    html += '      <select class="lms-modal-question-type" style="width: 100%; height: 38px; border: 1px solid #8c8f94; border-radius: 4px;">';
                    html += '        <option value="single" ' + (q.type === 'single' ? 'selected' : '') + '>Single choice</option>';
                    html += '        <option value="multi" ' + (q.type === 'multi' ? 'selected' : '') + '>Multiple choice</option>';
                    html += '        <option value="truefalse" ' + (q.type === 'truefalse' ? 'selected' : '') + '>True-False</option>';
                    html += '        <option value="matching" ' + (q.type === 'matching' ? 'selected' : '') + '>Matching</option>';
                    html += '        <option value="image_matching" ' + (q.type === 'image_matching' ? 'selected' : '') + '>Image matching</option>';
                    html += '        <option value="keywords" ' + (q.type === 'keywords' ? 'selected' : '') + '>Keywords</option>';
                    html += '        <option value="fill_gap" ' + (q.type === 'fill_gap' ? 'selected' : '') + '>Fill in the gap</option>';
                    html += '        <option value="ordering" ' + (q.type === 'ordering' ? 'selected' : '') + '>Ordering</option>';
                    html += '      </select>';
                    html += '    </div>';
                    html += '  </div>';

                    // Options list
                    if (q.type === 'truefalse') {
                        html += '  <div style="background: #f6f7f7; padding: 12px; border-radius: 4px; border: 1px solid #e5e5e5;">';
                        html += '    <label style="font-weight: 600; font-size: 12px; display: block; margin-bottom: 8px;">Correct Answer</label>';
                        html += '    <div style="display: flex; gap: 20px;">';
                        html += '      <label><input type="radio" name="modal_tf_' + qIdx + '" class="lms-modal-tf-answer" value="true" ' + (q.answer === 'true' || q.answer === true ? 'checked' : '') + '> True</label>';
                        html += '      <label><input type="radio" name="modal_tf_' + qIdx + '" class="lms-modal-tf-answer" value="false" ' + (q.answer === 'false' || q.answer === false ? 'checked' : '') + '> False</label>';
                        html += '    </div>';
                        html += '  </div>';
                    } else {
                        html += '  <div style="background: #f6f7f7; padding: 12px; border-radius: 4px; border: 1px solid #e5e5e5;">';
                        html += '    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
                        html += '      <label style="font-weight: 600; font-size: 12px;">Answer Options</label>';
                        html += '      <button type="button" class="lms-modal-btn-add-option button button-small" data-index="' + qIdx + '">Add Option</button>';
                        html += '    </div>';
                        html += '    <div class="lms-modal-options-list" style="display: flex; flex-direction: column; gap: 8px;">';

                        var options = q.options || [];
                        if (options.length === 0) {
                            options = ["Option 1", "Option 2"];
                            q.options = options;
                            q.answer = (q.type === 'single' ? 0 : [0]);
                        }

                        options.forEach(function(opt, optIdx) {
                            html += '      <div style="display: flex; align-items: center; gap: 10px;">';
                            
                            if (q.type === 'single') {
                                html += '        <input type="radio" name="modal_ans_' + qIdx + '" class="lms-modal-opt-correct-radio" data-opt-idx="' + optIdx + '" ' + (parseInt(q.answer) === optIdx ? 'checked' : '') + '>';
                            } else {
                                var isChecked = Array.isArray(q.answer) ? q.answer.indexOf(optIdx) !== -1 : false;
                                html += '        <input type="checkbox" class="lms-modal-opt-correct-checkbox" data-opt-idx="' + optIdx + '" ' + (isChecked ? 'checked' : '') + '>';
                            }

                            html += '        <input type="text" class="lms-modal-opt-text" style="flex: 1; padding: 6px; border: 1px solid #8c8f94; border-radius: 4px;" value="' + (opt || '') + '" placeholder="Enter option text">';
                            html += '        <button type="button" class="lms-modal-btn-remove-option" style="background:none; border:none; color:#b32d2d; cursor:pointer;" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '"><span class="dashicons dashicons-no-alt"></span></button>';
                            html += '      </div>';
                        });

                        html += '    </div>';
                        html += '  </div>';
                    }

                    html += '</div>';
                    $container.append(html);
                });

                serializeModalData();
            }

            function serializeModalData() {
                var $dataInput = $('#lms-edit-quiz-questions-data');
                $dataInput.val(JSON.stringify(modalQuestions));
            }

            // Bind modal quiz events
            $(document).on('click', '#lms-modal-btn-add-question', function() {
                modalQuestions.push({
                    question: '',
                    type: 'single',
                    options: ['Option 1', 'Option 2'],
                    answer: 0
                });
                renderModalQuestions();
            });

            $(document).on('click', '.lms-modal-btn-remove-question', function() {
                var idx = $(this).data('index');
                modalQuestions.splice(idx, 1);
                renderModalQuestions();
            });

            $(document).on('click', '.lms-modal-btn-add-option', function() {
                var qIdx = $(this).data('index');
                modalQuestions[qIdx].options.push('New Option');
                if (modalQuestions[qIdx].type === 'multi' && !Array.isArray(modalQuestions[qIdx].answer)) {
                    modalQuestions[qIdx].answer = [];
                }
                renderModalQuestions();
            });

            $(document).on('click', '.lms-modal-btn-remove-option', function() {
                var qIdx = $(this).data('q-idx');
                var optIdx = $(this).data('opt-idx');
                modalQuestions[qIdx].options.splice(optIdx, 1);
                if (modalQuestions[qIdx].type === 'single') {
                    if (parseInt(modalQuestions[qIdx].answer) === optIdx) {
                        modalQuestions[qIdx].answer = 0;
                    } else if (parseInt(modalQuestions[qIdx].answer) > optIdx) {
                        modalQuestions[qIdx].answer = parseInt(modalQuestions[qIdx].answer) - 1;
                    }
                } else if (modalQuestions[qIdx].type === 'multi') {
                    var ans = Array.isArray(modalQuestions[qIdx].answer) ? modalQuestions[qIdx].answer : [];
                    var newAns = [];
                    ans.forEach(function(val) {
                        if (val < optIdx) {
                            newAns.push(val);
                        } else if (val > optIdx) {
                            newAns.push(val - 1);
                        }
                    });
                    modalQuestions[qIdx].answer = newAns;
                }
                renderModalQuestions();
            });

            $(document).on('change', '.lms-modal-question-type', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var type = $(this).val();
                modalQuestions[qIdx].type = type;
                if (type === 'truefalse') {
                    modalQuestions[qIdx].answer = true;
                    delete modalQuestions[qIdx].options;
                } else if (type === 'multi') {
                    modalQuestions[qIdx].options = modalQuestions[qIdx].options || ['Option 1', 'Option 2'];
                    modalQuestions[qIdx].answer = [0];
                } else {
                    modalQuestions[qIdx].options = modalQuestions[qIdx].options || ['Option 1', 'Option 2'];
                    modalQuestions[qIdx].answer = 0;
                }
                renderModalQuestions();
            });

            $(document).on('input', '.lms-modal-question-text', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                modalQuestions[qIdx].question = $(this).val();
                serializeModalData();
            });

            $(document).on('input', '.lms-modal-opt-text', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var optIdx = $(this).closest('div').find('.lms-modal-btn-remove-option').data('opt-idx');
                modalQuestions[qIdx].options[optIdx] = $(this).val();
                serializeModalData();
            });

            $(document).on('change', '.lms-modal-opt-correct-radio', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var optIdx = $(this).data('opt-idx');
                modalQuestions[qIdx].answer = optIdx;
                serializeModalData();
            });

            $(document).on('change', '.lms-modal-opt-correct-checkbox', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var optIdx = $(this).data('opt-idx');
                var ans = Array.isArray(modalQuestions[qIdx].answer) ? modalQuestions[qIdx].answer : [];
                var pos = ans.indexOf(optIdx);
                if (this.checked) {
                    if (pos === -1) ans.push(optIdx);
                } else {
                    if (pos !== -1) ans.splice(pos, 1);
                }
                modalQuestions[qIdx].answer = ans;
                serializeModalData();
            });

            $(document).on('change', '.lms-modal-tf-answer', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                modalQuestions[qIdx].answer = ($(this).val() === 'true');
                serializeModalData();
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

    $lesson_type = get_post_meta( $post->ID, '_lesson_type', true );
    if ( empty( $lesson_type ) && isset( $_GET['lesson_type'] ) ) {
        $lesson_type = sanitize_text_field( $_GET['lesson_type'] );
    }
    $is_quiz = ( $lesson_type === 'quiz' );

    if ( ! $is_quiz ) {
        reandaily_lms_render_lesson_builder_html( $post );
    }
}

// Hook Quiz Builder into footer so it is immune to Screen Options toggles
add_action( 'admin_footer', 'reandaily_lms_render_quiz_builder_in_footer' );
function reandaily_lms_render_quiz_builder_in_footer() {
    global $pagenow, $post;
    if ( ! is_admin() || ( $pagenow !== 'post-new.php' && $pagenow !== 'post.php' ) ) {
        return;
    }
    if ( ! $post || get_post_type( $post ) !== 'lessons' ) {
        return;
    }

    $lesson_type = get_post_meta( $post->ID, '_lesson_type', true );
    if ( empty( $lesson_type ) && isset( $_GET['lesson_type'] ) ) {
        $lesson_type = sanitize_text_field( $_GET['lesson_type'] );
    }
    $is_quiz = ( $lesson_type === 'quiz' );

    if ( $is_quiz ) {
        reandaily_lms_render_quiz_builder_html( $post );
        ?>
        <style>
            #poststuff { display: none !important; }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Append hidden inputs to the main form so they are submitted properly
                $('<input>').attr({
                    type: 'hidden',
                    name: 'lms_lesson_type',
                    value: 'quiz'
                }).appendTo('#post');
                $('#lms_quiz_questions_data').appendTo('#post');
            });
        </script>
        <?php
    }
}

// 1. RENDER LESSON BUILDER
function reandaily_lms_render_lesson_builder_html( $post ) {
    $video_url = get_post_meta( $post->ID, '_video_url', true );
    $duration = get_post_meta( $post->ID, '_duration', true );
    $is_preview = get_post_meta( $post->ID, '_is_preview', true );
    $lesson_content = get_post_field( 'post_content', $post->ID );
    ?>
    <style>
        #poststuff { max-width: 100% !important; }
        #post-body.columns-2 { margin-right: 0 !important; }
        #postbox-container-1 { float: none !important; width: 100% !important; display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        #postbox-container-1 .postbox { flex: 1; min-width: 280px; margin-bottom: 0 !important; }
        #postbox-container-2 { width: 100% !important; float: none !important; }
        .lms-builder-container { display: flex; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 10px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .lms-builder-tabs { width: 220px; background: #f6f7f7; border-right: 1px solid #ccd0d4; display: flex; flex-direction: column; }
        .lms-builder-tab-btn { padding: 16px 20px; font-size: 14px; font-weight: 600; color: #444; border: none; background: transparent; text-align: left; cursor: pointer; border-bottom: 1px solid #e5e5e5; transition: all 0.15s ease; display: flex; align-items: center; gap: 10px; outline: none; }
        .lms-builder-tab-btn:hover { background: #f0f0f1; color: #2271b1; }
        .lms-builder-tab-btn.active { background: #fff; color: #2271b1; border-left: 4px solid #2271b1; padding-left: 16px; }
        .lms-builder-tab-btn .dashicons { color: #646970; }
        .lms-builder-tab-btn.active .dashicons { color: #2271b1; }
        .lms-builder-panels { flex: 1; padding: 28px; min-height: 450px; background: #fff; }
        .lms-builder-panel { display: none; }
        .lms-builder-panel.active { display: block; }
        .lms-builder-row { margin-bottom: 24px; }
        .lms-builder-row label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13.5px; color: #1d2327; }
        .lms-builder-row input[type="text"] { width: 100%; max-width: 500px; padding: 10px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }
    </style>
    <div class="lms-builder-container">
        <div class="lms-builder-tabs">
            <button type="button" class="lms-builder-tab-btn active" data-target="lesson-content">
                <span class="dashicons dashicons-editor-paragraph"></span> <?php _e( 'Lesson Content', 'reandaily-lms-theme' ); ?>
            </button>
            <button type="button" class="lms-builder-tab-btn" data-target="lesson-settings">
                <span class="dashicons dashicons-admin-generic"></span> <?php _e( 'Video & Rules', 'reandaily-lms-theme' ); ?>
            </button>
        </div>
        <div class="lms-builder-panels">
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
            <div id="lesson-settings" class="lms-builder-panel">
                <h3><?php _e( 'Lesson Media & Preview Settings', 'reandaily-lms-theme' ); ?></h3>
                <hr style="border: 0; border-top: 1px solid #dcdcde; margin: 16px 0 24px 0;">
                <div class="lms-builder-row">
                    <label for="lms_video_url"><?php _e( 'Video URL', 'reandaily-lms-theme' ); ?></label>
                    <input type="text" id="lms_video_url" name="lms_video_url" value="<?php echo esc_url( $video_url ); ?>" placeholder="e.g. YouTube, Vimeo or MP4 file link">
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

function reandaily_lms_render_quiz_builder_html( $post ) {
    $categories = get_terms( array(
        'taxonomy'   => 'question_category',
        'hide_empty' => false,
    ) );
    $quiz_questions = get_post_meta( $post->ID, '_quiz_questions', true ) ?: '[]';
    $quiz_time_limit = get_post_meta( $post->ID, '_quiz_time_limit', true ) ?: '0';
    $quiz_passing_grade = get_post_meta( $post->ID, '_quiz_passing_grade', true ) ?: '70';
    $quiz_retakes = get_post_meta( $post->ID, '_quiz_retakes', true ) ?: '0';
    ?>
    <style>
        /* Hide WordPress sidebar and admin bar on Quiz builder page */
        #wpadminbar,
        #adminmenuback,
        #adminmenuwrap {
            display: none !important;
        }
        html, body {
            margin-top: 0 !important;
            padding-top: 0 !important;
            overflow: hidden !important;
        }
        #wpcontent {
            margin-left: 0 !important;
            padding-top: 0 !important;
        }
        .lms-quiz-fullscreen-editor {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #f1f5f9;
            z-index: 99999;
            overflow-y: auto;
            scrollbar-gutter: stable;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            box-sizing: border-box;
            padding-bottom: 80px;
        }

        /* Header styling */
        .lms-quiz-header {
            height: 64px;
            background: #0f172a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .lms-quiz-header-left { display: flex; align-items: center; gap: 16px; flex: 1; }
        .lms-quiz-back-btn {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            padding-right: 18px;
            border-right: 1px solid #334155;
            transition: color 0.2s;
        }
        .lms-quiz-back-btn:hover { color: #fff; }
        .lms-quiz-badge {
            background: #334155;
            color: #f8fafc;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .lms-quiz-title-input {
            background: #1e293b;
            border: 1px solid #334155;
            color: #fff;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 16px;
            font-weight: 600;
            width: 320px;
            outline: none;
            transition: border-color 0.2s;
        }
        .lms-quiz-title-input:focus { border-color: #3b82f6; }

        .lms-quiz-header-right { display: flex; align-items: center; gap: 12px; }
        .lms-quiz-shortcode-badge {
            background: #1e293b;
            border: 1px solid #334155;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lms-quiz-shortcode-copy-btn { color: #94a3b8; cursor: pointer; }
        .lms-quiz-shortcode-copy-btn:hover { color: #fff; }
        .lms-quiz-add-btn {
            background: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .lms-quiz-add-btn:hover { background: #1e293b; color: #fff; }
        .lms-quiz-save-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .lms-quiz-save-btn:hover { background: #1d4ed8; }

        /* Container & Tabs */
        .lms-quiz-content-wrapper { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .lms-quiz-tabs-bar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .lms-quiz-tabs-list { display: flex; gap: 8px; }
        .lms-quiz-tab-item {
            background: transparent;
            border: none;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lms-quiz-tab-item.active { background: #f1f5f9; color: #0f172a; }
        .lms-quiz-tab-count {
            background: #94a3b8;
            color: #fff;
            border-radius: 9999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .lms-quiz-tab-item.active .lms-quiz-tab-count { background: #475569; }

        .lms-quiz-tabs-actions { display: flex; align-items: center; gap: 12px; }
        .lms-quiz-sort-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
            width: 38px;
            height: 38px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        .lms-quiz-sort-btn:hover { background: #f1f5f9; }
        .lms-quiz-library-btn {
            background: transparent;
            color: #3b82f6;
            border: 1px solid #3b82f6;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .lms-quiz-library-btn:hover { background: #3b82f6; color: #fff; }

        /* Panels */
        .lms-quiz-panel { display: none; }
        .lms-quiz-panel.active { display: block; }

        /* Questions List */
        .lms-quiz-question-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: border-color 0.2s;
        }
        .lms-quiz-question-card:hover { border-color: #cbd5e1; }
        .lms-quiz-question-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        .lms-quiz-question-title-text { font-size: 14.5px; font-weight: 600; color: #1e293b; flex: 1; }
        .lms-quiz-question-badge {
            background: #64748b;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .lms-quiz-question-body {
            display: none;
            padding: 20px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .lms-quiz-question-card.expanded .lms-quiz-question-body { display: block; }

        /* Dashed Box */
        .lms-quiz-dashed-box {
            border: 2px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            position: relative;
        }
        .lms-quiz-add-dropdown { position: relative; display: inline-block; }
        .lms-quiz-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background-color: #fff;
            min-width: 220px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            z-index: 10;
            margin-top: 8px;
            text-align: left;
            padding: 6px 0;
        }
        .lms-quiz-dropdown-content::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 0;
            right: 0;
            height: 12px;
            background: transparent;
        }
        .lms-quiz-dropdown-content a {
            color: #1e293b;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            font-size: 14.5px;
            font-weight: 500;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            transition: all 0.15s ease;
        }
        .lms-quiz-dropdown-content a:hover {
            background-color: #f0f5fa;
            color: #0f172a;
        }
        .lms-quiz-add-dropdown:hover .lms-quiz-dropdown-content { display: block; }

        /* Settings card */
        .lms-quiz-settings-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
    </style>

    <div class="lms-quiz-fullscreen-editor">
        <!-- HEADER BAR -->
        <div class="lms-quiz-header">
            <div class="lms-quiz-header-left">
                <a href="edit.php?post_type=lessons&lesson_type=quiz" class="lms-quiz-back-btn">
                    <span class="dashicons dashicons-arrow-left-alt"></span> <?php _e( 'Back to quizzes', 'reandaily-lms-theme' ); ?>
                </a>
                <div class="lms-quiz-badge">
                    <span class="dashicons dashicons-editor-help" style="font-size: 17px; margin-top:2px;"></span> <?php _e( 'Quiz', 'reandaily-lms-theme' ); ?>
                </div>
                <input type="text" id="lms-quiz-header-title-input" class="lms-quiz-title-input" value="<?php echo esc_attr( $post->post_title ); ?>" placeholder="<?php esc_attr_e( 'Quiz Title...', 'reandaily-lms-theme' ); ?>">
            </div>

            <div class="lms-quiz-header-right">
                <div class="lms-quiz-shortcode-badge">
                    <span>[stm_lms_quiz_online id=<?php echo $post->ID; ?>]</span>
                    <span class="dashicons dashicons-admin-page lms-quiz-shortcode-copy-btn" title="<?php esc_attr_e( 'Copy shortcode', 'reandaily-lms-theme' ); ?>"></span>
                </div>
                <button type="button" class="lms-quiz-add-btn" onclick="alert('Added to course map!');"><?php _e( 'Add to course', 'reandaily-lms-theme' ); ?></button>
                <button type="button" class="lms-quiz-save-btn" id="lms-quiz-btn-header-save"><?php _e( 'Save', 'reandaily-lms-theme' ); ?></button>
            </div>
        </div>

        <!-- MAIN WRAPPER -->
        <div class="lms-quiz-content-wrapper">
            <!-- TABS CONTROLLER -->
            <div class="lms-quiz-tabs-bar">
                <div class="lms-quiz-tabs-list">
                    <button type="button" class="lms-quiz-tab-item active" data-target="lms-quiz-panel-questions">
                        <?php _e( 'Questions', 'reandaily-lms-theme' ); ?> <span class="lms-quiz-tab-count" id="lms-quiz-count-bubble">0</span>
                    </button>
                    <button type="button" class="lms-quiz-tab-item" data-target="lms-quiz-panel-settings">
                        <?php _e( 'Settings', 'reandaily-lms-theme' ); ?>
                    </button>
                    <button type="button" class="lms-quiz-tab-item" data-target="lms-quiz-panel-qa">
                        <?php _e( 'Q&A', 'reandaily-lms-theme' ); ?>
                    </button>
                </div>
                <div class="lms-quiz-tabs-actions">
                    <button type="button" class="lms-quiz-sort-btn" title="<?php esc_attr_e( 'Sort Order', 'reandaily-lms-theme' ); ?>">
                        <span class="dashicons dashicons-editor-justify"></span>
                    </button>
                    <button type="button" class="lms-quiz-library-btn" id="lms-quiz-btn-open-library"><?php _e( 'Questions library', 'reandaily-lms-theme' ); ?></button>
                </div>
            </div>

            <!-- HIDDEN FIELDS FOR NATIVE SUBMIT -->
            <input type="hidden" name="post_title" id="lms-quiz-hidden-title" value="<?php echo esc_attr( $post->post_title ); ?>">
            <input type="hidden" name="lms_quiz_questions" id="lms_quiz_questions_data" value="<?php echo esc_attr( is_string($quiz_questions) ? $quiz_questions : json_encode($quiz_questions) ); ?>">

            <!-- QUESTIONS PANEL -->
            <div id="lms-quiz-panel-questions" class="lms-quiz-panel active">
                <div id="lms-quiz-questions-list-wrapper">
                    <!-- Dynamic rendering -->
                </div>

                <div class="lms-quiz-dashed-box">
                    <div class="lms-quiz-add-dropdown">
                        <button type="button" class="button button-primary button-large" style="background:#2563eb; border-color:#2563eb; font-weight:600; display:inline-flex !important; align-items:center !important; justify-content:center !important; gap:8px; border-radius: 6px; padding: 0 20px; height: 40px; font-size:14px; outline:none; line-height: 1 !important; vertical-align: middle;">
                            <span class="dashicons dashicons-plus" style="font-size:16px !important; width:16px !important; height:16px !important; line-height:1 !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; margin:0 !important; vertical-align: middle !important;"></span>
                            <span style="display:inline-flex; align-items:center; line-height:1; height:100%;"><?php _e( 'Question', 'reandaily-lms-theme' ); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2" style="font-size:12px !important; width:12px !important; height:12px !important; line-height:1 !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; margin:0 !important; vertical-align: middle !important;"></span>
                        </button>
                        <div class="lms-quiz-dropdown-content">
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="single"><?php _e( 'Single choice', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="multi"><?php _e( 'Multiple choice', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="truefalse"><?php _e( 'True-False', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="matching"><?php _e( 'Matching', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="image_matching"><?php _e( 'Image matching', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="keywords"><?php _e( 'Keywords', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="fill_gap"><?php _e( 'Fill in the gap', 'reandaily-lms-theme' ); ?></a>
                            <a href="#" class="lms-quiz-add-q-type" data-qtype="ordering"><?php _e( 'Ordering', 'reandaily-lms-theme' ); ?></a>
                        </div>
                    </div>
                    <button type="button" class="button button-large" style="background:#10b981; color:#fff; border-color:#10b981; font-weight:600; border-radius:6px; height:40px;" onclick="alert('Question bank has no entries. Create quiz questions first!');">
                        <?php _e( '+ Question Bank', 'reandaily-lms-theme' ); ?>
                    </button>

                    <button type="button" class="lms-quiz-save-btn" id="lms-quiz-btn-bottom-save" style="position: absolute; right: 0; bottom:-60px;"><?php _e( 'Save', 'reandaily-lms-theme' ); ?></button>
                </div>
            </div>

            <!-- SETTINGS PANEL -->
            <div id="lms-quiz-panel-settings" class="lms-quiz-panel">
                <div class="lms-quiz-settings-card">
                    <h3 style="margin-top:0; margin-bottom: 24px; font-weight: 700; color: #0f172a;"><?php _e( 'Quiz Settings Config', 'reandaily-lms-theme' ); ?></h3>
                    
                    <div style="margin-bottom: 24px;">
                        <label for="lms_quiz_time_limit" style="display:block; font-weight: 600; margin-bottom: 8px; font-size: 13.5px;"><?php _e( 'Time Limit (Minutes)', 'reandaily-lms-theme' ); ?></label>
                        <input type="number" id="lms_quiz_time_limit" name="lms_quiz_time_limit" value="<?php echo esc_attr( $quiz_time_limit ); ?>" min="0" style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <p style="color:#64748b; font-size:12.5px; margin-top:4px;"><?php _e( '0 means no time limit.', 'reandaily-lms-theme' ); ?></p>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label for="lms_quiz_passing_grade" style="display:block; font-weight: 600; margin-bottom: 8px; font-size: 13.5px;"><?php _e( 'Passing Grade (%)', 'reandaily-lms-theme' ); ?></label>
                        <input type="number" id="lms_quiz_passing_grade" name="lms_quiz_passing_grade" value="<?php echo esc_attr( $quiz_passing_grade ); ?>" min="0" max="100" style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label for="lms_quiz_retakes" style="display:block; font-weight: 600; margin-bottom: 8px; font-size: 13.5px;"><?php _e( 'Allowed Retakes', 'reandaily-lms-theme' ); ?></label>
                        <input type="number" id="lms_quiz_retakes" name="lms_quiz_retakes" value="<?php echo esc_attr( $quiz_retakes ); ?>" min="0" style="width: 100%; max-width: 400px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <p style="color:#64748b; font-size:12.5px; margin-top:4px;"><?php _e( '0 means unlimited retakes.', 'reandaily-lms-theme' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Q&A PANEL -->
            <div id="lms-quiz-panel-qa" class="lms-quiz-panel">
                <div class="lms-quiz-settings-card">
                    <h3 style="margin-top:0; margin-bottom: 24px; font-weight: 700; color: #0f172a;"><?php _e( 'Student Questions & Answers', 'reandaily-lms-theme' ); ?></h3>
                    <p style="color:#64748b; font-size:14px; font-style:italic; text-align:center; padding: 40px 0; border: 1px dashed #cbd5e1; border-radius:8px; background:#f8fafc;">
                        <?php _e( 'No student questions on this quiz yet.', 'reandaily-lms-theme' ); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- QUESTIONS LIBRARY MODAL -->
        <div id="lms-quiz-library-modal" class="lms-quiz-modal-wrapper" style="display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(15,23,42,0.6); z-index: 999999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
            <div class="lms-quiz-modal-content" style="background:#fff; width: 100%; max-width: 700px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); display:flex; flex-direction:column; max-height: 80vh; overflow:hidden;">
                <!-- Modal Header -->
                <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="margin:0; font-weight:700; font-size:18px; color:#0f172a;"><?php _e( 'Questions Library', 'reandaily-lms-theme' ); ?></h3>
                    <button type="button" id="lms-quiz-close-library-modal" style="background:none; border:none; font-size:24px; color:#94a3b8; cursor:pointer; line-height:1; font-weight:bold;">&times;</button>
                </div>
                
                <!-- Modal Filter Bar -->
                <div style="padding: 16px 24px; background:#f8fafc; border-bottom: 1px solid #e2e8f0; display:flex; gap:12px;">
                    <input type="text" id="lms-library-search" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13.5px;" placeholder="<?php esc_attr_e( 'Search questions...', 'reandaily-lms-theme' ); ?>">
                    <select id="lms-library-filter-category" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13.5px;">
                        <option value=""><?php _e( 'All Categories', 'reandaily-lms-theme' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="lms-library-filter-type" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13.5px;">
                        <option value=""><?php _e( 'All Types', 'reandaily-lms-theme' ); ?></option>
                        <option value="single"><?php _e( 'Single choice', 'reandaily-lms-theme' ); ?></option>
                        <option value="multi"><?php _e( 'Multiple choice', 'reandaily-lms-theme' ); ?></option>
                        <option value="truefalse"><?php _e( 'True-False', 'reandaily-lms-theme' ); ?></option>
                        <option value="matching"><?php _e( 'Matching', 'reandaily-lms-theme' ); ?></option>
                        <option value="image_matching"><?php _e( 'Image matching', 'reandaily-lms-theme' ); ?></option>
                        <option value="keywords"><?php _e( 'Keywords', 'reandaily-lms-theme' ); ?></option>
                        <option value="fill_gap"><?php _e( 'Fill in the gap', 'reandaily-lms-theme' ); ?></option>
                        <option value="ordering"><?php _e( 'Ordering', 'reandaily-lms-theme' ); ?></option>
                    </select>
                </div>
                
                <!-- Modal Body (List) -->
                <div id="lms-library-questions-list" style="flex:1; padding: 24px; overflow-y:auto; display:flex; flex-direction:column; gap:12px;">
                    <!-- dynamic rows -->
                </div>
                
                <!-- Modal Footer -->
                <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="button button-large" id="lms-quiz-cancel-library"><?php _e( 'Cancel', 'reandaily-lms-theme' ); ?></button>
                    <button type="button" class="button button-primary button-large" id="lms-quiz-import-library" style="background:#2563eb; border-color:#2563eb; font-weight:600;"><?php _e( 'Import Selected', 'reandaily-lms-theme' ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            var catOptionsHtml = '<option value="">(Select Category)</option>';
            <?php foreach ( $categories as $cat ) : ?>
                catOptionsHtml += '<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_js( $cat->name ); ?></option>';
            <?php endforeach; ?>

            // Shortcode copy logic
            $('.lms-quiz-shortcode-copy-btn').on('click', function() {
                var text = $(this).siblings('span').text();
                navigator.clipboard.writeText(text);
                var $btn = $(this);
                $btn.removeClass('dashicons-admin-page').addClass('dashicons-yes');
                setTimeout(function() {
                    $btn.removeClass('dashicons-yes').addClass('dashicons-admin-page');
                }, 2000);
            });

            // Title binding
            $('#lms-quiz-header-title-input').on('input', function() {
                var val = $(this).val();
                $('#lms-quiz-hidden-title').val(val);
                // Also update WordPress native page header if visible
                $('#title-prompt-text').addClass('screen-reader-text');
                $('input[name="post_title"]').val(val);
            });

            // Save triggers
            $('#lms-quiz-btn-header-save, #lms-quiz-btn-bottom-save').on('click', function(e) {
                e.preventDefault();
                // trigger native publishing save
                if ($('#publish').length) {
                    $('#publish').click();
                } else if ($('#save-post').length) {
                    $('#save-post').click();
                } else {
                    $('#post').submit();
                }
            });

            // Tab toggles
            $('.lms-quiz-tab-item').on('click', function() {
                var target = $(this).data('target');
                $('.lms-quiz-tab-item').removeClass('active');
                $('.lms-quiz-panel').removeClass('active');

                $(this).addClass('active');
                $('#' + target).addClass('active');
            });

            // Quiz Builder Logic
            var $dataInput = $('#lms_quiz_questions_data');
            var questions = [];
            try {
                questions = JSON.parse($dataInput.val() || '[]');
            } catch(e) {
                console.error("Failed to parse quiz questions JSON", e);
                questions = [];
            }

            function getBadgeName(type) {
                if (type === 'single') return 'SINGLE CHOICE';
                if (type === 'multi') return 'MULTIPLE CHOICE';
                if (type === 'truefalse') return 'TRUE-FALSE';
                if (type === 'matching') return 'MATCHING';
                if (type === 'image_matching') return 'IMAGE MATCHING';
                if (type === 'keywords') return 'KEYWORDS';
                if (type === 'fill_gap') return 'FILL IN THE GAP';
                if (type === 'ordering') return 'ORDERING';
                return 'QUESTION';
            }

            function renderQuestionsList() {
                var $container = $('#lms-quiz-questions-list-wrapper');
                $container.empty();

                // Update count bubble
                $('#lms-quiz-count-bubble').text(questions.length);

                if (questions.length === 0) {
                    $container.append('<p style="color: #64748b; font-style: italic; padding: 40px; text-align: center; border: 2px dashed #e2e8f0; background: #fff; border-radius: 8px;"><?php _e( "No questions added yet. Click \'+ Question\' dropdown to create your first question.", "reandaily-lms-theme" ); ?></p>');
                    return;
                }

                questions.forEach(function(q, qIdx) {
                    var html = '';
                    var displayTitle = q.question ? q.question : 'Question #' + (qIdx + 1);
                    
                    html += '<div class="lms-quiz-question-card" data-index="' + qIdx + '">';
                    html += '  <div class="lms-quiz-question-header">';
                    html += '    <span class="lms-quiz-question-title-text">' + displayTitle + '</span>';
                    html += '    <div style="display:flex; align-items:center; gap:12px;">';
                    html += '      <span class="lms-quiz-question-badge">' + getBadgeName(q.type) + '</span>';
                    html += '      <span class="dashicons dashicons-arrow-down-alt2" style="color:#64748b;"></span>';
                    html += '    </div>';
                    html += '  </div>';
                    
                    html += '  <div class="lms-quiz-question-body">';
                    
                    // Question title text field
                    html += '    <div style="margin-bottom:16px;">';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#334155;">Question Title</label>';
                    html += '      <input type="text" class="lms-question-text-input" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;" value="' + (q.question || '') + '" placeholder="e.g. What does the Internet prefix WWW stand for?">';
                    html += '    </div>';

                    // Question image attachment field
                    html += '    <div style="margin-bottom:16px;">';
                    html += '      <label style="display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#334155;">Question Image (Optional)</label>';
                    html += '      <div style="display:flex; align-items:center; gap:12px;">';
                    if (q.image) {
                        html += '        <img src="' + q.image + '" style="width:80px; height:80px; object-fit:cover; border-radius:6px; border:1px solid #cbd5e1;">';
                        html += '        <button type="button" class="button button-secondary lms-btn-remove-q-image" data-index="' + qIdx + '">Remove Image</button>';
                    } else {
                        html += '        <button type="button" class="button button-secondary lms-btn-select-q-image" data-index="' + qIdx + '">Select Image</button>';
                    }
                    html += '      </div>';
                    html += '    </div>';

                    // Options Rendering based on type
                    if (q.type === 'truefalse') {
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <label style="font-weight:600; font-size:13px; display:block; margin-bottom:10px; color:#334155;">Correct Answer</label>';
                        html += '      <div style="display:flex; gap:24px;">';
                        html += '        <label style="font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><input type="radio" name="tf_' + qIdx + '" class="lms-tf-answer-radio" value="true" ' + (q.answer === 'true' || q.answer === true ? 'checked' : '') + '> True</label>';
                        html += '        <label style="font-weight:500; font-size:14px; display:flex; align-items:center; gap:8px;"><input type="radio" name="tf_' + qIdx + '" class="lms-tf-answer-radio" value="false" ' + (q.answer === 'false' || q.answer === false ? 'checked' : '') + '> False</label>';
                        html += '      </div>';
                        html += '    </div>';

                    } else if (q.type === 'matching') {
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
                        html += '        <label style="font-weight:600; font-size:13px; color:#334155;">Matching Pairs (Left matches Right)</label>';
                        html += '        <button type="button" class="lms-btn-add-match-pair button button-small" data-index="' + qIdx + '">+ Match Pair</button>';
                        html += '      </div>';
                        html += '      <div class="lms-match-pairs-list" style="display:flex; flex-direction:column; gap:10px;">';
                        
                        var pairs = q.pairs || [];
                        if (pairs.length === 0) {
                            pairs = [{left: 'Item A', right: 'Match A'}];
                            q.pairs = pairs;
                        }
                        
                        pairs.forEach(function(pair, pIdx) {
                            html += '        <div style="display:flex; align-items:center; gap:12px;">';
                            html += '          <input type="text" class="lms-match-left" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (pair.left || '') + '" placeholder="Left Item" data-pair-idx="' + pIdx + '">';
                            html += '          <span class="dashicons dashicons-arrow-right-alt" style="color:#64748b; font-size:20px; width:20px;"></span>';
                            html += '          <input type="text" class="lms-match-right" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (pair.right || '') + '" placeholder="Right Item" data-pair-idx="' + pIdx + '">';
                            html += '          <button type="button" class="lms-btn-remove-match-pair" style="background:none; border:none; color:#ef4444; cursor:pointer;" data-q-idx="' + qIdx + '" data-pair-idx="' + pIdx + '"><span class="dashicons dashicons-trash" style="font-size:16px;"></span></button>';
                            html += '        </div>';
                        });
                        html += '      </div>';
                        html += '    </div>';

                    } else if (q.type === 'image_matching') {
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
                        html += '        <label style="font-weight:600; font-size:13px; color:#334155;">Image Matching Pairs (Left Text matches Right Image)</label>';
                        html += '        <button type="button" class="lms-btn-add-image-match-pair button button-small" data-index="' + qIdx + '">+ Match Pair</button>';
                        html += '      </div>';
                        html += '      <div class="lms-image-match-pairs-list" style="display:flex; flex-direction:column; gap:12px;">';
                        
                        var pairs = q.pairs || [];
                        if (pairs.length === 0) {
                            pairs = [{left: 'Item A', right: ''}];
                            q.pairs = pairs;
                        }
                        
                        pairs.forEach(function(pair, pIdx) {
                            html += '        <div style="display:flex; align-items:center; gap:16px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">';
                            html += '          <input type="text" class="lms-image-match-left" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (pair.left || '') + '" placeholder="Left Text Item" data-pair-idx="' + pIdx + '">';
                            html += '          <span class="dashicons dashicons-arrow-right-alt" style="color:#64748b; font-size:20px; width:20px;"></span>';
                            
                            html += '          <div style="flex:1; display:flex; align-items:center; gap:10px;">';
                            if (pair.right) {
                                html += '            <img src="' + pair.right + '" style="width:40px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #cbd5e1;">';
                                html += '            <button type="button" class="button button-secondary button-small lms-btn-clear-match-image" data-q-idx="' + qIdx + '" data-pair-idx="' + pIdx + '">Remove</button>';
                            } else {
                                html += '            <button type="button" class="button button-secondary button-small lms-btn-select-match-image" data-q-idx="' + qIdx + '" data-pair-idx="' + pIdx + '">Select Image</button>';
                            }
                            html += '          </div>';
                            
                            html += '          <button type="button" class="lms-btn-remove-image-match-pair" style="background:none; border:none; color:#ef4444; cursor:pointer;" data-q-idx="' + qIdx + '" data-pair-idx="' + pIdx + '"><span class="dashicons dashicons-trash" style="font-size:16px;"></span></button>';
                            html += '        </div>';
                        });
                        html += '      </div>';
                        html += '    </div>';

                    } else if (q.type === 'keywords') {
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <label style="font-weight:600; font-size:13px; color:#334155; display:block; margin-bottom:6px;">Acceptable Keywords (separated by commas)</label>';
                        html += '      <input type="text" class="lms-keywords-input" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (q.answer || '') + '" placeholder="e.g. HTML, CSS, JavaScript">';
                        html += '    </div>';

                    } else if (q.type === 'fill_gap') {
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <div style="margin-bottom:12px;">';
                        html += '        <label style="font-weight:600; font-size:13px; color:#334155; display:block; margin-bottom:6px;">Sentence with [gap]</label>';
                        html += '        <input type="text" class="lms-gap-sentence" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (q.gap_text || '') + '" placeholder="e.g. The capital of Cambodia is [gap].">';
                        html += '      </div>';
                        html += '      <div>';
                        html += '        <label style="font-weight:600; font-size:13px; color:#334155; display:block; margin-bottom:6px;">Correct Gap Word</label>';
                        html += '        <input type="text" class="lms-gap-word" style="width:100%; max-width:300px; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (q.answer || '') + '" placeholder="e.g. Phnom Penh">';
                        html += '      </div>';
                        html += '    </div>';

                    } else if (q.type === 'ordering') {
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
                        html += '        <label style="font-weight:600; font-size:13px; color:#334155;">Items in Correct Order (Top to Bottom)</label>';
                        html += '        <button type="button" class="lms-btn-add-order-item button button-small" data-index="' + qIdx + '">+ Add Item</button>';
                        html += '      </div>';
                        html += '      <div class="lms-order-items-list" style="display:flex; flex-direction:column; gap:10px;">';
                        
                        var options = q.options || [];
                        if (options.length === 0) {
                            options = ["First Item", "Second Item"];
                            q.options = options;
                        }
                        
                        options.forEach(function(opt, optIdx) {
                            html += '        <div style="display:flex; align-items:center; gap:12px;">';
                            html += '          <span style="font-weight:bold; color:#64748b; font-size:13px; width:20px;">' + (optIdx + 1) + '.</span>';
                            html += '          <input type="text" class="lms-order-item-text" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (opt || '') + '" placeholder="Enter item text" data-opt-idx="' + optIdx + '">';
                            html += '          <button type="button" class="lms-btn-remove-order-item" style="background:none; border:none; color:#ef4444; cursor:pointer;" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '"><span class="dashicons dashicons-trash" style="font-size:16px;"></span></button>';
                            html += '        </div>';
                        });
                        html += '      </div>';
                        html += '    </div>';

                    } else { // single or multi
                        html += '    <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:16px;">';
                        html += '      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">';
                        html += '        <label style="font-weight:600; font-size:13px; color:#334155;">Answer Options</label>';
                        html += '        <button type="button" class="lms-btn-add-option button button-small" data-index="' + qIdx + '">+ Add Option</button>';
                        html += '      </div>';
                        html += '      <div class="lms-options-list" style="display:flex; flex-direction:column; gap:10px;">';
                        
                        var options = q.options || [];
                        if (options.length === 0) {
                            options = ["Option A", "Option B"];
                            q.options = options;
                            q.answer = (q.type === 'single' ? 0 : [0]);
                        }
                        
                        options.forEach(function(opt, optIdx) {
                            var isImage = (typeof opt === 'object' && opt !== null && opt.is_image);
                            var optText = (typeof opt === 'object' && opt !== null) ? (opt.text || '') : opt;
                            var optImg = (typeof opt === 'object' && opt !== null) ? (opt.image || '') : '';

                            html += '        <div style="display:flex; align-items:center; gap:12px;">';
                            if (q.type === 'single') {
                                html += '          <input type="radio" name="ans_' + qIdx + '" class="lms-opt-correct-radio" data-opt-idx="' + optIdx + '" ' + (parseInt(q.answer) === optIdx ? 'checked' : '') + '>';
                            } else {
                                var isChecked = Array.isArray(q.answer) ? q.answer.indexOf(optIdx) !== -1 : false;
                                html += '          <input type="checkbox" class="lms-opt-correct-checkbox" data-opt-idx="' + optIdx + '" ' + (isChecked ? 'checked' : '') + '>';
                            }
                            
                            if (isImage) {
                                html += '          <div style="flex:1; display:flex; align-items:center; gap:10px;">';
                                if (optImg) {
                                    html += '            <img src="' + optImg + '" style="width:40px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #cbd5e1;">';
                                    html += '            <button type="button" class="button button-secondary button-small lms-btn-clear-opt-image" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '">Remove</button>';
                                } else {
                                    html += '            <button type="button" class="button button-secondary button-small lms-btn-select-opt-image" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '">Select Image</button>';
                                }
                                html += '          </div>';
                            } else {
                                html += '          <input type="text" class="lms-opt-text" style="flex:1; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" value="' + (optText || '') + '" placeholder="Enter option text">';
                            }

                            // Toggle button to switch type
                            html += '          <button type="button" class="button button-secondary button-small lms-btn-toggle-opt-type" style="padding: 0 8px; height: 28px; line-height: 26px;" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '" title="Toggle Text/Image">' + (isImage ? 'Use Text' : 'Use Image') + '</button>';
                            
                            html += '          <button type="button" class="lms-btn-remove-option" style="background:none; border:none; color:#ef4444; cursor:pointer;" data-q-idx="' + qIdx + '" data-opt-idx="' + optIdx + '"><span class="dashicons dashicons-trash" style="font-size:16px;"></span></button>';
                            html += '        </div>';
                        });
                        
                        html += '      </div>';
                        html += '    </div>';
                    }

                    // Delete & Save to Library buttons
                    html += '    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:16px; align-items:center;">';
                    html += '      <select class="lms-save-q-category-select" style="padding:4px 8px; border-radius:6px; border:1px solid #cbd5e1; font-size:12.5px; height: 32px; background:#fff; color:#334155; max-width: 160px;">' + catOptionsHtml + '</select>';
                    html += '      <button type="button" class="lms-btn-save-to-library button button-secondary" style="font-weight:600; border-radius:6px; height: 32px; display: inline-flex; align-items: center; gap: 4px;" data-index="' + qIdx + '"><span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px; margin: 0 !important; display: inline-flex; align-items: center; justify-content: center;"></span> Save to Bank</button>';
                    html += '      <button type="button" class="lms-btn-remove-question button button-link-delete" style="color:#ef4444; font-weight:600;" data-index="' + qIdx + '"><span class="dashicons dashicons-trash" style="margin-top:2px;"></span> Delete Question</button>';
                    html += '    </div>';

                    html += '  </div>'; // body
                    html += '</div>'; // card
                    $container.append(html);
                });

                serializeData();
            }

            function serializeData() {
                $dataInput.val(JSON.stringify(questions));
            }

            // Toggle Expand Card
            $(document).on('click', '.lms-quiz-question-header', function(e) {
                var $card = $(this).closest('.lms-quiz-question-card');
                var isExpanded = $card.hasClass('expanded');
                
                $('.lms-quiz-question-card').removeClass('expanded');
                if (!isExpanded) {
                    $card.addClass('expanded');
                }
            });

            // Add new question of type
            $('.lms-quiz-add-q-type').on('click', function(e) {
                e.preventDefault();
                var qtype = $(this).data('qtype');
                
                var newQ = {
                    question: '',
                    type: qtype
                };

                if (qtype === 'single') {
                    newQ.options = ['Option A', 'Option B'];
                    newQ.answer = 0;
                } else if (qtype === 'multi') {
                    newQ.options = ['Option A', 'Option B'];
                    newQ.answer = [0];
                } else if (qtype === 'truefalse') {
                    newQ.answer = true;
                } else if (qtype === 'matching') {
                    newQ.pairs = [{left: 'Item 1', right: 'Match 1'}];
                } else if (qtype === 'image_matching') {
                    newQ.pairs = [{left: 'Item 1', right: ''}];
                } else if (qtype === 'keywords') {
                    newQ.answer = '';
                } else if (qtype === 'fill_gap') {
                    newQ.gap_text = 'The capital of Cambodia is [gap].';
                    newQ.answer = 'Phnom Penh';
                } else if (qtype === 'ordering') {
                    newQ.options = ['Item A', 'Item B'];
                }

                questions.push(newQ);
                renderQuestionsList();
                
                // Expand the newly added card immediately
                $('.lms-quiz-question-card').removeClass('expanded');
                $('.lms-quiz-question-card').last().addClass('expanded');
                
                // Scroll to bottom of list
                $('.lms-quiz-fullscreen-editor').animate({
                    scrollTop: $('.lms-quiz-dashed-box').offset().top
                }, 400);
            });

            // Remove question card
            $(document).on('click', '.lms-btn-remove-question', function(e) {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete this question?')) {
                    var idx = $(this).data('index');
                    questions.splice(idx, 1);
                    renderQuestionsList();
                }
            });

            // Question title input update
            $(document).on('input', '.lms-question-text-input', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var val = $(this).val();
                questions[qIdx].question = val;
                $(this).closest('.lms-quiz-question-card').find('.lms-quiz-question-title-text').text(val ? val : 'Question #' + (qIdx + 1));
                serializeData();
            });

            // Option text update
            $(document).on('input', '.lms-opt-text', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var optIdx = $(this).closest('div').find('.lms-btn-remove-option').data('opt-idx');
                var val = $(this).val();
                if (typeof questions[qIdx].options[optIdx] === 'object' && questions[qIdx].options[optIdx] !== null) {
                    questions[qIdx].options[optIdx].text = val;
                } else {
                    questions[qIdx].options[optIdx] = val;
                }
                serializeData();
            });

            // Add Option button
            $(document).on('click', '.lms-btn-add-option', function() {
                var qIdx = $(this).data('index');
                questions[qIdx].options.push('New Option');
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Remove Option button
            $(document).on('click', '.lms-btn-remove-option', function() {
                var qIdx = $(this).data('q-idx');
                var optIdx = $(this).data('opt-idx');
                questions[qIdx].options.splice(optIdx, 1);
                
                // Sync answers
                if (questions[qIdx].type === 'single') {
                    questions[qIdx].answer = 0;
                } else if (questions[qIdx].type === 'multi') {
                    questions[qIdx].answer = [0];
                }
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Single choice correct radio change
            $(document).on('change', '.lms-opt-correct-radio', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var optIdx = $(this).data('opt-idx');
                questions[qIdx].answer = optIdx;
                serializeData();
            });

            // Multi choice correct checkbox change
            $(document).on('change', '.lms-opt-correct-checkbox', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var optIdx = $(this).data('opt-idx');
                var ans = Array.isArray(questions[qIdx].answer) ? questions[qIdx].answer : [];
                var pos = ans.indexOf(optIdx);
                if (this.checked) {
                    if (pos === -1) ans.push(optIdx);
                } else {
                    if (pos !== -1) ans.splice(pos, 1);
                }
                questions[qIdx].answer = ans;
                serializeData();
            });

            // True / False correct answer radio change
            $(document).on('change', '.lms-tf-answer-radio', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                questions[qIdx].answer = ($(this).val() === 'true');
                serializeData();
            });

            // Fill Gap sentence change
            $(document).on('input', '.lms-gap-sentence', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                questions[qIdx].gap_text = $(this).val();
                serializeData();
            });

            // Fill Gap word change
            $(document).on('input', '.lms-gap-word', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                questions[qIdx].answer = $(this).val();
                serializeData();
            });

            // Matching Pairs Add Button
            $(document).on('click', '.lms-btn-add-match-pair', function() {
                var qIdx = $(this).data('index');
                questions[qIdx].pairs.push({left: '', right: ''});
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Matching Pairs Remove Button
            $(document).on('click', '.lms-btn-remove-match-pair', function() {
                var qIdx = $(this).data( 'q-idx' );
                var pairIdx = $(this).data( 'pair-idx' );
                questions[qIdx].pairs.splice(pairIdx, 1);
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Matching Left input change
            $(document).on('input', '.lms-match-left', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var pairIdx = $(this).data('pair-idx');
                questions[qIdx].pairs[pairIdx].left = $(this).val();
                serializeData();
            });

            // Matching Right input change
            $(document).on('input', '.lms-match-right', function() {
                var qIdx = $(this).closest('.lms-quiz-question-card').data('index');
                var pairIdx = $(this).data('pair-idx');
                questions[qIdx].pairs[pairIdx].right = $(this).val();
                serializeData();
            });

            // Question image select click handler
            $(document).on('click', '.lms-btn-select-q-image', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-index'));
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({
                        title: 'Select Question Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        questions[qIdx].image = attachment.url;
                        renderQuestionsList();
                        $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
                    });
                    frame.open();
                }
            });

            // Question image remove click handler
            $(document).on('click', '.lms-btn-remove-q-image', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-index'));
                questions[qIdx].image = '';
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Matching Pairs select image click handler
            $(document).on('click', '.lms-btn-select-match-image', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-q-idx'));
                var pairIdx = parseInt($(this).attr('data-pair-idx'));
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({
                        title: 'Select Matching Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        questions[qIdx].pairs[pairIdx].right = attachment.url;
                        renderQuestionsList();
                        $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
                    });
                    frame.open();
                }
            });

            // Matching Pairs clear image click handler
            $(document).on('click', '.lms-btn-clear-match-image', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-q-idx'));
                var pairIdx = parseInt($(this).attr('data-pair-idx'));
                questions[qIdx].pairs[pairIdx].right = '';
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Option Toggle Text/Image type handler
            $(document).on('click', '.lms-btn-toggle-opt-type', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-q-idx'));
                var optIdx = parseInt($(this).attr('data-opt-idx'));
                var opt = questions[qIdx].options[optIdx];
                
                var isImage = (typeof opt === 'object' && opt !== null && opt.is_image);
                var optText = (typeof opt === 'object' && opt !== null) ? (opt.text || '') : opt;
                var optImg = (typeof opt === 'object' && opt !== null) ? (opt.image || '') : '';
                
                questions[qIdx].options[optIdx] = {
                    text: optText,
                    image: optImg,
                    is_image: !isImage
                };
                
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Option Select Image click handler
            $(document).on('click', '.lms-btn-select-opt-image', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-q-idx'));
                var optIdx = parseInt($(this).attr('data-opt-idx'));
                if (typeof wp !== 'undefined' && wp.media) {
                    var frame = wp.media({
                        title: 'Select Option Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        if (typeof questions[qIdx].options[optIdx] !== 'object' || questions[qIdx].options[optIdx] === null) {
                            questions[qIdx].options[optIdx] = {
                                text: questions[qIdx].options[optIdx] || '',
                                is_image: true
                            };
                        }
                        questions[qIdx].options[optIdx].image = attachment.url;
                        renderQuestionsList();
                        $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
                    });
                    frame.open();
                }
            });

            // Option Clear Image click handler
            $(document).on('click', '.lms-btn-clear-opt-image', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-q-idx'));
                var optIdx = parseInt($(this).attr('data-opt-idx'));
                if (typeof questions[qIdx].options[optIdx] === 'object' && questions[qIdx].options[optIdx] !== null) {
                    questions[qIdx].options[optIdx].image = '';
                }
                renderQuestionsList();
                $('.lms-quiz-question-card').eq(qIdx).addClass('expanded');
            });

            // Sort list hook (jQuery UI Sortable)
            $('#lms-quiz-questions-list-wrapper').sortable({
                handle: '.lms-quiz-question-header',
                placeholder: 'ui-state-highlight',
                update: function(event, ui) {
                    var newQuestions = [];
                    $('#lms-quiz-questions-list-wrapper .lms-quiz-question-card').each(function() {
                        var oldIdx = $(this).data('index');
                        newQuestions.push(questions[oldIdx]);
                    });
                    questions = newQuestions;
                    renderQuestionsList();
                }
            });

            // Save to bank click handler
            $(document).on('click', '.lms-btn-save-to-library', function(e) {
                e.preventDefault();
                var qIdx = parseInt($(this).attr('data-index'));
                var q = questions[qIdx];
                var title = q.question || 'Question #' + (qIdx + 1);
                var catId = parseInt($(this).siblings('.lms-save-q-category-select').val()) || 0;

                var $btn = $(this);
                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: reandaily_lms_admin_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lms_save_question_to_library',
                        nonce: reandaily_lms_admin_vars.nonce,
                        title: title,
                        category_id: catId,
                        q_data: JSON.stringify(q)
                    },
                    success: function(res) {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px; margin: 0 !important; display: inline-flex; align-items: center; gap: 4px;"></span> Save to Bank');
                        if (res.success) {
                            alert(res.data.message);
                        } else {
                            alert('Error: ' + res.data);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px; margin: 0 !important; display: inline-flex; align-items: center; gap: 4px;"></span> Save to Bank');
                        alert('Server error saving question.');
                    }
                });
            });

            // Open library click handler
            $(document).on('click', '#lms-quiz-btn-open-library', function(e) {
                e.preventDefault();
                $('#lms-quiz-library-modal').css('display', 'flex');
                loadLibraryQuestions();
            });

            // Close library click handlers
            $(document).on('click', '#lms-quiz-close-library-modal, #lms-quiz-cancel-library', function(e) {
                e.preventDefault();
                $('#lms-quiz-library-modal').hide();
            });

            // Filter changes
            $(document).on('input', '#lms-library-search', function() {
                loadLibraryQuestions();
            });
            $(document).on('change', '#lms-library-filter-type, #lms-library-filter-category', function() {
                loadLibraryQuestions();
            });

            // Import selected questions click handler
            $(document).on('click', '#lms-quiz-import-library', function(e) {
                e.preventDefault();
                var selected = [];
                $('.lms-library-question-checkbox:checked').each(function() {
                    var data = JSON.parse(decodeURIComponent($(this).attr('data-q-data')));
                    selected.push(data);
                });

                if (selected.length === 0) {
                    alert('Please select at least one question to import.');
                    return;
                }

                selected.forEach(function(q) {
                    questions.push(q);
                });

                renderQuestionsList();
                $('#lms-quiz-library-modal').hide();
                alert(selected.length + ' question(s) imported from bank successfully!');
            });

            function loadLibraryQuestions() {
                var search = $('#lms-library-search').val();
                var type = $('#lms-library-filter-type').val();
                var category = $('#lms-library-filter-category').val();
                var $list = $('#lms-library-questions-list');
                $list.html('<p style="text-align:center; color:#64748b;">Loading questions...</p>');

                $.ajax({
                    url: reandaily_lms_admin_vars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'lms_get_questions_library',
                        nonce: reandaily_lms_admin_vars.nonce,
                        search: search,
                        type: type,
                        category: category
                    },
                    success: function(res) {
                        if (res.success) {
                            $list.empty();
                            var items = res.data;
                            if (items.length === 0) {
                                $list.html('<p style="text-align:center; color:#64748b; font-style:italic; padding:20px;">No questions found in bank.</p>');
                                return;
                            }
                            items.forEach(function(item) {
                                var badge = getBadgeName(item.type);
                                var catBadge = item.category ? ' <span style="font-size:11px; font-weight:700; background:#eff6ff; color:#2563eb; padding:2px 6px; border-radius:4px; margin-top:4px; display:inline-block; text-transform:uppercase; letter-spacing:0.5px; margin-left:6px;">' + item.category + '</span>' : '';
                                var html = '';
                                html += '<label style="display:flex; align-items:center; gap:16px; padding:12px 16px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer; transition:all 0.15s; margin-bottom:8px; box-sizing:border-box; width:100%;">';
                                html += '  <input type="checkbox" class="lms-library-question-checkbox" data-q-data="' + encodeURIComponent(JSON.stringify(item.data)) + '" style="margin:0;">';
                                html += '  <div style="flex:1; text-align:left;">';
                                html += '    <div style="font-weight:600; font-size:14px; color:#0f172a;">' + item.title + '</div>';
                                html += '    <span style="font-size:11px; font-weight:700; background:#f1f5f9; color:#475569; padding:2px 6px; border-radius:4px; margin-top:4px; display:inline-block; text-transform:uppercase; letter-spacing:0.5px;">' + badge + '</span>' + catBadge;
                                html += '  </div>';
                                html += '</label>';
                                $list.append(html);
                            });
                        } else {
                            $list.html('<p style="text-align:center; color:#ef4444;">Error loading questions library.</p>');
                        }
                    },
                    error: function() {
                        $list.html('<p style="text-align:center; color:#ef4444;">Network error loading questions library.</p>');
                    }
                });
            }

            // Initialize rendering
            renderQuestionsList();
        });
    </script>
    <?php
}

// Save Metabox Data
function reandaily_lms_save_metaboxes_data( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Save Course Builder fields
    $is_saving_course = false;
    if ( isset( $_POST['lms_course_meta_nonce'] ) && wp_verify_nonce( $_POST['lms_course_meta_nonce'], 'reandaily_lms_save_course_meta' ) ) {
        $is_saving_course = true;
    } elseif ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'courses' && current_user_can( 'edit_posts' ) ) {
        $is_saving_course = true;
    }

    error_log( 'REANDAILY SAVE: Post ID: ' . $post_id . ', Post Type in $_POST: ' . (isset($_POST['post_type']) ? $_POST['post_type'] : 'not set') . ', Actual Post Type: ' . get_post_type($post_id) . ', is_saving_course: ' . ($is_saving_course ? 'true' : 'false') );
    if ( isset( $_POST['lms_course_sections'] ) ) {
        error_log( 'REANDAILY SAVE: lms_course_sections in $_POST: ' . $_POST['lms_course_sections'] );
    } else {
        error_log( 'REANDAILY SAVE: lms_course_sections NOT in $_POST' );
    }

    if ( $is_saving_course ) {
        // Save/Update Primary Teacher (Author)
        if ( isset( $_POST['lms_course_author'] ) ) {
            $author_id = intval( $_POST['lms_course_author'] );
            $current_author = intval( get_post_field( 'post_author', $post_id ) );
            if ( $author_id > 0 && $author_id !== $current_author ) {
                remove_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );
                wp_update_post( array(
                    'ID'          => $post_id,
                    'post_author' => $author_id,
                ) );
                add_action( 'save_post', 'reandaily_lms_save_metaboxes_data' );
            }
        }

        // Save/Update Co-teachers List
        if ( isset( $_POST['lms_co_teachers'] ) ) {
            $co_teachers = array_map( 'intval', (array) $_POST['lms_co_teachers'] );
            update_post_meta( $post_id, '_lms_co_teachers', $co_teachers );
        } else {
            delete_post_meta( $post_id, '_lms_co_teachers' );
        }

        if ( isset( $_POST['lms_price'] ) ) {
            update_post_meta( $post_id, '_price', sanitize_text_field( $_POST['lms_price'] ) );
        }
        if ( isset( $_POST['lms_price_khr'] ) ) {
            update_post_meta( $post_id, '_price_khr', sanitize_text_field( $_POST['lms_price_khr'] ) );
        }
        
        // Save FAQ duplicate price fields
        if ( isset( $_POST['lms_price_faq'] ) ) {
            update_post_meta( $post_id, '_price_faq', sanitize_text_field( $_POST['lms_price_faq'] ) );
        }
        if ( isset( $_POST['lms_price_khr_faq'] ) ) {
            update_post_meta( $post_id, '_price_khr_faq', sanitize_text_field( $_POST['lms_price_khr_faq'] ) );
        }
        if ( isset( $_POST['lms_price_type_faq'] ) ) {
            update_post_meta( $post_id, '_price_type_faq', sanitize_text_field( $_POST['lms_price_type_faq'] ) );
        }
        if ( isset( $_POST['lms_duration'] ) ) {
            update_post_meta( $post_id, '_duration', sanitize_text_field( $_POST['lms_duration'] ) );
        }
        if ( isset( $_POST['lms_video_duration'] ) ) {
            update_post_meta( $post_id, '_video_duration', sanitize_text_field( $_POST['lms_video_duration'] ) );
        }
        if ( isset( $_POST['lms_preview_description'] ) ) {
            update_post_meta( $post_id, '_preview_description', sanitize_textarea_field( $_POST['lms_preview_description'] ) );
        }
        update_post_meta( $post_id, '_featured_course', isset( $_POST['lms_featured_course'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_lock_lessons_order', isset( $_POST['lms_lock_lessons'] ) ? '1' : '0' );
        if ( isset( $_POST['lms_drip_dependencies'] ) ) {
            $drip_json = stripslashes( $_POST['lms_drip_dependencies'] );
            $drip_data = json_decode( $drip_json, true );
            if ( is_array( $drip_data ) ) {
                // Sanitize structural IDs and arrays
                $sanitized_drip = array();
                foreach ( $drip_data as $group ) {
                    if ( isset( $group['id'] ) && isset( $group['parent_id'] ) ) {
                        $deps = isset( $group['dependents'] ) && is_array( $group['dependents'] ) ? array_filter( array_map( 'intval', $group['dependents'] ) ) : array();
                        $sanitized_drip[] = array(
                            'id' => sanitize_text_field( $group['id'] ),
                            'parent_id' => intval( $group['parent_id'] ),
                            'dependents' => array_values( $deps )
                        );
                    }
                }
                update_post_meta( $post_id, '_drip_dependencies', $sanitized_drip );
            } else {
                update_post_meta( $post_id, '_drip_dependencies', array() );
            }
        }
        if ( isset( $_POST['lms_access_duration'] ) ) {
            update_post_meta( $post_id, '_access_duration', sanitize_text_field( $_POST['lms_access_duration'] ) );
        }
        if ( isset( $_POST['lms_access_device_types'] ) ) {
            update_post_meta( $post_id, '_access_device_types', sanitize_text_field( $_POST['lms_access_device_types'] ) );
        }
        if ( isset( $_POST['lms_certification_info'] ) ) {
            update_post_meta( $post_id, '_certification_info', sanitize_text_field( $_POST['lms_certification_info'] ) );
        }
        if ( isset( $_POST['_thumbnail_id'] ) ) {
            $thumb_id = intval( $_POST['_thumbnail_id'] );
            if ( $thumb_id > 0 ) {
                set_post_thumbnail( $post_id, $thumb_id );
            } else {
                delete_post_thumbnail( $post_id );
            }
        }
        if ( isset( $_POST['lms_level'] ) ) {
            update_post_meta( $post_id, '_level', sanitize_text_field( $_POST['lms_level'] ) );
        }
        if ( isset( $_POST['lms_course_category'] ) ) {
            $cat_id = intval( $_POST['lms_course_category'] );
            if ( $cat_id > 0 ) {
                wp_set_post_terms( $post_id, array( $cat_id ), 'course_category' );
            } else {
                wp_set_post_terms( $post_id, array(), 'course_category' );
            }
        }
        if ( isset( $_POST['lms_course_faq'] ) ) {
            $faq_json = stripslashes( $_POST['lms_course_faq'] );
            $faq_data = json_decode( $faq_json, true );
            if ( is_array( $faq_data ) ) {
                $sanitized_faq = array();
                foreach ( $faq_data as $item ) {
                    if ( isset( $item['question'] ) && isset( $item['answer'] ) ) {
                        $sanitized_faq[] = array(
                            'question' => sanitize_text_field( $item['question'] ),
                            'answer'   => wp_kses_post( $item['answer'] )
                        );
                    }
                }
                update_post_meta( $post_id, '_course_faq', $sanitized_faq );
            }
        }
        if ( isset( $_POST['lms_trailer_url'] ) ) {
            update_post_meta( $post_id, '_trailer_url', esc_url_raw( $_POST['lms_trailer_url'] ) );
        }
        if ( isset( $_POST['lms_course_notice'] ) ) {
            update_post_meta( $post_id, '_course_notice', wp_kses_post( $_POST['lms_course_notice'] ) );
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
    $is_saving_lesson = false;
    if ( isset( $_POST['lms_lesson_meta_nonce'] ) && wp_verify_nonce( $_POST['lms_lesson_meta_nonce'], 'reandaily_lms_save_lesson_meta' ) ) {
        $is_saving_lesson = true;
    } elseif ( isset( $_POST['post_type'] ) && $_POST['post_type'] === 'lessons' && current_user_can( 'edit_posts' ) ) {
        $is_saving_lesson = true;
    }

    if ( $is_saving_lesson ) {
        if ( isset( $_POST['lms_lesson_type'] ) ) {
            update_post_meta( $post_id, '_lesson_type', sanitize_text_field( $_POST['lms_lesson_type'] ) );
        }
        if ( isset( $_POST['lms_video_url'] ) ) {
            // Check if it's an iframe tag, extract the src if it is, otherwise save raw string safely
            $raw_video_url = $_POST['lms_video_url'];
            if ( preg_match( '/src=["\']([^"\']+)["\']/i', $raw_video_url, $src_match ) ) {
                $raw_video_url = $src_match[1];
            }
            update_post_meta( $post_id, '_video_url', esc_url_raw( trim( $raw_video_url ) ) );
        }
        if ( isset( $_POST['lms_lesson_duration'] ) ) {
            update_post_meta( $post_id, '_duration', sanitize_text_field( $_POST['lms_lesson_duration'] ) );
        }
        
        $is_preview = isset( $_POST['lms_is_preview'] ) ? '1' : '0';
        update_post_meta( $post_id, '_is_preview', $is_preview );

        if ( isset( $_POST['lms_quiz_time_limit'] ) ) {
            update_post_meta( $post_id, '_quiz_time_limit', intval( $_POST['lms_quiz_time_limit'] ) );
        }
        if ( isset( $_POST['lms_quiz_passing_grade'] ) ) {
            update_post_meta( $post_id, '_quiz_passing_grade', intval( $_POST['lms_quiz_passing_grade'] ) );
        }
        if ( isset( $_POST['lms_quiz_retakes'] ) ) {
            update_post_meta( $post_id, '_quiz_retakes', intval( $_POST['lms_quiz_retakes'] ) );
        }
        if ( isset( $_POST['lms_quiz_questions'] ) ) {
            $questions_json = wp_unslash( $_POST['lms_quiz_questions'] );
            update_post_meta( $post_id, '_quiz_questions', $questions_json );
        }
        if ( isset( $_POST['lms_video_questions'] ) ) {
            $video_questions_json = wp_unslash( $_POST['lms_video_questions'] );
            update_post_meta( $post_id, '_video_questions', $video_questions_json );
        }

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
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=reandaily-lms-enrollments&action=approve&id=' . $e->id ), $nonce_url ); ?>" class="button button-primary button-small" style="background:#10b981; border-color:#10b981;"><?php _e( 'Approve', 'reandaily-lms-theme' ); ?></a>
                                <?php else : ?>
                                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=reandaily-lms-enrollments&action=pending&id=' . $e->id ), $nonce_url ); ?>" class="button button-secondary button-small"><?php _e( 'Make Pending', 'reandaily-lms-theme' ); ?></a>
                                <?php endif; ?>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=reandaily-lms-enrollments&action=delete&id=' . $e->id ), $nonce_url ); ?>" class="button button-link-delete button-small" style="color:#ef4444; margin-left: 8px;" onclick="return confirm('<?php _e( 'Are you sure you want to delete this registration request?', 'reandaily-lms-theme' ); ?>')"><?php _e( 'Delete', 'reandaily-lms-theme' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function reandaily_lms_get_lesson_type_and_icon( $lesson_id ) {
    $type = get_post_meta( $lesson_id, '_lesson_type', true );
    $video_url = get_post_meta( $lesson_id, '_video_url', true );
    
    // Incase type is empty, infer from video_url
    if ( empty( $type ) ) {
        if ( empty( $video_url ) ) {
            $type = 'text';
        } else {
            // Check if it's a YouTube link
            if ( preg_match( '/(?:youtube\.com|youtu\.be|youtube-nocookie\.com)/i', $video_url ) ) {
                $type = 'video';
            } else {
                $type = 'video'; // Keep default fallback logic
            }
        }
    }

    if ( $type === 'text' ) {
        return array(
            'type'      => 'text',
            'fa_icon'   => 'fa-regular fa-file-lines',
            'dashicon'  => 'dashicons-media-text',
        );
    } elseif ( $type === 'quiz' ) {
        return array(
            'type'      => 'quiz',
            'fa_icon'   => 'fa-regular fa-circle-question',
            'dashicon'  => 'dashicons-welcome-write-blog',
        );
    } elseif ( $type === 'assignment' ) {
        return array(
            'type'      => 'assignment',
            'fa_icon'   => 'fa-regular fa-clipboard',
            'dashicon'  => 'dashicons-clipboard',
        );
    } elseif ( $type === 'stream' ) {
        return array(
            'type'      => 'stream',
            'fa_icon'   => 'fa-solid fa-rss',
            'dashicon'  => 'dashicons-rss',
        );
    } elseif ( $type === 'zoom' ) {
        return array(
            'type'      => 'zoom',
            'fa_icon'   => 'fa-solid fa-video',
            'dashicon'  => 'dashicons-welcome-teleport-reline',
        );
    }

    $path = parse_url( $video_url, PHP_URL_PATH );
    $ext = $path ? strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) : '';

    if ( $ext === 'pdf' ) {
        return array(
            'type'      => 'pdf',
            'fa_icon'   => 'fa-regular fa-file-pdf',
            'dashicon'  => 'dashicons-document',
        );
    } elseif ( in_array( $ext, array( 'doc', 'docx' ) ) ) {
        return array(
            'type'      => 'docx',
            'fa_icon'   => 'fa-regular fa-file-word',
            'dashicon'  => 'dashicons-document',
        );
    } elseif ( in_array( $ext, array( 'ppt', 'pptx' ) ) ) {
        return array(
            'type'      => 'pptx',
            'fa_icon'   => 'fa-regular fa-file-powerpoint',
            'dashicon'  => 'dashicons-presentation',
        );
    } elseif ( in_array( $ext, array( 'xls', 'xlsx' ) ) ) {
        return array(
            'type'      => 'xlsx',
            'fa_icon'   => 'fa-regular fa-file-excel',
            'dashicon'  => 'dashicons-media-spreadsheet',
        );
    } elseif ( in_array( $ext, array( 'zip', 'rar' ) ) ) {
        return array(
            'type'      => 'archive',
            'fa_icon'   => 'fa-regular fa-file-archive',
            'dashicon'  => 'dashicons-archive',
        );
    }

    // Default to video
    return array(
        'type'      => $type,
        'fa_icon'   => 'fa-brands fa-youtube',
        'dashicon'  => 'dashicons-video-alt3',
    );
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

    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'text';

    $lesson_id = wp_insert_post( array(
        'post_title'  => $title,
        'post_type'   => 'lessons',
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $lesson_id ) ) {
        wp_send_json_error( $lesson_id->get_error_message() );
    }

    update_post_meta( $lesson_id, '_lesson_type', $type );

    wp_send_json_success( array(
        'id'    => $lesson_id,
        'title' => $title,
        'type'  => $type,
    ) );
}

add_action( 'wp_ajax_reandaily_lms_get_lesson_settings', 'reandaily_lms_ajax_get_lesson_settings' );
function reandaily_lms_ajax_get_lesson_settings() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    if ( ! $lesson_id ) {
        wp_send_json_error( 'Invalid lesson' );
    }

    $video_url   = get_post_meta( $lesson_id, '_video_url', true );
    $duration    = get_post_meta( $lesson_id, '_duration', true );
    $is_preview  = get_post_meta( $lesson_id, '_is_preview', true );
    $unlock_drip = get_post_meta( $lesson_id, '_lesson_unlock_drip', true );
    $start_date  = get_post_meta( $lesson_id, '_lesson_start_date', true );
    $start_time  = get_post_meta( $lesson_id, '_lesson_start_time', true );
    $start_ampm  = get_post_meta( $lesson_id, '_lesson_start_ampm', true );
    $description = get_post_meta( $lesson_id, '_lesson_description', true );
    $title       = get_the_title( $lesson_id );
    $content     = get_post_field( 'post_content', $lesson_id );

    $quiz_passing_grade = get_post_meta( $lesson_id, '_quiz_passing_grade', true ) ?: '70';
    $quiz_time_limit    = get_post_meta( $lesson_id, '_quiz_time_limit', true ) ?: '0';
    $quiz_retakes       = get_post_meta( $lesson_id, '_quiz_retakes', true ) ?: '0';
    $quiz_questions     = get_post_meta( $lesson_id, '_quiz_questions', true ) ?: '[]';
    $video_questions    = get_post_meta( $lesson_id, '_video_questions', true ) ?: '[]';

    $lesson_info = reandaily_lms_get_lesson_type_and_icon( $lesson_id );
    wp_send_json_success( array(
        'id'                 => $lesson_id,
        'title'              => $title,
        'video_url'          => $video_url,
        'duration'           => $duration,
        'is_preview'         => ( $is_preview === '1' || $is_preview === true ) ? 1 : 0,
        'unlock_drip'        => ( $unlock_drip === '1' || $unlock_drip === true ) ? 1 : 0,
        'start_date'         => $start_date,
        'start_time'         => $start_time,
        'start_ampm'         => $start_ampm ? $start_ampm : 'AM',
        'description'        => $description,
        'content'            => $content,
        'type'               => $lesson_info['type'],
        'icon'               => $lesson_info['dashicon'],
        'fa_icon'            => $lesson_info['fa_icon'],
        'quiz_passing_grade' => $quiz_passing_grade,
        'quiz_time_limit'    => $quiz_time_limit,
        'quiz_retakes'       => $quiz_retakes,
        'quiz_questions'     => is_string($quiz_questions) ? $quiz_questions : json_encode($quiz_questions),
        'video_questions'    => is_string($video_questions) ? $video_questions : json_encode($video_questions),
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

    $title       = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    
    $video_url   = isset( $_POST['video_url'] ) ? $_POST['video_url'] : '';
    if ( preg_match( '/src=["\']([^"\']+)["\']/i', $video_url, $src_match ) ) {
        $video_url = $src_match[1];
    }
    $video_url   = esc_url_raw( trim( $video_url ) );

    $duration    = isset( $_POST['duration'] ) ? sanitize_text_field( $_POST['duration'] ) : '';
    $is_preview  = ( isset( $_POST['is_preview'] ) && ( $_POST['is_preview'] === '1' || $_POST['is_preview'] === 1 ) ) ? '1' : '0';
    $unlock_drip = ( isset( $_POST['unlock_drip'] ) && ( $_POST['unlock_drip'] === '1' || $_POST['unlock_drip'] === 1 ) ) ? '1' : '0';
    $start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
    $start_time  = isset( $_POST['start_time'] ) ? sanitize_text_field( $_POST['start_time'] ) : '';
    $start_ampm  = isset( $_POST['start_ampm'] ) ? sanitize_text_field( $_POST['start_ampm'] ) : 'AM';
    $description = isset( $_POST['description'] ) ? wp_kses_post( $_POST['description'] ) : '';
    $content     = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';

    // Update title and content
    wp_update_post( array(
        'ID'           => $lesson_id,
        'post_title'  => $title,
        'post_content' => $content,
    ) );

    update_post_meta( $lesson_id, '_video_url', $video_url );
    update_post_meta( $lesson_id, '_duration', $duration );
    update_post_meta( $lesson_id, '_is_preview', $is_preview );
    update_post_meta( $lesson_id, '_lesson_unlock_drip', $unlock_drip );
    update_post_meta( $lesson_id, '_lesson_start_date', $start_date );
    update_post_meta( $lesson_id, '_lesson_start_time', $start_time );
    update_post_meta( $lesson_id, '_lesson_start_ampm', $start_ampm );
    update_post_meta( $lesson_id, '_lesson_description', $description );

    if ( isset( $_POST['quiz_passing_grade'] ) ) {
        update_post_meta( $lesson_id, '_quiz_passing_grade', intval( $_POST['quiz_passing_grade'] ) );
    }
    if ( isset( $_POST['quiz_time_limit'] ) ) {
        update_post_meta( $lesson_id, '_quiz_time_limit', intval( $_POST['quiz_time_limit'] ) );
    }
    if ( isset( $_POST['quiz_retakes'] ) ) {
        update_post_meta( $lesson_id, '_quiz_retakes', intval( $_POST['quiz_retakes'] ) );
    }
    if ( isset( $_POST['quiz_questions'] ) ) {
        update_post_meta( $lesson_id, '_quiz_questions', wp_unslash( $_POST['quiz_questions'] ) );
    }
    if ( isset( $_POST['video_questions'] ) ) {
        update_post_meta( $lesson_id, '_video_questions', wp_unslash( $_POST['video_questions'] ) );
    }

    $lesson_info = reandaily_lms_get_lesson_type_and_icon( $lesson_id );
    wp_send_json_success( array(
        'message'     => 'Saved successfully',
        'id'          => $lesson_id,
        'title'       => $title,
        'video_url'   => $video_url,
        'duration'    => $duration,
        'is_preview'  => ( $is_preview === '1' || $is_preview === true ) ? 1 : 0,
        'unlock_drip' => ( $unlock_drip === '1' || $unlock_drip === true ) ? 1 : 0,
        'start_date'  => $start_date,
        'start_time'  => $start_time,
        'start_ampm'  => $start_ampm ? $start_ampm : 'AM',
        'description' => $description,
        'content'     => $content,
        'icon'        => $lesson_info['dashicon'],
        'fa_icon'     => $lesson_info['fa_icon'],
    ) );
}

add_action( 'wp_ajax_reandaily_lms_save_course_sections', 'reandaily_lms_ajax_save_course_sections' );
function reandaily_lms_ajax_save_course_sections() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
    if ( ! $course_id ) {
        wp_send_json_error( 'Invalid course ID' );
    }

    $sections_json = isset( $_POST['sections'] ) ? stripslashes( $_POST['sections'] ) : '';
    $sections_data = json_decode( $sections_json, true );

    if ( is_array( $sections_data ) ) {
        update_post_meta( $course_id, '_course_sections', $sections_data );
        
        // Update flat lessons order for backward compatibility
        $flat_lessons = array();
        foreach ( $sections_data as $section ) {
            if ( isset( $section['lessons'] ) && is_array( $section['lessons'] ) ) {
                foreach ( $section['lessons'] as $lid ) {
                    $flat_lessons[] = intval( $lid );
                }
            }
        }
        update_post_meta( $course_id, '_lessons_order', $flat_lessons );
        wp_send_json_success( 'Curriculum saved successfully' );
    } else {
        wp_send_json_error( 'Invalid sections data format' );
    }
}

add_action( 'wp_ajax_reandaily_lms_search_materials', 'reandaily_lms_ajax_search_materials' );
function reandaily_lms_ajax_search_materials() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
    
    $args = array(
        'post_type'      => 'lessons',
        'posts_per_page' => 40,
        'post_status'    => 'publish',
        's'              => $search,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    if ( ! empty( $type ) ) {
        $args['meta_query'] = array(
            array(
                'key'     => '_lesson_type',
                'value'   => $type,
                'compare' => '=',
            ),
        );
    }

    $query = new WP_Query( $args );
    $materials = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $lid = get_the_ID();
            $ltype = get_post_meta( $lid, '_lesson_type', true ) ?: 'text';
            
            $lesson_info = reandaily_lms_get_lesson_type_and_icon( $lid );
            
            $materials[] = array(
                'id'      => $lid,
                'title'   => get_the_title(),
                'type'    => $ltype,
                'icon'    => $lesson_info['dashicon'],
                'fa_icon' => $lesson_info['fa_icon']
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( $materials );
}

function reandaily_lms_ajax_get_lesson_questions() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    if ( ! $lesson_id ) {
        wp_send_json_error( 'Invalid lesson ID' );
    }
    
    $comments = get_comments( array(
        'post_id' => $lesson_id,
        'parent'  => 0,
        'status'  => 'approve',
        'order'   => 'DESC', // Show latest questions first
    ) );
    
    $formatted = array();
    foreach ( $comments as $comment ) {
        $replies = get_comments( array(
            'post_id' => $lesson_id,
            'parent'  => $comment->comment_ID,
            'status'  => 'approve',
            'order'   => 'ASC',
        ) );
        
        $replies_formatted = array();
        foreach ( $replies as $reply ) {
            $replies_formatted[] = array(
                'id'         => $reply->comment_ID,
                'author'     => $reply->comment_author,
                'content'    => esc_html( $reply->comment_content ),
                'date'       => get_comment_date( 'M j, Y g:i a', $reply->comment_ID ),
                'avatar_url' => get_avatar_url( $reply->comment_author_email, array( 'size' => 32 ) ),
            );
        }
        
        $formatted[] = array(
            'id'         => $comment->comment_ID,
            'author'     => $comment->comment_author,
            'content'    => esc_html( $comment->comment_content ),
            'date'       => get_comment_date( 'M j, Y g:i a', $comment->comment_ID ),
            'avatar_url' => get_avatar_url( $comment->comment_author_email, array( 'size' => 48 ) ),
            'replies'    => $replies_formatted,
        );
    }
    
    wp_send_json_success( $formatted );
}
add_action( 'wp_ajax_reandaily_lms_get_lesson_questions', 'reandaily_lms_ajax_get_lesson_questions' );

function reandaily_lms_ajax_reply_to_question() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }
    
    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    $parent_id = isset( $_POST['parent_id'] ) ? intval( $_POST['parent_id'] ) : 0;
    $content   = isset( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '';
    
    if ( ! $lesson_id || empty( $content ) ) {
        wp_send_json_error( 'Missing parameters or content' );
    }
    
    $user = wp_get_current_user();
    
    $comment_data = array(
        'comment_post_ID'      => $lesson_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $content,
        'comment_parent'       => $parent_id,
        'user_id'              => $user->ID,
        'comment_approved'     => 1,
    );
    
    $comment_id = wp_insert_comment( $comment_data );
    
    if ( $comment_id ) {
        wp_send_json_success( array(
            'id'         => $comment_id,
            'author'     => $user->display_name,
            'content'    => esc_html( $content ),
            'date'       => get_comment_date( 'M j, Y g:i a', $comment_id ),
            'avatar_url' => get_avatar_url( $user->user_email, array( 'size' => 32 ) ),
        ) );
    } else {
        wp_send_json_error( 'Failed to submit answer' );
    }
}
add_action( 'wp_ajax_reandaily_lms_reply_to_question', 'reandaily_lms_ajax_reply_to_question' );

function reandaily_lms_ajax_post_student_question() {
    check_ajax_referer( 'reandaily_lms_nonce', 'security' );
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Please log in to ask questions' );
    }
    
    $lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;
    $content   = isset( $_POST['content'] ) ? sanitize_textarea_field( $_POST['content'] ) : '';
    
    if ( ! $lesson_id || empty( $content ) ) {
        wp_send_json_error( 'Content is required' );
    }
    
    $user = wp_get_current_user();
    
    $comment_data = array(
        'comment_post_ID'      => $lesson_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $content,
        'comment_parent'       => 0,
        'user_id'              => $user->ID,
        'comment_approved'     => 1,
    );
    
    $comment_id = wp_insert_comment( $comment_data );
    
    if ( $comment_id ) {
        wp_send_json_success( array(
            'id'         => $comment_id,
            'author'     => $user->display_name,
            'content'    => esc_html( $content ),
            'date'       => get_comment_date( 'M j, Y g:i a', $comment_id ),
            'avatar_url' => get_avatar_url( $user->user_email, array( 'size' => 48 ) ),
        ) );
    } else {
        wp_send_json_error( 'Failed to post question' );
    }
}
add_action( 'wp_ajax_reandaily_lms_post_student_question', 'reandaily_lms_ajax_post_student_question' );
add_action( 'wp_ajax_nopriv_reandaily_lms_post_student_question', 'reandaily_lms_ajax_post_student_question' );

// ── 4. REGISTER ADMIN MENUS & FILTERING ───────────────────────────────────────
add_action( 'admin_menu', 'reandaily_lms_admin_menu' );
function reandaily_lms_admin_menu() {
    add_menu_page(
        __( 'Reandaily LMS', 'reandaily-lms-theme' ),
        __( 'Reandaily LMS', 'reandaily-lms-theme' ),
        'edit_posts',
        'reandaily-lms',
        'reandaily_lms_admin_dashboard_page',
        'dashicons-welcome-learn-more',
        3
    );

    // 1. Courses
    add_submenu_page(
        'reandaily-lms',
        __( 'Courses', 'reandaily-lms-theme' ),
        __( 'Courses', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit.php?post_type=courses'
    );

    // 2. Lessons
    add_submenu_page(
        'reandaily-lms',
        __( 'Lessons', 'reandaily-lms-theme' ),
        __( 'Lessons', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit.php?post_type=lessons&lesson_type=lesson'
    );

    // 3. Quizzes
    add_submenu_page(
        'reandaily-lms',
        __( 'Quizzes', 'reandaily-lms-theme' ),
        __( 'Quizzes', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit.php?post_type=lessons&lesson_type=quiz'
    );

    // 4. Questions
    add_submenu_page(
        'reandaily-lms',
        __( 'Questions', 'reandaily-lms-theme' ),
        __( 'Questions', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit-comments.php?post_type=lessons'
    );

    // 4b. Questions Bank
    add_submenu_page(
        'reandaily-lms',
        __( 'Questions Bank', 'reandaily-lms-theme' ),
        __( 'Questions Bank', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit.php?post_type=lms_questions'
    );

    // 4c. Question Categories
    add_submenu_page(
        'reandaily-lms',
        __( 'Question Categories', 'reandaily-lms-theme' ),
        __( 'Question Categories', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit-tags.php?taxonomy=question_category&post_type=lms_questions'
    );

    // 5. Assignments
    add_submenu_page(
        'reandaily-lms',
        __( 'Assignments', 'reandaily-lms-theme' ),
        __( 'Assignments', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit.php?post_type=lessons&lesson_type=assignment'
    );

    // 6. Submissions
    add_submenu_page(
        'reandaily-lms',
        __( 'Submissions', 'reandaily-lms-theme' ),
        __( 'Submissions', 'reandaily-lms-theme' ),
        'edit_posts',
        'edit.php?post_type=submissions'
    );

    // 7. Orders
    add_submenu_page(
        'reandaily-lms',
        __( 'Orders', 'reandaily-lms-theme' ),
        __( 'Orders', 'reandaily-lms-theme' ),
        'manage_options',
        'reandaily-lms-enrollments',
        'reandaily_lms_admin_enrollments_page'
    );

    // 8. Bundles
    add_submenu_page(
        'reandaily-lms',
        __( 'Bundles', 'reandaily-lms-theme' ),
        __( 'Bundles', 'reandaily-lms-theme' ),
        'edit_posts',
        'reandaily-lms-bundles',
        'reandaily_lms_bundles_page'
    );

    // 9. Reviews
    add_submenu_page(
        'reandaily-lms',
        __( 'Reviews', 'reandaily-lms-theme' ),
        __( 'Reviews', 'reandaily-lms-theme' ) . ' <span style="float: right; margin-right: 18px; color: #94a3b8; font-weight: 500;">1</span>',
        'edit_posts',
        'reandaily-lms-reviews',
        'reandaily_lms_reviews_page'
    );

    // 10. Payouts
    add_submenu_page(
        'reandaily-lms',
        __( 'Payouts', 'reandaily-lms-theme' ),
        __( 'Payouts', 'reandaily-lms-theme' ),
        'edit_posts',
        'reandaily-lms-payouts',
        'reandaily_lms_payouts_page'
    );

    // 11. Instructors
    add_submenu_page(
        'reandaily-lms',
        __( 'Instructors', 'reandaily-lms-theme' ),
        __( 'Instructors', 'reandaily-lms-theme' ) . ' <span style="float: right; margin-right: 18px; color: #94a3b8; font-weight: 500;">2</span>',
        'edit_posts',
        'users.php?role=administrator'
    );

    // 12. Students
    add_submenu_page(
        'reandaily-lms',
        __( 'Students', 'reandaily-lms-theme' ),
        __( 'Students', 'reandaily-lms-theme' ),
        'edit_posts',
        'users.php'
    );

    // 13. Email Manager
    add_submenu_page(
        'reandaily-lms',
        __( 'Email Manager', 'reandaily-lms-theme' ),
        __( 'Email Manager', 'reandaily-lms-theme' ),
        'edit_posts',
        'reandaily-lms-email-manager',
        'reandaily_lms_email_manager_page'
    );

    // 14. Forms Editor
    add_submenu_page(
        'reandaily-lms',
        __( 'Forms Editor', 'reandaily-lms-theme' ),
        __( 'Forms Editor', 'reandaily-lms-theme' ),
        'edit_posts',
        'reandaily-lms-forms-editor',
        'reandaily_lms_forms_editor_page'
    );

    // 15. Enterprise Groups
    add_submenu_page(
        'reandaily-lms',
        __( 'Enterprise Groups', 'reandaily-lms-theme' ),
        __( 'Enterprise Groups', 'reandaily-lms-theme' ),
        'edit_posts',
        'reandaily-lms-enterprise-groups',
        'reandaily_lms_enterprise_groups_page'
    );

    // Remove the default duplicate parent menu submenu item that WordPress automatically adds
    remove_submenu_page( 'reandaily-lms', 'reandaily-lms' );
}

// ── 4a. PLACEHOLDER PAGES & SEPARATOR STYLING ────────────────────────────────
function reandaily_lms_placeholder_page_callback( $title, $description = '' ) {
    ?>
    <div class="wrap" style="padding: 20px;">
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 40px; text-align: center; max-width: 600px; margin: 40px auto; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
            <div style="font-size: 64px; margin-bottom: 20px; color: #3b82f6;">🚀</div>
            <h1 style="font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 12px;"><?php echo esc_html( $title ); ?></h1>
            <p style="font-size: 16px; color: #64748b; line-height: 1.6; margin-bottom: 24px;">
                <?php echo esc_html( $description ? $description : __( 'This feature is currently under active development. Stay tuned for updates!', 'reandaily-lms-theme' ) ); ?>
            </p>
            <div style="display: inline-block; padding: 8px 16px; background: #eff6ff; color: #2563eb; font-weight: 600; border-radius: 9999px; font-size: 14px;">
                Coming Soon
            </div>
        </div>
    </div>
    <?php
}

function reandaily_lms_bundles_page() {
    reandaily_lms_placeholder_page_callback( __( 'Bundles', 'reandaily-lms-theme' ) );
}
function reandaily_lms_reviews_page() {
    reandaily_lms_placeholder_page_callback( __( 'Reviews', 'reandaily-lms-theme' ) );
}
function reandaily_lms_payouts_page() {
    reandaily_lms_placeholder_page_callback( __( 'Payouts', 'reandaily-lms-theme' ) );
}
function reandaily_lms_email_manager_page() {
    reandaily_lms_placeholder_page_callback( __( 'Email Manager', 'reandaily-lms-theme' ) );
}
function reandaily_lms_forms_editor_page() {
    reandaily_lms_placeholder_page_callback( __( 'Forms Editor', 'reandaily-lms-theme' ) );
}
function reandaily_lms_enterprise_groups_page() {
    reandaily_lms_placeholder_page_callback( __( 'Enterprise Groups', 'reandaily-lms-theme' ) );
}

add_action( 'admin_head', 'reandaily_lms_admin_menu_separators' );
function reandaily_lms_admin_menu_separators() {
    ?>
    <style>
        /* Separator before Instructors (after Payouts) */
        #toplevel_page_reandaily-lms ul.wp-submenu li a[href*="users.php?role=administrator"] {
            border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
            margin-top: 6px !important;
            padding-top: 10px !important;
        }
        /* Separator before Email Manager (after Students) */
        #toplevel_page_reandaily-lms ul.wp-submenu li a[href*="page=reandaily-lms-email-manager"] {
            border-top: 1px solid rgba(255, 255, 255, 0.15) !important;
            margin-top: 6px !important;
            padding-top: 10px !important;
        }
    </style>
    <?php
}

// Adjust CPT labels when editing quizzes
add_action( 'admin_init', 'reandaily_lms_adjust_lessons_labels_for_quizzes' );
function reandaily_lms_adjust_lessons_labels_for_quizzes() {
    global $wp_post_types;
    $is_quiz_screen = false;

    if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'lessons' ) {
        if ( isset( $_GET['lesson_type'] ) && $_GET['lesson_type'] === 'quiz' ) {
            $is_quiz_screen = true;
        }
    }
    
    // Also check if editing an existing quiz post
    if ( isset( $_GET['post'] ) ) {
        $post_id = intval( $_GET['post'] );
        if ( get_post_type( $post_id ) === 'lessons' ) {
            $ltype = get_post_meta( $post_id, '_lesson_type', true );
            if ( $ltype === 'quiz' ) {
                $is_quiz_screen = true;
            }
        }
    }

    if ( $is_quiz_screen && isset( $wp_post_types['lessons'] ) ) {
        $labels = &$wp_post_types['lessons']->labels;
        $labels->name = __( 'Quizzes', 'reandaily-lms-theme' );
        $labels->singular_name = __( 'Quiz', 'reandaily-lms-theme' );
        $labels->add_new = __( 'Add New Quiz', 'reandaily-lms-theme' );
        $labels->add_new_item = __( 'Add New Quiz', 'reandaily-lms-theme' );
        $labels->edit_item = __( 'Edit Quiz', 'reandaily-lms-theme' );
        $labels->new_item = __( 'New Quiz', 'reandaily-lms-theme' );
        $labels->view_item = __( 'View Quiz', 'reandaily-lms-theme' );
        $labels->all_items = __( 'All Quizzes', 'reandaily-lms-theme' );
        $labels->menu_name = __( 'Quizzes', 'reandaily-lms-theme' );
    }
}

// Redirect post-new.php?post_type=lessons to include &lesson_type=quiz if referer was quizzes list
add_action( 'admin_init', 'reandaily_lms_post_new_quiz_referrer_redirect' );
function reandaily_lms_post_new_quiz_referrer_redirect() {
    global $pagenow;
    if ( is_admin() && $pagenow === 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'lessons' && ! isset( $_GET['lesson_type'] ) ) {
        if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
            $referer = $_SERVER['HTTP_REFERER'];
            if ( strpos( $referer, 'lesson_type=quiz' ) !== false ) {
                wp_redirect( admin_url( 'post-new.php?post_type=lessons&lesson_type=quiz' ) );
                exit;
            }
        }
    }
}

// Javascript fallback to rewrite button links in case
add_action( 'admin_footer', 'reandaily_lms_admin_quizzes_add_new_url_fix' );
function reandaily_lms_admin_quizzes_add_new_url_fix() {
    global $pagenow;
    if ( is_admin() && $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'lessons' && isset( $_GET['lesson_type'] ) && $_GET['lesson_type'] === 'quiz' ) {
        ?>
        <script>
            jQuery(document).ready(function($) {
                var $addNew = $('a.page-title-action');
                if ($addNew.length) {
                    var href = $addNew.attr('href');
                    if (href && href.indexOf('lesson_type') === -1) {
                        $addNew.attr('href', href + '&lesson_type=quiz');
                    }
                    $addNew.text('<?php esc_js( _e( 'Add New Quiz', 'reandaily-lms-theme' ) ); ?>');
                }
            });
        </script>
        <?php
    }
}

function reandaily_lms_admin_dashboard_page() {
    wp_redirect( admin_url( 'edit.php?post_type=courses' ) );
    exit;
}

// Filter the lessons edit screen list based on the lesson_type query parameter
add_action( 'pre_get_posts', 'reandaily_lms_filter_lessons_by_type_in_admin' );
function reandaily_lms_filter_lessons_by_type_in_admin( $query ) {
    global $pagenow;
    if ( is_admin() && $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'lessons' && $query->is_main_query() ) {
        $lesson_type = isset( $_GET['lesson_type'] ) ? sanitize_text_field( $_GET['lesson_type'] ) : '';
        if ( $lesson_type === 'quiz' ) {
            $query->set( 'meta_key', '_lesson_type' );
            $query->set( 'meta_value', 'quiz' );
        } elseif ( $lesson_type === 'assignment' ) {
            $query->set( 'meta_key', '_lesson_type' );
            $query->set( 'meta_value', 'assignment' );
        } elseif ( $lesson_type === 'lesson' ) {
            $query->set( 'meta_query', array(
                'relation' => 'OR',
                array(
                    'key'     => '_lesson_type',
                    'value'   => array( 'quiz', 'assignment' ),
                    'compare' => 'NOT IN'
                ),
                array(
                    'key'     => '_lesson_type',
                    'compare' => 'NOT EXISTS'
                )
            ) );
        }
    }
}// AJAX: Save Question to Library
function reandaily_lms_ajax_save_question_to_library() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    $q_data_json = isset( $_POST['q_data'] ) ? stripslashes( $_POST['q_data'] ) : '';
    $cat_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;

    if ( empty( $title ) ) {
        $title = __( 'Untitled Question', 'reandaily-lms-theme' );
    }

    $q_data = json_decode( $q_data_json, true );
    if ( ! is_array( $q_data ) ) {
        wp_send_json_error( 'Invalid question data' );
    }

    // Create new lms_questions post
    $post_id = wp_insert_post( array(
        'post_title'  => $title,
        'post_type'   => 'lms_questions',
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Failed to save question: ' . $post_id->get_error_message() );
    }

    // Update metadata
    update_post_meta( $post_id, '_q_type', sanitize_text_field( $q_data['type'] ) );
    update_post_meta( $post_id, '_q_data', $q_data_json );

    if ( $cat_id > 0 ) {
        wp_set_post_terms( $post_id, array( $cat_id ), 'question_category' );
    }

    wp_send_json_success( array(
        'message' => __( 'Question saved to bank successfully!', 'reandaily-lms-theme' ),
        'id'      => $post_id,
    ) );
}
add_action( 'wp_ajax_lms_save_question_to_library', 'reandaily_lms_ajax_save_question_to_library' );

// AJAX: Get Questions Library List
function reandaily_lms_ajax_get_questions_library() {
    check_ajax_referer( 'reandaily_lms_ajax_nonce', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
    $category_slug = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';

    $args = array(
        'post_type'      => 'lms_questions',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        's'              => $search,
    );

    if ( ! empty( $type ) ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_q_type',
                'value' => $type,
            ),
        );
    }

    if ( ! empty( $category_slug ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'question_category',
                'field'    => 'slug',
                'terms'    => $category_slug,
            ),
        );
    }

    $query = new WP_Query( $args );
    $questions = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $id = get_the_ID();
            $q_type = get_post_meta( $id, '_q_type', true );
            $q_data = get_post_meta( $id, '_q_data', true );
            
            // Get category name
            $terms = get_the_terms( $id, 'question_category' );
            $cat_name = ($terms && ! is_wp_error($terms)) ? $terms[0]->name : '';

            $questions[] = array(
                'id'       => $id,
                'title'    => get_the_title(),
                'type'     => $q_type,
                'category' => $cat_name,
                'data'     => json_decode( $q_data, true ),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success( $questions );
}
add_action( 'wp_ajax_lms_get_questions_library', 'reandaily_lms_ajax_get_questions_library' );
