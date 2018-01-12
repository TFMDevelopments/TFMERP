<?php
ob_start();

session_set_cookie_params(0);
session_start();

$page_title = 'Update Purchase Order';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(2);

$all_Supplier = find_by_sql("call spSelectAllSuppliers();");
$all_workflows = find_by_sql("call spSelectAllWorkFlow();");

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

        $prod_count = find_by_sp("call spSelectProductFromCode('{$p_ProductCode}');");


        if(!$prod_count)
        {
            $session->msg("d", "This product code not exist in the system.");
            return include('_partial_podetails.php');  
        }


        if ($_SESSION['details'] == null)
        {
            $arr_item[]  = array($p_ProductCode,$p_ProductDesc,$p_CostPrice,$p_Qty);
            $_SESSION['details'] = $arr_item; 
            return include('_partial_podetails.php'); 
        }
        else
        {
            $arr_item= $_SESSION['details'];

            if(!ExistInArray($arr_item,$p_ProductCode))
            {
                $arr_item[] = array($p_ProductCode,$p_ProductDesc,$p_CostPrice,$p_Qty);
                $_SESSION['details'] = $arr_item;
                return include('_partial_podetails.php'); 
            }
            else
            {
                $session->msg("w", "This product exist in the table.");
                return include('_partial_podetails.php');  
            }

        }

    }
}


