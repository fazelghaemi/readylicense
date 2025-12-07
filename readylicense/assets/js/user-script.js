/**
 * ReadyLicense Frontend Script
 * Optimized specifically for speed and performance.
 * Vanilla JS (No jQuery dependency).
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Performance Check: Does the license container exist?
    // اگر المان اصلی در صفحه نیست، هیچ کدی اجرا نکن.
    const container = document.querySelector('.rl-dashboard-wrapper');
    if (!container) return;

    // متغیرهای دریافتی از PHP (که در فایل اصلی تعریف کردیم)
    const config = window.rl_front || {};

    // --- توابع عمومی (Global Scope) برای استفاده در onclick HTML ---

    /**
     * کپی کردن لایسنس
     */
    window.rlCopyLicense = function(btn, text) {
        navigator.clipboard.writeText(text).then(() => {
            const tooltip = btn.querySelector('.rl-tooltip');
            if (tooltip) {
                tooltip.style.display = 'block';
                setTimeout(() => tooltip.style.display = 'none', 2000);
            }
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    };

    /**
     * باز/بسته کردن پنل دانلود
     */
    window.rlToggleDownloads = function(btn) {
        const card = btn.closest('.rl-license-card');
        const panel = card.querySelector('.rl-downloads-panel');
        
        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            btn.classList.add('active');
        } else {
            panel.style.display = 'none';
            btn.classList.remove('active');
        }
    };

    /**
     * باز کردن مودال دامین
     */
    window.rlOpenDomainModal = function(licenseId, currentDomain) {
        const modal = document.getElementById('rl-domain-modal');
        const input = document.getElementById('rl-domain-input');
        const hiddenId = document.getElementById('rl-modal-license-id');

        if (modal && input && hiddenId) {
            input.value = currentDomain || '';
            hiddenId.value = licenseId;
            modal.classList.add('show');
            input.focus();
        }
    };

    /**
     * بستن مودال
     */
    window.rlCloseModal = function() {
        const modal = document.getElementById('rl-domain-modal');
        if (modal) {
            modal.classList.remove('show');
        }
    };

    /**
     * ثبت فرم دامین
     */
    window.rlSubmitDomain = async function(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerText;
        
        // حالت لودینگ
        submitBtn.disabled = true;
        submitBtn.innerText = '...';

        try {
            const formData = new URLSearchParams();
            formData.append('action', 'rl_handle_frontend_request');
            formData.append('security', config.nonce);
            formData.append('request_type', 'add_domain'); // اکشن واحد برای افزودن/آپدیت

            // جمع‌آوری داده‌های فرم
            new FormData(form).forEach((value, key) => {
                formData.append(key, value);
            });

            const response = await fetch(config.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showMessage('success', data.data.message);
                if (data.data.reload) {
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    rlCloseModal();
                }
            } else {
                showMessage('error', data.data.message || 'Error occurred');
            }

        } catch (error) {
            console.error('RL Error:', error);
            showMessage('error', config.strings.error || 'ارتباط با سرور برقرار نشد.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
        }
    };

    // --- توابع داخلی ---

    /**
     * نمایش پیام‌ها (جایگزین alert و کتابخانه‌های سنگین)
     */
    function showMessage(type, text) {
        // حذف پیام‌های قبلی
        const oldMsg = document.querySelector('.rl-notification');
        if(oldMsg) oldMsg.remove();

        const msgBox = document.createElement('div');
        msgBox.className = `rl-notification rl-${type}`; // استایل این کلاس باید در CSS باشد (قبلاً بود، الان مطمئن می‌شویم)
        msgBox.style.cssText = `
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            background-color: ${type === 'success' ? '#e6f4ea' : '#fce8e6'};
            color: ${type === 'success' ? '#137333' : '#c5221f'};
            border: 1px solid ${type === 'success' ? '#a8dab5' : '#f6aea9'};
        `;
        msgBox.innerText = text;
        
        const alertBox = document.getElementById('rl-alert-box');
        if (alertBox) {
            alertBox.style.display = 'block';
            alertBox.appendChild(msgBox);

            // اسکرول به بالا برای دیدن پیام
            alertBox.scrollIntoView({ behavior: 'smooth' });
        }

        // حذف خودکار
        setTimeout(() => msgBox.remove(), 4000);
    }

    // بستن مودال با کلیک بیرون آن
    window.onclick = function(event) {
        const modal = document.getElementById('rl-domain-modal');
        if (event.target == modal) {
            rlCloseModal();
        }
    };
});
