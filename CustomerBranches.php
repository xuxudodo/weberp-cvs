<?php

/* $Revision: 1.45 $ */

$PageSecurity = 3;

include('includes/session.inc');
$title = _('Customer Branches');
include('includes/header.inc');

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = strtoupper($_GET['DebtorNo']);
} else if (isset($_POST['DebtorNo'])){
	$DebtorNo = strtoupper($_POST['DebtorNo']);
}

if (!isset($DebtorNo)) {
	prnMsg(_('This page must be called with the debtor code of the customer for whom you wish to edit the branches for').'. <BR>'._('When the pages is called from within the system this will always be the case').' <BR>'._('Select a customer first then select the link to add/edit/delete branches'),'warn');
	include('includes/footer.inc');
	exit;
}


if (isset($_GET['SelectedBranch'])){
	$SelectedBranch = strtoupper($_GET['SelectedBranch']);
} else if (isset($_POST['SelectedBranch'])){
	$SelectedBranch = strtoupper($_POST['SelectedBranch']);
}

// This link is already available on the menu on this page
//echo "<A HREF='" . $rootpath . '/SelectCustomer.php?' . SID . "'>" . _('Back to Customers') . '</A><BR>';

if (isset($Errors)) {
	unset($Errors);
}

	//initialise no input errors assumed initially before we test
