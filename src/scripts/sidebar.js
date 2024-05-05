document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('.btn-toggle');

    toggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var badge = this.querySelector('.badge');
            if (badge.textContent.trim() === '+') {
                badge.textContent = '-';
            } else {
                badge.textContent = '+';
            }
        });
    });
    showNotification();
});
function showNotification() {
    const notificationElement = document.getElementById('notification');
    const notificationContent = document.getElementById('notification-content').value;

    if(notificationContent != '') {
        notificationElement.textContent = notificationContent
        notificationElement.style.display = 'block';

        // Set a timer to hide the notification after 5 seconds
        setTimeout(function() {
            notificationElement.style.display = 'none';
        }, 2000);
        notificationElement.value = '';
    }
}

function confirmProjectDeletion(projectId, projectName) {
    console.table(projectId, projectName)
    if (confirm('Are you sure you want to delete '+ projectName + ' project?')) {
        const fullURL = window.location.pathname;
        document.getElementById('deleteProjectId').value = projectId;
        window.sidebarForm.submit();
    }
}

function confirmTableDeletion(tableId, tableName) {
    console.table(tableId, tableName)
    
    if (confirm('Are you sure you want to delete '+ tableName + ' table?')) {
        const fullURL = window.location.pathname;
        document.getElementById('deleteTableId').value = tableId;
        window.sidebarForm.submit();
    }
}



