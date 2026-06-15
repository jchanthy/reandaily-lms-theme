<?php
/**
 * Template Name: Course Enrollment Page
 *
 * Handles course enrollment with KHQR payment for ReanDaily LMS Theme.
 */
get_header();

// Gather course data
$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
$course    = $course_id ? get_post( $course_id ) : null;
$is_valid  = $course && $course->post_type === 'courses' && $course->post_status === 'publish';

// Redirect if course not found
if ( ! $is_valid ) : ?>
<section style="padding: 120px 20px; text-align: center; font-family: var(--font-primary);">
    <span style="font-size: 60px;">😕</span>
    <h1 style="font-size: 28px; color: #ffffff; margin: 20px 0 10px; font-family: var(--font-khmer-heading);">មិនឃើញវគ្គសិក្សា</h1>
    <p style="color: var(--text-muted); margin-bottom: 30px;">សូមត្រលប់ទៅជ្រើសរើសវគ្គសិក្សាម្ដងទៀត។</p>
    <a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>" style="background: linear-gradient(135deg,#007bff,#00f2fe); color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; font-size:16px;">
        ← ត្រលប់ទៅ វគ្គសិក្សា
    </a>
</section>
<?php get_footer(); return; endif;

// Redirect to login if user is logged out
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() . '?course_id=' . $course_id ) );
    exit;
}

$user_id = get_current_user_id();

// Redirect to classroom if already enrolled
$enroll_status = reandaily_lms_is_enrolled( $user_id, $course_id );
if ( $enroll_status === 'active' ) {
    $lessons_order = get_post_meta( $course_id, '_lessons_order', true );
    if ( ! empty( $lessons_order ) ) {
        wp_redirect( add_query_arg( 'course_id', $course_id, get_permalink( $lessons_order[0] ) ) );
        exit;
    }
}

// Course details
$price_usd    = floatval( get_post_meta( $course_id, '_price', true ) );
$price_khr    = floatval( get_post_meta( $course_id, '_price_khr', true ) );
if ( ! $price_khr ) {
    $price_khr = round( $price_usd * 4100 );
}

$course_title = get_the_title( $course_id );
$course_url   = get_permalink( $course_id );

// Retrieve Customizer payment configurations
$payway_link   = get_theme_mod( 'reandaily_aba_payway_link', '' );
$bakong_id     = get_theme_mod( 'reandaily_aba_bakong_id', '' );
$merchant_name = get_theme_mod( 'reandaily_aba_merchant_name', 'ReanDaily' );
$merchant_city = get_theme_mod( 'reandaily_aba_merchant_city', 'Phnom Penh' );

$manual_bank_name    = get_theme_mod( 'reandaily_manual_bank_name', 'Advanced Bank of Asia (ABA)' );
$manual_account_name  = get_theme_mod( 'reandaily_manual_account_name', '' );
$manual_account_no    = get_theme_mod( 'reandaily_manual_account_no', '' );

// Retrieve Custom/Site Logo for QR Code
$qr_logo_url = get_theme_mod( 'reandaily_qr_code_logo', '' );
if ( empty( $qr_logo_url ) ) {
    if ( has_custom_logo() ) {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $logo_image = wp_get_attachment_image_src( $custom_logo_id, 'full' );
        if ( $logo_image ) {
            $qr_logo_url = $logo_image[0];
        }
    }
}
if ( empty( $qr_logo_url ) ) {
    $theme_logo_path = get_stylesheet_directory() . '/logo.png';
    if ( file_exists( $theme_logo_path ) ) {
        $qr_logo_url = get_stylesheet_directory_uri() . '/logo.png';
    } else {
        $qr_logo_url = get_stylesheet_directory_uri() . '/bakong-logo.svg';
    }
}

// Generate the KHQR manual payment string locally using our custom logic or fallback to PayWay link
$manual_qr_payload = '';
if ( ! empty( $bakong_id ) ) {
    $manual_qr_payload = reandaily_lms_generate_khqr( $bakong_id, $merchant_name, $merchant_city, $price_usd, 'USD' );
} elseif ( ! empty( $payway_link ) ) {
    $manual_qr_payload = $payway_link;
} else {
    // If absolutely nothing is configured, construct a placeholder or basic payment payload
    $manual_qr_payload = 'https://pay.ababank.com/oRF8/8czyh8ox'; // Fallback to ABA Link
}
?>

