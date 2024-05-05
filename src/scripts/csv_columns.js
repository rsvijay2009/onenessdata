function toggleCheckboxes(source) {
    checkboxes = document.getElementsByName('columns[]');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}