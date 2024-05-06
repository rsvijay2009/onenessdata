$(document).ready(function() {
    $('.edit-btn').click(function() {
        var row = $(this).closest('tr');
        row.find('.editable').show();
        row.find('.non-editable').hide();
        $(this).hide();
        row.find('.save-btn').show();
    });

    $('.save-btn').click(function() {
        var row = $(this).closest('tr');
        var updatedData = {};
        row.find('input.editable').each(function() {
            var input = $(this);
            var key = input.prev().text().trim(); // Assuming column name is in the span
            updatedData[key] = input.val();
        });

        // AJAX call to save data
        $.post('ajax_save_table_data.php', {data: updatedData, table: '<?= $tableName ?>', tableId: '<?= $tableId ?>'}, function(response) {
            row.find('.editable').hide();
            row.find('.non-editable').each(function() {
                $(this).text($(this).next().val());
            }).show();
            row.find('.edit-btn').show();
            row.find('.save-btn').hide();
        });
    });
});