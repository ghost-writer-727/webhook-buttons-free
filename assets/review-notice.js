/* Review request notice: "Maybe later" snoozes, "Do not ask again" / "Leave a review" retire it, the X snoozes. */
( function () {
	var box = document.querySelector( '.webhook-buttons-review' );
	if ( ! box ) {
		return;
	}
	function tell( what ) {
		var body = new URLSearchParams( { action: 'webhook_buttons_review_dismiss', nonce: box.dataset.nonce, what: what } );
		fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } );
	}
	box.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( '[data-review]' );
		if ( link ) {
			if ( link.getAttribute( 'href' ) === '#' ) {
				e.preventDefault();
			}
			tell( link.dataset.review );
			box.style.display = 'none';
			return;
		}
		if ( e.target.closest( '.notice-dismiss' ) ) {
			tell( 'later' );
		}
	} );
} )();
