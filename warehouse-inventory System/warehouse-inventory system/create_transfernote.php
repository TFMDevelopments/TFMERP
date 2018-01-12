<?php
ob_start();

session_set_cookie_params(0);
session_start();

$page_title = 'Create Transfer Note';
require_once('includes/load.php');
// Checkin What level user has permission to view this page
page_require_level(2);

$all_locations = find_by_sql("call spSelectAllLocations();");
 

$arr_item = array();
$arr_header = array();

if($_SESSION['header'] != null) $arr_header = $_SESSION['header'];
if($_SESSION['details'] != null) $arr_item = $_SESSION['details'];

//After page refresh load bin details
$all_FromBin =  find_by_sql("call spSelectBinFromLocationCode('{$arr_header['FromLocation']}');");
$all_ToBin =  find_by_sql("call spSelectBinFromLocationCode('{$arr_header['ToLocation']}');");
?>

<?php


if(isset($_POST['create_transfernote'])){

    if($_POST['create_transfernote'] == "save")
    {
        $req_fields = array('FromLocationCode','ToLocationCode','FromBinCode','ToBinCode');

        validate_fields($req_fields);

        if(empty($errors))
        {
            $p_FromLocationCode  = remove_junk($db->escape($_POST['FromLocationCode']));
            $p_ToLocationCode  = remove_junk($db->escape($_POST['ToLocationCode']));
            $p_FromBinCode  = remove_junk($db->escape($_POST['FromBinCode']));
            $p_ToBinCode  = remove_junk($db->escape($_POST['ToBinCode']));
            $p_Remarks  = remove_junk($db->escape($_POST['Remarks']));
            $date    = make_date();
            $datetime    = make_datetime();
            $user = "anush";

            $arr_header = array('FromLocation'=>$p_FromLocationCode,'ToLocation'=>$p_ToLocationCode,
                                'FromBin'=>$p_FromBinCode,'ToBin'=>$p_ToBinCode,'Remarks'=>$p_Remarks);

            //Get all sessions values
            $arr_item= $_SESSION['details'];

            $_SESSION['header'] = $arr_header;

            //check details values
            if(count($arr_item)>0)
            {
                //save transfer note no
                
                try
                {
                    $p_TransferNoteNo  = autoGenerateNumber('tfmTransferNoteHT',1);

                    $db->begin();

                    $TransferNote_count = find_by_sp("call spSelectTransferNoteFromCode('{$p_TransferNoteNo}');");


                    if($TransferNote_count)
                    {
                       // unset($_SESSION['details']);
                       $session->msg("d", "This transfer note number exist in the system.");
                       redirect('create_transfernote.php',false);
                       exit;
                    }

                    $IsQtyExist = false;

                    foreach($arr_item as $row => $value)
                        if ($value[6] > 0)
                            $IsQtyExist = true;

                    if(!$IsQtyExist)
                    {
                        //unset($_SESSION['details']);
                        $session->msg("d", "Transfer note qty not found.");
                        redirect('create_transfernote.php',false);
                        exit;
                    }

                    foreach($arr_item as $row => $value)
                    {
                        $StockCode = $value[0];
                        $TrnQty = $value[6];

                        if($TrnQty > 0)
                        {
                            $SerialCount = count($value[7]);
                            if($TrnQty != $SerialCount)
                            {
                                //unset($_SESSION['details']);
                                $session->msg("d", "Transfer serial details are invalid. Reference: ".$StockCode);
                                redirect('create_transfernote.php',false);
                                exit;
                            }
                        }
                    }


                    //Insert transfer note header details
                    $query  = "call spInsertTransferNoteH('{$p_TransferNoteNo}','{$p_FromLocationCode}','{$p_FromBinCode}','{$p_ToLocationCode}','{$p_ToBinCode}','{$date}','{$p_Remarks}',7,'{$date}','{$user}');";
                    $db->query($query);
                   

                    //Insert transfer note item details
                    foreach($arr_item as $row => $value)
                    {
                        if ($value[6] > 0)
                        {
                            $query  = "call spInsertTransferNoteD('{$p_TransferNoteNo}','{$value[0]}',{$value[3]},'{$value[2]}','{$value[4]}',{$value[6]});";
                            $db->query($query);


                            //Update New Serial Location
                            foreach($value[7] as $serialrow => $serialvalue)
                            {
                                $query  = "call spChangeGrnSerialLocationDetails('{$value[0]}','{$p_ToLocationCode}','{$p_FromLocationCode}','{$p_ToLocationCode}','{$p_ToBinCode}','{$p_TransferNoteNo}','{$serialvalue}');";
                                $db->query($query);
                            }

                            //Select stock
                            $stock = find_by_sp("call spSelectStock('{$value[0]}','{$p_FromLocationCode}','{$p_FromBinCode}');");


                            //Insert or update New Stock 
                            $query  = "call spStock('{$stock["StockCode"]}','{$p_ToLocationCode}','{$p_ToBinCode}',
                                       '{$stock["ProductCode"]}','{$stock["SupplierCode"]}',{$stock["CostPrice"]},{$stock["SalePrice"]},0,{$stock["AvgCostPrice"]},{$stock["AvgSalePrice"]},0,0,{$value[6]},'{$stock["ExpireDate"]}',
                                         '{$stock["PurchaseDate"]}','{$date}');";
                            $db->query($query);

                            //Update Old Stock 
                            $query  = "call spUpdateStock('{$stock["StockCode"]}','{$p_FromLocationCode}','{$p_FromBinCode}',{$value[6]},'{$date}');";
                            $db->query($query);


                            //Insert stock movement (-)
                            $Qty = -1 * $value[6];
                            $query  = "call spStockMovement('{$stock["StockCode"]}','{$p_FromLocationCode}','{$p_FromBinCode}',
                                       '{$stock["ProductCode"]}','{$stock["SupplierCode"]}','003',{$stock["CostPrice"]},{$stock["SalePrice"]},0,{$stock["AvgCostPrice"]},{$stock["AvgSalePrice"]},{$Qty},'{$stock["ExpireDate"]}',
                                         '{$date}','{$user}');";
                            $db->query($query);


                            //Insert stock movement (+)
                            $query  = "call spStockMovement('{$stock["StockCode"]}','{$p_ToLocationCode}','{$p_ToBinCode}',
                                       '{$stock["ProductCode"]}','{$stock["SupplierCode"]}','003',{$stock["CostPrice"]},{$stock["SalePrice"]},0,{$stock["AvgCostPrice"]},{$stock["AvgSalePrice"]},{$value[6]},'{$stock["ExpireDate"]}',
                                         '{$date}','{$user}');";
                            $db->query($query);

                        }
                    }

                    $db->commit();
                    
                    unset($_SESSION['header']);
                    unset($_SESSION['details']);

                    $session->msg('s',"Transfer note has been saved successfully,\n   Your transfer note No: ".$p_TransferNoteNo);
                    redirect('create_transfernote.php', false);

                }
                catch(Exception $ex)
                {
                    $db->rollback();

                    $session->msg('d',' Sorry failed to added!');
                    redirect('create_transfernote.php', false);
                }

            }
            else
            {
                $session->msg("w",' Transfer note item(s) not found!');
                redirect('create_transfernote.php',false);
            }
        }
        else
        {
            $session->msg("d", $errors);
            redirect('create_transfernote.php',false);
        }

    }
  
}


