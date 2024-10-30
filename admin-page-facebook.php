<div class="wrap">
    <h1>Facebook Settings</h1>
    <div class="designed-by-wrapper">
        <p>Plugin designed and developed by<br/><a href="https://www.balcom-vetillo.com/" target="_blank">Balcom-Vetillo Design</a>.</p>
        <a href="https://www.balcom-vetillo.com/" target="_blank"><img src="<?php echo plugins_url('images/BVD-Logo-vert.png', __FILE__); ?>" /></a>
    </div>
    <div class="main-content-wrapper">
        <?php
        //Check if there is a Pro Key in the DB
        //if ($this->check_pro_key()) {
            ?>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&tab=basic-settings'); ?>" class="nav-tab <?php echo $active_tab == 'basic-settings' ? 'nav-tab-active' : ''; ?>">Basic Settings</a>
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&tab=display-options'); ?>" class="nav-tab <?php echo $active_tab == 'display-options' ? 'nav-tab-active' : ''; ?>">Display Options</a>
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&tab=design-options'); ?>" class="nav-tab <?php echo $active_tab == 'design-options' ? 'nav-tab-active' : ''; ?>">Design Options</a>
                <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&tab=shortcode'); ?>" class="nav-tab <?php echo $active_tab == 'shortcode' ? 'nav-tab-active' : ''; ?>">Shortcode and Shortcode Options</a>
            </h2>

            <?php
            if ($this->submit_success) {
                ?>
                <div id="social-feeds-message" class="updated">
                    <p>Social Feeds Plugin Settings Updated.</p>
                </div>
                <?php
            }
            ?>

            <div class="social-feeds-settings-container">
                <?php
                switch ($active_tab) {
                    case 'basic-settings' :
                        ?>
                        <div class="social-feeds-login-btn-wrapper">
                            <?php
                            //Check Facebook creds
                            $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=verify";
                            $data = $this->url_get_contents($request);
                            $data = json_decode($data);
                            //print_r($data);
                            if ($data->verify->is_valid) { //Facebook creds are valid
                                //update_option("bvads_social_feed_facebook_user_id", $data->user->id);
                                ?>
                                <div class="facebook-connection-status-message">Facebook Connection Status: <strong>Connected</strong></div>
                                <div class="social-feeds-disconnect-btn-wrapper">
                                    <a href="<?php echo get_admin_url(null, 'admin.php?page=bvd-social-feeds-facebook&disconnect_account=facebook'); ?>">Disconnect Account</a>
                                </div>
                                <div class="social-feeds-admin-page-section social-feeds-current-user-wrapper">
                                    <div class="social-feeds-current-user-item">
                                        <strong>Current Page ID:</strong> <?php echo get_option("bvads_social_feed_facebook_page_id"); ?>
                                    </div>
                                    <div class="social-feeds-current-user-item">
                                        <strong>Current Page Name:</strong> <?php echo get_option("bvads_social_feed_facebook_page_name"); ?>
                                    </div>
                                </div>
                                <?php
                            } else { //Facebook creds are not valid, so login
                                ?>
                                <div class="facebook-connection-status-message">Facebook Connection Status: <strong>Not Connected</strong></div>
                                <a class="button button-primary button-large" href="<?php echo SFR_URL; ?>?sfr_uuid=<?php echo $this->uuid; ?>&callback=<?php echo $this->callback_url(); ?>&sfr_facebook=facebook_login">Authorize Facebook Account</a>
                                <div class="facebook-permissions-text">
                                    <p>You will be asked if you want to allow this plugin to manage your pages. This will simply allow the plugin to see what Facebook pages you manage. It will <strong>NOT</strong> allow the plugin to post anything to Facebook.<br/></p>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="social-feeds-admin-page-section social-feeds-extra-settings-wrapper">
                            <div class="social-feeds-section-title">
                                Facebook Page Information
                            </div>
                            <form action="" method="post">
                                <input type="hidden" name="bvd-post-action" value="set-facebook-api-settings" />
                                <?php
                                if ($data->verify->is_valid) {
                                    ?>
                                    <div class="form-section">
                                        <div class="form-section-left">
                                            <label for="facebook-pages-list">Your Facebook Pages</label>
                                        </div>
                                        <div class="form-section-right">
                                            <?php
                                            $request = SFR_URL_FACEBOOK . "?sfr_uuid=" . get_option("bvads_social_feed_uuid") . "&callback=" . $this->callback_url() . "&action=get_page_list";
                                            $data_pages = json_decode($this->url_get_contents($request));
                                            ?>
                                            <select name="facebook-pages-list" id="facebook-pages-list">
                                                <option value="0">Facebook page</option>
                                                <?php
                                                if(is_array($data_pages->pages->data)) {
                                                    foreach($data_pages->pages->data as $page) {
                                                        ?>
                                                        <option value="<?php echo $page->id; ?>|<?php echo $page->name; ?>"><?php echo $page->name; ?></option>
                                                        <?php
                                                    }
                                                } else {
                                                    ?>
                                                        <option value="0">no array</option>    
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                            <div class="form-section-right-hint">These are all the Facebook pages you manage. Select one to display a feed from that page. A page selected here will override a page entered below.</div>
                                        </div>
                                        <div style="clear:left;"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="form-section">
                                    <div class="form-section-left">
                                        <label for="page-id">Facebook Page</label>
                                    </div>
                                    <div class="form-section-right">
                                        <input type="text" name="page-id" id="page-id" placeholder="Page ID">
                                        <div class="form-section-right-hint">Enter the page id of any public Facebook page here. This will be ignored if a page is selected from the drop down list above.<br/>You can copy the last part of the page's url here<br/>ex. If your page's url is: www.facebook.com/xxxxxxxxx, all you would need to enter in this box is: xxxxxxxxx</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <input type="submit" value="Submit" />
                                </div>
                            </form>
                        </div>
                        <?php
                        break;

                    case 'display-options' :
                        ?>
                        <div class="social-feeds-admin-page-section social-feeds-display-options-wrapper">
                            <?php
                            $number_items = get_option("bvads_social_feed_facebook_number_items");
                            $show_header = get_option("bvads_social_feed_facebook_show_header");
                            $show_more = get_option("bvads_social_feed_facebook_show_more_link");
                            $header_text = get_option("bvads_social_feed_facebook_header_text");
                            $more_text = get_option("bvads_social_feed_facebook_more_link_text");
                            ?>
                            <div class="social-feeds-section-title">
                                Display Options
                            </div>
                            <form action="" method="post">
                                <input type="hidden" name="bvd-post-action" value="set-facebook-display-options" />
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="number-display">Number of Feed Items to Display</label>
                                    </div>
                                    <div class="form-section-right">
                                    <?php
                                    if(!$this->check_pro_key()) {
                                        if ($number_items) {
                                            if($number_items == 1) {
                                                $option1 = 'selected';
                                            } else {
                                                $option1 = '';
                                            }
                                            if($number_items == 2) {
                                                $option2 = 'selected';
                                            } else {
                                                $option2 = '';
                                            }
                                            ?>                                            
                                            <select name="number-display" id="number-display">
                                                <option value="0">Select Number of Feed Items</option>
                                                <option value="1" <?php echo $option1; ?>>1</option>
                                                <option value="2" <?php echo $option2; ?>>2</option>
                                            </select>
                                            <?php
                                        } else {
                                            ?>
                                            <select name="number-display" id="number-display">
                                                <option value="0">Select Number of Feed Items</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                            </select>
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">Unlock the ability to display any number of feed items with a Pro Key.</div>
                                        <?php
                                    } else {
                                        if ($number_items) {
                                            ?>
                                            <input type="text" name="number-display" id="number-display" placeholder="Default: 2" value="<?php echo $number_items; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="number-display" id="number-display" placeholder="Default: 2" />
                                            <?php
                                        }
                                    }
                                    ?>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <?php
                                    if ($show_header) {
                                        ?>
                                        <input type="checkbox" name="show-header" id="show-header" value="1" checked />
                                        <?php
                                    } else {
                                        ?>
                                        <input type="checkbox" name="show-header" id="show-header" value="1" />
                                        <?php
                                    }
                                    ?>
                                    <label for="show-header">Show Header</label>
                                    <div class="form-section-right-hint">Set the default text below.</div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="header-text">Header Text</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($header_text) {
                                            ?>
                                            <input type="text" name="header-text" id="header-text" placeholder="Header Text" value="<?php echo $header_text; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="header-text" id="header-text" placeholder="Header Text" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will set what the feed header says. Default is: Recent From Facebook</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <?php
                                    if ($show_more) {
                                        ?>
                                        <input type="checkbox" name="show-more-link" id="show-more-link" value="1" checked />
                                        <?php
                                    } else {
                                        ?>
                                        <input type="checkbox" name="show-more-link" id="show-more-link" value="1" />
                                        <?php
                                    }
                                    ?>
                                    <label for="show-more-link">Show More Link</label>
                                    <div class="form-section-right-hint">Checking this option will show a button that links back to your Facebook page.</div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="more-link-text">More Link Text</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($more_text) {
                                            ?>
                                            <input type="text" name="more-link-text" id="more-link-text" placeholder="More Link Text" value="<?php echo $more_text; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="more-link-text" id="more-link-text" placeholder="More Link Text" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will set what the show more button says. Default is: view more</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <input type="submit" value="Submit" />
                                </div>
                            </form>
                        </div>
                        <?php
                        break;
                        
                    case 'design-options' :
                        ?>
                        <div class="social-feeds-admin-page-section social-feeds-display-options-wrapper">
                            <?php
                            $header_background = get_option("bvads_social_feed_facebook_header_background");
                            $header_font_color = get_option("bvads_social_feed_facebook_header_font_color");
                            $border_bottom = get_option("bvads_social_feed_facebook_border_bottom");
                            $border_bottom_weight = get_option("bvads_social_feed_facebook_border_bottom_weight");
                            $btn_background = get_option("bvads_social_feed_facebook_more_button_background");
                            $btn_background_hover = get_option("bvads_social_feed_facebook_more_button_background_hover");
                            $btn_font_color = get_option("bvads_social_feed_facebook_more_button_font_color");
                            $btn_font_color_hover = get_option("bvads_social_feed_facebook_more_button_font_color_hover");
                            ?>
                            <div class="social-feeds-section-title">
                                Design Options
                            </div>
                            <form action="" method="post">
                                <input type="hidden" name="bvd-post-action" value="set-facebook-design-options" />
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="header-background">Feed Header Background Color</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($header_background) {
                                            ?>
                                            <input type="text" name="header-background" id="header-background" placeholder="Header Background Color" value="<?php echo $header_background; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="header-background" id="header-background" placeholder="Header Background Color" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the background color of the Recent From Facebook header.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="header-font-color">Feed Header Font Color</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($header_font_color) {
                                            ?>
                                            <input type="text" name="header-font-color" id="header-font-color" placeholder="Header Font Color" value="<?php echo $header_font_color; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="header-font-color" id="header-font-color" placeholder="Header Font Color" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the font color of the Recent From Facebook header.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="border-bottom">Feed Item Bottom Border Color</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($border_bottom) {
                                            ?>
                                            <input type="text" name="border-bottom" id="border-bottom" placeholder="Border Bottom" value="<?php echo $border_bottom; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="border-bottom" id="border-bottom" placeholder="Border Bottom" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the color of the border between each feed item.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="border-bottom">Feed Item Bottom Border Thickness (px)</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($border_bottom_weight) {
                                            ?>
                                            <input type="text" name="border-bottom-weight" id="border-bottom-weight" placeholder="Border Bottom Weight" value="<?php echo $border_bottom_weight; ?>" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="border-bottom-weight" id="border-bottom-weight" placeholder="Border Bottom Weight" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the thickness (weight) of the border between each feed item. Any number can be entered here, however for the best look, a lower number is better.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="btn-background">Feed More Button Background Color</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($btn_background) {
                                            ?>
                                            <input type="text" name="btn-background" id="btn-background" value="<?php echo $btn_background; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="btn-background" id="btn-background" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the background color of the optional view more button.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="btn-background">Feed More Button Background Hover Color</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($btn_background_hover) {
                                            ?>
                                            <input type="text" name="btn-background-hover" id="btn-background-hover" value="<?php echo $btn_background_hover; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="btn-background-hover" id="btn-background-hvoer" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the background color of the optional view more button when hovered.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="btn-font-color">Feed More Button Font Color</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($btn_font_color) {
                                            ?>
                                            <input type="text" name="btn-font-color" id="btn-font-color" value="<?php echo $btn_font_color; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="btn-font-color" id="btn-font-color" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the font color of the optional view more button.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <div class="form-section-left">
                                    <label for="btn-font-color-hover">Feed More Button Font Color Hover</label>
                                    </div>
                                    <div class="form-section-right">
                                        <?php
                                        if($btn_font_color_hover) {
                                            ?>
                                            <input type="text" name="btn-font-color-hover" id="btn-font-color-hover" value="<?php echo $btn_font_color_hover; ?>" class="color-field" />
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" name="btn-font-color-hover" id="btn-font-color-hover" class="color-field" />
                                            <?php
                                        }
                                        ?>
                                        <div class="form-section-right-hint">This will control the font color of the optional view more button when hovered.</div>
                                    </div>
                                    <div style="clear:left;"></div>
                                </div>
                                <div class="form-section">
                                    <input type="submit" value="Submit" />
                                </div>
                            </form>
                            
                            <form action="" method="post" id="design-options-reset-form">
                                <input type="hidden" name="bvd-post-action" value="reset-facebook-design-options" />
                                <div class="social-feeds-section-title">
                                    Reset Design Options
                                </div>
                                <div class="sf-design-options-reset-text">
                                    <p>Reset all design options to their defaults. This cannot be undone.</p>
                                </div>
                                <div class="form-section">
                                    <input type="submit" value="Reset" />
                                </div>
                            </form>
                        </div>
                        <?php
                        break;

                    case 'shortcode' :
                        ?>
                        <div class="social-feeds-admin-page-section social-feeds-shortcode-doc-wrapper">
                            <div class="social-feeds-section-title">
                                Shortcode
                            </div>
                            <div class="social-feeds-shortcode-doc">
                                <div class="social-feeds-shortcode">
                                    <p>Base shortcode to display feed with all preset options</p>
                                    <pre>[bvd-facebook-feed]</pre>
                                </div>
                                <div class="social-feeds-shortcode-options">
                                    <div class="social-feeds-section-title">
                                        Shortcode Options
                                    </div>
                                    <div class="social-feeds-shortcode-message">
                                        <strong>Warning!</strong> Options set in the shortcode will override the options set in the plugin settings.
                                    </div>
                                    <div class="social-feeds-shortcode-option-item">
                                        <strong>count</strong> -- integer -- Number of feed items to display. -- Default: 2
                                        <div class="shortcode-example">Example: [bvd-facebook-feed count=2]</div>
                                        <?php
                                        if(!$this->check_pro_key()) {
                                            ?>
                                            <div class="shortcode-no-pro-key">You will only be able to display a max of 2 feed items. If a number higher than 2 is passed with this option, the value will be reset to 2. To unlock the ability to display any number of feed items, you need a pro key.</div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="social-feeds-shortcode-option-item">
                                        <strong>header</strong> -- integer -- Choose to show or hide the feed header. Set to 0 to hide, 1 to show. -- Default: 0
                                        <div class="shortcode-example">Example: [bvd-facebook-feed header=0]</div>
                                    </div>
                                    <div class="social-feeds-shortcode-option-item">
                                        <strong>header_text</strong> -- string -- Choose what the header should say. -- Default: "Recent From Facebook"
                                        <div class="shortcode-example">Example: [bvd-facebook-feed header=1 header_text="Recent From Facebook"]</div>
                                    </div>
                                    <div class="social-feeds-shortcode-option-item">
                                        <strong>more_link</strong> -- integer -- Choose to show or hide the more button link. Set to 0 to hide, 1 to show. -- Default: 0
                                        <div class="shortcode-example">Example: [bvd-facebook-feed more_link=0]</div>
                                    </div>
                                    <div class="social-feeds-shortcode-option-item">
                                        <strong>more_link_text</strong> -- string -- Choose what the more link should say. -- Default: "read more"
                                        <div class="shortcode-example">Example: [bvd-facebook-feed more_link=1 more_link_text="read more"]</div>
                                    </div>
                                </div>
                                <div class="social-feeds-shortcode-usage-wrapper">
                                    <div class="social-feeds-section-title">
                                        Shortcode Usage
                                    </div>
                                    <p>You can place the shortcode in the Wordpress visual editor on any page or post (make sure the visual editor tab is selected) just as it appears above.</p>
                                    <p>To use the shortcode in a template file, use this PHP code: <code>echo do_shortcode("[bvd-facebook-feed]");</code></p>
                                </div>
                            </div>
                        </div>
                        <?php
                        break;
                }
                ?>
            </div>
            <?php
        /*} else { //There isn't a Pro Key
            echo '<strong>Facebook is a Pro Feature.</strong>';
        }*/
        ?>
    </div>
</div>