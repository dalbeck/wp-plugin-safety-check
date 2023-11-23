document.addEventListener('DOMContentLoaded', function() {
    var exportButton = document.getElementById('export-csv');
    if (exportButton) {
        exportButton.addEventListener('click', function(e) {
            e.preventDefault();

            var data = [['User ID', 'User Email', 'Timestamp', 'Plugin Name', 'Plugin Action']];
            var rows = document.querySelectorAll('.wp-list-table tbody tr');

            rows.forEach(function(row) {
                var rowData = [];
                var cells = row.querySelectorAll('td');
                cells.forEach(function(cell) {
                    rowData.push(cell.textContent);
                });
                data.push(rowData);
            });

            var csv = data.map(function(row) {
                return row.join(',');
            }).join('\n');

            var date = new Date();
            var timestamp = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + '_' + date.getHours() + '-' + date.getMinutes() + '-' + date.getSeconds();
            var filename = 'plugin-actions-log_' + timestamp + '.csv';

            var hiddenElement = document.createElement('a');
            hiddenElement.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            hiddenElement.target = '_blank';
            hiddenElement.download = filename;
            hiddenElement.click();
        });
    }
});
