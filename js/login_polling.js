/**
 * This script polls the server every 500ms to check if the user
 * has been successfully authenticated.
 * 
 * @author SecSign Technologies Inc.
 * @copyright 2019 SecSign Technologies Inc.
 */
(function (OC, window, $) {
	'use strict';

	function addCancelListener() {
		$(".two-factor-secondary").click(function () {
			$.post(OC.generateUrl('/apps/secsignid/cancel/')),
				function () {
					//console.log("cancelled auth session");
				}
		})
	}

	$(document).ready(function () {
		addCancelListener();
		let polling = new Promise((resolve, reject) => {
			var getState = function (attempts, resolve, reject) {
				attempts += 1;
				if (attempts > 100) {
					reject('Login not authenticated. Please try again');
					return;
				}
				$.get(OC.generateUrl('/apps/secsignid/state/')).success(function (data) {
					if (data.accepted) {
						resolve(data);
					} else {
						setTimeout(getState, 500, attempts, resolve, reject);
					}
				}).error(function (data) {
					reject(data);
					setTimeout(getState, 500, attempts, resolve, reject);
				});
			}
			getState(0, resolve, reject);
		});
		polling.then((message) => {
			if (message.accepted) {
				$("button").click();
				$("button").prop("disabled", true);
			}
		});

	});
})(OC, window, jQuery);