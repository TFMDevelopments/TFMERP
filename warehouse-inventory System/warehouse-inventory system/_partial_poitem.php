
<div class="row">
    <form method="post" action="create_po.php">
        <!-- /.box-header -->
        <div class="col-xs-4">
            <div class="form-group">
                <label>Product Code</label>
                <input type="text" class="form-control" id="ProductCode" name="ProductCode" placeholder="Product Code" required="required" autocomplete="off" value="<?php echo $serchitem[0]; ?>" readonly="readonly" disabled="disabled" />
                <input type="hidden" name="hProductCode" id="hProductCode" value="<?php echo $serchitem[0]; ?>" />
            </div>
        </div>

        <div class="col-xs-4">
            <div class="form-group">
                <label>Product Description</label>
                <input type="text" class="form-control" name="ProductDesc" id="ProductDesc" placeholder="Product Description" required="required" readonly="readonly" disabled="disabled" value="<?php echo $serchitem[1]; ?>" />
            </div>
        </div>


        <div class="col-xs-4">
            <div class="form-group">
                <label>Qty</label>
                <input type="number" class="integer form-control integer" name="Qty" id="pQty" placeholder="Qty" required="required" value="<?php echo $serchitem[3]; ?>" />
            </div>
        </div>
    </form>
</div>




<script type="text/javascript">
    function EditItem(ctrl, event) {
        event.preventDefault();

        var Qty = parseInt($("#pQty").val());
        var ProductCode = $("#hProductCode").val();

        if (Qty <= 0) {
            $("#pQty").focus();
            bootbox.alert('You enter qty is invalid.');
        }
        else {
            $.ajax({
                url: "create_po.php",
                type: "POST",
                data: { Edit: 'Edit', ProductCode: ProductCode, Qty: Qty },
                success: function (result) {
                    $("#table").html(result);
                    $('#myModal').modal('toggle');
                    //$('#modal-container').modal('hide');
                }
            });
        }
    }


    //Textbox integer accept
    $(".integer").keypress(function (evt) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    });
</script>