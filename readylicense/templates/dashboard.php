<?php
/**
 * تمپلت داشبورد لایسنس‌ها در حساب کاربری ووکامرس
 *
 * @var array $licenses آرایه‌ای از لایسنس‌های کاربر که از کنترلر ارسال شده است.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="rl-dashboard-wrapper">

    <!-- اعلان‌ها (توسط جاوااسکریپت پر می‌شود) -->
    <div id="rl-alert-box" style="display: none;"></div>

    <?php if ( empty( $licenses ) ) : ?>
        
        <div class="rl-empty-state">
            <img src="<?php echo RL_PLUGIN_URL . 'assets/img/empty-box.svg'; ?>" alt="Empty" width="150">
            <p><?php _e( 'هنوز هیچ لایسنسی برای شما ثبت نشده است.', 'readylicense' ); ?></p>
            <a href="<?php echo wc_get_page_permalink( 'shop' ); ?>" class="rl-btn rl-btn-primary">
                <?php _e( 'مشاهده فروشگاه', 'readylicense' ); ?>
            </a>
        </div>

    <?php else : ?>

        <div class="rl-license-grid">
            <?php foreach ( $licenses as $license ) : ?>
                
                <div class="rl-license-card <?php echo $license['is_expired'] ? 'expired' : 'active'; ?>">
                    
                    <!-- هدر کارت: اطلاعات محصول -->
                    <div class="rl-card-header">
                        <div class="rl-product-info">
                            <?php if ( $license['product_image'] ) : ?>
                                <div class="rl-product-thumb">
                                    <?php echo $license['product_image']; ?>
                                </div>
                            <?php endif; ?>
                            <div class="rl-product-details">
                                <h3>
                                    <a href="<?php echo esc_url( $license['product_url'] ); ?>">
                                        <?php echo esc_html( $license['product_name'] ); ?>
                                    </a>
                                </h3>
                                <div class="rl-meta-badges">
                                    <span class="rl-badge <?php echo $license['is_expired'] ? 'expired' : 'success'; ?>">
                                        <?php echo $license['is_expired'] ? __( 'منقضی شده', 'readylicense' ) : __( 'فعال', 'readylicense' ); ?>
                                    </span>
                                    <?php if ( $license['expires_at'] ) : ?>
                                        <span class="rl-badge neutral">
                                            <?php printf( __( 'انقضا: %s', 'readylicense' ), date_i18n( get_option( 'date_format' ), strtotime( $license['expires_at'] ) ) ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="rl-badge success"><?php _e( 'مادام‌العمر', 'readylicense' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- دکمه کپی لایسنس -->
                        <div class="rl-license-key-box" onclick="rlCopyLicense(this, '<?php echo esc_attr( $license['license_key'] ); ?>')">
                            <span class="dashicons dashicons-admin-network"></span>
                            <span class="code"><?php echo esc_html( $license['license_key'] ); ?></span>
                            <span class="tooltip"><?php _e( 'کپی شد!', 'readylicense' ); ?></span>
                        </div>
                    </div>

                    <!-- بدنه کارت: لیست دامین‌ها -->
                    <div class="rl-card-body">
                        <h4><?php _e( 'دامنه‌های فعال:', 'readylicense' ); ?></h4>
                        
                        <?php if ( empty( $license['activations'] ) ) : ?>
                            <p class="rl-text-muted"><?php _e( 'هنوز روی هیچ دامنه‌ای فعال نشده است.', 'readylicense' ); ?></p>
                        <?php else : ?>
                            <ul class="rl-domain-list">
                                <?php foreach ( $license['activations'] as $activation ) : ?>
                                    <li>
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <a href="//<?php echo esc_attr( $activation->domain ); ?>" target="_blank" class="domain-link">
                                            <?php echo esc_html( $activation->domain ); ?>
                                        </a>
                                        <span class="activation-date">
                                            (<?php echo date_i18n( 'Y/m/d', strtotime( $activation->activated_at ) ); ?>)
                                        </span>
                                        
                                        <!-- دکمه حذف دامین (مدیریت لایسنس) -->
                                        <button class="rl-btn-icon remove-domain" 
                                                title="<?php _e( 'غیرفعال‌سازی', 'readylicense' ); ?>"
                                                onclick="rlManageDomain(<?php echo $license['id']; ?>, '<?php echo esc_js( $activation->domain ); ?>', 'deactivate')">
                                            <span class="dashicons dashicons-trash"></span>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- فوتر کارت: دکمه‌های عملیاتی -->
                    <div class="rl-card-footer">
                        
                        <!-- وضعیت ظرفیت -->
                        <div class="rl-usage-stats">
                            <span class="dashicons dashicons-chart-pie"></span>
                            <?php printf( __( '%d از %d نصب استفاده شده', 'readylicense' ), $license['activation_count'], $license['activation_limit'] ); ?>
                        </div>

                        <div class="rl-actions">
                            <?php if ( $license['activation_count'] < $license['activation_limit'] ) : ?>
                                <button class="rl-btn rl-btn-outline-primary" onclick="rlOpenModal(<?php echo $license['id']; ?>)">
                                    <span class="dashicons dashicons-plus"></span> <?php _e( 'افزودن دامنه', 'readylicense' ); ?>
                                </button>
                            <?php endif; ?>

                            <a href="<?php echo esc_url( $license['product_url'] ); ?>" class="rl-btn rl-btn-primary">
                                <span class="dashicons dashicons-download"></span> <?php _e( 'دانلود محصول', 'readylicense' ); ?>
                            </a>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- مودال افزودن دامنه (مخفی) -->
    <div id="rl-domain-modal" class="rl-modal">
        <div class="rl-modal-content">
            <span class="rl-close-modal" onclick="rlCloseModal()">&times;</span>
            <h3><?php _e( 'ثبت دامنه جدید', 'readylicense' ); ?></h3>
            <p><?php _e( 'لطفاً آدرس دامنه خود را بدون http و www وارد کنید (مثال: google.com).', 'readylicense' ); ?></p>
            
            <form id="rl-add-domain-form" onsubmit="rlSubmitDomain(event)">
                <input type="hidden" id="rl-modal-license-id" name="license_id">
                <div class="rl-form-group">
                    <input type="text" id="rl-domain-input" name="domain" placeholder="example.com" required class="rl-input ltr">
                </div>
                <div class="rl-form-actions">
                    <button type="submit" class="rl-btn rl-btn-primary block">
                        <?php _e( 'ثبت و فعال‌سازی', 'readylicense' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- استایل‌های جزئی برای فرانت (می‌تواند به فایل CSS منتقل شود) -->
<style>
    .rl-dashboard-wrapper { font-family: inherit; direction: rtl; }
    .rl-license-grid { display: grid; gap: 20px; }
    .rl-license-card { background: #fff; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; transition: box-shadow 0.3s; }
    .rl-license-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    .rl-card-header { padding: 20px; background: #f9f9f9; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .rl-product-info { display: flex; align-items: center; gap: 15px; }
    .rl-product-thumb img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
    .rl-product-details h3 { margin: 0 0 5px; font-size: 16px; font-weight: bold; }
    .rl-product-details h3 a { text-decoration: none; color: #333; }
    
    .rl-license-key-box { background: #fff; border: 1px dashed #ccc; padding: 8px 12px; border-radius: 4px; cursor: pointer; position: relative; font-family: monospace; display: flex; align-items: center; gap: 5px; }
    .rl-license-key-box:hover { border-color: #007cba; background: #f0f6fc; }
    .rl-license-key-box .tooltip { display: none; position: absolute; top: -30px; right: 50%; transform: translateX(50%); background: #333; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; }
    
    .rl-card-body { padding: 20px; }
    .rl-domain-list { list-style: none; padding: 0; margin: 0; }
    .rl-domain-list li { background: #f5f5f5; padding: 8px 12px; border-radius: 4px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .rl-btn-icon { background: none; border: none; color: #d63638; cursor: pointer; padding: 2px; margin-right: auto; }
    
    .rl-card-footer { padding: 15px 20px; background: #fff; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .rl-actions { display: flex; gap: 10px; }
    
    /* Responsive */
    @media (max-width: 768px) {
        .rl-card-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        .rl-license-key-box { width: 100%; justify-content: center; }
        .rl-card-footer { flex-direction: column; gap: 15px; }
        .rl-actions { width: 100%; }
        .rl-actions .rl-btn { flex: 1; justify-content: center; }
    }
</style>

<script>
// اسکریپت ساده برای کپی کردن و باز کردن مودال (می‌تواند به فایل JS منتقل شود)
function rlCopyLicense(element, text) {
    navigator.clipboard.writeText(text).then(() => {
        const tooltip = element.querySelector('.tooltip');
        tooltip.style.display = 'block';
        setTimeout(() => tooltip.style.display = 'none', 2000);
    });
}

function rlOpenModal(licenseId) {
    document.getElementById('rl-modal-license-id').value = licenseId;
    document.getElementById('rl-domain-modal').style.display = 'flex';
}

function rlCloseModal() {
    document.getElementById('rl-domain-modal').style.display = 'none';
}

// ارسال درخواست AJAX برای مدیریت دامین
function rlManageDomain(licenseId, domain, action) {
    if (!confirm('آیا مطمئن هستید؟')) return;
    
    // اینجا باید لاجیک AJAX فراخوانی شود که در فایل JS اصلی پیاده‌سازی می‌کنیم
    // فعلاً برای نمایش است
    console.log(action, licenseId, domain);
}
</script>