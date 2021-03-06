<?php
ob_start();

session_set_cookie_params(0);
session_start();

$page_title = 'Update Purchase Order';
require_once('includes/load.php');

preventGetAction('home.php');

// Checkin What level user has permission to view this page
UserPageAccessControle(1,'PO Update');

$all_Supplier = find_by_sql("call spSelectAllSuppliers();");
$all_workflows = find_by_sql("call spSelectAllWorkFlow();");

$PurchaseOrder  = $_SESSION['PurchaseOrder'];
$Level  = $_SESSION['Level'];

$PurchaseOrderH = find_by_sp("call spSelectPurchaseOrderFromCode('{$PurchaseOrder}');");

if (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' && !$flashMessages->hasErrors() && !$flashMessages->hasWarnings())
{
    unset($_SESSION['details']);
    unset($_SESSION['header']);
}

$arr_item = array();

if($_SESSION['details'] != null) $arr_item = $_SESSION['details'];
?>

<?php
if(isset($_POST["ProductCode"]))
{
    $req_fields = array('ProductCode','hProductDesc','CostPrice','pQty');

    validate_fields($req_fields);

    if(empty($errors)){
        $p_ProductCode  = remove_junk($db->escape($_POST['ProductCode']));
        $p_ProductDesc  = remove_junk($db->escape($_POST['hProductDesc']));
        $p_CostPrice  = remove_junk($db->escape($_POST['CostPrice']));
        $p_Qty = remove_junk($db->escape($_POST['pQty']));
        $p_Tax  =    remove_junk($db->escape($_POST['Taxs']));

        $prod_count = find_by_sp("call spSelectProductFromCode('{$p_ProductCode}');");

        //------------------  Tax calculation ----------------------------------------------
        $ToatlTax = 0;


        $TaxRatesM = find_by_sql("call spSelectTaxRatesFromCode('{$p_Tax}');");
        foreach($TaxRatesM as &$TaxRt)
        {
            $ToatlTax += $TaxRt["TaxRate"];
        }


        $Amount = $p_CostPrice * $p_Qty;
        $TaxAmount = (($Amount * $ToatlTax)/100);
        //-----------------------------------------------------------------------------------

        if(!$prod_count)
        {
            $flashMessages->warning('This product code not exist in the system.');

            return include('_partial_podetails.php');
        }


        if ($_SESSION['details'] == null)
        {
            $arr_item[]  = array($p_ProductCode,$p_ProductDesc,$p_CostPrice,$p_Qty,$ToatlTax,$TaxAmount,$p_Tax);
            $_SESSION['details'] = $arr_item;

            return include('_partial_podetails.php');
        }
        else
        {
            $arr_item= $_SESSION['details'];

            if(!ExistInArray($arr_item,$p_ProductCode))
            {
                $arr_item[] = array($p_ProductCode,$p_ProductDesc,$p_CostPrice,$p_Qty,$ToatlTax,$TaxAmount,$p_Tax);
                $_SESSION['details'] = $arr_item;

                return include('_partial_podetails.php');
            }
            else
            {
                $flashMessages->warning('This product exist in the list.');

                return include('_partial_podetails.php');
            }

        }

    }
}


if(isset($_POST['edit_po'])){
    if($_POST['edit_po'] == "save")
    {
        $req_fields = array();

        validate_fields($req_fields);

        if(empty($errors))
        {
            //$p_PoCode  = remove_junk($db->escape($_POST['PONo']));
            //$p_SupplierCode  = remove_junk($db->escape($_POST['SupplierCode']));
           // $p_PurchaseRequisition  = remove_junk($db->escape($_POST['PRNo']));
           // $p_WorkFlowCode  = remove_junk($db->escape($_POST['WorkFlowCode']));
           // $p_Remarks  = remove_junk($db->escape($_POST['Remarks']));

            $date    = make_date();
            $user =  current_user();

            //Get all sessions values
            $arr_item= $_SESSION['details'];

            //check details values
            if(count($arr_item)>0)
            {
                //update purchase order

                try
                {

                    $Po_count = find_by_sp("call spSelectPurchaseOrderFromCode('{$PurchaseOrder}');");

                    if(!$Po_count)
                    {
                        $flashMessages->warning('This purchase order number not exist in the system.','edit_po.php?TransactionCode=001');
                    }

                    $db->begin();

                    //Update purchase order header details
                    //$query  = "call spUpdatePurchaseOrderH('{$p_PoCode}','{$p_SupplierCode}','{$p_WorkFlowCode}','{$p_Remarks}','{$date}','{$user["username"]}');";
                    //$db->query($query);

                    //Delete purchase order details
                    $query  = "call spDeletePurchaseOrderD('{$PurchaseOrder}');";
                    $db->query($query);

                    //Insert purchase order item details
                    foreach($arr_item as $row => $value)
                    {
                        $amount =$value[2] * $value[3] + $value[5];

                        $query  = "call spInsertPurchaseOrderD('{$PurchaseOrder}','{$value[0]}','{$value[1]}',{$value[2]},{$value[3]},{$value[4]},{$value[5]},'{$value[6]}',{$amount});";
                        $db->query($query);
                    }

                    //Transaction Approve
                    $query  = "call spTransactionApproved('001','{$PurchaseOrder}',{$Level});";
                    $db->query($query);

                    InsertRecentActvity("Purchase order approved","Reference No. ".$PurchaseOrder);

                    $db->commit();

                    unset($_SESSION['PurchaseOrder']);
                    unset($_SESSION['Level']);

                    $flashMessages->success('Purchase order has been successfully approved.','approval_task.php?TransactionCode=001');

                }
                catch(Exception $ex)
                {
                    $db->rollback();
                    $flashMessages->error('Sorry failed to approve purchase order. '.$ex->getMessage(),'edit_po_.php');

                }

            }
            else
            {
                $flashMessages->warning('Purchase order item(s) not found!','edit_po_.php');
            }
        }
        else
        {
            $flashMessages->warning($errors,'edit_po_.php');
        }

    }
}

if (isset($_POST['_prodcode'])) {
    $prodcode = remove_junk($db->escape($_POST['_prodcode']));
    $arr_item = $_SESSION['details'];
    $arr_item = RemoveValueFromListOfArray( $arr_item,$prodcode);
    $_SESSION['details'] = $arr_item;

    return include('_partial_podetails.php');
}


if (isset($_SESSION['redirect'])) {
    $all_PODetsils = find_by_sql("call spSelectAllPODetailsFromPONo('{$PurchaseOrder}');");

    $_SESSION['details'] == null;

    foreach($all_PODetsils as $row => $value){
        $arr_item[]  = array($value["ProductCode"],$value["ProductDesc"],$value["CostPrice"],$value["Qty"],$value["TaxRate"],$value["TaxAmount"],$value["TaxCode"]);
        $_SESSION['details'] = $arr_item;
    }

    $_SESSION['details'] = $arr_item;

    unset($_SESSION['redirect']);
}



if (isset($_POST['Edit'])) {
    $ProductCode = remove_junk($db->escape($_POST['ProductCode']));
    $Qty = remove_junk($db->escape($_POST['Qty']));
    $CostPrice = remove_junk($db->escape($_POST['CostPrice']));

    $arr_item = $_SESSION['details'];

    //Change Qty
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,3,$Qty);
    //Change Cost price
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,2,$CostPrice);

    $_SESSION['details'] = $arr_item;

    return include('_partial_podetails.php');
}