if (isset($_POST['Edit'])) {
    $StockCode = remove_junk($db->escape($_POST['hStockCode']));
    $TrnQty = remove_junk($db->escape($_POST['TrnQty']));

    $arr_item = $_SESSION['details'];

    //Change Trn qty
    $arr_item = ChangValueFromListOfArray( $arr_item,$StockCode,6,$TrnQty);
    $_SESSION['details'] = $arr_item;

    return include('_partial_bindetails.php');  
}


if (isset($_POST['_stockcode'])) {
    $stockcode = remove_junk($db->escape($_POST['_stockcode']));
    $arr_item = $_SESSION['details'];
    $arr_item = RemoveValueFromListOfArray( $arr_item,$stockcode);

    $_SESSION['details'] = $arr_item;

    return include('_partial_bindetails.php');  
}

if (isset($_POST['StockCode']) && isset($_POST['TrnQty'])) {
    $_SESSION['StockCode'] = $_POST['StockCode'];
    $_SESSION['TrnQty'] = $_POST['TrnQty'];
  
    return include('_partial_seriallist.php');  
}


if (isset($_POST['FromLocationCode']) && isset($_POST['FromBinCode'])) {
    $FromLocationCode = remove_junk($db->escape($_POST['FromLocationCode']));
    $FromBinCode = remove_junk($db->escape($_POST['FromBinCode']));

    $arr_serial = array();

    $all_stocks = find_by_sql("call spSelectStockFromLocationNBinCode('{$FromLocationCode}','{$FromBinCode}');");
    foreach($all_stocks as &$value){
        $arr_item[]  = array($value["StockCode"],$value["ProductCode"],$value["ProductDesc"],$value["CostPrice"],$value["ExpireDate"],intval($value["SIH"]),0,$arr_serial);
    }
    $_SESSION['details'] = $arr_item; 

    return include('_partial_bindetails.php'); 
}



