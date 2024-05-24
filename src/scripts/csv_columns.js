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

    //Make the datatype as mandatory, while choose the columns from the uploaded csv
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxesWithoutSelectAll = document.querySelectorAll('.highlightCheck:not(#selectAll)');
    const datatypeSelectors = document.querySelectorAll('.form-select');

    checkboxesWithoutSelectAll.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            validateCheckbox(this);
        });
    });

    selectAllCheckbox.addEventListener('change', function() {
        checkboxesWithoutSelectAll.forEach(checkbox => {
            checkbox.checked = this.checked;
            validateCheckbox(checkbox);
        });
    });

    function validateCheckbox(checkbox) {
        const datatypeSelector = document.getElementById(`datatype_${checkbox.id}`);
        if (checkbox.checked) {
            datatypeSelector.style.borderWidth = "medium";
            datatypeSelector.style.borderColor = "red";
            datatypeSelector.setAttribute('required', 'required');
        } else {
            datatypeSelector.removeAttribute('required');
        }

        datatypeSelector.addEventListener('change', function(e) {
            if(e.target.value == '') {
                datatypeSelector.style.borderWidth = "medium";
                datatypeSelector.style.borderColor = "red";
            } else {
                datatypeSelector.style.borderColor = "";
            }
        });
    }
});