$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {

	$i=1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	$_POST['BranchCode'] = strtoupper($_POST['BranchCode']);

	if (strstr($_POST['BranchCode'],"'") OR strstr($_POST['BranchCode'],'"') OR strstr($_POST['BranchCode'],'&')) {
		$InputError = 1;
		prnMsg(_('The Branch code cannot contain any of the following characters')." -  & \'",'error');
		$Errors[$i] = 'BranchCode';
		$i++;
	}
	if (strlen($_POST['BranchCode'])==0) {
		$InputError = 1;
		prnMsg(_('The Branch code must be at least one character long'),'error');
		$Errors[$i] = 'BranchCode';
		$i++;
	}
	if (!is_numeric($_POST['FwdDate'])) {
		$InputError = 1;
		prnMsg(_('The date after which invoices are charged to the following month is expected to be a number and a recognised number has not been entered'),'error');
		$Errors[$i] = 'FwdDate';
		$i++;
	}
	if ($_POST['FwdDate'] >30) {
		$InputError = 1;
		prnMsg(_('The date (in the month) after which invoices are charged to the following month should be a number less than 31'),'error');
		$Errors[$i] = 'FwdDate';
		$i++;
	}
	if (!is_numeric($_POST['EstDeliveryDays'])) {
		$InputError = 1;
		prnMsg(_('The estimated delivery days is expected to be a number and a recognised number has not been entered'),'error');
		$Errors[$i] = 'EstDeliveryDays';
		$i++;
	}
	if ($_POST['EstDeliveryDays'] >60) {
		$InputError = 1;
		prnMsg(_('The estimated delivery days should be a number of days less than 60') . '. ' . _('A package can be delivered by seafreight anywhere in the world normally in less than 60 days'),'error');
		$Errors[$i] = 'EstDeliveryDays';
		$i++;
	}
	if (!isset($_POST['EstDeliveryDays'])) {
		$_POST['EstDeliveryDays']=1;
	}

	if ($_SESSION['geocode_integration']==1 ){
		// Get the lat/long from our geocoding host
		$sql = "SELECT * FROM geocode_param WHERE 1";
		$ErrMsg = _('An error occurred in retrieving the information');
		$resultgeo = DB_query($sql, $db, $ErrMsg);
		$row = DB_fetch_array($resultgeo);
		$api_key = $row['geocode_key'];
		$map_host = $row['map_host'];
		define("MAPS_HOST", $map_host);
		define("KEY", $api_key);

		$address = $_POST["BrAddress1"] . ", " . $_POST["BrAddress2"] . ", " . $_POST["BrAddress3"] . ", " . $_POST["BrAddress4"];

		$base_url = "http://" . MAPS_HOST . "/maps/geo?output=xml" . "&key=" . KEY;
		$request_url = $base_url . "&q=" . urlencode($address);
		$xml = simplexml_load_file($request_url) or die("url not loading");

      	$coordinates = $xml->Response->Placemark->Point->coordinates;
      	$coordinatesSplit = split(",", $coordinates);
      	// Format: Longitude, Latitude, Altitude
      	$latitude = $coordinatesSplit[1];
      	$longitude = $coordinatesSplit[0];

    	$status = $xml->Response->Status->code;
    	if (strcmp($status, "200") == 0) {
      		// Successful geocode
	    	$geocode_pending = false;
      		$coordinates = $xml->Response->Placemark->Point->coordinates;
      		$coordinatesSplit = split(",", $coordinates);
      		// Format: Longitude, Latitude, Altitude
      		$latitude = $coordinatesSplit[1];
      		$longitude = $coordinatesSplit[0];
    	} else {
      		// failure to geocode
      		$geocode_pending = false;
      		echo '<DIV class="page_help_text"><b>Geocode Notice:</b> Address: ' . $address . ' failed to geocode. ';
      		echo 'Received status ' . $status . '</DIV>';
    	}
	}
	if (isset($SelectedBranch) AND $InputError !=1) {

		/*SelectedBranch could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the 	delete code below*/

		$sql = "UPDATE custbranch SET brname = '" . $_POST['BrName'] . "',
						braddress1 = '" . $_POST['BrAddress1'] . "',
						braddress2 = '" . $_POST['BrAddress2'] . "',
						braddress3 = '" . $_POST['BrAddress3'] . "',
						braddress4 = '" . $_POST['BrAddress4'] . "',
						braddress5 = '" . $_POST['BrAddress5'] . "',
						braddress6 = '" . $_POST['BrAddress6'] . "',
						lat = '" . $latitude . "',
						lng = '" . $longitude . "',
						specialinstructions = '" . $_POST['specialinstructions'] . "',
						phoneno='" . $_POST['PhoneNo'] . "',
						faxno='" . $_POST['FaxNo'] . "',
						fwddate= " . $_POST['FwdDate'] . ",
						contactname='" . $_POST['ContactName'] . "',
						salesman= '" . $_POST['Salesman'] . "',
						area='" . $_POST['Area'] . "',
						estdeliverydays =" . $_POST['EstDeliveryDays'] . ",
						email='" . $_POST['Email'] . "',
						taxgroupid=" . $_POST['TaxGroup'] . ",
						defaultlocation='" . $_POST['DefaultLocation'] . "',
						brpostaddr1 = '" . $_POST['BrPostAddr1'] . "',
						brpostaddr2 = '" . $_POST['BrPostAddr2'] . "',
						brpostaddr3 = '" . $_POST['BrPostAddr3'] . "',
						brpostaddr4 = '" . $_POST['BrPostAddr4'] . "',
						disabletrans=" . $_POST['DisableTrans'] . ",
						defaultshipvia=" . $_POST['DefaultShipVia'] . ",
						custbranchcode='" . $_POST['CustBranchCode'] ."',
						deliverblind=" . $_POST['DeliverBlind'] . "
					WHERE branchcode = '$SelectedBranch' AND debtorno='$DebtorNo'";

		$msg = $_POST['BrName'] . ' '._('branch has been updated.');

	} else if ($InputError !=1) {

	/*Selected branch is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Customer Branches form */

        $sql = "INSERT INTO custbranch (branchcode,
						debtorno,
						brname,
						braddress1,
						braddress2,
						braddress3,
						braddress4,
						braddress5,
						braddress6,
						lat,
						lng,
 						specialinstructions,
						estdeliverydays,
						fwddate,
						salesman,
						phoneno,
						faxno,
						contactname,
						area,
						email,
						taxgroupid,
						defaultlocation,
						brpostaddr1,
						brpostaddr2,
						brpostaddr3,
						brpostaddr4,
						disabletrans,
						defaultshipvia,
						custbranchcode,
                       			        deliverblind)
				VALUES ('" . $_POST['BranchCode'] . "',
					'" . $DebtorNo . "',
					'" . $_POST['BrName'] . "',
					'" . $_POST['BrAddress1'] . "',
					'" . $_POST['BrAddress2'] . "',
					'" . $_POST['BrAddress3'] . "',
					'" . $_POST['BrAddress4'] . "',
					'" . $_POST['BrAddress5'] . "',
					'" . $_POST['BrAddress6'] . "',
					'" . $latitude . "',
					'" . $longitude . "',
					'" . $_POST['specialinstructions'] . "',
					" . $_POST['EstDeliveryDays'] . ",
					" . $_POST['FwdDate'] . ",
					'" . $_POST['Salesman'] . "',
					'" . $_POST['PhoneNo'] . "',
					'" . $_POST['FaxNo'] . "',
					'" . $_POST['ContactName'] . "',
					'" . $_POST['Area'] . "',
					'" . $_POST['Email'] . "',
					" . $_POST['TaxGroup'] . ",
					'" . $_POST['DefaultLocation'] . "',
					'" . $_POST['BrPostAddr1'] . "',
					'" . $_POST['BrPostAddr2'] . "',
					'" . $_POST['BrPostAddr3'] . "',
					'" . $_POST['BrPostAddr4'] . "',
					" . $_POST['DisableTrans'] . ",
					" . $_POST['DefaultShipVia'] . ",
					'" . $_POST['CustBranchCode'] ."',
					" . $_POST['DeliverBlind'] . "
					)";
	}

	$msg = _('Customer branch<b>').' ' . $_POST['BranchCode'] . ': ' . $_POST['BrName'] . ' '._('</b>has been added, add another branch, or return to <a href=index.php>Main Menu</a>');

	//run the SQL from either of the above possibilites

	$ErrMsg = _('The branch record could not be inserted or updated because');
	if ($InputError==0) {
		$result = DB_query($sql,$db, $ErrMsg);
	}

	if (DB_error_no($db) ==0 and $InputError==0) {
		prnMsg($msg,'success');
		unset($_POST['BranchCode']);
		unset($_POST['BrName']);
		unset($_POST['BrAddress1']);
		unset($_POST['BrAddress2']);
		unset($_POST['BrAddress3']);
		unset($_POST['BrAddress4']);
		unset($_POST['BrAddress5']);
		unset($_POST['BrAddress6']);
		unset($_POST['specialinstructions']);
		unset($_POST['EstDeliveryDays']);
		unset($_POST['FwdDate']);
		unset($_POST['Salesman']);
		unset($_POST['PhoneNo']);
		unset($_POST['FaxNo']);
		unset($_POST['ContactName']);
		unset($_POST['Area']);
		unset($_POST['Email']);
		unset($_POST['TaxGroup']);
		unset($_POST['DefaultLocation']);
		unset($_POST['DisableTrans']);
		unset($_POST['BrPostAddr1']);
		unset($_POST['BrPostAddr2']);
		unset($_POST['BrPostAddr3']);
		unset($_POST['BrPostAddr4']);
		unset($_POST['DefaultShipVia']);
		unset($_POST['CustBranchCode']);
		unset($_POST['DeliverBlind']);
		unset($SelectedBranch);
    }
} else if (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'

	$sql= "SELECT COUNT(*) FROM debtortrans WHERE debtortrans.branchcode='$SelectedBranch' AND debtorno = '$DebtorNo'";
	$result = DB_query($sql,$db);
	$myrow = DB_fetch_row($result);
	if ($myrow[0]>0) {
		prnMsg(_('Cannot delete this branch because customer transactions have been created to this branch') . '<BR>' .
			 _('There are').' ' . $myrow[0] . ' '._('transactions with this Branch Code'),'error');

	} else {
		$sql= "SELECT COUNT(*) FROM salesanalysis WHERE salesanalysis.custbranch='$SelectedBranch' AND salesanalysis.cust = '$DebtorNo'";

		$result = DB_query($sql,$db);

		$myrow = DB_fetch_row($result);
		if ($myrow[0]>0) {
			prnMsg(_('Cannot delete this branch because sales analysis records exist for it'),'error');
			echo '<BR>'._('There are').' ' . $myrow[0] . ' '._('sales analysis records with this Branch Code/customer');

		} else {

			$sql= "SELECT COUNT(*) FROM salesorders WHERE salesorders.branchcode='$SelectedBranch' AND salesorders.debtorno = '$DebtorNo'";
			$result = DB_query($sql,$db);

			$myrow = DB_fetch_row($result);
			if ($myrow[0]>0) {
				prnMsg(_('Cannot delete this branch because sales orders exist for it') . '. ' . _('Purge old sales orders first'),'warn');
				echo '<BR>'._('There are').' ' . $myrow[0] . ' '._('sales orders for this Branch/customer');
			} else {
				// Check if there are any users that refer to this branch code
				$sql= "SELECT COUNT(*) FROM www_users WHERE www_users.branchcode='$SelectedBranch' AND www_users.customerid = '$DebtorNo'";

				$result = DB_query($sql,$db);
				$myrow = DB_fetch_row($result);

				if ($myrow[0]>0) {
					prnMsg(_('Cannot delete this branch because users exist that refer to it') . '. ' . _('Purge old users first'),'warn');
				    echo '<BR>'._('There are').' ' . $myrow[0] . ' '._('users referring to this Branch/customer');
				} else {

					$sql="DELETE FROM custbranch WHERE branchcode='" . $SelectedBranch . "' AND debtorno='" . $DebtorNo . "'";
					$ErrMsg = _('The branch record could not be deleted') . ' - ' . _('the SQL server returned the following message');
    					$result = DB_query($sql,$db,$ErrMsg);
					if (DB_error_no($db)==0){
						prnMsg(_('Branch Deleted'),'success');
					}
				}
			}
		}
	} //end ifs to test if the branch can be deleted

}
if (!isset($SelectedBranch)){

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedBranch will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters then none of the above are true and the list of branches will be displayed with links to delete or edit each. These will call the same page again and allow update/input or deletion of the records*/

	$sql = "SELECT debtorsmaster.name,
			custbranch.branchcode,
			brname,
			salesman.salesmanname,
			areas.areadescription,
			contactname,
			phoneno,
			faxno,
			email,
			taxgroups.taxgroupdescription,
			custbranch.branchcode,
			custbranch.disabletrans
		FROM custbranch,
			debtorsmaster,
			areas,
			salesman,
			taxgroups
		WHERE custbranch.debtorno=debtorsmaster.debtorno
		AND custbranch.area=areas.areacode
		AND custbranch.salesman=salesman.salesmancode
		AND custbranch.taxgroupid=taxgroups.taxgroupid
		AND custbranch.debtorno = '$DebtorNo'";

	$result = DB_query($sql,$db);
	$myrow = DB_fetch_row($result);
	$TotalEnable = 0;
	$TotalDisable = 0;
	if ($myrow) {
		echo '<P Class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/customer.png" TITLE="' . _('Customer') . '" ALT="">' . ' ' . _('Branches defined for'). ' '. $DebtorNo . ' - ' . $myrow[0] . '</P>';
		echo '<CENTER><table border=1>';
		echo "<tr><th>"._('Code')."</th>
			<th>"._('Name')."</th>
			<th>"._('Branch Contact')."</th>
			<th>"._('Salesman')."</th>
			<th>"._('Area')."</th>
			<th>"._('Phone No')."</th>
			<th>"._('Fax No')."</th>
			<th>"._('Email')."</th>
			<th>"._('Tax Group')."</th>
			<th>"._('Enabled?')."</th></tr>";

		do {
			printf("<tr><td><font size=2>%s</td>
				<td><font size=2>%s</td>
				<td><font size=2>%s</font></td>
				<td><font size=2>%s</font></td>
				<td><font size=2>%s</font></td>
				<td><font size=2>%s</font></td>
				<td><font size=2>%s</font></td>
				<td><font size=2><a href='Mailto:%s'>%s</a></font></td>
				<td><font size=2>%s</font></td>
				<td><font size=2>%s</font></td>
				<td><font size=2><a href='%s?DebtorNo=%s&SelectedBranch=%s'>%s</font></td>
				<td><font size=2><a href='%s?DebtorNo=%s&SelectedBranch=%s&delete=yes' onclick=\"return confirm('" . _('Are you sure you wish to delete this branch?') . "');\">%s</font></td></tr>",
				$myrow[10],
				$myrow[2],
				$myrow[5],
				$myrow[3],
				$myrow[4],
				$myrow[6],
				$myrow[7],
				$myrow[8],
				$myrow[8],
				$myrow[9],
				($myrow[11]?_('No'):_('Yes')),
				$_SERVER['PHP_SELF'],
				$DebtorNo,
				urlencode($myrow[1]),
				_('Edit'),
				$_SERVER['PHP_SELF'],
				$DebtorNo,
				urlencode($myrow[1]),
				_('Delete Branch'));
			if ($myrow[11]){ $TotalDisable++; }
			else { $TotalEnable++; }

		} while ($myrow = DB_fetch_row($result));
		//END WHILE LIST LOOP
		echo '</table>';
		echo '<b>'.$TotalEnable.'</b> ' . _('Branches are enabled.') . '<br>';
		echo '<b>'.$TotalDisable.'</b> ' . _('Branches are disabled.') . '<br>';
		echo '<b>'.($TotalEnable+$TotalDisable). '</b> ' . _('Total Branches') . '<br>';
	} else {
		$sql = "SELECT debtorsmaster.name,
				address1,
				address2,
				address3,
				address4,
				address5,
				address6
			FROM debtorsmaster
			WHERE debtorno = '$DebtorNo'";

		$result = DB_query($sql,$db);
		$myrow = DB_fetch_row($result);
		echo '<B><CENTER>'._('No Branches are defined for').' - '.$myrow[0].'</CENTER></B>';
		$_POST['BranchCode'] = substr($DebtorNo,0,10);
		$_POST['BrName'] = $myrow[0];
		$_POST['BrAddress1'] = $myrow[1];
		$_POST['BrAddress2'] = $myrow[2];
		$_POST['BrAddress3'] = $myrow[3];
		$_POST['BrAddress4'] = $myrow[4];
		$_POST['BrAddress5'] = $myrow[5];
		$_POST['BrAddress6'] = $myrow[6];
		unset($myrow);
	}


}

