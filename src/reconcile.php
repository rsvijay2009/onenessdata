<?php

include_once "database.php";
include_once "sidebar.php";

$errorMsg = "";
$successMsg = "";

include_once "header.php";
?>
<link rel="stylesheet" href="styles/merge.css">
</head>
<body>
  <div class="container-fluid">
    <div class="row"> <?php include_once "sidebar_template.php"; ?> <input type="hidden" id="selectedTable" name="selectedTable" value="">
      <input type="hidden" id="selectedColumns" name="selectedColumns" value="">
      <input type="hidden" id="selectedColumnsToSum" name="selectedColumnsToSum" value="">
      <input type="hidden" id="selectedColumnsForUserKeyGen" name="selectedColumnsForUserKeyGen" value="">
      <input type="hidden" id="selectedTablesForCompare" name="selectedTablesForCompare" value="">
      <input type="hidden" id="relationshipColumnsToCompare" name="relationshipColumnsToCompare" value="">
      <input type="hidden" id="columnToCompare" name="columnToCompare" value="">
      <!-- Left Card -->
      <div class="col-md-5"  style="margin-top:40px;">
        <div class="card" id="prepareDiv" style="cursor:pointer;">
          <div class="plus-icon" style="font-size:30px;">Prepare</div>
          <div class="card-body"></div>
          <div class="card-body"></div>
        </div>
        <div id="tableListDropDown" style="display:none;">
          <div style="display:flex;">
            <div style="width:50%;">
              <select class='form-select' id='tablesList' name='tablesList' style="margin-left:20px;">
                <option value=''>Choose table</option>
              <?php foreach($tables as $table) {?>
                  <option value='<?=$table['name']?>'> <?=$table['original_table_name']?> </option> <?php }?>
              </select>
            </div>
            <div style="width:50%;">
              <div id="reconcileTableColumns1" name="reconcileTableColumns1" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%"></div>
            </div>
          </div>
          <div id="uniqueKeyGenerationTitleId" style="display:none; margin-left:20px;font-weight:bold;margin-top:15px;">Select columns for unique key generation <input class="form-check-input column-checkbox" type="checkbox" name="mandateUniqueKeyGen" id="mandateUniqueKeyGen" style="margin-right: 10px;">
          </div>
          <div style="display:flex; display:none;" id="uniquKeyGenOptionsDiv">
            <div id="uniquKeyGenColumns" name="uniquKeyGenColumns" class="dropdown" style="display: flex; justify-content: flex-start;width:39.2%"></div>
          </div>
          <div id="selectColumnsToSumTitleId" style="display:none; margin-left:20px;font-weight:bold;margin-top:15px;">Select columns to sum </div>
          <div style="display:flex; display:none;justify-content: flex-start;width:74%" id="reconcileTableColumnsToSumDiv" >
          </div>
          <p>
            <a href="#" id="prepareBtnId" class="btn btn-success" style="width:20%; height:45px; padding:10px;margin-top:20px;margin-left:20px;display:none;">Prepare</a>
          </p>
        </div>
      </div>
      <!-- Right Card -->
      <div class="col-md-5"  style="margin-top:40px;">
      <div class="card" id="compareDiv" style="cursor:pointer;">
          <div class="plus-icon" style="font-size:30px;" id="">Compare</div>
          <div class="card-body"></div>
          <div class="card-body"></div>
        </div>
        <div id="selectTableAForCompare" style="display:none;">
          <div style="display:flex;">
            <div style="width:50%;">
              <select class='form-select' id='selectedTableTocompare1' name='selectedTableTocompare1' style="margin-left:20px; width:90%">
                <option value=''>Choose table A</option>
                  <?php foreach($tables as $table) {?>
                    <option value='<?=$table['name']?>'> <?=$table['original_table_name']?> </option> <?php }?>
              </select>
            </div>
            <div id="selectTableBForCompare" style="display:none;">
              <div style="display:flex;">
                <div style="margin-left:5px;width:265px;">
                  <select class='form-select' id='selectedTableTocompare2' name='selectedTableTocompare2' style="margin-left:20px;">
                    <option value=''>Choose table B</option>
                      <?php foreach($tables as $table) {?>
                        <option value='<?=$table['name']?>'><?=$table['original_table_name']?></option> <?php }?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div style="display:flex; display:none;" id="selectRelationShipColumns">
            <div id="selectRelationshipTitleId" style=" margin-left:20px;font-weight:bold;margin-top:15px;margin-bottom:5px;">Select relationship </div>
            <div style="display:flex;">
              <div style="width:50%;">
                <select class='form-select' id='selectedRelationshipColumn1' name='selectedRelationshipColumn1' style="margin-left:20px; width:90%" required></select>
              </div>
              <div id="selectTableBForCompare">
                <div style="display:flex;">
                  <div style="margin-left:5px;width:265px;">
                    <select class='form-select' id='selectedRelationshipColumn2' name='selectedTableToSelectRelationShip2' style="margin-left:20px;" required></select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div style="display:flex; display:none;" id="selectColumnsToCompare">
            <div id="selectRelationshipTitleId" style=" margin-left:20px;font-weight:bold;margin-top:15px;margin-bottom:5px;">Select columns to compare </div>
            <div style="display:flex;">
              <div style="width:50%;">
                <select class='form-select' id='selectColumnsToCompare1' name='selectColumnsToCompare1' style="margin-left:20px; width:90%" required></select>
              </div>
              <div id="selectTableBForCompare">
                <div style="display:flex;">
                  <div style="margin-left:5px;width:265px;">
                    <select class='form-select' id='selectColumnsToCompare2' name='selectColumnsToCompare2' style="margin-left:20px;" required></select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <p>
            <a id="compareBtnId" class="btn btn-success" style="width:20%; height:45px; padding:10px;margin-top:20px;margin-left:20px;display:none;">Compare</a>
          </p>
        </div>
      </div>
    </div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="scripts/reconcile.js"></script>
