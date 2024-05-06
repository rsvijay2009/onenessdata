function toggleCheckboxes(source) {
    checkboxes = document.getElementsByName('columns[]');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var checkboxes = document.querySelectorAll('.highlightCheck');

    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            var row = checkbox.closest('tr');

            if (checkbox.checked) {
                row.classList.add('highlighted');
            } else {
                row.classList.remove('highlighted');
            }
        });
    });
});