if (isset($_POST['Edit2'])) {
    $ProductCode = remove_junk($db->escape($_POST['ProductCode']));
    $Qty = remove_junk($db->escape($_POST['Qty']));
    $CostPrice = remove_junk($db->escape($_POST['AverageCost']));
    $TaxRate = remove_junk($db->escape($_POST['Tax']));
    $TaxAmount = remove_junk($db->escape($_POST['TaxAmmount']));

    $arr_item = $_SESSION['details'];

    //Change Qty
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,3,$Qty);
    //Change Cost price
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,2,$CostPrice);
    //Change Tax Rate
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,4,$TaxRate);
    //Change Tax Ammount
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,5,$TaxAmount);


    $_SESSION['details'] = $arr_item;

    return include('_partial_podetails.php');
}


if (isset($_POST['_PONo'])) {
    $_SESSION['details'] = null;

    $PONo = remove_junk($db->escape($_POST['_PONo']));
    //$all_PRHeader = find_by_sql("call spSelectAllPOHeaderDetailsFromPONo('{$PONo}');");


    $all_PODetsils = find_by_sql("call spSelectAllPODetailsFromPONo('{$PONo}');");

    if($_SESSION['details'] == null) $arr_item = $_SESSION['details']; else $arr_item[] = $_SESSION['details'];

    foreach($all_PODetsils as $row => $value){
        $arr_item[]  = array($value["ProductCode"],$value["ProductDesc"],$value["CostPrice"],$value["Qty"],$value["TaxRate"],$value["TaxAmount"],$value["TaxCode"]);
        $_SESSION['details'] = $arr_item;
    }

    return include('_partial_podetails.php');
}