<script>
$(document).ready(function() {
    $('#tablesList').change(function(e) {
        const tableName = e.target.value;
        $('#selectedTable').val(tableName);
        $.ajax({
            type: "POST",
            url: "get_tables_for_reconcile.php",
            data: {
                tableName: tableName,
                selectBoxId: 'reconcileTableColumns1',
                selectBoxName: 'reconcileTableColumns1'
            },
            success: function(response) {
                $("#reconcileTableColumns1").html(response);
                $("#uniquKeyGenColumns").html(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Something went wrong");
            }
        });
    });
    $('#prepareDiv').click(function(){
        $('#tableListDropDown').show();
    });
    $('#compareDiv').click(function(){
        $('#selectTableAForCompare').show();
        $('#selectTableBForCompare').show();
    });
    $('#reconcileTableColumns1').on('change', '.tableColsChkBox', function() {
        updateDropdown();
    });
    $('#uniquKeyGenColumns').on('change', '.uniqueKeyGenChkBox', function() {
        const checkboxes = document.querySelectorAll('.uniqueKeyGenChkBox');
        const selectedTableColumnsForUniquKeyGen = [];
        let selectedTableColumnsForUniquKeyGenStr = '';
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedTableColumnsForUniquKeyGen.push(checkbox.value);
                selectedTableColumnsForUniquKeyGenStr = selectedTableColumnsForUniquKeyGen.join(", ");
                document.getElementById('selectedColumnsForUserKeyGen').value = selectedTableColumnsForUniquKeyGen.join(", ");
            }
        });
        let newUrl = generateLinkForDataPrepare(selectedTableColumnsForUniquKeyGenStr);
        const link = document.getElementById('prepareBtnId');
        link.setAttribute('href', newUrl);
    });
    $('#reconcileTableColumnsToSumDiv').on('change', '#reconcileTableColumnsToSum', function() {
        if($(this).val()) {
          console.log($(this).val());
          $('#prepareBtnId').show();
          document.getElementById('selectedColumnsToSum').value =  $(this).val();
          let newUrl = generateLinkForDataPrepare();
          let link = document.getElementById('prepareBtnId');
          link.setAttribute('href', newUrl);
        }
    });
    // $('#reconcileTableColumnsToSum').on('change', '.columnsToSumChkBox', function() {
    //     const checkboxes1 = document.querySelectorAll('.tableColsChkBox');
    //     const checkboxes3 = document.querySelectorAll('.columnsToSumChkBox');
    //     // Collect selected checkboxes
    //     const selectedTableColumns = [];
    //     const selectedTableColumnsToSum = [];

    //     checkboxes1.forEach(checkbox => {
    //         if (checkbox.checked) {
    //             selectedTableColumns.push(checkbox.value);
    //         }
    //     });
    //     checkboxes3.forEach(checkbox => {
    //         if (checkbox.checked) {
    //             selectedTableColumnsToSum.push(checkbox.value);
    //             console.log(selectedTableColumnsToSum);
    //             $('#prepareBtnId').show();
    //             document.getElementById('selectedColumnsToSum').value = selectedTableColumnsToSum.join(", ");
    //         }
    //     });
    //     let newUrl = generateLinkForDataPrepare();
    //     const link = document.getElementById('prepareBtnId');
    //     link.setAttribute('href', newUrl);
    // });
    function updateDropdown() {
        // reconcileTableColumnsToSum = '<button class="btn dropdown-toggle" type="button" id="reconcileTableColumnsToSum" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:2px; margin-left:20px; color:#000; border: 1px solid #c9c5c5;"><span style="margin-right: 310px;">Select columns</span></button><ul class="dropdown-menu" aria-labelledby="reconcileTableColumnsToSum" style="width: 455px; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start">';


        reconcileTableColumnsToSum = '<select class="form-select" id="reconcileTableColumnsToSum" name="reconcileTableColumnsToSum" style="margin-left:20px;"><option value="">Choose column to sum</option>';

        uniquKeyGenColumns = '<button class="btn dropdown-toggle" type="button" id="uniquKeyGenColumns" data-bs-toggle="dropdown" aria-expanded="true" style="margin-top:2px; margin-left:20px; color:#000; width:450px;border: 1px solid #c9c5c5;"><span style="margin-right: 310px;">Select columns</span></button><ul class="dropdown-menu" aria-labelledby="uniquKeyGenColumns" style="width: 450px; position: absolute; inset: 0px auto auto 0px; margin: 0px; transform: translate3d(20px, 60px, 0px);" data-popper-placement="bottom-start"><li><a class="dropdown-item"><input class="form-check-input column-checkbox uniqueKeyGenChkBoxAll" type="checkbox"  style="margin-right: 10px;"><label class="form-check-label" for="checkbox">Select all</label></a></li>';

        var elements1 = document.querySelectorAll('.tableColsChkBox');
        var elements1Array = Array.from(elements1);
        var checkboxeElements = new Set(elements1Array);
        var checkboxes1 = Array.from(checkboxeElements);
        const checkboxes2 = document.querySelectorAll('.uniqueKeyGenChkBox');
        const selectedTableColumns = [];
        const selectedTableColumnsForUniquKeyGen = [];
        const selectedTableColumnsToSum = [];
        checkboxes1.forEach(checkbox => {
            if (checkbox.checked) {
              var allChecked = $('.tableColsChkBox').length === $('.tableColsChkBox:checked').length;
              $('.tableColsChkBoxAll').prop('checked', allChecked);
              if (!selectedTableColumns.includes(checkbox.value)) {
                selectedTableColumns.push(checkbox.value);
              }
            }
            document.getElementById('selectedColumns').value = selectedTableColumns.join(", ");
        });
        // Populate dropdown with selected options
        selectedTableColumns.forEach(option => {
            // reconcileTableColumnsToSum+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox columnsToSumChkBox" type="checkbox" name="reconcileTableColumnsToSum[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';

            reconcileTableColumnsToSum+='<option value='+option+'>'+option+'</option>';

            uniquKeyGenColumns+='<li><a class="dropdown-item"><input class="form-check-input column-checkbox uniqueKeyGenChkBox" type="checkbox" name="uniquKeyGenColumns[]" value="'+option+'" id="" style="margin-right: 10px;"><label class="form-check-label" for="checkbox">'+option+'</label></a></li>';
        });
        reconcileTableColumnsToSum+='</select>';
        $('#uniqueKeyGenerationTitleId').show();
        $('#selectColumnsToSumTitleId').show();
        $('#reconcileTableColumnsToSumDiv').show();
        $('#uniquKeyGenColumns').html(uniquKeyGenColumns);
        $('#reconcileTableColumnsToSumDiv').html(reconcileTableColumnsToSum);
    }

    //Display the unique key generation dropdown conditionally
    const checkbox = document.getElementById('mandateUniqueKeyGen');
    const uniquKeyGenOptionsDiv = document.getElementById('uniquKeyGenOptionsDiv');

    checkbox.addEventListener('change', function() {
        if (checkbox.checked) {
            uniquKeyGenOptionsDiv.style.display = 'block';
        } else {
            uniquKeyGenOptionsDiv.style.display = 'none';
        }
    });

    function generateLinkForDataPrepare(userkeyGenColumnsStr= '') {
        //Dynamically generate the link for prepare
        const tableName = document.getElementById('selectedTable').value;
        let selectedColumns = document.getElementById('selectedColumns').value;
        selectedColumns = selectedColumns.replace(/,\s*/g, ',');
        const selectedColumnsToSum = document.getElementById('selectedColumnsToSum').value;
        let selectedColumnsArr = selectedColumns.split(',');
        let selectedColumnsToSumArr = selectedColumnsToSum.split(',');

        let groupBycolumnsArr = selectedColumnsArr.filter(item => !selectedColumnsToSumArr.includes(item));

        const groupBycolumns = groupBycolumnsArr.join(',');

        let userkeyGenColumns = (userkeyGenColumnsStr == '') ? document.getElementById('selectedColumnsForUserKeyGen').value : userkeyGenColumnsStr;
        const newUrl = 'view_reconcile_data.php?table='+tableName+'&selectedColumns='+selectedColumns+'&selectedColumnsToSum='+selectedColumnsToSum+'&selectedColumnsToGroupBy='+groupBycolumns+'&uniqueKeyGenColumns='+userkeyGenColumns
        console.log(newUrl);
        return newUrl;
    }
    $('#selectColumnsToCompare1, #selectColumnsToCompare2').change(function(e) {
        // Get selected options from both select boxes
        let column1 = $('#selectColumnsToCompare1').val() || '';
        let column2 = $('#selectColumnsToCompare2').val() || '';
        let combinedArray = [];
        combinedArray.push(column1.trim(), column2.trim());
        let combinedString = combinedArray.join(',');
        document.getElementById('columnToCompare').value = combinedString;
    });
    $('#selectedTableTocompare1, #selectedTableTocompare2').change(function(e) {
        const tableName = e.target.value;
        const selectedElementId = e.target.id;
        // Get selected options from both select boxes
        let table1 = $('#selectedTableTocompare1').val() || '';
        let table2 = $('#selectedTableTocompare2').val() || '';
        let combinedArray = [];
        combinedArray.push(table1.trim(), table2.trim());
        let combinedString = combinedArray.join(',');
        document.getElementById('selectedTablesForCompare').value = combinedString;

        if(selectedElementId == 'selectedTableTocompare1') {
            var replaceDiv = 'selectedRelationshipColumn1';
            var replaceDiv1 = 'selectColumnsToCompare1';
        } else {
            var replaceDiv = 'selectedRelationshipColumn2';
            var replaceDiv1 = 'selectColumnsToCompare2';
        }
        $('#selectedTable').val(tableName);
        $.ajax({
            type: "POST",
            url: "get_tables_for_compare.php",
            data: {
                tableName: tableName.trim()
            },
            success: function(response) {
                var html = '<option value="">Choose option</option>';
                let items = response.split(',');
                let selectBoxClass ='';
                // Loop through the array and extract values in sets of three
                for (let i = 0; i < items.length; i++) {
                    html+='<option value="'+items[i]+'">'+items[i]+'</option>';
                }
                $("#"+replaceDiv).html(html);
                $("#"+replaceDiv1).html(html);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Something went wrong");
            }
        });
        $('#selectRelationShipColumns').show();
        $('#selectColumnsToCompare').show();
        $('#compareBtnId').show();
        let relationship1 = $('#selectedRelationshipColumn1').val() || '';
        let relationship2 = $('#selectedRelationshipColumn2').val() || '';
        let combinedRelationshipArray = [];
        combinedRelationshipArray.push(relationship1.trim(), relationship2.trim());
        let combinedRelationshipString = combinedRelationshipArray.join(', ');
        document.getElementById('relationshipColumnsToCompare').value = combinedRelationshipString;
    });
    $('#selectedRelationshipColumn1, #selectedRelationshipColumn2').change(function(e) {
        let relationship1 = $('#selectedRelationshipColumn1').val() || '';
        let relationship2 = $('#selectedRelationshipColumn2').val() || '';
        let combinedRelationshipArray = [];
        combinedRelationshipArray.push(relationship1.trim(), relationship2.trim());
        let combinedRelationshipString = combinedRelationshipArray.join(',');
        document.getElementById('relationshipColumnsToCompare').value = combinedRelationshipString;
    });
    $("#compareBtnId").click(function(){
        event.preventDefault();
        let relationship1 = $('#selectedRelationshipColumn1').val() || '';
        let relationship2 = $('#selectedRelationshipColumn2').val() || '';
        let compare1 = $('#selectColumnsToCompare1').val() || '';
        let compare2 = $('#selectColumnsToCompare2').val() || '';

        if(relationship1 == '' || relationship2 == '') {
            alert('Please select the relationship columns');
            return;
        }
        if(compare1 == '' || compare2 == '') {
            alert('Please select the columns to compare');
            return;
        } else {
            let selectedTablesForCompare = document.getElementById('selectedTablesForCompare').value;
            let relationshipColumnsToCompare = document.getElementById('relationshipColumnsToCompare').value;
            let compareColumns = document.getElementById('columnToCompare').value;
            let url = "view_compare_data.php?selectedTablesForCompare="+selectedTablesForCompare+"&relationshipColumns="+relationshipColumnsToCompare+"&compareColumns="+compareColumns;
            window.location.href = url;
        }
    })
    $(document).on('change', '.tableColsChkBoxAll', function() {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
          $('.tableColsChkBox').prop('checked', true);
        } else {
          $('.tableColsChkBox').prop('checked', false);
        }
        $('#uniqueKeyGenerationTitleId').show();
        $('#selectColumnsToSumTitleId').show();
        $('#reconcileTableColumnsToSumDiv').show();
        updateDropdown();
    });
    $(document).on('change', '.uniqueKeyGenChkBoxAll', function() {
        var isChecked = $(this).prop('checked');
        if (isChecked) {
          $('.uniqueKeyGenChkBox').prop('checked', true);

          const checkboxes = document.querySelectorAll('.uniqueKeyGenChkBox');
          const selectedTableColumnsForUniquKeyGen = [];
          let selectedTableColumnsForUniquKeyGenStr = '';
          checkboxes.forEach(checkbox => {
              if (checkbox.checked) {
                  selectedTableColumnsForUniquKeyGen.push(checkbox.value);
                  selectedTableColumnsForUniquKeyGenStr = selectedTableColumnsForUniquKeyGen.join(", ");
                  document.getElementById('selectedColumnsForUserKeyGen').value = selectedTableColumnsForUniquKeyGen.join(", ");
              }
          });
        } else {
          $('.uniqueKeyGenChkBox').prop('checked', false);
        }
    });
    $(document).on('change', '.uniqueKeyGenChkBox', function() {
        var allChecked = $('.uniqueKeyGenChkBox').length === $('.uniqueKeyGenChkBox:checked').length;
        $('.uniqueKeyGenChkBoxAll').prop('checked', allChecked);
    });
});
</script>
</body>
</html>