<style>
    .checkout-wrap {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 48px;
        padding: 60px 24px;
        align-items: start;
    }

    .card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-md);
        padding: 32px;
        box-shadow: var(--shadow-sm);
    }

    .tabs {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 24px;
        gap: 16px;
    }

    .tab {
        padding: 12px 20px;
        cursor: pointer;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 14.5px;
        border-bottom: 2px solid transparent;
        transition: var(--transition-fast);
    }

    .tab:hover, .tab.active {
        color: var(--text-main);
        border-bottom-color: var(--color-primary);
    }

    .payment-section {
        display: none;
    }

    .payment-section.active {
        display: block;
    }

    .qr-box {
        background: #ffffff;
        padding: 24px;
        border-radius: var(--border-radius-md);
        width: fit-content;
        margin: 24px auto;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: var(--shadow-md);
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 14px;
        font-weight: 700;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        transition: var(--transition-fast);
        border: none;
        font-size: 15px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
        color: #ffffff;
        box-shadow: 0 4px 16px rgba(229, 47, 46, 0.2);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(229, 47, 46, 0.3);
    }

    .receipt-upload-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 30px;
        border: 2px dashed #cbd5e1;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        background: rgba(255, 255, 255, 0.02);
        transition: var(--transition-fast);
        margin-bottom: 20px;
    }

    .receipt-upload-label:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: var(--color-secondary);
    }
</style>