if (isset($_POST['StockCode']) && isset($_POST['arr'])) {
    $arr_serial = array();
  
    $StockCode = remove_junk($db->escape($_POST['StockCode']));
    $arr_serial = $db->escape_array($_POST['arr']);

    
    //Get all sessions values
    $arr_item = $_SESSION['details'];

    $arr_item = ChangValueFromListOfArray($arr_item,$StockCode,7,$arr_serial);

    $_SESSION['details'] = $arr_item;  
}



if (isset($_POST['StockCode'])) {
    $StockCode = remove_junk($db->escape($_POST['StockCode']));
    $serchitem = ArraySearch($arr_item,$StockCode);

    return include('_partial_binitem.php'); 
}



if (isset($_POST['FromLocationCode'])) {
    unset($_SESSION['details']); 
    
    $FromLocationCode = remove_junk($db->escape($_POST['FromLocationCode']));

    $Bins = find_by_sql("call spSelectBinFromLocationCode('{$FromLocationCode}');");
    echo "<option>Select Bin</option>";
    $selected = "";

    foreach($Bins as &$value){
        if($value["BinCode"] == $arr_header["FromBin"]) $selected = "selected";

        echo "<option value ={$value["BinCode"]} {$selected}>{$value["BinDesc"]}</option>";
    }

    return;
}

if (isset($_POST['ToLocationCode'])) {
   
    $ToLocationCode = remove_junk($db->escape($_POST['ToLocationCode']));
    $selected = "";

    $Bins = find_by_sql("call spSelectBinFromLocationCode('{$ToLocationCode}');");
    echo "<option>Select Bin</option>";

    foreach($Bins as &$value){
        if($value["BinCode"] == $arr_header["ToBin"]) $selected = "selected";

        echo "<option value ={$value["BinCode"]} {$selected}>{$value["BinDesc"]}</option>";
    }

    return;
}
?>

<?php include_once('layouts/header.php'); ?>

