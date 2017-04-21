<div id="ga_access_code_modal" class="ga-modal" tabindex="-1">
    <div class="ga-modal-dialog">
        <div class="ga-modal-content">
            <div class="ga-modal-header">
                <span id="ga_close" class="ga-close">&times;</span>
                <h4 class="ga-modal-title"><?php _e( 'Please paste the access code obtained from Google below:' ) ?></h4>
            </div>
            <div class="ga-modal-body">
                <label for="ga_access_code"><strong><?php _e( 'Access Code' ); ?></strong>:</label>
                &nbsp;<input id="ga_access_code_tmp" type="text"
                             placeholder="<?php _e( 'Paste your access code here' ) ?>"/>
                <div class="ga-loader-wrapper">
                    <div class="ga-loader"></div>
                </div>
            </div>
            <div class="ga-modal-footer">
                <button id="ga_btn_close" type="button" class="button">Close</button>
                <button type="button" class="button-primary"
                        id="ga_save_access_code"
                        onclick="ga_popup.saveAccessCode( event )"><?php _e( 'Save Changes' ); ?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="wrap ga-wrap">
    <h2>Google Analytics - <?php _e( 'Settings' ); ?></h2>
    <div class="ga_container">
	    <?php if ( ! empty( $data['error_message'] ) ) : ?>
		    <?php echo $data['error_message']; ?>
	    <?php endif; ?>
        <form id="ga_form" method="post" action="options.php">
	        <?php settings_fields( 'googleanalytics' ); ?>
            <input id="ga_access_code" type="hidden"
                   name="<?php echo esc_attr( Ga_Admin::GA_OAUTH_AUTH_CODE_OPTION_NAME ); ?>" value=""/>
            <table class="form-table">
                <tr valign="top">
	                <?php if ( ! empty( $data['popup_url'] ) ): ?>
                        <th scope="row">
                            <label <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'class="label-grey ga-tooltip"' : '' ?>><?php echo _e( 'Google Profile' ) ?>
                                :
                                <span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
                            </label>
                        </th>
                        <td <?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'class="ga-tooltip"' : ''; ?>>
                            <button id="ga_authorize_with_google_button" class="button-primary"
	                            <?php if ( Ga_Helper::are_features_enabled() ) : ?>
                                    onclick="ga_popup.authorize( event, '<?php echo esc_attr( $data['popup_url'] ); ?>' )"
	                            <?php endif; ?>
	                            <?php echo( ( esc_attr( $data[ Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ] ) || ! Ga_Helper::are_features_enabled() ) ? 'disabled="disabled"' : '' ); ?>
                            ><?php _e( 'Authenticate
						with Google' ) ?>
                            </button>
                            <span class="ga-tooltiptext"><?php _e( $tooltip ); ?></span>
			                <?php if ( ! empty( $data[ Ga_Admin::GA_WEB_PROPERTY_ID_MANUALLY_OPTION_NAME ] ) ): ?>
                                <div class="ga_warning">
                                    <strong><?php _e( 'Notice' ) ?></strong>:&nbsp;<?php _e( 'Please uncheck the "Manually enter Tracking ID" option to authenticate and view statistics.' ); ?>
                                </div>
			                <?php endif; ?>
                        </td>
	                <?php endif; ?>
                </tr>

                <?php if ( ! empty( $data['ga_accounts_selector'] ) ): ?>
                    <th scope="row"><?php echo _e( 'Google Analytics Account' ) ?>:</th>
                    <td><?php echo $data['ga_accounts_selector']; ?></td>
                <?php endif; ?>

                <?php if (!empty($data[Ga_Admin::GA_ACCOUNT_AND_DATA_ARRAY])) :
                    foreach ($data[Ga_Admin::GA_ACCOUNT_AND_DATA_ARRAY] as $account_email => $account) : ?>
                        <tr>
                            <th><?php echo esc_attr($account_email) ?></th>
                            <td><strong>Include in stats and reports</strong></td>
                        </tr>
                        <?php foreach ($account->account_summaries as $account_summary):
                            foreach ($account_summary->webProperties as $property): ?>
                            <tr>
                                <th> <?php echo esc_attr($account_summary->name) ?> </th>
                                <td>
                                    <?php foreach ($property->profiles as $profile) : ?>
                                        <div class="checkbox">
                                            <label class="ga_checkbox_label"
                                                   for="checkbox_<?php echo $profile->id; ?>">
                                                <input id="checkbox_<?php echo $profile->id; ?>" type="checkbox"
                                                       name="<?php echo esc_attr( Ga_Admin::GA_SELECTED_VIEWS . "[" . $profile->id . "]" ); ?>"
                                                       id="<?php echo esc_attr( $profile->id ); ?>"
                                                    <?php if (!empty($profile->include_in_stats)){ echo esc_attr( ( $profile->include_in_stats ? 'checked="checked"' : '' ) ); }?> />&nbsp;
                                                <?php echo esc_html( $profile->name . ' (' . $profile->id . ')'); ?>
                                                <span class="ga-tooltiptext"><?php _e( $tooltip ); ?></span>

                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach;?>
                <?php endif; ?>

            </table>

            <p class="submit">
                <input type="submit" class="button-primary"
                       value="<?php _e( 'Save Changes' ) ?>"/>
            </p>
        </form>
    </div>
</div>
<script type="text/javascript">

</script>
