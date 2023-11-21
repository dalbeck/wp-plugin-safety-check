    jQuery(document).ready(function($) {
        // Handle deactivation and activation
        $('.deactivate a, .activate a').click(function(e) {
            e.preventDefault();

            // Store the URL of the deactivate or activate link
            var actionUrl = $(this).attr('href');

            // Get the plugin name
            var pluginName = $(this).closest('tr').find('strong').text();

            // Determine the action (activate or deactivate)
            var action = $(this).parent().hasClass('activate') ? 'activate' : 'deactivate';

            // Get the domain URL
            var domainUrl = window.location.hostname;

            // Create the modal
            var modalHtml = '<div id=\"action-warning-modal\" style=\"display: none; background: rgba(0, 0, 0, 0.8); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999;\">';
            modalHtml += '<div style=\"background: white; border-radius: 5px; padding: 20px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 300px; max-width: 90%; box-sizing: border-box; text-align: center;\">';
            modalHtml += '<h1 style=\"color: red; margin-bottom: 20px;\">WARNING</h1>';
            modalHtml += '<p style=\"font-size: 16px; margin-bottom: 20px;\">Are you sure you want to <strong style=\"color: blue;\">' + action + '</strong> the <strong style=\"color: blue;\">' + pluginName + '</strong> plugin on <strong style=\"color: blue;\">' + domainUrl + '</strong>?</p>';
            modalHtml += '<button id=\"proceed-action\" style=\"background: ' + (action == 'activate' ? 'green' : 'red') + '; border: none; color: white; padding: 10px 20px; margin-right: 10px; cursor: pointer;\">' + (action.charAt(0).toUpperCase() + action.slice(1)) + ' Plugin</button>';
            modalHtml += '<button id=\"cancel-action\" style=\"background: gray; border: none; color: white; padding: 10px 20px; cursor: pointer;\">Deny</button>';
            modalHtml += '<p id=\"countdown-message\" style=\"display: none; margin-top: 20px;\"></p>';
            modalHtml += '<a id=\"cancel-countdown\" style=\"display: none; margin-top: 20px; color: red; font-size: 15px; font-weight: bold; cursor: pointer;\">Cancel</a>';
            modalHtml += '</div></div>';

            // Add the modal to the body
            $('body').append(modalHtml);

            // Prevent scrolling
            $('body').css('overflow', 'hidden');

            // Show the modal
            $('#action-warning-modal').show();

            // If the user chooses to proceed, send an AJAX request and then redirect to the action URL
            $('#proceed-action').click(function() {
                $(this).prop('disabled', true);
                $('#cancel-action').hide();
                $(this).hide();

                var countdown = 10;
                $('#countdown-message').text('Plugin ' + action + ' will complete in ' + countdown + ' seconds').show();
                $('#cancel-countdown').show();

                var countdownInterval = setInterval(function() {
                    countdown--;
                    $('#countdown-message').text('Plugin ' + action + ' will complete in ' + countdown + ' seconds');
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        $.post(da_ajax_object.ajax_url, {
                            action: 'log_plugin_' + action,
                            plugin_name: pluginName,
                            plugin_action: action,
                            nonce: da_ajax_object.nonce
                        }, function(response) {
                            setTimeout(function() {
                                window.location.href = actionUrl;
                            }, 3000);
                        });
                    }
                }, 1000);

                // If the user chooses to cancel the countdown, stop the countdown and hide the modal
                $('#cancel-countdown').click(function() {
                    clearInterval(countdownInterval);
                    $('#action-warning-modal').remove();
                    $('body').css('overflow', 'auto');
                });
            });

            // If the user chooses to cancel, hide the modal and allow scrolling
            $('#cancel-action').click(function() {
                $('#action-warning-modal').remove();
                $('body').css('overflow', 'auto');
            });
        });
    });
