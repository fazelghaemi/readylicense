
                            <div class="mf-settings-area mf-settings-label">
                                <h2 class="mf-settings-title">
                                    <span class="mf-title-icon"><span uk-icon="file-edit" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="file-edit"><path fill="none" stroke="#000" d="M18.65,1.68 C18.41,1.45 18.109,1.33 17.81,1.33 C17.499,1.33 17.209,1.45 16.98,1.68 L8.92,9.76 L8,12.33 L10.55,11.41 L18.651,3.34 C19.12,2.87 19.12,2.15 18.65,1.68 L18.65,1.68 L18.65,1.68 Z"></path><polyline fill="none" stroke="#000" points="16.5 8.482 16.5 18.5 3.5 18.5 3.5 1.5 14.211 1.5"></polyline></svg></span></span>                                    <strong>عناوین</strong>
                                </h2>



                                <form id="general-label-form">
                                    <?php wp_nonce_field('license_settings_nonce', 'license_settings_nonce'); ?>
                                    <div class="mf-description">
                                        <span uk-icon="info" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="info"><path d="M12.13,11.59 C11.97,12.84 10.35,14.12 9.1,14.16 C6.17,14.2 9.89,9.46 8.74,8.37 C9.3,8.16 10.62,7.83 10.62,8.81 C10.62,9.63 10.12,10.55 9.88,11.32 C8.66,15.16 12.13,11.15 12.14,11.18 C12.16,11.21 12.16,11.35 12.13,11.59 C12.08,11.95 12.16,11.35 12.13,11.59 L12.13,11.59 Z M11.56,5.67 C11.56,6.67 9.36,7.15 9.36,6.03 C9.36,5 11.56,4.54 11.56,5.67 L11.56,5.67 Z"></path><circle fill="none" stroke="#000" stroke-width="1.1" cx="10" cy="10" r="9"></circle></svg></span>
                                        این متن‌ها با متن‌های افزونه جایگزین خواهند شد. در صورتی که فیلدی خالی رها شود متن پیشفرض نمایش داده خواهد شد.
                                    </div>

                                    <div class="uk-grid uk-margin-medium-top" >

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_title_menu" name="label_title_menu" value="<?php echo get_option('label_title_menu'); ?>" placeholder="لایسنس های من">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_title_page" name="label_title_page" value="<?php echo get_option('label_title_page'); ?>" placeholder="لیست لایسنس ها">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_title_button" name="label_title_button" value="<?php echo get_option('label_title_button'); ?>" placeholder="ویرایش دامنه">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_title_product" name="label_title_product" value="<?php echo get_option('label_title_product'); ?>" placeholder="نام محصول">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_count_domain" name="label_count_domain" value="<?php echo get_option('label_count_domain'); ?>" placeholder="تعداد تغییر دامنه">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_name_domain" name="label_name_domain" value="<?php echo get_option('label_name_domain'); ?>" placeholder="نام دامنه">
                                        </div>


                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_status_domain" name="label_status_domain" value="<?php echo get_option('label_status_domain'); ?>" placeholder="وضعیت">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="label_edit_domain" name="label_edit_domain" value="<?php echo get_option('label_edit_domain'); ?>" placeholder="عملیات">
                                        </div>


                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="alert_count_day" name="alert_count_day" value="<?php echo get_option('alert_count_day'); ?>" placeholder="روز های باقی مانده">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="alert_renewal" name="alert_renewal" value="<?php echo get_option('alert_renewal'); ?>" placeholder="تمدید">
                                        </div>

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="btn_renewal" name="btn_renewal" value="<?php echo get_option('btn_renewal'); ?>" placeholder="پرداخت">
                                        </div>


                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="title_license_help" name="title_license_help" value="<?php echo get_option('title_license_help'); ?>" placeholder="ثبت دامنه جدید">
                                        </div>


                                         <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="title_license_no_view" name="title_license_no_view" value="<?php echo get_option('title_license_no_view'); ?>" placeholder="در حال حاضر اطلاعاتی برای نمایش به شما وجود ندارد.">
                                        </div>


                                         <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="No_information" name="No_information" value="<?php echo get_option('No_information'); ?>" placeholder="هنوز اطلاعاتی ثبت نشده است.">
                                        </div>

                                         <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="save" name="save" value="<?php echo get_option('save'); ?>" placeholder="ذخیره">
                                        </div>

                                        

                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="active" name="active" value="<?php echo get_option('active'); ?>" placeholder="فعال">
                                        </div>


                                        <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="de_active" name="de_active" value="<?php echo get_option('de_active'); ?>" placeholder="غیرفعال">
                                        </div>


                                          <div class="uk-margin-top uk-width-1-2@m">
                                            <input type="text" class="uk-input" id="order_created_successfully" name="order_created_successfully" value="<?php echo get_option('order_created_successfully'); ?>" placeholder="سفارش تمدید با موفقیت ایجاد شد. لطفاً پرداخت را تکمیل کنید.">
                                        </div>


                                    </div>

                                    <button type="submit" id="save-button-label" class="mf-save uk-button uk-button-primary uk-margin-medium-top">     ذخیره تغییرات
                                        <img class="loader" src="#" width="26" height="26" alt="loader">
                                    </button>
                                    <div id="response"></div>
                                </form>
                                </div>