//end of ifs and buts!

if (isset($SelectedBranch)) {
	echo '<Center><a href=' . $_SERVER['PHP_SELF'] . '?' . SID . 'DebtorNo=' . $DebtorNo. '>' . _('Show all branches defined for'). ' '. $DebtorNo . '</a></Center>';
}
echo '<BR>';

if (!isset($_GET['delete'])) {

	echo "<FORM METHOD='post' action=" . $_SERVER['PHP_SELF'] .'?' . SID . '>';

	if (isset($SelectedBranch)) {
		//editing an existing branch

		$sql = "SELECT branchcode,
				brname,
				braddress1,
				braddress2,
				braddress3,
				braddress4,
				braddress5,
				braddress6,
	                        specialinstructions,
				estdeliverydays,
				fwddate,
				salesman,
				area,
				phoneno,
				faxno,
				contactname,
				email,
				taxgroupid,
				defaultlocation,
				brpostaddr1,
				brpostaddr2,
				brpostaddr3,
				brpostaddr4,
				disabletrans,
				defaultshipvia,
				custbranchcode,
				deliverblind
			FROM custbranch
			WHERE branchcode='$SelectedBranch'
			AND debtorno='$DebtorNo'";

		$result = DB_query($sql, $db);
		$myrow = DB_fetch_array($result);

		if ($InputError==0) {
			$_POST['BranchCode'] = $myrow['branchcode'];
			$_POST['BrName']  = $myrow['brname'];
			$_POST['BrAddress1']  = $myrow['braddress1'];
			$_POST['BrAddress2']  = $myrow['braddress2'];
			$_POST['BrAddress3']  = $myrow['braddress3'];
			$_POST['BrAddress4']  = $myrow['braddress4'];
			$_POST['BrAddress5']  = $myrow['braddress5'];
			$_POST['BrAddress6']  = $myrow['braddress6'];
			$_POST['specialinstructions']  = $myrow['specialinstructions'];
			$_POST['BrPostAddr1']  = $myrow['brpostaddr1'];
			$_POST['BrPostAddr2']  = $myrow['brpostaddr2'];
			$_POST['BrPostAddr3']  = $myrow['brpostaddr3'];
			$_POST['BrPostAddr4']  = $myrow['brpostaddr4'];
			$_POST['EstDeliveryDays']  = $myrow['estdeliverydays'];
			$_POST['FwdDate'] =$myrow['fwddate'];
			$_POST['ContactName'] = $myrow['contactname'];
			$_POST['Salesman'] =$myrow['salesman'];
			$_POST['Area'] =$myrow['area'];
			$_POST['PhoneNo'] =$myrow['phoneno'];
			$_POST['FaxNo'] =$myrow['faxno'];
			$_POST['Email'] =$myrow['email'];
			$_POST['TaxGroup'] = $myrow['taxgroupid'];
			$_POST['DisableTrans'] = $myrow['disabletrans'];
			$_POST['DefaultLocation'] = $myrow['defaultlocation'];
			$_POST['DefaultShipVia'] = $myrow['defaultshipvia'];
			$_POST['CustBranchCode'] = $myrow['custbranchcode'];
			$_POST['DeliverBlind'] = $myrow['deliverblind'];
		}

		echo "<INPUT TYPE=HIDDEN NAME='SelectedBranch' VALUE='" . $SelectedBranch . "'>";
		echo "<INPUT TYPE=HIDDEN NAME='BranchCode'  VALUE='" . $_POST['BranchCode'] . "'>";
		echo "<CENTER><b>"._('Change Branch')."</b><br><TABLE> <TR><TD>"._('Branch Code').':</TD><TD>';
		echo $_POST['BranchCode'] . '</TD></TR>';

	} else { //end of if $SelectedBranch only do the else when a new record is being entered

	/* SETUP ANY $_GET VALUES THAT ARE PASSED.  This really is just used coming from the Customers.php when a new customer is created.
			Maybe should only do this when that page is the referrer?
	*/
		if (isset($_GET['BranchCode'])){
			$sql="SELECT name,
					address1,
					address2,
					address3,
					address4,
					address5,
					address6
					FROM
					debtorsmaster
					WHERE debtorno='".$_GET['BranchCode']."'";
			$result = DB_query($sql, $db);
			$myrow = DB_fetch_array($result);
			$_POST['BranchCode'] = $_GET['BranchCode'];
			$_POST['BrName']     = $myrow['name'];
		 	$_POST['BrAddress1'] = $myrow['addrsss1'];
        	$_POST['BrAddress2'] = $myrow['addrsss2'];
			$_POST['BrAddress3'] = $myrow['addrsss3'];
		 	$_POST['BrAddress4'] = $myrow['addrsss4'];
        	$_POST['BrAddress5'] = $myrow['addrsss5'];
			$_POST['BrAddress6'] = $myrow['addrsss6'];
		}
		if (!isset($_POST['BranchCode'])) {
			$_POST['BranchCode']='';
		}
		echo '<CENTER><b>'._('Add a Branch').'</b><br><TABLE><TR><TD>'._('Branch Code'). ':</TD>
		<TD><input ' .(in_array('BranchCode',$Errors) ?  'class="inputerror"' : '' ) .
				" tabindex=1 type='Text' name='BranchCode' SIZE=12 MAXLENGTH=10 value=" . $_POST['BranchCode'] . '></TD></TR>';
		$_POST['DeliverBlind'] = $_SESSION['DefaultBlindPackNote'];
	}

	//SQL to poulate account selection boxes
	$sql = "SELECT salesmanname, salesmancode FROM salesman";

	$result = DB_query($sql,$db);

	if (DB_num_rows($result)==0){
		echo '</TABLE>';
		prnMsg(_('There are no sales people defined as yet') . ' - ' . _('customer branches must be allocated to a sales person') . '. ' . _('Please use the link below to define at least one sales person'),'error');
		echo "<BR><A HREF='$rootpath/SalesPeople.php?" . SID . "'>"._('Define Sales People').'</A>';
		include('includes/footer.inc');
		exit;
	}

	echo '<input type=HIDDEN name="DebtorNo" value="'. $DebtorNo . '">';


	echo '<TR><TD>'._('Branch Name').':</TD>';
	if (!isset($_POST['BrName'])) {$_POST['BrName']='';}
	echo '<TD><input tabindex=2 type="Text" name="BrName" SIZE=41 MAXLENGTH=40 value="'. $_POST['BrName'].'"></TD></TR>';
	echo '<TR><TD>'._('Branch Contact').':</TD>';
	if (!isset($_POST['ContactName'])) {$_POST['ContactName']='';}
	echo '<TD><input tabindex=3 type="Text" name="ContactName" SIZE=41 MAXLENGTH=40 value="'. $_POST['ContactName'].'"></TD></TR>';
	echo '<TR><TD>'._('Street Address 1 (Street)').':</TD>';
	if (!isset($_POST['BrAddress1'])) {$_POST['BrAddress1']='';}
	echo '<TD><input tabindex=4 type="Text" name="BrAddress1" SIZE=41 MAXLENGTH=40 value="'. $_POST['BrAddress1'].'"></TD></TR>';
	echo '<TR><TD>'._('Street Address 2 (Suburb/City)').':</TD>';
	if (!isset($_POST['BrAddress2'])) {$_POST['BrAddress2']='';}
	echo '<TD><input tabindex=5 type="Text" name="BrAddress2" SIZE=41 MAXLENGTH=40 value="'. $_POST['BrAddress2'].'"></TD></TR>';
	echo '<TR><TD>'._('Street Address 3 (State)').':</TD>';
	if (!isset($_POST['BrAddress3'])) {$_POST['BrAddress3']='';}
	echo '<TD><input tabindex=6 type="Text" name="BrAddress3" SIZE=41 MAXLENGTH=40 value="'. $_POST['BrAddress3'].'"></TD></TR>';
	echo '<TR><TD>'._('Street Address 4 (Postal Code)').':</TD>';
	if (!isset($_POST['BrAddress4'])) {$_POST['BrAddress4']='';}
	echo '<TD><input tabindex=7 type="Text" name="BrAddress4" SIZE=31 MAXLENGTH=40 value="'. $_POST['BrAddress4'].'"></TD></TR>';
	echo '<TR><TD>'._('Street Address 5').':</TD>';
	if (!isset($_POST['BrAddress5'])) {$_POST['BrAddress5']='';}
	echo '<TD><input tabindex=8 type="Text" name="BrAddress5" SIZE=21 MAXLENGTH=20 value="'. $_POST['BrAddress5'].'"></TD></TR>';
	echo '<TR><TD>'._('Street Address 6').':</TD>';
	if (!isset($_POST['BrAddress6'])) {$_POST['BrAddress6']='';}
	echo '<TD><input tabindex=9 type="Text" name="BrAddress6" SIZE=16 MAXLENGTH=15 value="'. $_POST['BrAddress6'].'"></TD></TR>';
	echo '<TR><TD>'._('Special Instructions').':</TD>';
	if (!isset($_POST['specialinstructions'])) {$_POST['specialinstructions']='';}
	echo '<TD><input tabindex=10 type="Text" name="specialinstructions" size=56 value="'. $_POST['specialinstructions'].'"></TD></TR>';
	echo '<TR><TD>'._('Default days to deliver').':</TD>';
	if (!isset($_POST['EstDeliveryDays'])) {$_POST['EstDeliveryDays']=0;}
	echo '<TD><input ' .(in_array('EstDeliveryDays',$Errors) ?  'class="inputerror"' : '' ) .' tabindex=11 type="Text" name="EstDeliveryDays" SIZE=4 MAXLENGTH=2 value='. $_POST['EstDeliveryDays'].'></TD></TR>';
	echo '<TR><TD>'._('Forward Date After (day in month)').':</TD>';
	if (!isset($_POST['FwdDate'])) {$_POST['FwdDate']=0;}
	echo '<TD><input ' .(in_array('FwdDate',$Errors) ?  'class="inputerror"' : '' ) .' tabindex=12 type="Text" name="FwdDate" SIZE=4 MAXLENGTH=2 value='. $_POST['FwdDate'].'></TD></TR>';

	echo '<TR><TD>'._('Salesperson').':</TD>';
	echo '<TD><SELECT tabindex=13 name="Salesman">';

	while ($myrow = DB_fetch_array($result)) {
		if (isset($_POST['Salesman']) and $myrow['salesmancode']==$_POST['Salesman']) {
			echo '<OPTION SELECTED VALUE=';
		} else {
			echo '<OPTION VALUE=';
		}
		echo $myrow['salesmancode'] . '>' . $myrow['salesmanname'];

	} //end while loop

	echo '</SELECT></TD></TR>';

	DB_data_seek($result,0);

	$sql = 'SELECT areacode, areadescription FROM areas';
	$result = DB_query($sql,$db);
	if (DB_num_rows($result)==0){
		echo '</TABLE>';
		prnMsg(_('There are no areas defined as yet') . ' - ' . _('customer branches must be allocated to an area') . '. ' . _('Please use the link below to define at least one sales area'),'error');
		echo "<BR><A HREF='$rootpath/Areas.php?" . SID . "'>"._('Define Sales Areas').'</A>';
		include('includes/footer.inc');
		exit;
	}

	echo '<TR><TD>'._('Sales Area').':</TD>';
	echo '<TD><SELECT tabindex=14 name="Area">';
	while ($myrow = DB_fetch_array($result)) {
		if (isset($_POST['Area']) and $myrow['areacode']==$_POST['Area']) {
			echo '<OPTION SELECTED VALUE=';
		} else {
			echo '<OPTION VALUE=';
		}
		echo $myrow['areacode'] . '>' . $myrow['areadescription'];

	} //end while loop


	echo '</SELECT></TD></TR>';
	DB_data_seek($result,0);

	$sql = 'SELECT loccode, locationname FROM locations';
	$result = DB_query($sql,$db);

	if (DB_num_rows($result)==0){
		echo '</TABLE>';
		prnMsg(_('There are no stock locations defined as yet') . ' - ' . _('customer branches must refer to a default location where stock is normally drawn from') . '. ' . _('Please use the link below to define at least one stock location'),'error');
		echo "<BR><A HREF='$rootpath/Locations.php?" . SID . "'>"._('Define Stock Locations').'</A>';
		include('includes/footer.inc');
		exit;
	}

	echo '<TR><TD>'._('Draw Stock From').':</TD>';
	echo '<TD><SELECT tabindex=15 name="DefaultLocation">';

	while ($myrow = DB_fetch_array($result)) {
		if (isset($_POST['DefaultLocation']) and $myrow['loccode']==$_POST['DefaultLocation']) {
			echo '<OPTION SELECTED VALUE=';
		} else {
			echo '<OPTION VALUE=';
		}
		echo $myrow['loccode'] . '>' . $myrow['locationname'];

	} //end while loop

	echo '</SELECT></TD></TR>';
	echo '<TR><TD>'._('Phone Number').':</TD>';
	if (!isset($_POST['PhoneNo'])) {$_POST['PhoneNo']='';}
	echo '<TD><input tabindex=16 type="Text" name="PhoneNo" SIZE=22 MAXLENGTH=20 value="'. $_POST['PhoneNo'].'"></TD></TR>';

	echo '<TR><TD>'._('Fax Number').':</TD>';
	if (!isset($_POST['FaxNo'])) {$_POST['FaxNo']='';}
	echo '<TD><input tabindex=17 type="Text" name="FaxNo" SIZE=22 MAXLENGTH=20 value="'. $_POST['FaxNo'].'"></TD></TR>';

	if (!isset($_POST['Email'])) {$_POST['Email']='';}
	echo '<TR><TD>'.(($_POST['Email']) ? '<a href="Mailto:'.$_POST['Email'].'">'._('Email').':</a>' : _('Email').':').'</TD>';
      //only display email link if there is an email address
	echo '<TD><input tabindex=18 type="Text" name="Email" SIZE=56 MAXLENGTH=55 value="'. $_POST['Email'].'"></TD></TR>';

	echo '<TR><TD>'._('Tax Group').':</TD>';
	echo '<TD><SELECT tabindex=19 name="TaxGroup">';

	DB_data_seek($result,0);

	$sql = 'SELECT taxgroupid, taxgroupdescription FROM taxgroups';
	$result = DB_query($sql,$db);

	while ($myrow = DB_fetch_array($result)) {
		if (isset($_POST['TaxGroup']) and $myrow['taxgroupid']==$_POST['TaxGroup']) {
			echo '<OPTION SELECTED VALUE=';
		} else {
			echo '<OPTION VALUE=';
		}
		echo $myrow['taxgroupid'] . '>' . $myrow['taxgroupdescription'];

	} //end while loop

	echo '</SELECT></TD></TR>';
	echo '<TR><TD>'._('Transactions on this branch').":</TD><TD><SELECT tabindex=20 NAME='DisableTrans'>";
	if ($_POST['DisableTrans']==0){
		echo '<OPTION SELECTED VALUE=0>' . _('Enabled');
		echo '<OPTION VALUE=1>' . _('Disabled');
	} else {
		echo '<OPTION SELECTED VALUE=1>' . _('Disabled');
		echo '<OPTION VALUE=0>' . _('Enabled');
	}

	echo '	</SELECT></TD></TR>';

	echo '<TR><TD>'._('Default freight/shipper method').":</TD><TD><SELECT tabindex=21 name='DefaultShipVia'>";
	$SQL = 'SELECT shipper_id, shippername FROM shippers';
	$ShipperResults = DB_query($SQL,$db);
	while ($myrow=DB_fetch_array($ShipperResults)){
		if (isset($_POST['DefaultShipVia'])and $myrow['shipper_id']==$_POST['DefaultShipVia']){
			echo '<OPTION SELECTED VALUE=' . $myrow['shipper_id'] . '>' . $myrow['shippername'];
		}else {
			echo '<OPTION VALUE=' . $myrow['shipper_id'] . '>' . $myrow['shippername'];
		}
	}

	echo '</SELECT></TD></TR>';

	/* This field is a default value that will be used to set the value
	on the sales order which will control whether or not to display the
	company logo and address on the packlist */
	echo '<TR><TD>' . _('Default Packlist') . ":</TD><TD><SELECT tabindex=22 NAME='DeliverBlind'>";
        for ($p = 1; $p <= 2; $p++) {
            echo '<OPTION VALUE=' . $p;
            if ($p == $_POST['DeliverBlind']) {
                echo ' SELECTED>';
            } else {
                echo '>';
            }
            switch ($p) {
                case 1:
                    echo _('Show company details and logo'); break;
                case 2:
                    echo _('Hide company details and logo'); break;
            }
        }
    echo '</SELECT></TD></TR>';

	echo '<TR><TD>'._('Postal Address 1 (Street)').':</TD>';
	if (!isset($_POST['BrPostAddr1'])) {$_POST['BrPostAddr1']='';}
	echo '<TD><input tabindex=23 type="Text" name="BrPostAddr1" SIZE=41 MAXLENGTH=40 value="'. $_POST['BrPostAddr1'].'"></TD></TR>';
	echo '<TR><TD>'._('Postal Address 2 (Suburb/City)').':</TD>';
	if (!isset($_POST['BrPostAddr2'])) {$_POST['BrPostAddr2']='';}
	echo '<TD><input tabindex=24 type="Text" name="BrPostAddr2" SIZE=41 MAXLENGTH=40 value="'. $_POST['BrPostAddr2'].'"></TD></TR>';
	echo '<TR><TD>'._('Postal Address 3 (State)').':</TD>';
	if (!isset($_POST['BrPostAddr3'])) {$_POST['BrPostAddr3']='';}
	echo '<TD><input tabindex=25 type="Text" name="BrPostAddr3" SIZE=31 MAXLENGTH=30 value="'. $_POST['BrPostAddr3'].'"></TD></TR>';
	echo '<TR><TD>'._('Postal Address 4 (Postal Code)').':</TD>';
	if (!isset($_POST['BrPostAddr4'])) {$_POST['BrPostAddr4']='';}
	echo '<TD><input tabindex=26 type="Text" name="BrPostAddr4" SIZE=21 MAXLENGTH=20 value="'. $_POST['BrPostAddr4'].'"></TD></TR>';
	echo '<TR><TD>'._('Customers Internal Branch Code (EDI)').':</TD>';
	if (!isset($_POST['CustBranchCode'])) {$_POST['CustBranchCode']='';}
	echo '<TD><input tabindex=27 type="Text" name="CustBranchCode" SIZE=31 MAXLENGTH=30 value="'. $_POST['CustBranchCode'].'"></TD></TR>';
	echo '</TABLE>';
	echo '<CENTER><input tabindex=28 type="Submit" name="submit" value="' . _('Enter Branch') . '">';
	echo '</FORM>';

} //end if record deleted no point displaying form to add record

include('includes/footer.inc');
?>