<section class="content-header">
    <h1>
       Create Transfer Note
    </h1>
    <ol class="breadcrumb">
        <li>
            <a href="#">
                <i class="fa fa-dashboard"></i>Transaction
            </a>
        </li>
        <li class="active">Transfer Note</li>
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
    <form method="post" action="create_transfernote.php">
        <div class="box box-default">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12 ">
                        <div class="btn-group">
                            <button type="submit" name="create_transfernote" class="btn btn-primary" value="save">&nbsp;Save&nbsp;&nbsp;</button>
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
                            <div class="form-group">
                                <label>Transfer Note No</label>
                                <input type="text" class="form-control" name="TrnNoteNo" placeholder="Code will generate after save" readonly="readonly" disabled="disabled" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>To Location</label>
                            <select class="form-control select2" style="width: 100%;" name="ToLocationCode" id="ToLocationCode" required="required" onchange="FillToBin();">
                                <option value="">Select Location</option><?php  foreach ($all_locations as $loc): ?>
                                <option value="<?php echo $loc['LocationCode'] ?>" <?php if($loc['LocationCode'] == $arr_header["ToLocation"]) echo "selected";  ?> ><?php echo $loc['LocationName'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>
                       
                        <div class="form-group">
                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea name="Remarks" id="Remarks" class="form-control" placeholder="Enter remarks here.." > <?php echo remove_junk($arr_header['Remarks']) ?> </textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>From Location</label>
                            <select class="form-control select2" style="width: 100%;" name="FromLocationCode" id="FromLocationCode" required="required" onchange="FillFromBin();">
                                <option value="">Select Location</option><?php  foreach ($all_locations as $loc): ?>
                                <option value="<?php echo $loc['LocationCode'] ?>" <?php if($loc['LocationCode'] == $arr_header["FromLocation"]) echo "selected";  ?>><?php echo $loc['LocationName'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>To Bin</label>
                            <select class="form-control select2" style="width: 100%;" name="ToBinCode" id="ToBinCode" required="required">
                                <option value="">Select Bin</option><?php foreach ($all_ToBin as $bin): ?>
                                <option value="<?php echo $bin['BinCode'] ?>" <?php if($bin['BinCode'] == $arr_header["ToBin"]) echo "selected";  ?>><?php echo $bin['BinDesc'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>

                    </div>


                    <div class="col-md-4">
                        <div class="form-group">
                            <label>From Bin</label>
                            <select class="form-control select2" style="width: 100%;" name="FromBinCode" id="FromBinCode" required="required" onchange="FillDetails();">
                                <option value="">Select Bin</option><?php foreach ($all_FromBin as $bin): ?>
                                <option value="<?php echo $bin['BinCode'] ?>" <?php if($bin['BinCode'] == $arr_header["FromBin"]) echo "selected";  ?>><?php echo $bin['BinDesc'] ?>
                                </option><?php endforeach; ?>
                            </select>
                        </div>


                        <div class="form-group">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="text" class="form-control" name="TrnNoteDate" placeholder="Date" readonly="readonly" disabled="disabled" value="<?php echo make_date(); ?>" />
                            </div>
                        </div>
                      
                    </div>

                </div>
            </div>
        </div>
    </form>


    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Bin Stock Item(s)</h3>

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
                        <?php include('_partial_bindetails.php'); ?>
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
                url: 'create_grn.php',
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

    function FillFromBin() {
        var FromLocationCode = $('#FromLocationCode').val();
        $.ajax({
            url: "create_transfernote.php",
            type: "POST",
            data: { FromLocationCode: FromLocationCode },
            success: function (result) {
                $("#FromBinCode").html(""); // clear before appending new list
                $("#FromBinCode").html(result);
            }
        });

    }


    function FillToBin() {
        var ToLocationCode = $('#ToLocationCode').val();

        $.ajax({
            url: "create_transfernote.php",
            type: "POST",
            data: { ToLocationCode: ToLocationCode },
            success: function (result) {
                $("#ToBinCode").html(""); // clear before appending new list
                $("#ToBinCode").html(result);
            }
        });

    }


    function FillDetails() {
        var FromLocationCode = $('#FromLocationCode').val();
        var FromBinCode = $('#FromBinCode').val();

        $.ajax({
            type: "POST",
            url: "create_transfernote.php", // Name of the php files
            data: { FromLocationCode: FromLocationCode, FromBinCode: FromBinCode },
            success: function (result) {
                $("#table").html(result);
            }
        });

    }
</script>

<?php include_once('layouts/footer.php'); ?>
