function allowDrop(ev) {
    ev.preventDefault();
}
function drag(ev) {
    ev.dataTransfer.setData("text", ev.target.innerText);
}
var droppedData = [null, null, null];

function updateDroppedDataList() {
    var dataList = droppedData.filter(data => data !== null).join(", ").toLowerCase();
    document.getElementById('selected_tables').value = dataList;
    doAjaxCall(dataList);
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

function doAjaxCall(dataList) {
      var tableName = 'php_customers';
      var selectBoxId = 'joinDropdown1';
      var selectBoxName = 'joinTable1Columns';

      // Create a new XMLHttpRequest object
      var xhr = new XMLHttpRequest();

      // Configure it: POST-request for the URL /data.php
      xhr.open('POST', 'load_join_tables.php', false);

      // Set the request header to tell the server the type of content being sent
      xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');

      // Set up a callback function that will be called when the request status changes
      xhr.onreadystatechange = function () {
        // Only run if the request is complete
        if (xhr.readyState === 4) {
          // Process the response
          if (xhr.status === 200) {
            const result = xhr.responseText.split("||");
            console.log(result);
                // $("#joinTableColumns1").html(result[0]);
                // $("#table1RelationShip").html(result[1]);
                // $("#table1RelationShip").show();
            // If the request was successful, insert the response text into the DOM
           
          } else {
            // If the request failed, log an error message
            console.error('Error:', xhr.statusText);
          }
        }
      };

      // Prepare the data to be sent in JSON format
      var data = JSON.stringify({ tableName: tableName, selectBoxId: selectBoxId,  selectBoxName:selectBoxName});

      // Send the request with the data
      xhr.send(data);
}