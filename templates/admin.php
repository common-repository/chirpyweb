<?php
/**
 * Display Html
 *
 * @file Display For menu
 * Description of module of what it is doing
 *
 * @package WordPress
 */

$opfunction = get_option( 'cw-settings' );
?>	
<div class="chirpyweb-wordpress">
<div class="header">
	<img src="<?php echo esc_url( CHIRPYWEB_BASE_URL . 'assets/images/Chirpy Web Logo - Full.png' ); ?>" width="250">
</div>
<div class="body">
<h4 class="subheading-1"><?php esc_html_e( 'Enable Web Push on your WordPress website', 'chirypweb' ); ?></h4>
<!-- Active status  -->
<?php
if ( ! empty( $opfunction['token'] ) ) {
	$site_url = ! empty( $opfunction['site_url'] ) ? esc_url( $opfunction['site_url'] ) : '';
}
?>
<div class="shadow-card card-container-height">
	<div class="section-two">
		<div class="left">
			<div class="grey-border width-400" style="margin-top: 16px;">
					<form method="post">
						<input type="hidden" name="action" value="cw_submit_data">
						<div class="inline-content" style="margin-top: 6px;">
					<?php settings_fields( 'cw-settings' ); ?>		
					<?php do_settings_sections( 'cw-settings' ); ?>
					<?php
					if ( ! empty( $opfunction['token'] ) ) {
						?>
						<input type="text" name="token"  id="token" class="form-control" placeholder="<?php esc_attr_e( 'Place ID here to enable Web Push.', 'chirypweb' ); ?>" style="margin-right: 5px;" value="<?php echo esc_attr( $opfunction['token'] ); ?> " readonly>

						<a href="javascript:void(0)" id="edit-token" onclick="editcwId()" class="stroked-button icon-button" style="margin-left: 5px;"><i class="material-icons" style="font-size: 20px;">edit</i></a>
						<button type='submit' style="cursor:pointer;display:none" name='tokensubmit' id='tokensubmit' value='submit' class="primary-button" style="margin-left: 5px; ">Save</button>
						<?php
					} else {
						?>
					<input type="text" name="token"  id="token" class="form-control" placeholder="<?php esc_attr_e( 'Place ID here to enable Web Push.', 'chirypweb' ); ?>" style="margin-right: 5px;" value="<?php echo esc_attr( $opfunction['token'] ); ?>">
					<button type='submit' style="cursor:pointer;" name='tokensubmit' id='tokensubmit' value='submit' class="primary-button" style="margin-left: 5px;">Save</button>
					<?php } ?>
					<input id='freshUser' name='freshUser' hidden>
					<?php wp_nonce_field( 'cw_submit_action', 'submit_post' ); ?>
				</div>
			</form>
			</div>
				<div class="instructions width-500" style="margin-top: 30px;">
					<div>
						<label style="margin-bottom: 20px; display:inline-block;"><?php esc_html_e( 'Follow the steps below to get your ChirpyWeb Website Key.', 'chirypweb' ); ?></label>
							<li class="text-body" style="margin-bottom: 5px; list-style: none;">- <a href="<?php echo esc_url( 'https://app.chirpyweb.com/login' ); ?>" target="_blank"><?php esc_html_e( 'Log in', 'chirpyweb' ); ?></a> <?php esc_html_e( 'to your existing ChirpyWeb account or create a', 'chirpyweb' ); ?> <a href="<?php echo esc_url( 'https://app.chirpyweb.com/sign-up' ); ?>" target="_blank"> <?php esc_html_e( 'new ChirpyWeb account', 'chirpyweb' ); ?></a>.</li>
							<li class="text-body" style="margin-top: 5px; list-style: none;">-  <?php esc_html_e( 'Go to ChirpyWeb Account > Websites > How to Install > Website Key. Copy the Website Key &amp; paste it in the field above.', 'chirpyweb' ); ?>
							</li>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>