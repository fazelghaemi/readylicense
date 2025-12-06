                            <?php  $domain = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']); ?>
                            <div class="mf-settings-area mf-settings-help">
                                <h2 class="mf-settings-title">
                                    <span class="mf-title-icon"><span uk-icon="bell" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="bell"><path fill="none" stroke="#000" stroke-width="1.1" d="M17,15.5 L3,15.5 C2.99,14.61 3.79,13.34 4.1,12.51 C4.58,11.3 4.72,10.35 5.19,7.01 C5.54,4.53 5.89,3.2 7.28,2.16 C8.13,1.56 9.37,1.5 9.81,1.5 L9.96,1.5 C9.96,1.5 11.62,1.41 12.67,2.17 C14.08,3.2 14.42,4.54 14.77,7.02 C15.26,10.35 15.4,11.31 15.87,12.52 C16.2,13.34 17.01,14.61 17,15.5 L17,15.5 Z"></path><path fill="none" stroke="#000" d="M12.39,16 C12.39,17.37 11.35,18.43 9.91,18.43 C8.48,18.43 7.42,17.37 7.42,16"></path></svg></span></span>                                    <strong>راهنما</strong>
                                </h2>
                                <h6>پس از نصب افزونه و پیکربندی اولیه موارد زیر را انجام دهید:</h6>
                                <ul>
                                    <li>1- مرحله اول، برروی دکمه (دانلود فایل لایسنس) کلیک کنید و فایل را از حالت فشرده خارج کنید و در هاستی که افزونه نصب شده است در روت اصلی یعنی public-html آپلود کنید.</li>
                                    <li>2- در مرحله دوم، مرچند کد محصولی را که میخواهید لایسنس گذاری کنید را کپی و در کد زیر بجای متن (مرچنت کد محصول را اینجا قرار دهید) جایگزین کنید.</li>
                                    <li>3- نام دامنه ای که افزونه برروی آن نصب شده به صورت خودکار در خط چهارم قرار میگیرد دقت کنید فایل ابتدایی باید طبق کد مسیر دهی شده باشد.</li>
                                    <li>4- در مرحله سوم، کد پایین را در فایل پی اچ پی پروژه خود (منظور قالب یا افزونه) که میخواهید به فروش برسانید قرار دهید.</li>
                                    <li>*نکته : اگر نصب را در یک پوشه در روت اصلی یا ساب دامنه انجام میدهید. فایل نیز در پوشه در کنار فایل wp-config.php قرار گرفته شود و در آدرس دهی دامنه در کد (کد لایسنس گذاری پروژه) نیز نام پوشه را قرار دهید.</li>
                                    <li>*پنل کاربری: شما می توانید از شورت کد [show_licenses] نیز برای نمایش پنل کاربری استفاده کنید.</li>
                                    <li>*راهنمای تصویری: درصورتی که با موارد بالا مشکل دارید ویدیو آموزشی را مشاهده کنید.</li>

                                </ul>

                                <div class="btn-license">
                                    <a href="<?php echo plugin_dir_url(__FILE__) . '../assets/check-license.zip'; ?>" class="uk-button uk-button-primary">دانلود فایل لایسنس</a>
                                </div>

                                <div class="uk-margin-medium-top">
                                    <label class="uk-form-label uk-margin-bottom">کد لایسنس گذاری پروژه:</label>
                                    <textarea class="uk-textarea mf-scripts code-textarea" dir="ltr" rows="30">



                                    */Start License

                                    $domain = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);
                                    $transient_key = 'license_check_result_' . md5($domain);
                                    $response_data = get_transient($transient_key);

                                    if (!$response_data || empty($response_data['success'])) {
                                        $license_code = 'مرچنت کد محصول را اینجا قرار دهید';

                                        $post_data = http_build_query([
                                            'domain' => $domain,
                                            'license_code' => $license_code
                                        ]);
                                        $api_url = 'https://<?php echo $domain; ?>/check-license.php';

                                        if (function_exists('wp_remote_post')) {
                                            $response = wp_remote_post($api_url, [
                                                'body' => $post_data,
                                                'timeout' => 15,
                                                'sslverify' => false //
                                            ]);

                                            if (is_wp_error($response)) {
                                                $response_data = ['success' => false, 'data' => 'خطا در اتصال به API: ' . $response->get_error_message()];
                                            } else {
                                                $response_data = json_decode(wp_remote_retrieve_body($response), true);
                                            }
                                        } else {
                                            $ch = curl_init();
                                            curl_setopt($ch, CURLOPT_URL, $api_url);
                                            curl_setopt($ch, CURLOPT_POST, 1);
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                                            $response = curl_exec($ch);
                                            if ($response === false) {
                                                $response_data = ['success' => false, 'data' => 'خطا در اتصال به API: ' . curl_error($ch)];
                                            } else {
                                                $response_data = json_decode($response, true);
                                            }
                                            curl_close($ch);
                                        }

                                        if (!empty($response_data['success'])) {
                                            $cache_duration = rand(300, 600); // زمان را به دلخواه تنظیم کنید 5 تا 10 دقیقه
                                            set_transient($transient_key, $response_data, $cache_duration);
                                        }
                                    }
                                    if ($response_data && !empty($response_data['success'])) {

                                        // لایسنس معتبر است، عملیات مورد نظر را اینجا قرار دهید
                                        // مثلاً: echo '<p>لایسنس معتبر است!</p>';

                                    } else {
                                        $message = !empty($response_data['data']) ? htmlspecialchars($response_data['data']) : 'خطایی رخ داده است.';
                                        if (function_exists('add_action')) {
                                            add_action('admin_notices', function() use ($message) {
                                                echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
                                            });
                                        } else {
                                            echo '<p>' . esc_html($message) . '</p>';
                                        }
                                    }


                                    */End License


                                    </textarea>
                                </div>

                                </div>





