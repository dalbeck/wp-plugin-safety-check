document.addEventListener('DOMContentLoaded', function() {
    var purgeButton = document.getElementById('execute-log-purge');
    var purgeOptions = document.getElementById('log-purge-options');

    purgeButton.addEventListener('click', function(e) {
        e.preventDefault();
        var selectedOption = purgeOptions.value;

        // AJAX request to purge logs based on selected option
        var data = {
            'action': 'purge_plugin_action_logs', // The WordPress AJAX action hook
            'purge_option': selectedOption, // The selected purge option
            'nonce': dawpPurgeData.nonce // Nonce for security, passed from PHP
        };

        fetch(dawpPurgeData.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
            },
            body: new URLSearchParams({
                action: 'purge_plugin_action_logs',
                purge_option: selectedOption,
                nonce: dawpPurgeData.nonce
            }).toString()
        })

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Logs purged successfully.');
                window.location.reload();
            } else {
                alert('Failed to purge logs. ' + (data.data || 'Unknown error.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your request.');
        });
    });
});