if (isset($_POST['_RowNo'])) {
    $ProductCode = remove_junk($db->escape($_POST['_RowNo']));
    $serchitem = ArraySearch($arr_item,$ProductCode);

    return include('_partial_poitem.php');
}


if (isset($_POST['Supplier'])) {
    $_SESSION['details']  = null;

    $SupplierCode = remove_junk($db->escape($_POST['Supplier']));
    $Remarks = remove_junk($db->escape($_POST['Remarks']));

    $all_PO = find_by_sql("call spSelectAllPurchaseOrderFromSupplierCode('{$SupplierCode}');");

    echo "<option>Select Purchase Order</option>";
    foreach($all_PO as &$value){
        $arr_PONo[]  = array('PoNo' =>$value["PoNo"]);
        echo "<option value ={$value["PoNo"]}>{$value["PoNo"]}</option>";
    }
    return;
}


?>

<?php include_once('layouts/header.php'); ?>

<section class="content-header">
    <h1>
       Update Purchase Order
    </h1>
    <ol class="breadcrumb">
        <li>
            <a href="#">
                <i class="fa fa-dashboard"></i>Transaction
            </a>
        </li>
        <li class="active">Purchase Order</li>
    </ol>
    <style>
        form {
            display: inline;
        }
    </style>
</section>