if(isset($_POST['edit_po'])){
    if($_POST['edit_po'] == "save")
    {
        $req_fields = array('PONo','SupplierCode','WorkFlowCode');

        validate_fields($req_fields);

        if(empty($errors))
        {
            $p_PoCode  = remove_junk($db->escape($_POST['PONo']));
            $p_SupplierCode  = remove_junk($db->escape($_POST['SupplierCode']));
            $p_PurchaseRequisition  = remove_junk($db->escape($_POST['PRNo']));
            $p_WorkFlowCode  = remove_junk($db->escape($_POST['WorkFlowCode']));
            $p_Remarks  = remove_junk($db->escape($_POST['Remarks']));
            $date    = make_date();
            $user = "anush";

            //Get all sessions values
            $arr_item= $_SESSION['details'];

            //check details values
            if(count($arr_item)>0)
            {
                //save purchase order 
                
                try
                {
                    $db->begin();

                    $Po_count = find_by_sp("call spSelectPurchaseOrderFromCode('{$p_PoCode}');");

                    if(!$Po_count)
                    {
                        $session->msg("d", "This purchase order number not exist in the system.");
                        redirect('edit_po.php',false);
                    }

                    //Update purchase order header details
                    $query  = "call spUpdatePurchaseOrderH('{$p_PoCode}','{$p_SupplierCode}','{$p_WorkFlowCode}','{$p_Remarks}','{$date}','{$user}');";
                    $db->query($query);

                    //Delete purchase order details
                    $query  = "call spDeletePurchaseOrderD('{$p_PoCode}');";
                    $db->query($query);

                    //Insert purchase order item details
                    foreach($arr_item as $row => $value)
                    {
                        $amount = $value[2] * $value[3];
                        $query  = "call spInsertPurchaseOrderD('{$p_PoCode}','{$value[0]}','{$value[1]}',{$value[2]},{$value[3]},{$amount});";
                        $db->query($query);
                    }

                    $db->commit();
                    
                    unset($_SESSION['details']);

                    $session->msg('s',"Purchase order has been successfully updated");
                    redirect('edit_po.php', false);

                }
                catch(Exception $ex)
                {
                    $db->rollback();

                    $session->msg('d',' Sorry failed to update!');
                    redirect('edit_po.php', false);
                }

            }
            else
            {
                $session->msg("w",' Purchase order item(s) not found!');
                redirect('edit_po.php',false);
            }
        }
        else
        {
            $session->msg("d", $errors);
            redirect('edit_po.php',false);
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

if (isset($_POST['Edit'])) {
    $ProductCode = remove_junk($db->escape($_POST['ProductCode']));
    $Qty = remove_junk($db->escape($_POST['Qty']));

    $arr_item = $_SESSION['details'];
    //Change Qty
    $arr_item = ChangValueFromListOfArray( $arr_item,$ProductCode,3,$Qty);
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
        $arr_item[]  = array($value["ProductCode"],$value["ProductDesc"],$value["CostPrice"],$value["Qty"]);
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
    <form method="post" action="edit_po.php">
        <div class="box box-default">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12 ">
                        <div class="btn-group">
                            <button type="submit" name="edit_po" class="btn btn-primary" value="save">&nbsp;Save&nbsp;&nbsp;</button>
                            <button type="reset" class="btn btn-success">&nbsp;Reset&nbsp;&nbsp;</button>
                            <button type="button" class="btn btn-warning" onclick="window.location = 'home.php'">Cancel  </button>
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
                            <select class="form-control select2" style="width: 100%;" name="SupplierCode" id="SupplierCode" required="required" onchange="FillPO();">
                                <option value="">Select Supplier</option><?php  foreach ($all_Supplier as $supp): ?>
                                <option value="<?php echo $supp['SupplierCode'] ?>"><?php echo $supp['SupplierName'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Purchase Requisition</label>
                            <input type="text" class="form-control" name="PRNo" id="PRNo" placeholder="Purchase Requisition No" readonly="readonly" disabled="disabled" />
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Purchase Order</label>
                            <select class="form-control select2" style="width: 100%;" name="PONo" id="PONo" onchange="FillDetails();" required="required">
                                <option value="">Select Purchase Order</option><?php  foreach ($arr_PONo as $PO): ?>
                                <option value="<?php echo $PO['PoNo'] ?>"><?php echo $PO['PoNo'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Approvals Flow</label>
                            <select class="form-control select2" style="width: 100%;" name="WorkFlowCode" id="WorkFlowCode" required="required">
                                <option value="">Select Approvals Work-Flow</option><?php  foreach ($all_workflows as $wflow): ?>
                                <option value="<?php echo $wflow['WorkFlowCode'] ?>" <?php if($wflow['WorkFlowCode'] === $arr_header['WorkFlowCode']): echo "selected"; endif; ?>><?php echo $wflow['Description'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>
                       
                    </div>


                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="text" class="form-control" name="PoDate" id="PoDate" placeholder="Date" readonly="readonly" disabled="disabled" />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea name="Remarks" id="Remarks" class="form-control" placeholder="Enter remarks here.."></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>


    <div class="box box-default">
        <!-- /.box-header -->
        <form method="post" action="edit_po.php">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Product Code</label>
                            <input type="text" class="form-control" id="ProductCode" name="ProductCode" placeholder="Product Code" required="required" autocomplete="off" />
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

        if ($('#ProductCode').val() == "") {
            $("#ProductCode").focus();
            bootbox.alert('Please select a product code.');
        }
        else if ($('#ProductDesc').val() == "") {
            $("#ProductCode").focus();
            bootbox.alert('Please select a product code.');
        }
        else if ($('#CostPrice').val() <= 0) {
            $("#CostPrice").focus();
            bootbox.alert('Please enter valid cost price.');
        }
        else if ($('#Qty').val() <= 0) {
            $("#Qty").focus();
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


    function FillPO() {
        var Supplier = $('#SupplierCode').val();
        var Remarks = $('#Remarks').val();

        $.ajax({
            url: "edit_po.php",
            type: "POST",
            data: { Supplier: Supplier, Remarks: Remarks},
            success: function (result) {
                $("#PONo").html(""); // clear before appending new list
                $("#PONo").html(result);
            }
        });

    }

    function FillDetails() {
        var PONo = $('#PONo').val();


        $.ajax({
            url: "autocomplete.php",
            data: '_PONoForHeader=' + PONo,
            dataType: "json",
            type: "POST",
            success: function (data) {
                $('#PoDate').val(data.PoDate);
                $('#PRNo').val(data.PRNo);
                $("#WorkFlowCode").val(data.WorkFlowCode).trigger("change");
                //$("#WorkFlowCode").select2("val",data.WorkFlowCode);
                $('#Remarks').val(data.Remarks);
            }
        });



        $.ajax({
            type: "POST",
            url: "edit_po.php", // Name of the php files
            data: { "_PONo": PONo },
            success: function (result) {
                $("#table").html(result);
            }
        });

    }
</script>

<?php include_once('layouts/footer.php'); ?>
