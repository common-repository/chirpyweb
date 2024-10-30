var glob_user_info, popup, popupStatus, wp_site_url, help_msgs;
var panel_url = "https://app.chirpyweb.com/login";
popupStatus = 0;
help_msgs = { 
	'failed':'ChirpyWeb activation failed for your website.', 
	'success_email_verify_1' : 'Registered and activated ChirpyWeb on your site successfully. A verification email has been sent to your wordpress email ID ',
	'success_email_verify_2' : '. Please verify your email and set password to access your account on ChirpyWeb.'
};

window.addEventListener( "message", receiveMessage, false );

jQuery( document ).ready( function() {
		glob_user_info = JSON.parse( ChirpyWeb['user_info'] );
		wp_site_url = ChirpyWeb['wp_site_url'];
} );

if ('undefined' !== typeof(newUser)) { // New registeration message
	showWelcomeMessage();
}

function showWelcomeMessage() {
	var uinfo = glob_user_info;
	var email = uinfo['user_email'];
	var span = jQuery( '<span />' );
	var utag = jQuery( '<u />' );
	utag.text( email );
	span.text( help_msgs.success_email_verify1 );
	span.append( utag );
	span.append( help_msgs.success_email_verify2 );
	jQuery( '#izTokenMsg' ).append( span );
	jQuery( '#izTokenMsg' ).show();
}
function previewFile( imgId, fileId ) {
	var preview = document.querySelector( imgId );
	var file    = document.querySelector( fileId ).files[0];
	var reader  = new FileReader();

	reader.addEventListener(
		"load", function () {
			preview.src = reader.result;
		}, false
	);

	if ( file ) {
		reader.readAsDataURL( file );
	}
}
function receiveMessage( event ) {
	if ( event.data.status == 400 ) {
		jQuery( '#thanku-box' ).hide();
		jQuery( '#activate' ).hide();
		showcwId( help_msgs.failed );
	}
	if ( event.data.status == 200 ) {
		jQuery( '#token' ).val( event.data.izootoId );
		if ( typeof(event.data.newSignup) != "undefined" && event.data.newSignup == 1 ) {
			jQuery( '#freshUser' ).val( event.data.newSignup );
		}
		 jQuery( '#tokensubmit' ).click();
		 jQuery( "#activate" ).prop( "disabled", true );
		 jQuery( "#activate" ).css( "cursor", "no-drop" );
		 jQuery( "#token" ).prop( "disabled", true );
		 jQuery( "#tokensubmit" ).prop( "disabled", true );
		 jQuery( "#tokensubmit" ).css( "cursor", "no-drop" );
	}

}
function activate() {
	var alert = (ChirpyWeb.alert == undefined) ? 0 : ChirpyWeb.alert;
	var user_info = glob_user_info;
	if( undefined == user_info['user_nicename'] || '' == user_info['user_nicename'].trim() )
		alert = 1;
	if( alert ) {//handle with right message
		var msg = ''; 
		var admin_email = ChirpyWeb['admin_email'];
		var temp_email = user_info['user_email'];
		if( !user_info )
			msg = 'To activate iZooto, '+admin_email+' should exist in your WordPress account. Please add '+admin_email+' as a user and activate the plugin.';
		var data = {
			'action': 'error_alert',
			'message' : msg + ' As a fallback the plugin is being activated from current user '+temp_email+'. Also you can change the WordPress email to that of an existing administrator.'
		};
		if( popupStatus != -1 ) {
			jQuery.post( ajaxurl, data, function(response) {
				log( response );
			});	
			popupStatus = -1;
		}
		jQuery( '#regMsgDiv' ).show();
		jQuery( '#regMsgDiv' ).html( msg );
	}
	var user_name = user_info['user_nicename'];
	var user_url = wp_site_url;
	if( ! user_url.startsWith("http") ) {
		user_url = document.location.protocol+""+user_url;	
	}
	var user_email = user_info['user_email'];
	var openUrl = panel_url + "/wordpress/setup?name=" + user_name + "&email=" + encodeURIComponent( user_email ) + "&url=" + encodeURIComponent( user_url ) + '&ref=1&wp_onboarding=1';
	if ( user_email.trim() == '' || user_url.trim() == '' ) {
		jQuery( '#thanku-box' ).hide();
	  	jQuery( '#tokenDiv' ).show();
	  	jQuery( '#regMsgDiv' ).show();
		jQuery( '#regMsgDiv' ).html( 'Empty email or url' );
		return;
	}
	// popup position config
	var w = 200;
	var h = 200;
	var left = Number( (screen.width / 2) - (w / 2) );
	var tops = Number( (screen.height / 2) - (h / 2) );
	window.open(openUrl,'ChirpyWeb: Web Push Notification Platform for Chrome, Safari, Firefox');
	// PopupCenter( openUrl,'ChirpyWeb: Web Push Notification Platform for Chrome, Safari, Firefox',400,500 );
}

function PopupCenter( url, title, w, h ) {
	// Fixes dual-screen position                         Most browsers      Firefox
	var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
	var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

	var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
	var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

	var left = ((width / 2) - (w / 2)) + dualScreenLeft;
	var top = ((height / 2) - (h / 2)) + dualScreenTop;
	popup = window.open( url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left );
}

function log( msg ) {
	if( 'object' == typeof( msg ) )
		console.log( '[cw-web-push]', msg );
	else
		console.log( '[cw-web-push]' + msg );
}

function showcwId( msg ) {
	jQuery('#regMsgDiv').text( msg);
	jQuery('#regMsgDiv').show();
	jQuery('#tokenDiv').show();
}

function editcwId() {
	jQuery('#token').removeAttr('readonly');
	jQuery('#tokensubmit').show();
	jQuery('#edit-token').hide();
}
