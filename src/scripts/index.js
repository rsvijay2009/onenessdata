const projectName = document.getElementById('projectName');
const projectList = document.getElementById('projectList');

projectName.addEventListener('input', function() {
    if (projectName.value.trim() !== '') {
        projectList.disabled = true;
    } else {
        projectList.disabled = false;
    }
});
projectList.addEventListener('change', function() {
    const selectedValue = this.value;
    const selectedText = projectList.options[projectList.selectedIndex].text;
    document.getElementById('projectNameFromList').value = selectedText;

    if (selectedValue.trim() !== '') {
        projectName.disabled = true;
    } else {
        projectName.disabled = false;
    }
});

// setTimeout(function() {
//     var p = document.getElementById('errorMsg');
//     p.style.opacity = '0';

//     setTimeout(function() {
//         p.style.display = 'none';
//     }, 1000);
// }, 3000);