<div class="container checkout-wrap">
    
    <!-- Left Column: Checkout Summary & Tabs -->
    <div>
        <div class="card" style="margin-bottom: 30px;">
            <span style="color: var(--color-primary); font-weight: 600; font-size: 12px; text-transform: uppercase;">💳 SECURE CHECKOUT</span>
            <h2 style="font-size: 26px; margin: 8px 0 20px; font-family: var(--font-khmer-heading);">ព័ត៌មានការចុះឈ្មោះ</h2>
            
            <div style="display: flex; gap: 20px; align-items: center; background: rgba(15,23,42,0.02); padding: 16px; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);">
                <?php if ( has_post_thumbnail( $course_id ) ) : ?>
                    <img src="<?php echo esc_url( get_the_post_thumbnail_url( $course_id, 'thumbnail' ) ); ?>" alt="Course Thumbnail" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--border-radius-sm);">
                <?php endif; ?>
                <div>
                    <h4 style="font-size: 16px; font-family: var(--font-khmer-heading); margin-bottom: 6px;"><?php echo esc_html( $course_title ); ?></h4>
                    <p style="color: var(--text-muted); font-size: 13.5px;">តម្លៃ (Price): <strong style="color: var(--text-main);">$<?php echo number_format( $price_usd, 2 ); ?></strong> (<?php echo number_format( $price_khr ); ?>៛)</p>
                </div>
            </div>
        </div>

        <div class="card">
            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" id="tab-khqr" onclick="switchPaymentTab('khqr')"><i class="fa-solid fa-qrcode" style="margin-right: 8px;"></i>ទូទាត់រហ័ស (Automated KHQR)</div>
                <div class="tab" id="tab-manual" onclick="switchPaymentTab('manual')"><i class="fa-solid fa-building-columns" style="margin-right: 8px;"></i>ផ្ទេរប្រាក់ដោយដៃ (Manual)</div>
            </div>

            <!-- Tab 1: Automated KHQR -->
            <div class="payment-section active" id-section="khqr">
                <p style="color: var(--text-muted); font-size: 14.5px; line-height: 1.7; font-family: var(--font-khmer);">
                    សូមស្កេន KHQR ខាងក្រោមដើម្បីបង់ប្រាក់។ ប្រព័ន្ធនឹងធ្វើការផ្ទៀងផ្ទាត់ការទូទាត់ និងបើកវគ្គសិក្សាជូនលោកអ្នកស្វ័យប្រវត្តបន្ទាប់ពីការផ្ទេរបានជោគជ័យ។
                </p>

                <!-- Container to render automated QR -->
                <div class="qr-box" id="automated-qr-container" style="width:240px; height:240px;">
                    <!-- QRCode.js will render here -->
                </div>

                <div style="text-align: center; margin: 16px 0;">
                    <div style="display:inline-flex; align-items:center; gap:10px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); padding: 8px 16px; border-radius: 50px; font-size: 13px; color: var(--color-success);">
                        <i class="fa-solid fa-spinner fa-spin"></i> កំពុងរង់ចាំការទូទាត់ពីលោកអ្នក...
                    </div>
                </div>
            </div>

            <!-- Tab 2: Manual Bank Transfer -->
            <div class="payment-section" id-section="manual">
                <p style="color: var(--text-muted); font-size: 14.5px; line-height: 1.7; font-family: var(--font-khmer); margin-bottom: 24px;">
                    សូមផ្ទេរប្រាក់ទៅកាន់គណនីធនាគារខាងក្រោម រួចថតរូបភាពវិក្កយបត្រ (Receipt) រួចអាប់ឡូតខាងក្រោមដើម្បីផ្ទៀងផ្ទាត់។
                </p>

                <div style="background: rgba(15,23,42,0.02); border: 1px solid var(--border-color); padding: 20px; border-radius: var(--border-radius-sm); margin-bottom: 24px; font-size: 14.5px; display: flex; flex-direction: column; gap: 12px;">
                    <div><span style="color: var(--text-muted);">ធនាគារ (Bank):</span> <strong style="color: var(--text-main);"><?php echo esc_html( $manual_bank_name ); ?></strong></div>
                    <div><span style="color: var(--text-muted);">ឈ្មោះគណនី (Name):</span> <strong style="color: var(--text-main);"><?php echo esc_html( $manual_account_name ); ?></strong></div>
                    <div><span style="color: var(--text-muted);">លេខគណនី (Number):</span> <strong style="color: var(--text-main);"><?php echo esc_html( $manual_account_no ); ?></strong></div>
                </div>

                <!-- Receipt Upload Form -->
                <form id="receipt-upload-form" enctype="multipart/form-data">
                    <label class="receipt-upload-label" id="upload-label-area">
                        <i class="fa-solid fa-cloud-arrow-up" style="font-size: 36px; color: var(--color-secondary); margin-bottom: 12px;"></i>
                        <span style="font-weight: 600; font-size: 14.5px;">ជ្រើសរើសរូបភាពវិក្កយបត្រ / Upload Receipt</span>
                        <span style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Supports PNG, JPG, JPEG</span>
                        <input type="file" name="receipt_file" id="receipt_file" accept="image/*" style="display:none;" onchange="handleFileSelect(event)">
                    </label>
                    <div id="file-info" style="display:none; align-items:center; justify-content:space-between; background:rgba(255,255,255,0.03); border:1px solid var(--border-color); padding:12px; border-radius:var(--border-radius-sm); margin-bottom:20px; font-size:13.5px;">
                        <span id="file-name-text">filename.png</span>
                        <i class="fa-solid fa-circle-xmark" style="color: var(--color-danger); cursor:pointer;" onclick="clearFileSelect()"></i>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-submit-receipt" disabled>
                        ផ្ញើវិក្កយបត្រសម្រាប់ផ្ទៀងផ្ទាត់
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Security Banner / Instructions -->
    <div style="background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--border-radius-md); padding: 32px; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px;"><i class="fa-solid fa-circle-info" style="color: var(--color-secondary); margin-right: 8px;"></i> ណែនាំការបង់ប្រាក់</h3>
        
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px; font-size: 14px; color: var(--text-muted); line-height: 1.6;">
            <li>
                <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">១. ស្កេនទូទាត់</strong>
                សូមប្រើប្រាស់កម្មវិធីធនាគារណាមួយនៅក្នុងប្រទេសកម្ពុជា (ABA, Acleda, Wing, Bakong...) ដើម្បីស្កេន។
            </li>
            <li>
                <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">២. រក្សាទុកវិក្កយបត្រ</strong>
                បន្ទាប់ពីទូទាត់រួច សូមកុំទាន់បិទកម្មវិធីធនាគារ។ ប្រព័ន្ធនឹងបញ្ជាក់ការទូទាត់ស្វ័យប្រវត្តក្នុងរយៈពេល ១ ទៅ ៣ នាទី។
            </li>
            <li>
                <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">៣. ត្រូវការជំនួយ?</strong>
                ប្រសិនបើប្រព័ន្ធមិនដំណើរការ ឬរង់ចាំយូរ សូមទំនាក់ទំនងមកកាន់ក្រុមការងារគាំទ្រតាមរយៈ Telegram គាំទ្ររៀន។
            </li>
        </ul>
    </div>
</div>

