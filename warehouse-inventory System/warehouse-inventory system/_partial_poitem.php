<?php
  $all_Taxs = find_by_sql("call spSelectAllTaxRates();");
?>

<div class="row">
    <form method="post" action="create_po.php">
        <!-- /.box-header -->
        <div class="col-xs-3">
            <div class="form-group">
                <label>Product Code</label>
                <input type="text" class="form-control" id="ProductCode" name="ProductCode" placeholder="Product Code" required="required" autocomplete="off" value="<?php echo $serchitem[0]; ?>" readonly="readonly" disabled="disabled" />
                <input type="hidden" name="hProductCode" id="hProductCode" value="<?php echo $serchitem[0]; ?>" />
            </div>

            <div class="form-group">
                <label>Item Tax</label>
                <select class="form-control select2" name="Taxs" style="width: 100%;" id="pTaxs">
                    <option value="">Select Tax</option><?php  foreach ($all_Taxs as $tax): ?>
                       <option value="<?php echo $tax['TaxCode'] ?>"  <?php if($tax['TaxCode'] === $serchitem[6]): echo "selected"; endif; ?>  ><?php echo $tax['TaxDesc'] ?>
                    </option><?php endforeach; ?>
                </select>
             </div>
        </div>

        <div class="col-xs-3">
            <div class="form-group">
                <label>Product Description</label>
                <input type="text" class="form-control" name="ProductDesc" id="ProductDesc" placeholder="Product Description" required="required" readonly="readonly" disabled="disabled" value="<?php echo $serchitem[1]; ?>" />
            </div>
        </div>


        <div class="col-xs-3">
            <div class="form-group">
                <label>Cost Price</label>
                <input type="text" class="integer form-control decimal" name="CostPrice" id="pCostPrice" placeholder="Cost Price" required="required" value="<?php echo $serchitem[2]; ?>" />
            </div>
        </div>

        <div class="col-xs-3">
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
        $('.loader').show();

        var CostPrice = parseInt($("#pCostPrice").val());
        var Qty = parseInt($("#pQty").val());
        var ProductCode = $("#hProductCode").val();
        var Taxs = $("#pTaxs").val();

        if (Qty <= 0) {
            $("#pQty").focus();
            $('.loader').fadeOut();
            bootbox.alert('You enter qty is invalid.');
        }
        else {
            $.ajax({
                url: "create_po.php",
                type: "POST",
                data: { Edit: 'Edit', ProductCode: ProductCode, Qty: Qty, CostPrice: CostPrice, Taxs: Taxs },
                success: function (result) {
                    $("#table").html(result);
                    $('#myModal').modal('toggle');
                },
                complete: function (result) {
                    $('.loader').fadeOut();
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

    $(".decimal").keypress(function (e) {
        var keyCode = (e.which) ? e.which : e.keyCode;
        if ((keyCode >= 48 && keyCode <= 57) || (keyCode == 8))
            return true;
        else if (keyCode == 46) {
            var curVal = document.activeElement.value;
            if (curVal != null && curVal.trim().indexOf('.') == -1)
                return true;
            else
                return false;
        }
        else
            return false;
    });

    $(function () {
        //Initialize Select2 Elements
        $('.select2').select2();
    });
</script>
