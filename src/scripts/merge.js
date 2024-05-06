var successMsg = document.getElementById('successMsg').value;
var errMsg    = document.getElementById('errorMsg').value;

if(errMsg != '') {
    document.getElementById('errorMsg').style.display = "inline-block";
}
if(successMsg != '') {
    document.getElementById('successMsg').style.display = "inline-block";
}
function allowDrop(ev) {
    ev.preventDefault();
}
function drag(ev) {
    ev.dataTransfer.setData("text", ev.target.innerText);
}
var droppedData = [null, null, null];

function updateDroppedDataList() {
    var dataList = droppedData.filter(data => data !== null).join(", ");
    document.getElementById('selected_tables').value = dataList;
}
function drop(ev) {
    ev.preventDefault();
    var data = ev.dataTransfer.getData("text").trim();
    var cardContainer = ev.target.classList.contains('card') ? ev.target : ev.target.closest('.card');
    var cardIndex = Array.from(document.querySelectorAll('.card')).indexOf(cardContainer);
    if (droppedData.includes(data) && droppedData[cardIndex] !== data) {
        alert("This data is currently active on another card. Please use different data.");
        return;
    }
    droppedData[cardIndex] = data;
    updateDroppedDataList();

    cardContainer.innerHTML = '<p>' + data + '</p>';
    var icon = cardContainer.querySelector(".plus-icon");
    if (icon) {
        icon.remove();
    }
}

setTimeout(function() {
    var errMsg = document.getElementById('errorMsg');
    errMsg.style.opacity = '0';

    var successMsg = document.getElementById('successMsg');
    successMsg.style.opacity = '0';

    setTimeout(function() {
        errMsg.style.display = 'none';
        successMsg.style.display = 'none';
    }, 1000);
}, 2000);