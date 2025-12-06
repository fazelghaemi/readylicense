/**
 * ReadyLicense Frontend Script
 * Optimized specifically for speed and performance.
 * Vanilla JS (No jQuery dependency).
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Performance Check: Does the license container exist?
    // اگر المان اصلی در صفحه نیست، هیچ کدی اجرا نکن.
    const container = document.getElementById('readylicense-user-panel');
    if (!container) return;

    // متغیرهای دریافتی از PHP (که در فایل اصلی تعریف کردیم)
    const config = window.rl_front || {};

    /**
     * مدیریت کلیک‌ها با الگوی Event Delegation
     * این روش حافظه مرورگر را بسیار کمتر اشغال می‌کند
     */
    container.addEventListener('click', function(e) {
        
        // دکمه فعال‌سازی
        if (e.target.closest('.rl-btn-activate')) {
            e.preventDefault();
            const btn = e.target.closest('.rl-btn-activate');
            handleAction('activate', btn);
        }

        // دکمه مدیریت دامین
        if (e.target.closest('.rl-btn-manage-domain')) {
            e.preventDefault();
            const btn = e.target.closest('.rl-btn-manage-domain');
            toggleDomainModal(btn.dataset.licenseId);
        }
    });

    /**
     * تابع اصلی ارسال درخواست به سرور (Fetch API)
     */
    async function handleAction(actionType, element) {
        const licenseKey = element.dataset.key;
        const originalText = element.innerText;
        
        // حالت لودینگ
        element.disabled = true;
        element.innerText = '...';
        element.classList.add('rl-loading');

        try {
            const formData = new URLSearchParams();
            formData.append('action', 'rl_handle_frontend_request'); // اکشن واحد در PHP
            formData.append('security', config.nonce);
            formData.append('request_type', actionType);
            formData.append('license_key', licenseKey);

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
                // آپدیت UI بدون رفرش صفحه
                updateLicenseUI(element, data.data);
            } else {
                showMessage('error', data.data.message || 'Error occurred');
            }

        } catch (error) {
            console.error('RL Error:', error);
            showMessage('error', 'ارتباط با سرور برقرار نشد.');
        } finally {
            // بازگشت به حالت عادی
            element.disabled = false;
            element.innerText = originalText;
            element.classList.remove('rl-loading');
        }
    }

    /**
     * نمایش پیام‌ها (جایگزین alert و کتابخانه‌های سنگین)
     */
    function showMessage(type, text) {
        const msgBox = document.createElement('div');
        msgBox.className = `rl-notification rl-${type}`;
        msgBox.innerText = text;
        
        container.prepend(msgBox);

        // حذف خودکار بعد از ۳ ثانیه (برای جلوگیری از reflow زیاد)
        setTimeout(() => msgBox.remove(), 3000);
    }

    /**
     * به‌روزرسانی بخشی از رابط کاربری
     */
    function updateLicenseUI(element, newData) {
        // مثال: تغییر کلاس دکمه یا تغییر وضعیت متن
        const row = element.closest('.rl-license-row');
        if (row && newData.new_status) {
            row.querySelector('.rl-status').innerText = newData.new_status;
            row.classList.toggle('active', newData.new_status === 'active');
        }
    }

    // Modal Logic (Simple & Lightweight)
    function toggleDomainModal(licenseId) {
        const modal = document.getElementById('rl-domain-modal');
        if(modal) {
            modal.classList.toggle('open');
            // اینجا می‌توانید لیست دامین‌ها را با یک درخواست جداگانه لود کنید
            // فقط زمانی که مودال باز می‌شود
        }
    }
});