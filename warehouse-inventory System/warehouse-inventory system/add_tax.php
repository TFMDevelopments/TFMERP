<?php
$page_title = 'Tax Master - New Tax';
require_once('includes/load.php');
page_require_level(2);
?>

<?php
if(isset($_POST['add_tax'])){
    $req_fields = array('TaxCode','TaxDesc','TaxRate');

    validate_fields($req_fields);

    if(empty($errors)){
        $p_TaxCode  = remove_junk($db->escape($_POST['TaxCode']));
        $p_TaxDesc  = remove_junk($db->escape($_POST['TaxDesc']));
        $p_TaxRate  = remove_junk($db->escape($_POST['TaxRate']));

        $date    = make_date();
        $user = "anush";

        $tax_count = find_by_sp("call spSelectTaxRatesFromCode('{$p_TaxCode}');");

        if($tax_count)
        {
            $session->msg("d", "This tax code exist in the system.");
            redirect('add_tax.php',false);
        }

        $query  = "call spInsertTaxRates('{$p_TaxCode}','{$p_TaxDesc}',{$p_TaxRate},'{$date}','{$user}');";

        if($db->query($query)){
            $session->msg('s',"Tax added ");
            redirect('add_tax.php', false);
        } else {
            $session->msg('d',' Sorry failed to added!');
            redirect('tax.php', false);
        }

    } else{
        $session->msg("d", $errors);
        redirect('add_tax.php',false);
    }
}

?>


<?php include_once('layouts/header.php'); ?>
<section class="content-header">
    <h1>
        Tax Master
        <small>Enter New Tax Details</small>
    </h1>
    <ol class="breadcrumb">
        <li>
            <a href="#">
                <i class="fa fa-dashboard"></i>Master
            </a>
        </li>
        <li class="active">Tax</li>
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
    <form method="post" action="add_tax.php">
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tax Code</label>
                            <input type="text" class="form-control" name="TaxCode" placeholder="Tax Code" required="required" />
                        </div>

                        <div class="form-group">
                            <label>Tax Rate (%)&nbsp;<output for="fader" id="rate">0</output> </label>
                            <input type="range" min="0" max="100" value="0" step="1" class="form-control" name="TaxRate" placeholder="Tax Rate (%)" required="required" oninput="outputUpdate(value)" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tax Description</label>
                            <input type="text" id="fader" class="form-control col-md-4" name="TaxDesc" placeholder="Tax Description" required="required" />
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <button type="submit" name="add_tax" class="btn btn-success btn-lg">Save  </button>
    </form>

        <div class="form-group"></div>

</section>

<?php include_once('layouts/footer.php'); ?>

<script>
    function outputUpdate(vol) {
        document.querySelector('#rate').value = vol;
    }
</script>