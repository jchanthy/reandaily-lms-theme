<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        /* Header & Navigation Styles */
        .site-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 16px 0;
            transition: var(--transition-normal);
        }

        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-wrap a {
            display: flex;
            align-items: center;
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            gap: 10px;
        }

        .logo-img {
            max-height: 40px;
            width: auto;
        }

        .main-nav {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 15px;
            padding: 8px 12px;
            border-radius: var(--border-radius-sm);
        }

        .nav-link:hover, .nav-link.active {
            color: var(--text-main);
            background: rgba(15, 23, 42, 0.05);
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-login {
            color: var(--text-main);
            font-weight: 600;
            font-size: 15px;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
            color: #ffffff;
            padding: 10px 20px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(229, 47, 46, 0.2);
        }

        .btn-register:hover {
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(229, 47, 46, 0.3);
        }

        .user-dropdown {
            position: relative;
            cursor: pointer;
        }

        .user-profile-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.04);
            padding: 6px 14px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--color-secondary);
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
        }

        .profile-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            width: 200px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            z-index: 10;
        }

        .user-dropdown:hover .profile-menu {
            display: block;
        }

        .profile-menu-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: var(--text-muted);
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-menu-link:last-child {
            border-bottom: none;
        }

        .profile-menu-link:hover {
            background: rgba(255, 255, 255, 0.03);
            color: #ffffff;
        }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container header-wrap">
        <!-- Logo -->
        <div class="logo-wrap">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <?php 
                $custom_logo_id = get_theme_mod( 'custom_logo' );
                $logo = wp_get_attachment_image_src( $custom_logo_id , 'full' );
                if ( has_custom_logo() && $logo ) {
                    echo '<img src="' . esc_url( $logo[0] ) . '" alt="' . get_bloginfo('name') . '" class="logo-img">';
                } else {
                    echo '<i class="fa-solid fa-graduation-cap" style="color: var(--color-primary);"></i> ' . get_bloginfo( 'name' );
                }
                ?>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-link"><?php _e( 'Home', 'reandaily-lms-theme' ); ?></a>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>" class="nav-link"><?php _e( 'Courses', 'reandaily-lms-theme' ); ?></a>
            
            <?php if ( is_user_logged_in() ) : 
                $dashboard_url = home_url( '/dashboard/' );
                ?>
                <a href="<?php echo esc_url( $dashboard_url ); ?>" class="nav-link"><?php _e( 'My Learning', 'reandaily-lms-theme' ); ?></a>
            <?php endif; ?>
        </nav>

        <!-- Auth / Profile -->
        <div class="auth-wrap">
            <?php if ( is_user_logged_in() ) : 
                $current_user = wp_get_current_user();
                $avatar_url = get_avatar_url( $current_user->ID );
                ?>
                <div class="user-dropdown">
                    <div class="user-profile-trigger">
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar" class="user-avatar">
                        <span class="user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
                        <i class="fa-solid fa-chevron-down" style="font-size: 10px; color: var(--text-muted);"></i>
                    </div>
                    <div class="profile-menu">
                        <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>" class="profile-menu-link">
                            <i class="fa-solid fa-gauge"></i> <?php _e( 'Dashboard', 'reandaily-lms-theme' ); ?>
                        </a>
                        <?php if ( current_user_can( 'manage_options' ) ) : ?>
                            <a href="<?php echo esc_url( admin_url() ); ?>" class="profile-menu-link">
                                <i class="fa-solid fa-user-shield"></i> <?php _e( 'WP Admin', 'reandaily-lms-theme' ); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="profile-menu-link" style="color: var(--color-danger);">
                            <i class="fa-solid fa-right-from-bracket"></i> <?php _e( 'Logout', 'reandaily-lms-theme' ); ?>
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <div class="auth-buttons">
                    <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn-login"><?php _e( 'Log In', 'reandaily-lms-theme' ); ?></a>
                    <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="btn-register"><?php _e( 'Register', 'reandaily-lms-theme' ); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
