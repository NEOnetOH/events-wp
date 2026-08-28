(function () {
	const statusEl = document.getElementById('eswp-action-status');
	const testButton = document.getElementById('eswp-test-connection');

	if (!statusEl || !window.eswpAdmin) {
		return;
	}

	function setStatus(message, state) {
		statusEl.textContent = message;
		statusEl.classList.remove('is-error', 'is-success');
		if (state) {
			statusEl.classList.add(state);
		}
	}

	if (testButton) {
		testButton.addEventListener('click', function () {
			const original = testButton.textContent;
			testButton.disabled = true;
			setStatus('Working…', '');

			const body = new window.URLSearchParams();
			body.set('action', 'eswp_test_connection');
			body.set('nonce', window.eswpAdmin.nonce);

			window
				.fetch(window.eswpAdmin.ajaxUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
					},
					body: body.toString(),
					credentials: 'same-origin',
				})
				.then(function (response) {
					return response.json();
				})
				.then(function (payload) {
					const message =
						(payload.data && payload.data.message) ||
						payload.message ||
						'Request finished.';
					setStatus(message, payload.success ? 'is-success' : 'is-error');
				})
				.catch(function () {
					setStatus('The request failed. Try again.', 'is-error');
				})
				.finally(function () {
					testButton.disabled = false;
					testButton.textContent = original;
				});
		});
	}

	const connectForm = document.getElementById('eswp-connect-form');
	const connectUrl = document.getElementById('eswp-connect-url');
	if (connectForm && connectUrl) {
		connectForm.addEventListener('submit', function () {
			const typed = document.querySelector('[name="eswp_settings[api_base_url]"]');
			if (typed && typed.value) {
				connectUrl.value = typed.value;
			}
		});
	}
})();
