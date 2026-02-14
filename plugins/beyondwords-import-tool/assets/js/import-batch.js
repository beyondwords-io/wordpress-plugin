/* global jQuery, ajaxurl, wp, beyondwordsImportBatch */
jQuery(document).ready(function($) {
	var config = beyondwordsImportBatch;
	var totalRecords = config.totalRecords;
	var batchSize = config.batchSize;
	var processed = 0;
	var nonce = config.nonce;
	var isRunning = false;
	var retries = 0;
	var maxRetries = 2;
	var failedRecords = [];

	function processBatch() {
		if (isRunning) {
			return;
		}
		isRunning = true;

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			timeout: 60000,
			data: {
				action: 'beyondwords_import_batch',
				nonce: nonce,
				offset: processed,
				batch_size: batchSize
			},
			success: function(response) {
				isRunning = false;
				retries = 0;

				if (response.success) {
					processed = response.data.processed;
					var percent = Math.round((processed / totalRecords) * 100);
					$('#beyondwords-import-progress-bar').css('width', percent + '%');
					$('#beyondwords-import-status').text(wp.i18n.sprintf(config.i18n.processing, processed, totalRecords));

					if (response.data.complete) {
						$('#beyondwords-import-progress-container').hide();
						$('#beyondwords-import-complete').show();

						var successCount = response.data.success_count || 0;
						var failedCount = response.data.failed_count || 0;
						$('#beyondwords-import-summary').text(wp.i18n.sprintf(config.i18n.successSummary, successCount, successCount * 3));

						if (failedCount > 0 && response.data.failed) {
							$('#beyondwords-import-failed-summary').text(wp.i18n.sprintf(config.i18n.failedSummary, failedCount));
							var rows = '';
							failedRecords = response.data.failed;
							$.each(failedRecords, function(i, record) {
								rows += '<tr>';
								rows += '<td>' + $('<span>').text(record.source_id).html() + '</td>';
								rows += '<td>' + $('<span>').text(record.source_url).html() + '</td>';
								rows += '<td>' + $('<span>').text(record.project_id).html() + '</td>';
								rows += '<td>' + $('<span>').text(record.content_id).html() + '</td>';
								rows += '</tr>';
							});
							$('#beyondwords-import-failed-rows').html(rows);
							$('#beyondwords-import-failed-report').show();

							$('.beyondwords-copy-failed').on('click', function() {
								copyFailed($(this));
							});
						}

						// Ensure transients are cleaned up after results are displayed.
						// This provides additional safety beyond the server-side cleanup
						// that happens in the final batch response.
						cleanupTransients();
					} else {
						processBatch();
					}
				} else {
					$('#beyondwords-import-progress-container').hide();
					$('#beyondwords-import-error').show();
					$('#beyondwords-import-error-message').text(response.data.message || config.i18n.ajaxError);
					// Clean up transients on error to prevent them from lingering.
					cleanupTransients();
				}
			},
			error: function() {
				isRunning = false;
				retries++;

				if (retries <= maxRetries) {
					// Exponential backoff: 2s, 4s.
					var delay = Math.pow(2, retries) * 1000;
					setTimeout(processBatch, delay);
					return;
				}

				$('#beyondwords-import-progress-container').hide();
				$('#beyondwords-import-error').show();
				$('#beyondwords-import-error-message').text(config.i18n.networkError);
				// Clean up transients on error to prevent them from lingering.
				cleanupTransients();
			}
		});
	}

	function copyFailed($button) {
		var $success = $button.siblings('.beyondwords-copy-success');
		var json = JSON.stringify(failedRecords, null, 2);
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(json).then(function() {
				showCopySuccess($button, $success);
			}).catch(function() {
				fallbackCopy(json, $button, $success);
			});
		} else {
			fallbackCopy(json, $button, $success);
		}
	}

	function fallbackCopy(text, $button, $success) {
		var textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.setAttribute('readonly', '');
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();
		document.execCommand('copy');
		document.body.removeChild(textarea);
		showCopySuccess($button, $success);
	}

	function showCopySuccess($button, $success) {
		$success.show().attr('aria-hidden', 'false');
		if (wp && wp.a11y && wp.a11y.speak) {
			wp.a11y.speak(config.i18n.copiedMessage);
		}
		setTimeout(function() {
			$success.hide().attr('aria-hidden', 'true');
		}, 3000);
	}

	function cleanupTransients() {
		// Silent cleanup call - no need to handle response or errors
		// as transients are already cleaned up server-side in the final batch.
		// This is just an additional safety measure.
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'beyondwords_import_cleanup',
				nonce: nonce
			},
			error: function(jqXHR, textStatus, errorThrown) {
				// Log cleanup errors to console for debugging, but don't disrupt UX.
				if (window.console && console.error) {
					console.error('BeyondWords: Cleanup error:', textStatus, errorThrown);
				}
			}
		});
	}

	processBatch();
});
