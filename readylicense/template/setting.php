
                            <div class="mf-settings-area mf-settings-setting">
                                <h2 class="mf-settings-title">
                                    <span class="mf-title-icon"><span uk-icon="settings" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="settings"><ellipse fill="none" stroke="#000" cx="6.11" cy="3.55" rx="2.11" ry="2.15"></ellipse><ellipse fill="none" stroke="#000" cx="6.11" cy="15.55" rx="2.11" ry="2.15"></ellipse><circle fill="none" stroke="#000" cx="13.15" cy="9.55" r="2.15"></circle><rect x="1" y="3" width="3" height="1"></rect><rect x="10" y="3" width="8" height="1"></rect><rect x="1" y="9" width="8" height="1"></rect><rect x="15" y="9" width="3" height="1"></rect><rect x="1" y="15" width="3" height="1"></rect><rect x="10" y="15" width="8" height="1"></rect></svg></span></span>                                    <strong>پیکربندی</strong>
                                </h2>

                                <form id="general-settings-form">
                                    <?php wp_nonce_field('license_settings_nonce', 'license_settings_nonce'); ?>
                                    <div class="uk-margin-top">
                                    <label class="uk-form-label">حداکثر تعداد مجاز ثبت دامنه
                                        <sup uk-tooltip="title: به کاربر اجازه میدهید تعداد مشخصی اجازه ویرایش دامنه را داشته باشند.; pos: top"><span uk-icon="info" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="info"><path d="M12.13,11.59 C11.97,12.84 10.35,14.12 9.1,14.16 C6.17,14.2 9.89,9.46 8.74,8.37 C9.3,8.16 10.62,7.83 10.62,8.81 C10.62,9.63 10.12,10.55 9.88,11.32 C8.66,15.16 12.13,11.15 12.14,11.18 C12.16,11.21 12.16,11.35 12.13,11.59 C12.08,11.95 12.16,11.35 12.13,11.59 L12.13,11.59 Z M11.56,5.67 C11.56,6.67 9.36,7.15 9.36,6.03 C9.36,5 11.56,4.54 11.56,5.67 L11.56,5.67 Z"></path><circle fill="none" stroke="#000" stroke-width="1.1" cx="10" cy="10" r="9"></circle></svg></span></sup>
                                    </label>
                                    <input class="uk-input" type="number"  id="opt_number_license" name="opt_number_license" value="<?php echo get_option('opt_number_license'); ?>" placeholder="3">
                                </div>


                                    <div class="uk-margin-top">
                                        <label class="uk-form-label">عنوان راهنمای ثبت دامنه
                                            <sup uk-tooltip="title: قبل از ثبت دامنه در پنجره باز شده نمایش داده میشود.; pos: top"><span uk-icon="info" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="info"><path d="M12.13,11.59 C11.97,12.84 10.35,14.12 9.1,14.16 C6.17,14.2 9.89,9.46 8.74,8.37 C9.3,8.16 10.62,7.83 10.62,8.81 C10.62,9.63 10.12,10.55 9.88,11.32 C8.66,15.16 12.13,11.15 12.14,11.18 C12.16,11.21 12.16,11.35 12.13,11.59 C12.08,11.95 12.16,11.35 12.13,11.59 L12.13,11.59 Z M11.56,5.67 C11.56,6.67 9.36,7.15 9.36,6.03 C9.36,5 11.56,4.54 11.56,5.67 L11.56,5.67 Z"></path><circle fill="none" stroke="#000" stroke-width="1.1" cx="10" cy="10" r="9"></circle></svg></span></sup>
                                        </label>
                                        <input class="uk-input" type="text"  id="title_license_help" name="title_license_help" value="<?php echo get_option('title_license_help'); ?>" placeholder="لطفا برای ثبت دامنه به موارد زیر توجه کنید:">
                                    </div>


                                <div class="uk-margin-medium-top">
                                    <label class="uk-form-label">توضیحات مربوط به ثبت دامنه
                                        <sup uk-tooltip="title: هنگام ثبت دامنه به کاربر نمایش داده میشود. همچنین با قرار دادن دو نقطه : انتهای هر متن، خروجی هر متن در یک خط قرار میگیرد.; pos: top"><span uk-icon="info" class="uk-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="info"><path d="M12.13,11.59 C11.97,12.84 10.35,14.12 9.1,14.16 C6.17,14.2 9.89,9.46 8.74,8.37 C9.3,8.16 10.62,7.83 10.62,8.81 C10.62,9.63 10.12,10.55 9.88,11.32 C8.66,15.16 12.13,11.15 12.14,11.18 C12.16,11.21 12.16,11.35 12.13,11.59 C12.08,11.95 12.16,11.35 12.13,11.59 L12.13,11.59 Z M11.56,5.67 C11.56,6.67 9.36,7.15 9.36,6.03 C9.36,5 11.56,4.54 11.56,5.67 L11.56,5.67 Z"></path><circle fill="none" stroke="#000" stroke-width="1.1" cx="10" cy="10" r="9"></circle></svg></span></sup>
                                    </label>
                                    <textarea class="uk-textarea mf-scripts" id="opt_comment_license" name="opt_comment_license" rows="5"><?php echo get_option('opt_comment_license'); ?></textarea>
                                </div>

                                    <button type="submit" id="save-button" class="mf-save uk-button uk-button-primary uk-margin-medium-top">     ذخیره تغییرات
                                        <img class="loader" src="#" width="26" height="26" alt="loader">
                                    </button>
                                    <div id="response"></div>
                                </form>
                                </div>





