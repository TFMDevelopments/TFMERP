<?php
ob_start();
$page_title = 'Department Master - Edit Department';
require_once('includes/load.php');
page_require_level(2);

preventGetAction('department.php');

?>


<?php
if(isset($_POST['department'])){
    $p_depcode = remove_junk($db->escape($_POST['DepartmentCode']));

    if(!$p_depcode){
        $session->msg("d","Missing department identification.");
        redirect('department.php');
    }
    else
    {
        $department = find_by_sp("call spSelectDepartmentFromCode('{$p_depcode}');");

        if(!$department){
            $session->msg("d","Missing department details.");
            redirect('department.php');
        }
    }
}

?>

<?php
if(isset($_POST['edit_department'])){
    $req_fields = array('hDepartmentCode','DepartmentDesc');

    validate_fields($req_fields);

    if(empty($errors)){
        $p_DepartmentCode  = remove_junk($db->escape($_POST['hDepartmentCode']));
        $p_DepartmentDesc  = remove_junk($db->escape($_POST['DepartmentDesc']));

        $date    = make_date();
        $user =  current_user();

        $query  = "call spUpdateDepartment('{$p_DepartmentCode}','{$p_DepartmentDesc}','{$date}','{$user["username"]}');";

        if($db->query($query)){
            InsertRecentActvity("Department updated","Reference No. ".$p_DepartmentCode);

            $session->msg('s',"Department updated");
            redirect('department.php', false);
        } else {
            $session->msg('d',' Sorry failed to updated!');
            //redirect('customer.php', false);
        }

    } else{
        $session->msg("d", $errors);
        redirect('edit_department.php',false);
    }
}


?>

<?php include_once('layouts/header.php'); ?>
<section class="content-header">
    <h1>
        Department Master
        <small>Update Department Details</small>
    </h1>
    <ol class="breadcrumb">
        <li>
            <a href="#">
                <i class="fa fa-dashboard"></i>Master
            </a>
        </li>
        <li class="active">Department</li>
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
    <form method="post" action="edit_department.php">
        <div class="box box-default">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12 ">
                        <div class="btn-group">
                            <button type="submit" name="edit_department" class="btn btn-primary">&nbsp;Save&nbsp;&nbsp;</button>
                            <button type="button" class="btn btn-warning" onclick="window.location = 'department.php'">Cancel  </button>
                        </div>
                    </div>
                </div>
            </div>
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Department Code</label>
                            <input type="text" class="form-control" name="DepartmentCode" placeholder="Department Code" required="required" value="<?php echo remove_junk($department['DepartmentCode']);?>" readonly="readonly" disabled="disabled" />
                            <input type="hidden" name="hDepartmentCode" value="<?php echo remove_junk($department['DepartmentCode']);?>" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Department Description</label>
                            <input type="text" class="form-control" name="DepartmentDesc" placeholder="Department Description" required="required" value="<?php echo remove_junk($department['DepartmentDesc']);?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</section>

<?php include_once('layouts/footer.php'); ?>