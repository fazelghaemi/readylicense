from playwright.sync_api import sync_playwright

def verify_dashboard():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        # We need to simulate the HTML content since we can't run a full WordPress + WooCommerce environment here easily.
        # We will create a mock HTML file based on the dashboard template and CSS.

        html_content = """
        <!DOCTYPE html>
        <html dir="rtl" lang="fa-IR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ReadyLicense Dashboard</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                /* Dashicons mockup */
                @font-face {
                    font-family: "dashicons";
                    src: url("https://s.w.org/wp-includes/fonts/dashicons.eot");
                }
                .dashicons {
                    font-family: "dashicons";
                    display: inline-block;
                    line-height: 1;
                    font-weight: 400;
                    font-style: normal;
                    speak: never;
                    text-decoration: inherit;
                    text-transform: none;
                    text-rendering: auto;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                    width: 20px;
                    height: 20px;
                    font-size: 20px;
                    vertical-align: top;
                }
                .dashicons-download:before { content: "\\f316"; }
                .dashicons-admin-generic:before { content: "\\f111"; }
                .dashicons-plus:before { content: "\\f132"; }
                .dashicons-admin-network:before { content: "\\f112"; }
                .dashicons-update:before { content: "\\f463"; }
                .dashicons-archive:before { content: "\\f480"; }
                .dashicons-media-document:before { content: "\\f497"; }
                .dashicons-admin-site-alt3:before { content: "\\f11a"; }
                .dashicons-edit:before { content: "\\f464"; }

                /* Reset */
                body { margin: 0; padding: 20px; font-family: Tahoma, Arial, sans-serif; background: #f0f0f1; }
            </style>
            <!-- Include the actual CSS file content here -->
            <style>
                :root {
                    --rl-primary: #1a73e8;
                    --rl-gold: #ffc107;
                    --rl-gold-bg: #fff3cd;
                    --rl-green: #28a745;
                    --rl-blue: #17a2b8;
                    --rl-gray-text: #6c757d;
                    --rl-border: #e9ecef;
                    --rl-white: #ffffff;
                    --rl-shadow: 0 4px 6px rgba(0,0,0,0.05);
                    --rl-radius: 8px;
                }

                /* Base Wrapper */
                .rl-dashboard-wrapper {
                    font-family: inherit;
                    direction: rtl;
                    max-width: 100%;
                    margin: 0 auto;
                    box-sizing: border-box;
                }

                .rl-dashboard-wrapper * {
                    box-sizing: border-box;
                }

                /* Grid Layout */
                .rl-license-grid {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }

                /* Card Component */
                .rl-license-card {
                    background: var(--rl-white);
                    border: 1px solid var(--rl-border);
                    border-radius: var(--rl-radius);
                    box-shadow: var(--rl-shadow);
                    position: relative;
                    padding: 0;
                    transition: box-shadow 0.3s ease;
                    overflow: hidden;
                }

                .rl-license-card:hover {
                    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
                }

                .rl-card-main {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    gap: 20px;
                }

                /* Right Side: Info */
                .rl-col-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    flex: 1;
                }

                .rl-product-thumb img {
                    width: 60px;
                    height: 60px;
                    border-radius: 6px;
                    object-fit: cover;
                    border: 1px solid var(--rl-border);
                }

                .rl-product-details {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .rl-product-title {
                    margin: 0;
                    font-size: 16px;
                    font-weight: 700;
                    line-height: 1.4;
                }

                .rl-product-title a {
                    text-decoration: none;
                    color: #333;
                    transition: color 0.2s;
                }

                .rl-product-title a:hover {
                    color: var(--rl-primary);
                }

                .rl-meta-row {
                    font-size: 12px;
                    color: var(--rl-gray-text);
                }

                .rl-domain-status {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 13px;
                    color: #333;
                    margin-top: 2px;
                }

                .rl-domain-status .dashicons {
                    font-size: 16px;
                    width: 16px;
                    height: 16px;
                    color: var(--rl-gray-text);
                }

                .rl-not-set {
                    color: var(--rl-gray-text);
                    font-style: italic;
                }

                /* Icon Button (Edit Domain) */
                .rl-icon-btn {
                    background: none;
                    border: none;
                    padding: 2px;
                    cursor: pointer;
                    color: var(--rl-primary);
                    opacity: 0.7;
                    transition: opacity 0.2s;
                }

                .rl-icon-btn:hover {
                    opacity: 1;
                }

                /* Left Side: Actions */
                .rl-col-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    min-width: 160px;
                }

                .rl-action-row {
                    display: flex;
                    gap: 8px;
                }

                /* Buttons */
                .rl-btn {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 13px;
                    font-weight: 500;
                    cursor: pointer;
                    text-decoration: none;
                    transition: all 0.2s;
                    line-height: 1;
                    white-space: nowrap;
                }

                .rl-btn.block {
                    width: 100%;
                }

                /* Button Variants */
                .rl-btn-outline-gold {
                    background: transparent;
                    border: 1px solid var(--rl-gold);
                    color: #bfa900; /* Darker gold for text readability */
                }

                .rl-btn-outline-gold:hover {
                    background: var(--rl-gold-bg);
                }

                .rl-btn-outline-gray {
                    background: transparent;
                    border: 1px solid var(--rl-gray-text);
                    color: var(--rl-gray-text);
                    padding: 8px; /* Square button */
                }

                .rl-btn-outline-gray:hover {
                    background: #f8f9fa;
                    color: #333;
                    border-color: #333;
                }

                .rl-btn-green {
                    background: var(--rl-green);
                    border: 1px solid var(--rl-green);
                    color: #fff;
                }

                .rl-btn-green:hover {
                    background: #218838;
                }

                .rl-btn-blue {
                    background: var(--rl-blue);
                    border: 1px solid var(--rl-blue);
                    color: #fff;
                    position: relative; /* For tooltip */
                }

                .rl-btn-blue:hover {
                    background: #138496;
                }

                /* Tooltip */
                .rl-tooltip {
                    display: none;
                    position: absolute;
                    bottom: 110%;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #333;
                    color: #fff;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    white-space: nowrap;
                }

                /* Support Badge (Top Left) */
                .rl-support-badge {
                    position: absolute;
                    top: 15px;
                    left: 15px;
                    z-index: 2;
                }

                .rl-badge-ring {
                    border: 2px solid var(--rl-gold);
                    background: #fff;
                    border-radius: 50%;
                    width: 60px;
                    height: 60px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    transform: rotate(-10deg);
                }

                .rl-support-badge.lifetime .rl-badge-ring {
                    border-color: var(--rl-green);
                }

                .rl-badge-ring .days {
                    font-size: 14px;
                    font-weight: bold;
                    color: #333;
                }

                .rl-badge-ring .label {
                    font-size: 9px;
                    color: var(--rl-gray-text);
                }

                /* Downloads Panel (Slide Down) */
                .rl-downloads-panel {
                    background: #f8f9fa;
                    border-top: 1px solid var(--rl-border);
                    padding: 20px;
                    animation: slideDown 0.3s ease;
                }

                @keyframes slideDown {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .rl-downloads-grid {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 15px;
                }

                .rl-dl-item {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 16px;
                    background: #fff;
                    border: 1px solid var(--rl-border);
                    border-radius: 4px;
                    text-decoration: none;
                    color: #333;
                    font-size: 13px;
                    transition: border-color 0.2s;
                }

                .rl-dl-item:hover {
                    border-color: var(--rl-primary);
                    color: var(--rl-primary);
                }

                .rl-dl-item.main-dl {
                    border-color: var(--rl-green);
                    color: var(--rl-green);
                    font-weight: 500;
                }

                .rl-dl-item.main-dl:hover {
                    background: #e6f4ea;
                }

                /* Modal */
                .rl-modal {
                    display: none;
                    position: fixed;
                    z-index: 10000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    backdrop-filter: blur(2px);
                    align-items: center;
                    justify-content: center;
                }

                .rl-modal.show {
                    display: flex;
                }

                .rl-modal-content {
                    background-color: #fefefe;
                    padding: 0;
                    border-radius: var(--rl-radius);
                    width: 90%;
                    max-width: 400px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                    position: relative;
                    overflow: hidden;
                    animation: zoomIn 0.2s ease;
                }

                @keyframes zoomIn {
                    from { opacity: 0; transform: scale(0.95); }
                    to { opacity: 1; transform: scale(1); }
                }

                .rl-close-modal {
                    position: absolute;
                    left: 15px;
                    top: 15px;
                    font-size: 24px;
                    color: #aaa;
                    cursor: pointer;
                    z-index: 1;
                }

                .rl-modal-header {
                    background: #f8f9fa;
                    padding: 15px 20px;
                    border-bottom: 1px solid var(--rl-border);
                }

                .rl-modal-header h3 {
                    margin: 0;
                    font-size: 16px;
                }

                .rl-modal-body {
                    padding: 20px;
                }

                .rl-input-group {
                    margin-bottom: 20px;
                }

                .rl-input {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid var(--rl-border);
                    border-radius: 4px;
                    font-size: 14px;
                }

                .rl-input:focus {
                    border-color: var(--rl-primary);
                    outline: none;
                }

                /* Responsive Design */
                @media (max-width: 768px) {
                    .rl-card-main {
                        flex-direction: column;
                        align-items: flex-start;
                        padding-top: 60px; /* Space for the badge */
                    }

                    .rl-col-info {
                        width: 100%;
                    }

                    .rl-col-actions {
                        width: 100%;
                        flex-direction: row;
                        flex-wrap: wrap;
                    }

                    .rl-action-row {
                        flex: 1;
                    }

                    .rl-btn {
                        flex: 1;
                    }

                    .rl-support-badge {
                        top: 10px;
                        left: 10px;
                        transform: scale(0.8);
                        transform-origin: top left;
                    }
                }
            </style>
        </head>
        <body>
            <div class="rl-dashboard-wrapper">

                <!-- Mockup of a single card: Active License with Domain -->
                <div class="rl-license-grid">
                    <div class="rl-license-card active">

                        <div class="rl-support-badge">
                            <div class="rl-badge-ring">
                                <span class="days">172 روز</span>
                                <span class="label">پشتیبانی</span>
                            </div>
                        </div>

                        <div class="rl-card-main">
                            <div class="rl-col-info">
                                <div class="rl-product-thumb">
                                    <img src="https://via.placeholder.com/60" alt="Product">
                                </div>
                                <div class="rl-product-details">
                                    <h3 class="rl-product-title">
                                        <a href="#">افزونه ووکامرس پیشرفته</a>
                                    </h3>
                                    <div class="rl-meta-row">
                                        <span class="rl-version">نسخه: 2.1.0</span>
                                    </div>
                                    <div class="rl-domain-status">
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <span class="domain-text">example.com</span>
                                        <button class="rl-icon-btn" title="تغییر دامنه">
                                            <span class="dashicons dashicons-edit"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="rl-col-actions">
                                <div class="rl-action-row">
                                    <button class="rl-btn rl-btn-outline-gold" onclick="document.querySelector('.rl-downloads-panel').style.display = 'block'">
                                        <span class="dashicons dashicons-download"></span>
                                        دانلود
                                    </button>
                                    <a href="#" class="rl-btn rl-btn-outline-gray" title="جزئیات">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    </a>
                                </div>

                                <div class="rl-action-row">
                                    <button class="rl-btn rl-btn-blue block">
                                        <span class="dashicons dashicons-admin-network"></span>
                                        کپی لایسنس
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rl-downloads-panel" style="display: none;">
                            <div class="rl-downloads-grid">
                                <a href="#" class="rl-dl-item">
                                    <span class="icon dashicons dashicons-update"></span>
                                    <span class="text">نصب آسان</span>
                                </a>

                                <a href="#" class="rl-dl-item main-dl">
                                    <span class="icon dashicons dashicons-archive"></span>
                                    <span class="text">دانلود محصول</span>
                                </a>

                                <a href="#" class="rl-dl-item">
                                    <span class="icon dashicons dashicons-media-document"></span>
                                    <span class="text">فایل آموزشی</span>
                                </a>
                            </div>
                        </div>

                    </div>

                    <!-- Mockup of a second card: No Domain, Lifetime Support -->
                    <div class="rl-license-card active">

                        <div class="rl-support-badge lifetime">
                            <div class="rl-badge-ring">
                                <span class="days">مادام‌العمر</span>
                            </div>
                        </div>

                        <div class="rl-card-main">
                            <div class="rl-col-info">
                                <div class="rl-product-thumb">
                                    <img src="https://via.placeholder.com/60/000000/ffffff" alt="Product">
                                </div>
                                <div class="rl-product-details">
                                    <h3 class="rl-product-title">
                                        <a href="#">قالب وردپرس شرکتی</a>
                                    </h3>
                                    <div class="rl-meta-row">
                                        <span class="rl-version">نسخه: 1.0.5</span>
                                    </div>
                                    <div class="rl-domain-status">
                                        <span class="rl-not-set">دامنه‌ای ثبت نشده</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rl-col-actions">
                                <div class="rl-action-row">
                                    <button class="rl-btn rl-btn-outline-gold">
                                        <span class="dashicons dashicons-download"></span>
                                        دانلود
                                    </button>
                                    <a href="#" class="rl-btn rl-btn-outline-gray">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    </a>
                                </div>

                                <div class="rl-action-row">
                                    <button class="rl-btn rl-btn-green block">
                                        <span class="dashicons dashicons-plus"></span>
                                        افزودن دامنه
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </body>
        </html>
        """

        # Save HTML to a temporary file
        with open("/home/jules/verification/dashboard_mockup.html", "w") as f:
            f.write(html_content)

        page = browser.new_page()
        page.goto("file:///home/jules/verification/dashboard_mockup.html")

        # Take a screenshot of the initial state
        page.screenshot(path="/home/jules/verification/dashboard_initial.png", full_page=True)

        # Interact: Open downloads panel for first card
        page.click(".rl-license-card:first-child .rl-btn-outline-gold")

        # Take a screenshot with downloads open
        page.screenshot(path="/home/jules/verification/dashboard_downloads_open.png", full_page=True)

        browser.close()

if __name__ == "__main__":
    verify_dashboard()
