<?php
/**
 * تمپلت داشبورد لایسنس‌ها (طرح جدید: کارت/گرید)
 *
 * @var array $licenses آرایه‌ای از لایسنس‌های کاربر که از کنترلر ارسال شده است.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="rl-dashboard-wrapper">

    <!-- اعلان‌ها -->
    <div id="rl-alert-box" style="display: none;"></div>

    <?php if ( empty( $licenses ) ) : ?>
        
        <div class="rl-empty-state">
            <span class="dashicons dashicons-products" style="font-size: 64px; width: 64px; height: 64px; color: #ccc;"></span>
            <p><?php _e( 'هنوز هیچ لایسنسی برای شما ثبت نشده است.', 'readylicense' ); ?></p>
            <a href="<?php echo wc_get_page_permalink( 'shop' ); ?>" class="rl-btn rl-btn-primary">
                <?php _e( 'مشاهده فروشگاه', 'readylicense' ); ?>
            </a>
        </div>

    <?php else : ?>

        <div class="rl-license-grid">
            <?php foreach ( $licenses as $license ) : ?>
                
                <div class="rl-license-card <?php echo $license['is_expired'] ? 'expired' : 'active'; ?>">
                    
                    <!-- نشانگر روزهای پشتیبانی (شناور در بالا-چپ) -->
                    <div class="rl-support-badge <?php echo empty($license['expires_at']) ? 'lifetime' : ''; ?>">
                        <div class="rl-badge-ring">
                            <span class="days"><?php echo esc_html( $license['support_text'] ); ?></span>
                            <?php if ( ! empty($license['expires_at']) && ! $license['is_expired'] ) : ?>
                                <span class="label"><?php _e( 'پشتیبانی', 'readylicense' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rl-card-main">
                        <!-- ستون راست: اطلاعات محصول و دامنه -->
                        <div class="rl-col-info">
                            <div class="rl-product-thumb">
                                <?php echo $license['product_image']; ?>
                            </div>
                            <div class="rl-product-details">
                                <h3 class="rl-product-title">
                                    <a href="<?php echo esc_url( $license['product_url'] ); ?>">
                                        <?php echo esc_html( $license['product_name'] ); ?>
                                    </a>
                                </h3>
                                <div class="rl-meta-row">
                                    <span class="rl-version">
                                        <?php printf( __( 'نسخه: %s', 'readylicense' ), $license['product_version'] ? $license['product_version'] : '-' ); ?>
                                    </span>
                                </div>
                                <div class="rl-domain-status">
                                    <?php if ( $license['has_domain'] ) : ?>
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <span class="domain-text"><?php echo esc_html( $license['domain'] ); ?></span>
                                        <button class="rl-icon-btn" onclick="rlOpenDomainModal(<?php echo $license['id']; ?>, '<?php echo esc_js( $license['domain'] ); ?>')" title="<?php _e('تغییر دامنه', 'readylicense'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                    <?php else : ?>
                                        <span class="rl-not-set"><?php _e( 'دامنه‌ای ثبت نشده', 'readylicense' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ستون چپ: دکمه‌های عملیاتی -->
                        <div class="rl-col-actions">
                            <div class="rl-action-row">
                                <!-- دکمه دانلود (تاگل کردن بخش دانلود) -->
                                <button class="rl-btn rl-btn-outline-gold" onclick="rlToggleDownloads(this)">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php _e( 'دانلود', 'readylicense' ); ?>
                                </button>
                                
                                <!-- دکمه تنظیمات (فعلاً غیرفعال یا لینک به صفحه محصول) -->
                                <a href="<?php echo esc_url( $license['product_url'] ); ?>" class="rl-btn rl-btn-outline-gray" title="<?php _e('جزئیات', 'readylicense'); ?>">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </a>
                            </div>

                            <div class="rl-action-row">
                                <?php if ( ! $license['has_domain'] ) : ?>
                                    <!-- دکمه افزودن دامنه (سبز) -->
                                    <button class="rl-btn rl-btn-green block" onclick="rlOpenDomainModal(<?php echo $license['id']; ?>, '')">
                                        <span class="dashicons dashicons-plus"></span>
                                        <?php _e( 'افزودن دامنه', 'readylicense' ); ?>
                                    </button>
                                <?php else : ?>
                                    <!-- دکمه کپی لایسنس (آبی) -->
                                    <button class="rl-btn rl-btn-blue block" onclick="rlCopyLicense(this, '<?php echo esc_attr( $license['license_key'] ); ?>')">
                                        <span class="dashicons dashicons-admin-network"></span>
                                        <?php _e( 'کپی لایسنس', 'readylicense' ); ?>
                                        <span class="rl-tooltip"><?php _e( 'کپی شد!', 'readylicense' ); ?></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- بخش بازشو: لینک‌های دانلود -->
                    <div class="rl-downloads-panel" style="display: none;">
                        <div class="rl-downloads-grid">
                            <?php if ( ! empty( $license['links']['fast_install'] ) ) : ?>
                                <a href="<?php echo esc_url( $license['links']['fast_install'] ); ?>" class="rl-dl-item">
                                    <span class="icon dashicons dashicons-update"></span>
                                    <span class="text"><?php _e( 'نصب آسان', 'readylicense' ); ?></span>
                                </a>
                            <?php endif; ?>

                            <a href="<?php echo esc_url( $license['links']['product'] ? $license['links']['product'] : '#' ); ?>" class="rl-dl-item main-dl">
                                <span class="icon dashicons dashicons-archive"></span>
                                <span class="text"><?php _e( 'دانلود محصول', 'readylicense' ); ?></span>
                            </a>

                            <?php if ( ! empty( $license['links']['edu_file'] ) ) : ?>
                                <a href="<?php echo esc_url( $license['links']['edu_file'] ); ?>" class="rl-dl-item">
                                    <span class="icon dashicons dashicons-media-document"></span>
                                    <span class="text"><?php _e( 'فایل آموزشی', 'readylicense' ); ?></span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <!-- مودال مدیریت دامنه -->
    <div id="rl-domain-modal" class="rl-modal">
        <div class="rl-modal-content">
            <span class="rl-close-modal" onclick="rlCloseModal()">&times;</span>
            <div class="rl-modal-header">
                <h3><?php _e( 'مدیریت دامنه', 'readylicense' ); ?></h3>
            </div>
            <div class="rl-modal-body">
                <p><?php _e( 'آدرس دامنه خود را وارد کنید (مثال: example.com).', 'readylicense' ); ?></p>
                
                <form id="rl-domain-form" onsubmit="rlSubmitDomain(event)">
                    <input type="hidden" id="rl-modal-license-id" name="license_id">
                    <input type="hidden" id="rl-modal-action-type" name="action_type" value="add_domain">
                    
                    <div class="rl-input-group">
                        <input type="text" id="rl-domain-input" name="domain" placeholder="example.com" required class="rl-input ltr">
                    </div>

                    <div class="rl-modal-footer">
                        <button type="submit" class="rl-btn rl-btn-green block">
                            <?php _e( 'ذخیره تغییرات', 'readylicense' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