<!-- Main content -->
<section class="content">
    <!-- Your Page Content Here -->
    <form method="post" action="edit_po_.php">
        <div class="box box-default">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12 ">
                        <div class="btn-group">
                            <button type="submit" name="edit_po" class="btn btn-primary" value="save" id="btnApprove">&nbsp;Approve&nbsp;&nbsp;</button>
                            <button type="reset" class="btn btn-success">&nbsp;Reset&nbsp;&nbsp;</button>
                            <button type="button" class="btn btn-warning" onclick="window.location = 'approval_task.php?TransactionCode=001'">Cancel  </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div id="message" class="col-md-12"><?php include('_partial_message.php'); ?> </div>
        </div>

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Basic Details</h3>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select class="form-control select2" style="width: 100%;" name="SupplierCode" id="SupplierCode" required="required"  disabled>
                                <option value="">Select Supplier</option><?php  foreach ($all_Supplier as $supp): ?>
                                <option value="<?php echo $supp['SupplierCode'] ?>" <?php if($supp['SupplierCode'] === $PurchaseOrderH['SupplierCode']): echo "selected"; endif; ?>><?php echo $supp['SupplierName'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Purchase Requisition</label>
                            <input type="text" class="form-control" name="PRNo" id="PRNo" placeholder="Purchase Requisition No" readonly="readonly"  value="<?php echo $PurchaseOrderH['PRNo']; ?>" disabled="disabled" />
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Purchase Order</label>
                            <select class="form-control select2" style="width: 100%;" name="PONo" id="PONo" required="required" disabled>
                                <option value="<?php echo $PurchaseOrderH['PoNo']; ?>" selected><?php echo $PurchaseOrderH['PoNo']; ?></option>
                                <!--<option value="">Select Purchase Order</option>-->
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Approvals Flow</label>
                            <select class="form-control select2" style="width: 100%;" name="WorkFlowCode" id="WorkFlowCode" required="required" disabled>
                                <option value="">Select Approvals Work-Flow</option><?php  foreach ($all_workflows as $wflow): ?>
                                <option value="<?php echo $wflow['WorkFlowCode'] ?>" <?php if($wflow['WorkFlowCode'] === $PurchaseOrderH['WorkFlowCode']): echo "selected"; endif; ?>><?php echo $wflow['Description'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>

                    </div>


                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="text" class="form-control" name="PoDate" id="PoDate" placeholder="Date" readonly="readonly" disabled="disabled" value="<?php echo $PurchaseOrderH['PoDate']; ?>" />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea name="Remarks" id="Remarks" class="form-control" placeholder="Enter remarks here.." value="<?php echo $PurchaseOrderH['Remarks']; ?>" disabled="disabled"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>


    <div class="box box-default">
        <!-- /.box-header -->
        <form method="post" action="edit_po_.php">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Product Code</label>
                            <input type="text" class="form-control" id="ProductCode" name="ProductCode" placeholder="Product Code" required="required" autocomplete="off" />
                        </div>

                        <div class="form-group">
                            <label>Item Tax(s)</label>
                            <select class="form-control select2" name="Taxs" style="width: 100%;" id="Taxs">
                                <option value="">Select Tax</option><?php  foreach ($all_Taxs as $tax): ?>
                                <option value="<?php echo $tax['TaxCode'] ?>"><?php echo $tax['TaxDesc'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Product Description</label>
                            <input type="text" class="form-control" name="ProductDesc" id="ProductDesc" placeholder="Product Description" required="required" readonly="readonly" disabled="disabled" />
                            <input type="hidden" name="hProductDesc" id="hProductDesc" />
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cost Price</label>
                            <input type="text" class="form-control decimal" name="CostPrice" id="CostPrice" pattern="([0-9]+\.)?[0-9]+" placeholder="Cost Price" required="required" />
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Qty</label>
                            <input type="number" class="form-control integer" name="pQty" id ="Qty" placeholder="Qty" required="required" />
                        </div>

                        <div class="form-group pull-right">
                            <label>&nbsp;</label><br>
                            <button type="submit" class="btn btn-info" name="edit_po" onclick="AddItem(this, event);" value="item">&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;</button>
                            <button type="reset" class="btn btn-success">&nbsp;Reset&nbsp;</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
  </div>


    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Purchase Order Item(s)</h3>

            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <button id="btnUpdateChanges" class="btn btn-primary">Update Changes </button>                       
                        <?php include('_partial_podetails.php'); ?>
                    </div>
                    </div>
                </div>
            </div>
        </div>


</section>

<script type="text/javascript">

    function AddItem(ctrl, event) {
        event.preventDefault();
        $('.loader').show();

        if ($('#ProductCode').val() == "") {
            $("#ProductCode").focus();
            $('.loader').fadeOut();
            bootbox.alert('Please select a product code.');
        }
        else if ($('#ProductDesc').val() == "") {
            $("#ProductCode").focus();
            $('.loader').fadeOut();
            bootbox.alert('Please select a product code.');
        }
        else if ($('#CostPrice').val() <= 0) {
            $("#CostPrice").focus();
            $('.loader').fadeOut();
            bootbox.alert('Please enter valid cost price.');
        }
        else if ($('#Qty').val() <= 0) {
            $("#Qty").focus();
            $('.loader').fadeOut();
            bootbox.alert('Please enter valid purchase qty.');
        }
        else {
            $.ajax({
                url: 'edit_po.php',
                type: "POST",
                data: $("form").serialize(),
                success: function (result) {
                    $("#table").html(result);
                    $('#message').load('_partial_message.php');
                },
                complete: function (result) {
                    $('#ProductCode').val('');
                    $('#Taxs').val('').trigger('change');
                    $('#ProductDesc').val('');
                    $('#CostPrice').val('');
                    $('#Qty').val('');

                    $('.loader').fadeOut();
                    $('#ProductCode').focus();

                    $('.loader').fadeOut();
                }
            });
        }
    }

    $(document).ready(function () {
        $('#ProductCode').typeahead({
            hint: true,
            highlight: true,
            minLength: 3,
            source: function (request, response) {
                $.ajax({
                    url: "autocomplete.php",
                    data: 'productcode=' + request,
                    dataType: "json",
                    type: "POST",
                    success: function (data) {
                        items = [];
                        map = {};
                        $.each(data, function (i, item) {
                            var id = item.value;
                            var name = item.text;
                            var cprice = item.cprice;
                            map[name] = { id: id, name: name,cprice: cprice };
                            items.push(name);
                        });
                        response(items);
                        $(".dropdown-menu").css("height", "auto");
                    }
                });
            },
            updater: function (item) {
                $('#ProductDesc').val(map[item].name.substring(map[item].name.indexOf('|') + 2));
                $('#hProductDesc').val(map[item].name.substring(map[item].name.indexOf('|') + 2));
                $('#CostPrice').val(map[item].cprice);

                return map[item].id;
            }
        });
    });


</script>

<?php include_once('layouts/footer.php'); ?>

