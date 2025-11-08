jQuery( document ).ready( function ( $ ) {
	$( '#use_external_db' )
		.on( 'change', function () {
			const isChecked = $( this ).is( ':checked' );
			const rows = $(
				'#db_host, #db_name, #db_user, #db_password, #db_prefix'
			).closest( 'tr' );

			if ( isChecked ) {
				rows.show();
			} else {
				rows.hide();
			}
		} )
		.trigger( 'change' );

	$( '.notice.is-dismissible' ).on( 'click', '.notice-dismiss', function () {
		$( this ).closest( '.notice' ).fadeOut();
	} );

	$( 'form' ).on( 'submit', function ( e ) {
		const requiredFields = $( this ).find( '[required]' );
		let hasEmpty = false;

		requiredFields.each( function () {
			if ( ! $( this ).val() ) {
				hasEmpty = true;
				$( this ).css( 'border-color', 'red' );
			} else {
				$( this ).css( 'border-color', '' );
			}
		} );

		if ( hasEmpty ) {
			e.preventDefault();
			alert( 'Please fill in all required fields.' );
			return false;
		}
	} );

	setTimeout( function () {
		$( '.notice.is-dismissible' ).fadeOut();
	}, 5000 );
} );