<script>
    const CONFIG = {
        courseId       : <?php echo (int) $course_id; ?>,
        ajaxUrl        : <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
        nonce          : <?php echo wp_json_encode( wp_create_nonce( 'reandaily_lms_nonce' ) ); ?>,
        qrLogoUrl      : <?php echo wp_json_encode( esc_url( $qr_logo_url ) ); ?>,
        manualPayload  : <?php echo wp_json_encode( $manual_qr_payload ); ?>,
    };

    let activePaymentTab = 'khqr';
    let pollInterval = null;

    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Enrollment with Pending Record
        initiateEnrollmentRecord();
        
        // Setup manual QR code inside the tabs
        renderQrCode('automated-qr-container', CONFIG.manualPayload);
    });

    function switchPaymentTab(tab) {
        activePaymentTab = tab;
        document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.payment-section').forEach(el => el.classList.remove('active'));

        document.getElementById(`tab-${tab}`).classList.add('active');
        document.querySelector(`.payment-section[id-section="${tab}"]`).classList.add('active');
    }

    function initiateEnrollmentRecord() {
        const fd = new FormData();
        fd.append('action', 'reandaily_lms_enroll_student');
        fd.append('course_id', CONFIG.courseId);
        fd.append('payment_method', activePaymentTab);
        fd.append('security', CONFIG.nonce);

        fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.data.redirect) {
                    window.location.href = data.data.redirect;
                } else {
                    // Start Polling Status
                    startEnrollmentPolling();
                }
            }
        });
    }

    function renderQrCode(containerId, payload) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        new QRCode(container, {
            text: payload,
            width: 240,
            height: 240,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Append Center Logo
        if (CONFIG.qrLogoUrl) {
            const logo = document.createElement('img');
            logo.src = CONFIG.qrLogoUrl;
            logo.style.position = 'absolute';
            logo.style.top = '50%';
            logo.style.left = '50%';
            logo.style.transform = 'translate(-50%, -50%)';
            logo.style.width = '46px';
            logo.style.height = '46px';
            logo.style.objectFit = 'contain';
            logo.style.background = '#ffffff';
            logo.style.padding = '3px';
            logo.style.borderRadius = '8px';
            logo.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
            logo.style.zIndex = '10';
            container.appendChild(logo);
        }
    }

    function startEnrollmentPolling() {
        if (pollInterval) clearInterval(pollInterval);
        
        pollInterval = setInterval(() => {
            const fd = new FormData();
            fd.append('action', 'reandaily_lms_check_status');
            fd.append('course_id', CONFIG.courseId);
            fd.append('security', CONFIG.nonce);

            fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.status === 'active') {
                    clearInterval(pollInterval);
                    alert("🎉 ការទូទាត់ទទួលបានជោគជ័យ! ប្រព័ន្ធបានបើកវគ្គសិក្សាជូនអ្នករួចរាល់។");
                    window.location.href = '<?php echo esc_url( reandaily_lms_get_dashboard_url() ); ?>';
                }
            });
        }, 3000);
    }

    // File Selection Handlers for Manual Receipt
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        document.getElementById('file-name-text').textContent = file.name;
        document.getElementById('file-info').style.display = 'flex';
        document.getElementById('upload-label-area').style.display = 'none';
        document.getElementById('btn-submit-receipt').disabled = false;
    }

    function clearFileSelect() {
        document.getElementById('receipt_file').value = '';
        document.getElementById('file-info').style.display = 'none';
        document.getElementById('upload-label-area').style.display = 'flex';
        document.getElementById('btn-submit-receipt').disabled = true;
    }

    // Submit Receipt Form
    const receiptForm = document.getElementById('receipt-upload-form');
    if (receiptForm) {
        receiptForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-receipt');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> កំពុងផ្ញើ...';

            const fd = new FormData(this);
            fd.append('action', 'reandaily_lms_submit_receipt');
            fd.append('course_id', CONFIG.courseId);
            fd.append('security', CONFIG.nonce);

            fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    window.location.href = '<?php echo esc_url( reandaily_lms_get_dashboard_url() ); ?>';
                } else {
                    alert(data.data.message || 'Error uploading receipt.');
                    btn.disabled = false;
                    btn.textContent = 'ផ្ញើវិក្កយបត្រសម្រាប់ផ្ទៀងផ្ទាត់';
                }
            })
            .catch(err => {
                alert('Connection error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'ផ្ញើវិក្កយបត្រសម្រាប់ផ្ទៀងផ្ទាត់';
            });
        });
    }
</script>

<?php
get_footer();
