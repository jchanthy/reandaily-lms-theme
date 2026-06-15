<?php
get_header();
?>

<!-- Hero Section -->
<section class="hero-section" style="padding: 120px 0 100px 0; background: radial-gradient(circle at top right, rgba(229,47,46,0.15), transparent 60%); text-align: center; border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <span class="badge" style="background: rgba(229, 47, 46, 0.1); color: var(--color-primary); padding: 8px 16px; border-radius: 50px; font-weight: 600; font-size: 13.5px; border: 1px solid rgba(229, 47, 46, 0.2); letter-spacing: 0.5px; text-transform: uppercase;">🚀 CUSTOM LMS FOR REANDAILY</span>
        <h1 style="font-size: 52px; font-weight: 800; margin: 24px 0 16px; font-family: var(--font-heading); background: linear-gradient(135deg, var(--text-main) 50%, var(--text-muted) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            រៀនជំនាញអាយធីពីអ្នកជំនាញ <!-- RD-UPDATER-TEST-V1.0.1 -->
        </h1>
        <p style="color: var(--text-muted); font-size: 19px; max-width: 650px; margin: 0 auto 40px auto; font-family: var(--font-khmer);">
            វេទិកាសិក្សាអនឡាញ ដែលត្រូវបានរចនាឡើងយ៉ាងពិសេសសម្រាប់ការរៀនបច្ចេកវិទ្យា និងអភិវឌ្ឍន៍ជំនាញពិតប្រាកដ។
        </p>
        <div style="display: flex; gap: 16px; justify-content: center;">
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover)); color: #ffffff; padding: 16px 36px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 16px; box-shadow: 0 4px 20px rgba(229,47,46,0.25);">
                ចូលទៅកាន់វគ្គសិក្សា →
            </a>
            <?php if ( ! is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>" style="background: rgba(15, 23, 42, 0.03); color: var(--text-main); padding: 16px 36px; border-radius: var(--border-radius-sm); font-weight: 700; font-size: 16px; border: 1px solid var(--border-color);">
                    បង្កើតគណនីឥតគិតថ្លៃ
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stats Grid -->
<section style="padding: 60px 0; background-color: var(--bg-card); border-bottom: 1px solid var(--border-color);">
    <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 32px; text-align: center;">
        <div>
            <h3 style="font-size: 40px; color: var(--color-primary); font-weight: 800;">10K+</h3>
            <p style="color: var(--text-muted); font-size: 14px; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">សិស្សចុះឈ្មោះរៀន</p>
        </div>
        <div>
            <h3 style="font-size: 40px; color: #38bdf8; font-weight: 800;">50+</h3>
            <p style="color: var(--text-muted); font-size: 14px; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">វគ្គសិក្សាអាយធី</p>
        </div>
        <div>
            <h3 style="font-size: 40px; color: var(--color-success); font-weight: 800;">99.9%</h3>
            <p style="color: var(--text-muted); font-size: 14px; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">អត្រាជោគជ័យ</p>
        </div>
        <div>
            <h3 style="font-size: 40px; color: var(--color-warning); font-weight: 800;">24/7</h3>
            <p style="color: var(--text-muted); font-size: 14px; text-transform: uppercase; margin-top: 4px; letter-spacing: 0.5px;">ការគាំទ្រសិក្សានិងដោះស្រាយ</p>
        </div>
    </div>
</section>

<!-- Featured Courses -->
<section style="padding: 80px 0;">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 48px;">
            <div>
                <span style="color: var(--color-primary); font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">🎯 POPULAR COURSES</span>
                <h2 style="font-size: 36px; font-weight: 800; margin-top: 8px;">វគ្គសិក្សាដែលពេញនិយម</h2>
            </div>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>" style="font-weight: 600; color: var(--color-secondary);">
                មើលវគ្គសិក្សាទាំងអស់ <i class="fa-solid fa-arrow-right" style="margin-left: 6px;"></i>
            </a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
            <?php
            $query = new WP_Query( array(
                'post_type'      => 'courses',
                'posts_per_page' => 3,
                'post_status'    => 'publish'
            ) );

            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) : $query->the_post();
                    $course_id = get_the_ID();
                    $price     = get_post_meta( $course_id, '_price', true );
                    $price_khr = get_post_meta( $course_id, '_price_khr', true );
                    $duration  = get_post_meta( $course_id, '_duration', true );
                    $level     = get_post_meta( $course_id, '_level', true );
                    ?>
                    <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); overflow: hidden; display: flex; flex-direction: column; transition: var(--transition-normal); box-shadow: var(--shadow-sm);" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='var(--shadow-md)'; this.style.borderColor='rgba(229, 47, 46, 0.2)';" onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)'; this.style.borderColor='var(--border-color)';">
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
                            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; font-family: var(--font-khmer); line-height: 1.4;">
                                <a href="<?php the_permalink(); ?>" style="color: var(--text-main);"><?php the_title(); ?></a>
                            </h3>
                            
                            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px; font-family: var(--font-khmer); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo wp_strip_all_tags( get_the_excerpt() ); ?>
                            </p>

                            <!-- Meta -->
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: auto; font-size: 13.5px; color: var(--text-muted);">
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
                <?php
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <!-- Empty State -->
                <div style="grid-column: 1 / -1; background-color: var(--bg-card); border: 1px dashed var(--border-color); border-radius: var(--border-radius-md); padding: 48px; text-align: center; color: var(--text-muted);">
                    <i class="fa-regular fa-folder-open" style="font-size: 48px; color: var(--color-primary); margin-bottom: 16px;"></i>
                    <p style="font-size: 16px;"><?php _e( 'No courses available yet. Please check back later!', 'reandaily-lms-theme' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Custom Classroom Highlight -->
<section style="background-color: var(--bg-card); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 100px 0;">
    <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
        <div>
            <span style="color: var(--color-primary); font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">💎 MODERN CLASSROOM</span>
            <h2 style="font-size: 40px; font-weight: 800; margin: 12px 0 20px; line-height: 1.2;">ការរៀនប្រកបដោយផាសុកភាព គ្មានការរំខាន</h2>
            <p style="color: var(--text-muted); font-size: 16px; margin-bottom: 24px; font-family: var(--font-khmer); line-height: 1.8;">
                យើងបានបង្កើតប្រព័ន្ធ Classroom/Lesson Player យ៉ាងប្រណីត (Sidebar Sidebar & distraction-free) ដែលអនុញ្ញាតឱ្យសិស្សផ្តោតអារម្មណ៍ទាំងស្រុងលើវីដេអូមេរៀន និងឯកសារជំនួយ។
            </p>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 14px; font-size: 15px; color: var(--text-main);">
                <li><i class="fa-solid fa-circle-check" style="color: var(--color-success); margin-right: 10px;"></i> Sidebar រុករកមេរៀនយ៉ាងរហ័ស</li>
                <li><i class="fa-solid fa-circle-check" style="color: var(--color-success); margin-right: 10px;"></i> រក្សាទុកវឌ្ឍនភាពសិក្សាស្វ័យប្រវត្ត (Auto-save progress)</li>
                <li><i class="fa-solid fa-circle-check" style="color: var(--color-success); margin-right: 10px;"></i> សាកសមសម្រាប់ការសិក្សាតាមទូរស័ព្ទ និងកុំព្យូទ័រ</li>
            </ul>
        </div>
        <div style="position: relative; border-radius: var(--border-radius-md); overflow: hidden; border: 1px solid var(--border-color); box-shadow: var(--shadow-lg); background: #0b0f19;">
            <!-- Dummy Video Player Graphic -->
            <div style="padding-top: 56.25%; position: relative;">
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; background: linear-gradient(135deg, rgba(229,47,46,0.2), rgba(0,123,255,0.2)); color:#ffffff; font-family: var(--font-heading);">
                    <i class="fa-solid fa-circle-play" style="font-size: 64px; color: var(--color-primary); margin-bottom: 12px; filter: drop-shadow(0 4px 12px rgba(229,47,46,0.3));"></i>
                    <span style="font-size: 15px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">Classroom Dashboard Preview</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
get_footer();
