document.addEventListener('DOMContentLoaded', function() {
    var countdownInterval; // Define countdownInterval in a higher scope
    var modalTimeout = dawpModalData.timeout || 10000; // Use the timeout from PHP, default to 10000

    function handleClick(e) {
        e.preventDefault();

        // Store the URL of the deactivate or activate link
        var actionUrl = this.getAttribute('href');

        // Get the plugin name
        var pluginName = this.closest('tr').querySelector('strong').textContent;

        // Determine the action (activate or deactivate)
        var action = this.parentElement.classList.contains('activate') ? 'activate' : 'deactivate';

        // Get the domain URL
        var domainUrl = window.location.hostname;

        // Create the modal
        var modalHtml = '<div id="action-warning-modal">' +
                            '<div id="modal-content">' +
                                '<h1 id="modal-header">Attention</h1>' +
                                '<p id="modal-body">Are you sure you want to <strong>' + action + '</strong> the <strong>' + pluginName + '</strong> plugin on <strong>' + domainUrl + '</strong>?</p>' +
                                '<button class="'+ action +'" id="proceed-action">' + (action.charAt(0).toUpperCase() + action.slice(1)) + ' Plugin</button>' +
                                '<button id="cancel-action">Deny</button>' +
                                '<p id="countdown-message"></p>' +
                                '<a id="cancel-countdown">Cancel</a>' +
                            '</div>' +
                        '</div>';

        // Add the modal to the body
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Prevent scrolling
        document.body.style.overflow = 'hidden';

        var proceedAction = document.getElementById('proceed-action');
        var cancelAction = document.getElementById('cancel-action');
        var countdownMessage = document.getElementById('countdown-message');
        var cancelCountdown = document.getElementById('cancel-countdown');
        var modal = document.getElementById('action-warning-modal');

        // Show the modal
        modal.style.display = 'block';

        // If the user chooses to proceed
        proceedAction.addEventListener('click', function() {
            this.disabled = true;
            cancelAction.style.display = 'none';
            this.style.display = 'none';

            if (dawpModalData.timerDisabled) {
                // Timer is disabled, directly proceed with action
                window.location.href = actionUrl;
            } else {
                var countdown = modalTimeout / 1000;
                countdownMessage.textContent = 'Plugin ' + action + ' will complete in ' + countdown + ' seconds';
                countdownMessage.style.display = 'block';
                cancelCountdown.style.display = 'block';

                countdownInterval = setInterval(function() {
                    countdown--;
                    countdownMessage.textContent = 'Plugin ' + action + ' will complete in ' + countdown + ' seconds';
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        // AJAX request
                        fetch(dawpModalData.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: 'action=log_plugin_' + action + '&plugin_name=' + encodeURIComponent(pluginName) + '&plugin_action=' + action + '&nonce=' + dawpModalData.nonce
                        })
                        .then(function(response) {
                            // Redirect to action URL
                            window.location.href = actionUrl;
                        })
                        .catch(function(error) {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                            modal.parentNode.removeChild(modal);
                            document.body.style.overflow = 'auto';
                        });
                    }
                }, 1000);
            }
        });

        // If the user chooses to cancel the countdown
        cancelCountdown.addEventListener('click', function() {
            clearInterval(countdownInterval);
            modal.parentNode.removeChild(modal);
            document.body.style.overflow = 'auto';
        });

        // If the user chooses to cancel
        cancelAction.addEventListener('click', function() {
            modal.parentNode.removeChild(modal);
            document.body.style.overflow = 'auto';
        });
    }

    // Attach event listeners to activate and deactivate links
    var deactivateLinks = document.querySelectorAll('.deactivate a');
    var activateLinks = document.querySelectorAll('.activate a');

    deactivateLinks.forEach(function(link) {
        link.addEventListener('click', handleClick);
    });

    activateLinks.forEach(function(link) {
        link.addEventListener('click', handleClick);
    });
});
