/**
 * Order screen: click a button, confirm, send, show the result.
 * Plain DOM, no third-party library. Every string from the server goes in through textContent.
 *
 * Extension points for other scripts (Pro):
 *   - a button with data-dialog-input="placeholder" gets a text box in the confirm dialog; its value is sent as extra.dialog_input
 *   - before a send, jQuery(document).trigger('webhook_buttons:collect', [extra, buttonElement]) lets scripts add to the extra object
 */
jQuery(function ($) {
	const cfg  = window.webhookButtonsPostEdit || {};
	const i18n = cfg.i18n || {};
	const buttons = $('.webhook-button');

	buttons.on('click', function () {
		const button  = $(this);
		const id      = button.data('button-id');
		const title   = button.text();
		const confirm = String(button.data('confirm')) === '1';
		const input   = button.data('dialog-input');

		if (!confirm) {
			send(button, id, title, '');
			return;
		}
		dialog({
			title: title,
			text: button.data('message') || i18n.confirm,
			input: input ? String(input) : null,
			confirmText: i18n.send,
			cancelText: i18n.cancel,
			onConfirm: function (text) { send(button, id, title, text); }
		});
	});

	function send(button, id, title, dialogInput) {
		setBusy(true);
		const extra = { dialog_input: dialogInput || '' };
		$(document).trigger('webhook_buttons:collect', [extra, button.get(0)]);
		$.ajax({
			type: 'POST',
			url: cfg.ajaxurl,
			dataType: 'json',
			data: {
				action: 'webhook_buttons_send',
				nonce: cfg.nonce,
				button_id: id,
				post_id: cfg.post_id,
				extra: extra
			}
		}).done(function (response) {
			const data = (response && response.data) || {};
			if (response && response.success) {
				toast(format(i18n.sent, title), data.message || '');
			} else {
				fail(title, data);
			}
		}).fail(function (xhr) {
			// wp_send_json_error with a 4xx status lands here; the JSON body is on responseJSON
			fail(title, (xhr.responseJSON && xhr.responseJSON.data) || {});
		}).always(function () {
			setBusy(false);
		});
	}

	function fail(title, data) {
		dialog({
			title: format(i18n.not_sent, title),
			text: data.message || i18n.generic,
			error: true,
			confirmText: i18n.ok
		});
	}

	function format(template, value) {
		return String(template || '%s').replace('%s', value);
	}

	function setBusy(busy) {
		buttons.prop('disabled', busy).toggleClass('webhook-buttons-loading', busy);
	}

	/**
	 * Modal dialog. opts: title, text, input (placeholder or null), confirmText, cancelText, onConfirm(text),
	 * error (bool). Escape or the backdrop closes it.
	 */
	function dialog(opts) {
		const overlay = el('div', 'webhook-buttons-overlay');
		const box     = el('div', 'webhook-buttons-dialog' + (opts.error ? ' is-error' : ''));
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');

		box.appendChild(el('h2', 'webhook-buttons-dialog__title', opts.title));
		box.appendChild(el('p', 'webhook-buttons-dialog__text', opts.text));

		let input = null;
		if (opts.input) {
			input = el('input', 'webhook-buttons-dialog__input');
			input.type = 'text';
			input.placeholder = opts.input;
			box.appendChild(input);
		}

		const actions = el('div', 'webhook-buttons-dialog__actions');
		const ok = el('button', 'button button-primary', opts.confirmText);
		ok.type = 'button';
		actions.appendChild(ok);
		if (opts.cancelText) {
			const cancel = el('button', 'button', opts.cancelText);
			cancel.type = 'button';
			cancel.addEventListener('click', close);
			actions.appendChild(cancel);
		}
		box.appendChild(actions);
		overlay.appendChild(box);
		document.body.appendChild(overlay);

		function close() {
			document.removeEventListener('keydown', onKey);
			overlay.remove();
		}
		function confirm() {
			const text = input ? input.value.trim() : '';
			close();
			if (opts.onConfirm) opts.onConfirm(text);
		}
		function onKey(e) {
			if (e.key === 'Escape') close();
			if (e.key === 'Enter' && input && document.activeElement === input) confirm();
		}
		ok.addEventListener('click', confirm);
		overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
		document.addEventListener('keydown', onKey);
		(input || ok).focus();
	}

	/** Small notice, top right, goes away on its own. */
	function toast(title, text) {
		const t = el('div', 'webhook-buttons-toast');
		t.setAttribute('role', 'status');
		t.appendChild(el('strong', '', title));
		if (text) t.appendChild(el('span', '', text));
		document.body.appendChild(t);
		setTimeout(function () { t.classList.add('is-leaving'); }, 6000);
		setTimeout(function () { t.remove(); }, 7000);
	}

	function el(tag, className, text) {
		const node = document.createElement(tag);
		if (className) node.className = className;
		if (text !== undefined) node.textContent = text;
		return node;
	}
});
