<?php
/*
   department.php - Administer Departments
   Copyright (C) 2002-2011 Stephen Lawrence Jr.

   This program is free software; you can redistribute it and/or
   modify it under the terms of the GNU General Public License
   as published by the Free Software Foundation; either version 2
   of the License, or (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

// check for valid session 
session_start();
if (!isset($_SESSION['uid']))
{
    header('Location:index.php?redirection=' . urlencode( $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ) );
    exit;
}

// includes
include('odm-load.php');

$last_message = (isset($_REQUEST['last_message']) ? $_REQUEST['last_message'] : '');

// Make sure user is admin
$user_obj = new User($_SESSION['uid'], $GLOBALS['connection'], DB_NAME);
$secureurl = new phpsecureurl;
//If the user is not an admin and he/she is trying to access other account that
// is not his, error out.
if(!$user_obj->isAdmin() == true)
{
    header('Location:' . $secureurl->encode('error.php?ec=4'));
    exit;
}

/*
   Add A New Department
*/
if(isset($_GET['submit']) && $_GET['submit']=='add')
{
    draw_header(msg('area_add_new_department'), $last_message);
    ?>

        <form id="addDepartmentForm" action="department.php" method="POST" enctype="multipart/form-data">
    <table border="0" cellspacing="5" cellpadding="5">
            <tr>
                <td>
                    <b><?php echo msg('department')?></b>
                </td>
                <td colspan="3">
                    <input name="department" type="text" class="required" minlength="2">
<?php
                 // Call the plugin API
                 callPluginMethod('onDepartmentAddForm');
?>
                </td>
                <td align="center">
                    <input type="hidden" name="submit" value="Add Department">
                    <div class="buttons">
                        <button class="positive" type="submit" name="submit" value="Add Department"><?php echo msg('button_add_department')?></buttons>
                    </div>
                </td>
                <td align="center">
                    <div class="buttons">
                        <button class="negative cancel" type="submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                    </div>
                </td>
            </tr>
    </table>
           </form>
   <script>
  $(document).ready(function(){
    $('#addDepartmentForm').validate();
  });
  </script>
<?php
    draw_footer();
}
elseif(isset($_POST['submit']) && 'Add Department' == $_POST['submit'])
{
    //Add Departments
    //
    // Make sure they are an admin
    if (!$user_obj->isAdmin())
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }

    $department = (isset($_POST['department']) ? $_POST['department'] : '');
    if($department == '') {
        $last_message=msg('departmentpage_department_name_required');
        
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $last_message));
        exit;
    }
    //Check to see if this department is already in DB
    $query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}department where name='" . addslashes($department) . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    if(mysql_num_rows($result) != 0)
    {
        header('Location:' . $secureurl->encode(' error.php?ec=3&message=' . $department . ' already exist in the database'));
        exit;
    }
    $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}department (name) VALUES ('" . addslashes($department) . '\')';
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    // back to main page
    $last_message = urlencode(msg('message_department_successfully_added'));
    /////////Give New Department data's default rights///////////
    ////Get all default rights////
    $query = "SELECT id, default_rights FROM {$GLOBALS['CONFIG']['db_prefix']}data";
    $result = mysql_query($query, $GLOBALS['connection']) or die("Error in query: $query. " . mysql_error());
    $num_rows = mysql_num_rows($result);
    $data_array = array();

    for($index = 0; $index< $num_rows; $index++)
    {
        list($data_array[$index][0], $data_array[$index][1]) = mysql_fetch_row($result);
    }

    mysql_free_result($result);
    //////Get the new department's id////////////
    $query = "SELECT id FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE name = '" . addslashes($department) . "'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    $num_rows = mysql_num_rows($result);
    if( $num_rows != 1 )
    {
        header('Location: ' . $secureurl->encode('error.php?ec=14&message=unable to identify ' . $department));
        exit;
    }

    list($newly_added_dept_id) = mysql_fetch_row($result);
    ////Set default rights into department//////
    $num_rows = sizeof($data_array);
    for($index = 0; $index < $num_rows; $index++)
    {
        $query = "INSERT INTO {$GLOBALS['CONFIG']['db_prefix']}dept_perms (fid, dept_id, rights) values(".$data_array[$index][0].','. $newly_added_dept_id.','. $data_array[$index][1].')';
        $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    }
        
    // Call the plugin API
    callPluginMethod('onDepartmentAddSave', $newly_added_dept_id);
  
    header('Location: ' . $secureurl->encode('admin.php?last_message=' . $last_message));
}
elseif(isset($_POST['submit']) && $_POST['submit'] == 'Show Department')
{
    // query to show item
    draw_header(msg('area_department_information'), $last_message);
    //select name
    $query = "SELECT name,id FROM {$GLOBALS['CONFIG']['db_prefix']}department where id='$_POST[item]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    echo '<table name="main" cellspacing="15" border="0">';
    echo '<th>ID</th><th>' . msg('department') . '</th>';
    list($ldepartment) = mysql_fetch_row($result);
    echo '<tr><td>' . $_POST['item'] . '</td>';
    echo '<td>' . $ldepartment . '</td></tr>';
?>
                        <tr>
                            <td align="center" colspan="2"><b><?php echo msg('label_users_in_department')?></b></td>
                        </tr>
<?php
    // Display all users assigned to this department
    $query = "SELECT {$GLOBALS['CONFIG']['db_prefix']}department.id, {$GLOBALS['CONFIG']['db_prefix']}user.first_name, {$GLOBALS['CONFIG']['db_prefix']}user.last_name FROM {$GLOBALS['CONFIG']['db_prefix']}department, {$GLOBALS['CONFIG']['db_prefix']}user where {$GLOBALS['CONFIG']['db_prefix']}department.id='$_POST[item]' and {$GLOBALS['CONFIG']['db_prefix']}user.department='$_POST[item]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    while(list($lid, $lfirst_name, $llast_name) = mysql_fetch_row($result))
    {
        echo '<tr><td colspan="2">'.$lfirst_name.' '.$llast_name.'</td></tr>';
    }
?>
                        <form action="admin.php?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
                            <tr>
                                <td colspan="4" align="center"><div class="buttons"><button class="regular" type="Submit" name="" value="Back"><?php echo msg('button_back')?></button></div></td>
                            </tr>
                            </table>
                        </form>
<?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'showpick')
{
    draw_header(msg('area_choose_department'), $last_message);
    $showpick='';
?>
                            <table border="0" cellspacing="5" cellpadding="5">
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>?last_message=<?php echo $last_message; ?>" method="POST" enctype="multipart/form-data">
                                    <tr>
                                    <input type="hidden" name="state" value="<?php echo ($_GET['state']+1); ?>">
                                    <td><b><?php echo msg('department')?></b></td>
                                    <td colspan=3><select name="item">
    <?php
    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    while(list($lid, $lname) = mysql_fetch_row($result))
    {
        echo '<option value="' . $lid . '">' . $lname . '</option>';
    }

    mysql_free_result ($result);
    ?>
                                        </select></td>
                                    <td colspan="" align="center">
                                        <div class="buttons">
                                            <button class="positive" type="submit" name="submit" value="Show Department"><?php echo msg('button_view_department')?></button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="buttons">
                                            <button class="negative" type="Submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                                        </div>
                                    </td>

                                </td>
                                </tr>
                            </table>
                     </form>
 <?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'delete')
{
    draw_header(msg('department') . ': ' . msg('label_delete'), $last_message);

    $delete='';
    
    // Pull up a list of deparments excluding the one being deleted
    $reassign_list_query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department WHERE id != '{$_REQUEST['item']}' ORDER BY name";
    $reassign_list_result = mysql_query($reassign_list_query, $GLOBALS['connection']) or die ("Error in query: $reassign_list_query. " . mysql_error());

    // If the above statement returns less than 1 row they will need to create another category to re-assign to so display error
    if(mysql_num_rows($reassign_list_result) < 1)
    {
        echo msg('message_need_one_department');
        exit;
    }


    // query to show item
    echo '    <form action="department.php" method="POST" enctype="multipart/form-data">';
    echo '<table border=0>';
    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department where id={$_REQUEST['item']}";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in department lookup: $query. " . mysql_error());
    while(list($lid, $lname) = mysql_fetch_row($result))
    {
        echo '<tr><td>' .msg('label_id'). ' # :</td><td>' . $lid . '</td></tr>';
        echo '<tr><td>'.msg('label_name').' :</td><td>' . $lname . '</td></tr>';
    }


    {

    ?>
        <input type="hidden" name="id" value="<?php echo $_REQUEST['item']; ?>">
        <tr>
            <td>
                <?php echo msg('label_reassign_to');?>:
            </td>
            <td>
                  <select name="assigned_id">
                      <?php
                            while(list($lid, $lname) = mysql_fetch_row($reassign_list_result))
                            {
                                echo '<option value="' . $lid . '">' . $lname . '</option>';
                            }
                            mysql_free_result ($reassign_list_result);
                            ?>
                    </select>
            </td>
        </tr>
        <tr>
            <td valign="top"><?php echo msg('message_are_you_sure_remove')?></td>
            <td align="center">
                <div class="buttons">
                    <button class="positive" type="submit" name="deletedepartment" value="Yes"><?php echo msg('button_yes')?></button>
                </div>
                <div class="buttons">
                    <button class="negative" type="submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                </div>
            </td>
</tr>
</table>
    </form>
    <?php
    }
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'deletepick')
{
    draw_header(msg('department') . ': ' . msg('label_delete'), $last_message);
    ?>
    <table border="0" cellspacing="5" cellpadding="5">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
            <tr>
                <td><b><?php echo msg('department')?></b></td>
                <td colspan=3><select name="item">
                            <?php
                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                            $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                            while(list($lid, $lname) = mysql_fetch_row($result))
                            {
                                $str = '<option value="' . $lid . '"';
                                $str .= '>' . $lname . '</option>';
                                echo $str;
                            }
                            mysql_free_result ($result);
                            $deletepick='';
                            ?>
                    </select></td>

                <td></td>
                <td align="center">
                    <div class="buttons">
                        <button class="positive" type="submit" name="submit" value="delete"><?php echo msg('button_delete')?></button>                        
                    </div>
                </td>
                <td>
                    <div class="buttons">
                        <button class="negative cancel" type="submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                    </div>
                </td>
            </tr>
        </form>
    </table>
    <?php
    draw_footer();
}
elseif(isset($_REQUEST['deletedepartment']))
{
    // Make sure they are an admin
    if (!$user_obj->isAdmin())
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }

    // Set all old dept_id's to the new re-assigned dept_id or remove the old dept_id

    // Update entries in data table
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}data SET department='{$_REQUEST['assigned_id']}' WHERE department = '{$_REQUEST['id']}'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error when updating old department ID to re-assigned dept id: $query. " . mysql_error());

    // Update entries in user
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}user SET department='{$_REQUEST['assigned_id']}' WHERE department = '{$_REQUEST['id']}'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error when updating user old department ID to re-assigned dept id: $query. " . mysql_error());

    // Update entries in dept perms
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}dept_perms SET dept_id='{$_REQUEST['assigned_id']}' WHERE dept_id = '{$_REQUEST['id']}'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error when updating user old department ID to re-assigned dept id: $query. " . mysql_error());

    // Update entries in dept_reviewer
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}dept_reviewer SET dept_id='{$_REQUEST['assigned_id']}' WHERE dept_id = '{$_REQUEST['id']}'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error when updating dept_reviewer old department ID to re-assigned dept id: $query. " . mysql_error());

    // Delete from department
    $query = "DELETE FROM {$GLOBALS['CONFIG']['db_prefix']}department where id='$_REQUEST[id]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in deleting ID from department: $query. " . mysql_error());

    // back to main page
    $last_message = urlencode(msg('message_all_actions_successfull') . ' id:' . $_REQUEST['id']);
    header('Location: ' . $secureurl->encode('admin.php?last_message=' . $last_message));
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'modify')
{
    $dept_obj = new Department($_REQUEST['item'], $GLOBALS['connection'], DB_NAME);
    draw_header(msg('area_update_department') .': ' . $dept_obj->getName(),$last_message);
    ?>  
                        <form action="department.php" id="modifyDeptForm" method="POST" enctype="multipart/form-data">                                        
                            <table border="0" cellspacing="5" cellpadding="5">
                                
                                    <tr>
                                            <?php
                                            $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department where id='$_REQUEST[item]'";
                                            $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
                                            while(list($lid, $lname) = mysql_fetch_row($result))
                                            {
                                                ?>
                                        <td>
                                            <b><?php echo msg('department')?></b>
                                        </td>
                                        <td colspan="3">
                                            <input type="textbox" name="name" value="<?php echo $lname; ?>" class="required" maxlength="40">
                                            <input type="hidden" name="id" value="<?php echo $lid; ?>">
                                            <?php
                                            // Call the plugin API
                                            callPluginMethod('onDepartmentEditForm', $lid);
                                            ?>
                                        </td>
                                             <?php
                                            }
                                            mysql_free_result ($result);
                                            ?>
                                    </tr>
                                    <tr>
                                        <td align="center">
                                            <div class="buttons">
                                                <button class="positive" type="Submit" name="submit" value="Update Department"><?php echo msg('button_save')?></button>
                                            </div>
                                        </td>
                                        <td align="center">
                                            <div class="buttons">
                                                <button class="negative cancel" type="Submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                                            </div>
                                        </td>
                                    </tr>
                            </table>
                        </form>
   <script>
  $(document).ready(function(){
    $('#modifyDeptForm').validate();
  });
  </script>
                            <?php
    draw_footer();
}
elseif(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'updatepick')
{
    draw_header(msg('area_choose_department'), $last_message);
    ?>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" enctype="multipart/form-data">
                                <INPUT type="hidden" name="state" value="<?php echo ($_REQUEST['state']+1); ?>">
                                <table border="0" cellspacing="5" cellpadding="5">
                                    <tr>
                                        <td><b><?php echo msg('label_department_to_modify')?>:</b></td>
                                        <td colspan="3"><select name="item">
                                                    <?php
                                                    // query to get a list of departments
                                                    $query = "SELECT id, name FROM {$GLOBALS['CONFIG']['db_prefix']}department ORDER BY name";
                                                    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());

                                                    while(list($lid, $lname) = mysql_fetch_row($result))
                                                    {
                                                        echo '<option value="' . $lid . '">' . $lname . '</option>';
                                                    }
                                                    mysql_free_result ($result);
                                                    ?>
                                        </td>
                                        <td>
                                            <div class="buttons">
                                                <button class="positive" type="submit" name="submit" value="modify"><?php echo msg('button_modify_department')?></button>
                                            </div>
                                        </td>
                                        <td >
                                            <div class="buttons">
                                                <button class="negative" type="Submit" name="submit" value="Cancel"><?php echo msg('button_cancel')?></button>
                                            </div>
                                    </td>
                                    </tr>
                                </table>
                            </form>
    <?php
    draw_footer();
}
elseif(isset($_POST['submit']) && 'Update Department' == $_POST['submit'])
{ 
    // UPDATE Department
    // 
    // 
    // Make sure they are an admin
    if (!$user_obj->isAdmin())
    {
        header('Location:' . $secureurl->encode('error.php?ec=4'));
        exit;
    }
    
    $name = (isset($_POST['name']) ? $_POST['name'] : '');
    if($name == '') {
        $last_message=msg('departmentpage_department_name_required');
        
        header('Location: ' . $secureurl->encode('admin.php?last_message=' . $last_message));
        exit;
    }
    
    //Check to see if this department is already in DB
    $query = "SELECT name FROM {$GLOBALS['CONFIG']['db_prefix']}department where name=\"" . addslashes($name) . '" and id!=' . $_POST['id'];
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    if(mysql_num_rows($result) != 0)
    {
        header('Location: ' . $secureurl->encode('error.php?ec=3&last_message=' . $_POST['name'] . ' already exist in the database'));
        exit;
    }
    $query = "UPDATE {$GLOBALS['CONFIG']['db_prefix']}department SET name='" . addslashes($name) ."' where id='$_POST[id]'";
    $result = mysql_query($query, $GLOBALS['connection']) or die ("Error in query: $query. " . mysql_error());
    // back to main page
    $last_message = urlencode(msg('message_department_successfully_updated') . ' - ' . $name . '- id=' . $_POST['id']);
    
    // Call the plugin API
    callPluginMethod('onDepartmentModifySave', $_REQUEST);
    
    header('Location: ' . $secureurl->encode('admin.php?last_message=' . $last_message));
}
elseif (isset($_REQUEST['submit']) and $_REQUEST['submit'] == 'Cancel')
{
    header('Location: ' . $secureurl->encode("admin.php?last_message=" . urlencode(msg('message_action_cancelled'))));
}
else
{
    header('Location: ' . $secureurl->encode("admin.php?last_message=" . urlencode(msg('message_nothing_to_do'))));
}


