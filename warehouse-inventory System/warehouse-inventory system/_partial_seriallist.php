
<form method="post" action="_partial_seriallist.php">
    <!-- /.box-header -->
    <input type="hidden" name="Edit" value="Edit" />

    <div class="box-body">
        <table id="tblSerial" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Stock Code</th>
                    <th>Serial Number</th>
                </tr>
            </thead>
            <tbody>
                <?php for($count = 1;$count <= $_SESSION['TrnQty'];$count++) { ?>
                <tr>
                    <td class="clsStockCode">
                        <input type="text" class="form-control col-xs-3 input-sm" name="StockCode" value="<?php echo$_SESSION['StockCode'] ?>" required="required" readonly="readonly" disabled />
                    </td>
                    <td class="clsSerialId">
                        <input type="text" class="form-control col-xs-3 input-sm" name="SerialCode" placeholder="Serial Code" required="required" autocomplete="off" onkeyup="TextBoxKeyUp(this);" />
                    </td>
                </tr><?php  } ?>
            </tbody>
        </table>
    </div>
</form>
<!-- /.box -->

<script type="text/javascript">
    var arr = new Array();
    var AllSerailsAreValid = true;
    var StockCode = "";

    function EditItem(ctrl, event) {
        event.preventDefault();
        
        var i = 0;
        
        var AllSerailsAreFilled = true;
        var completeAll = false;
        var completecount = 0;

        AllSerailsAreValid = true;
        arr = [];


        $("#tblSerial tr").each(function () {
            $('td', this).each(function () {

                if ($(this).attr("class") == "clsStockCode") {
                    StockCode = $(this).find(":input").val().trim();
                }



                if ($(this).attr("class") == "clsSerialId") {
                    var value = $(this).find(":input").val().trim();
                    arr.push(value);
                }
            });
        });


        for (i = 0; i < arr.length; i++) {
            var value = arr[i];
            
            if (value == "") {
                AllSerailsAreFilled = false;
                bootbox.alert('Some serial details are missing.');
                break;
            }
        }

        if (arrHasDupes(arr))
        {
            AllSerailsAreFilled = false;
            bootbox.alert('Duplicate serial numbers found!');
        }

        if (AllSerailsAreFilled == true) {
            for (i = 0; i < arr.length; i++) {
                var value = arr[i];
                $.ajax({
                    url: 'autocomplete.php',
                    type: 'POST',
                    data: { StockCode: StockCode,SerialNo: value },
                    async: false,
                    success: function (data) {
                        if (data.trim() == "false")
                        {
                            AllSerailsAreValid = false;
                        }
                    },
                    complete: function (data) {
                        completecount++;
                        if (completecount == arr.length)
                            competeAjax();
                    }
                });
            }
        }

    }


    function competeAjax()
    {
        if (!AllSerailsAreValid)
            bootbox.alert('Some serials detail(s) are invalid.');
        else
        {
            //bootbox.alert('Sucesss.');
            //var JSONArray = JSON.stringify(arr);

            $.ajax({
                url: 'create_transfernote.php',
                type: 'POST',
                data: { StockCode: StockCode,'arr': arr },
                success: function (data) {
                    $('#myModal').modal('toggle');
                }
            });
        }
    }

    function arrHasDupes(A) {                         
        var i, j, n;
        n = A.length;
        // to ensure the fewest possible comparisons
        for (i = 0; i < n; i++) {                        
            for (j = i + 1; j < n; j++) {         
                if (A[i] == A[j]) return true;
            }
        }
        return false;
    }


    $(document).ready(function () {
        $('input:text:first').focus();
        $('input:text').bind("keydown", function (e) {
            var n = $("input:text").length;
            if (e.which == 13) { //Enter key
                e.preventDefault(); //Skip default behavior of the enter key
                var nextIndex = $('input:text').index(this) + 1;
                if (nextIndex < n)
                    $('input:text')[nextIndex].focus();
                else {
                    $('input:text')[nextIndex - 1].blur();
                    $('#btnSubmit').click();
                }
            }
        });
    });
         
</script>