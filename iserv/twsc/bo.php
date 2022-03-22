<?php
	if (!defined("%INCLUDE_CHECK_VAL%")) {
		header("Location: ../common/admin.php?view=orders");
		die();
	}
	if( !function_exists('json_decode') ) {
		@require_once('../common/json.php');
		function json_decode($data) {
			$json = new Services_JSON();
			return( $json->decode($data) );
		}
	}
	$basedir = "../twsc";	
	$datadir = "$basedir/data";	
	$DateFmt = "d/m/y H:i:s";
	$daysFromNow = isset($_GET['daysFromNow']) ? intval($_GET['daysFromNow']) : 0;
	if( $daysFromNow == 0 )
		$daysFromNow = 90;
	if( $lang == "fr" ) 
	{
		$Title = 'Commandes';
		$Periods = array (
			7 => '1 semaine',
			30 => '1 mois',
			90 => '3 mois',
			180 => '6 mois',
			365 => '1 an',
			1095 => '3 ans',
		);
		$States = array (
			'payment_wait' => array( 'label' => 'Attente paiement', 'color' => '' ),
			'payment_ok' => array( 'label' => 'Paiement validé', 'color' => 'label-important' ),
			'preparing' => array( 'label' => 'En préparation', 'color' => 'label-warning' ),
			'ready_for_pickup' => array( 'label' => 'Prête à enlever', 'color' => 'label-inverse' ),
			'shipped' => array( 'label' => 'Envoyée', 'color' => 'label-info' ),
			'delivered' => array( 'label' => 'Livrée', 'color' => 'label-success' ),
			'received' => array( 'label' => "Réceptionnée", 'color' => "label-success" ),			
			'canceled' => array( 'label' => "Annulée", 'color' => "label-bw" )
		);
		$NotifySubject = 'Commande #%s : %s';
		$ModalStatusLabel = 'Changer l\'état de #';
		$ModalNotifyLabel = 'Notifier ';
		$ModalNotifyHelper = 'Laissez ce champ vide pour désactiver la notification par email';
		$ModalCloseBtn = 'Annuler';
		$ModalSaveBtn = 'Changer l\'état';
		$TotalSales = 'Chiffre d\'affaire';
		$TotalOrders = 'Commandes';
		$AvgOrderValue = 'Panier moyen';
		$LastNDays = '%s les %d derniers jours';
		$LastNMonths = '%s les %d derniers mois';
		$DateFmt = "d/m/y H:i:s";
		$DeleteBtn = "Supprimer";
		$DeleteAllBtn = "Tout supprimer";
		$DeleteConfirm = "Cette action est irréversible ! Etes-vous sûr de vouloir supprimer cette commande ?";
		$DeleteAllConfirm = "Cette action est irréversible ! Etes-vous sûr de vouloir supprimer TOUS vos fichiers de commande ?";
		$noroderfiles = "Aucun fichier de commande";
		$OrderNum = "Commande #";
		$OrderState = "État";
		$TXTVersion = "TXT";
		$JSONVersion = "JSON";
		$HTMLVersion = "HTML";
		$Click4Details = "Commande dans l'ancien format TOWeb (< 5.16). Cliquez pour voir le détail.";	
	}
	else
	{
		$Title = 'Orders';
		$Periods = array (
			7 => '1 week',
			30 => '1 month',
			90 => '3 months',
			180 => '6 months',
			365 => '1 year',
			1095 => '3 years',
		);
		$States = array (
			'payment_wait' => array( 'label' => 'Awaiting payment', 'color' => '' ),
			'payment_ok' => array( 'label' =>  'Payment accepted', 'color' => 'label-important' ),
			'preparing' => array( 'label' =>  'In preparation', 'color' => 'label-warning' ),
			'ready_for_pickup' => array( 'label' => 'Ready for pickup', 'color' => 'label-inverse' ),
			'shipped' => array( 'label' =>  'Shipped', 'color' => 'label-info' ),
			'delivered' => array( 'label' =>  'Delivered', 'color' => 'label-success' ),
			'received' => array( 'label' => "Received", 'color' => "label-success" ),			
			'canceled' => array( 'label' => "Canceled", 'color' => "label-bw" )
		);
		$NotifySubject = 'Order #%s: %s';
		$ModalStatusLabel = 'Change status of #';
		$ModalNotifyLabel = 'Send notification to ';
		$ModalNotifyHelper = 'Leave this field blank to disable email notification.';
		$ModalCloseBtn = 'Cancel';
		$ModalSaveBtn = 'Change status';
		$TotalSales = 'Sales';
		$TotalOrders = 'Orders';
		$AvgOrderValue = 'Average shopping cart';
		$LastNDays = '%s in the last %d days';
		$LastNMonths = '%s in the last %d months';
		$DateFmt = "m/d/y H:i:s";
		$DeleteBtn = "Delete";
		$DeleteAllBtn = "Delete all files";
		$DeleteConfirm = "This action is irreversible ! Are you sure you want to delete this order ?";
		$DeleteAllConfirm = "This action is irreversible ! Are you sure you want to delete ALL your order files ?";
		$OrderNum = "Order #";
		$OrderState = "Status";
		$noroderfiles = "No order file";
		$TXTVersion = "TXT";
		$JSONVersion = "JSON";
		$HTMLVersion = "HTML";
		$Click4Details = "Order in the former format of TOWeb (<5.16). Click to see the detail.";
	}
	$dir_handle = @opendir($datadir) or die( $noroderfiles );      
	$delfile = isset($_REQUEST['delallfiles']) ? $_REQUEST['delallfiles'] : "";
	if( strlen($delfile) > 0 ) 
	{
		while ( $file = readdir($dir_handle) ) 
		{
			if( $file == "." || $file == ".." || $file == $scriptfilename )          
				continue;         
			$ext = strtolower( substr($file, strrpos($file, '.') + 1) );
			if( $ext == "txt" || $ext == "html" || $ext == "json")
				@unlink("$datadir/$file");
		}
	} 
	else
	{
		function getsourl( $oid, $fmt ) {
			global $basedir;
			return "$basedir/so.php?oid=" . $oid . "&fmt=" . $fmt . "&ctr=" . getCtr( $oid, $fmt );
		}
		function getOList() {
			global $datadir, $dir_handle;
			$oarray = array();
			while( false !== ($file = readdir($dir_handle)) ) 
			{
				$ext = strtolower( substr($file, strrpos($file, '.') + 1) );
				if($file == "." || $file == ".." || $ext != "txt" )
					continue;
				$oarray[] = array( substr($file, 0, strrpos($file, '.')), filemtime("$datadir/$file"));
			}
			function cmp($a, $b) {
				if($a[1] == $b[1])
					return 0;
				else
					return ($a[1] < $b[1]) ? 1 : -1;
			}
			usort($oarray, 'cmp');
			return $oarray;
		}
		function O2Stats($oarray, $daysFromNow) {
			global $scriptfilename, $datadir, $DateFmt, $TXTVersion, $DeleteBtn, $DeleteConfirm, $OrderNum, $OrderState, $States, $ModalStatusLabel, $ModalNotifyLabel, $ModalNotifyHelper, $ModalCloseBtn, $ModalSaveBtn, $NotifySubject, $MERCHANT_FROM, $Click4Details;
			$now = date("Y-m-d");
			$minDate = date("Y-m-d", strtotime("$now -$daysFromNow days" ));
			$cum = array();
			for( $i=$daysFromNow; $i>=0; $i-- ) {
				$date = date("Y-m-d", strtotime("$now -$i days" ));
				$cum[$date]['count'] = 0;
				$cum[$date]['shipping'] = 0;
				$cum[$date]['taxes'] = 0;
				$cum[$date]['total'] = 0;
			}
			$htmlOrders = 
				"<table class='table table-condensed table-hover'>
				<thead>
					<tr>
					  <th>Date</th>
					  <th>$OrderState</th>
					  <th>$OrderNum</th>
					  <th>Client</th>
					  <th style='text-align:right'>Total</th>
					  <th>Action</th>
					</tr>
				</thead><tbody>";
			for( $i=0;$i<count( $oarray );$i++ )
			{
				$oid = $oarray[$i][0];
				$file = "$oid.txt";	
				$href = file_exists( "$datadir/$oid.html" ) ? getsourl( $oid, "html" ) : getsourl( $oid, "txt" );
				$htmlOrder = 
					"<tr>
				<td>" . date($DateFmt, $oarray[$i][1]) . "</td>";
				$json = '';
				if(file_exists( "$datadir/$oid.json" ))
					$json = substr( file_get_contents( "$datadir/$oid.json" ), 3 );	
				if($json !== '') {
					$json = preg_replace('/[^:,{]"[^:,}]/', "'", $json);
					$json = json_decode( $json );
					$state = '';
					if(file_exists( "$datadir/$oid.state" )) {
						$state = getOrderStatus( "$datadir/$oid.state", "payment_wait" );
						if($States[$state]['label'] == "") {
							$state = "payment_wait";
						}
					} else
						$state = "delivered"; 
					$htmlOrder .= 
						"<td><a href='#myModal' onclick='event.stopPropagation(); 
						$(\"#stateOID\").val(\"$oid\");
						$(\"#titleOID\").html(\"$oid\");
						$(\"#sendTo\").val(\"".$json->shipping_details->csi_email."\");
						$(\"#clientEmail\").html(\"".$json->shipping_details->csi_email."\");
						$(\"#id_$state\").attr(\"checked\", \"checked\");
						$(\"#myModal\").modal();'>
						<span class='label ".$States[$state]['color']."'>".$States[$state]['label']."</span></a></td>".
						"<td><a href='$href' target='_blank'>$oid</a><br/><small>" . (isset($json->shipping_mode) ? $json->shipping_mode : "") . "<br/>" . (isset($json->payment_mode) ? $json->payment_mode : "") . "</small></td>" .
						"<td><a href='$href' target='_blank'>$oid</a><br/><small>" . (isset($json->shipping_mode) ? $json->shipping_mode : "") . "<br/>" . (isset($json->payment_mode) ? $json->payment_mode : "") . "</small></td>" .
						"<td>" . $json->shipping_details->csi_firstname . ' ' . $json->shipping_details->csi_lastname . 
						"<br/><small>" . $json->shipping_details->csi_address1 . 
						"<br/>" . $json->shipping_details->csi_zip . ' ' . $json->shipping_details->csi_city . 
						( ($json->shipping_details->csi_email == '') ? "" : "<br/><a onclick='location.href=\"mailto:" . $json->shipping_details->csi_email . "\";event.stopPropagation();' href='#'>" . $json->shipping_details->csi_email . "</a>" ) .
						"</small></td>
						<td style='text-align:right'>" . $json->total_str . "</td>";
					$date = date("Y-m-d", round($json->date/1000));
					if(array_key_exists($date, $cum)) {
						$cum[$date]['count'] += 1;
						$cum[$date]['shipping'] += $json->shipping_amount;
						$cum[$date]['total'] += $json->total_num;
					}
					if($date <= $minDate)
						$htmlOrder = '';
				}
				else {
					$htmlOrder .= "<td>$oid</td>" .
						 "<td colspan='3'><small><a target='_blank' onclick='event.stopPropagation();' href=\"" . getsourl( $oid, "txt" ) . "\">$Click4Details</a></small></td>";
				}
				if( $htmlOrder != '')
					$htmlOrder .= "
					<td>
						<form method=\"post\" action=\"$scriptfilename?view=orders&daysFromNow=$daysFromNow\" style='margin:0;padding:0;display:inline-block;'><button class=\"btn btn-small\" type=\"submit\" title=\"$DeleteBtn\" onclick='event.stopPropagation();return(confirm(\"$DeleteConfirm\"));' ><i class=\"icon-trash\"></i></button><input type=\"hidden\" name=\"delfile\" value=\"$file\"></form>&nbsp;" .
						"<a target='_blank' onclick='event.stopPropagation();' href=\"" . getsourl( $oid, "txt" ) . "\">$TXTVersion</a>" .
					"</td>";
				if( $htmlOrder != '')
					$htmlOrders .=  $htmlOrder;
			}
			$htmlOrders .= "</tbody></table><br/>";
	    $config = loadGlobalConfig();
			$htmlOrders .= "
				<div id='myModal' class='modal hide' tabindex='-1' role='dialog'>
				  <!--
				  <div class='modal-header'>
				    <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>×</button>
				    <h3 id='myModalLabel'>Status of order #<span id='titleOID'>OID</span></h3>
				  </div>
				  -->
				  <div class='modal-body'>
					<form method=\"post\" action=\"$scriptfilename?view=orders&daysFromNow=$daysFromNow\" style='margin:0;padding:0'>
						<fieldset>
						<legend>$ModalStatusLabel<span id='titleOID'>OID</span></legend>
						<input type=\"hidden\" name=\"action\" value=\"setOrderState\">
						<input id='stateOID' type=\"hidden\" name=\"orderID\" value=\"OID\">
						<input id='sendTo' type=\"hidden\" name=\"sendTo\" value=\"EMAIL\">
						<label class='radio'>
							<input type='radio' name='newState' id='id_payment_wait' value='payment_wait' onclick='$(\"#notifyMsg\").val(\"".$config['order_notifymessages']['payment_wait']."\")'>
							<span class='label ".$States['payment_wait']['color']."'>
						  		". $States['payment_wait']['label'] ."
							</span>
						</label>
						<label class='radio'>
							<input type='radio' name='newState' id='id_payment_ok' value='payment_ok' onclick='$(\"#notifyMsg\").val(\"".htmlentities(str_replace('"','\"', $config['order_notifymessages']['payment_ok']), ENT_QUOTES)."\")'>
							<span class='label ".$States['payment_ok']['color']."'>
							  ". $States['payment_ok']['label'] ."
							</span>
						</label>
						<label class='radio'>
							<input type='radio' name='newState' id='id_preparing' value='preparing' onclick='$(\"#notifyMsg\").val(\"".htmlentities(str_replace('"','\"', $config['order_notifymessages']['preparing']), ENT_QUOTES)."\")'>
							<span class='label ".$States['preparing']['color']."'>
							  ". $States['preparing']['label'] ."
							</span>
						</label>
						<label class='radio'>
							<input type='radio' name='newState' id='id_ready_for_pickup' value='ready_for_pickup' onclick='$(\"#notifyMsg\").val(\"".htmlentities(str_replace('"','\"', $config['order_notifymessages']['ready_for_pickup']), ENT_QUOTES)."\")'>
							<span class='label ".$States['ready_for_pickup']['color']."'>
							  ". $States['ready_for_pickup']['label'] ."
							</span>
						</label>
						<label class='radio'>
							<input type='radio' name='newState' id='id_shipped' value='shipped' onclick='$(\"#notifyMsg\").val(\"".htmlentities(str_replace('"','\"', $config['order_notifymessages']['shipped']), ENT_QUOTES)."\")'>
							<span class='label ".$States['shipped']['color']."'>
							  ". $States['shipped']['label'] ."
							</span>
						</label>
						<label class='radio'>
							<input type='radio' name='newState' id='id_delivered' value='delivered' onclick='$(\"#notifyMsg\").val(\"".htmlentities(str_replace('"','\"', $config['order_notifymessages']['delivered']), ENT_QUOTES)."\")'>
							<span class='label ".$States['delivered']['color']."'>
							  ". $States['delivered']['label'] ."
							</span>
						</label>
						<label class='radio'>
							<input type='radio' name='newState' id='id_delivered' value='canceled' onclick='$(\"#notifyMsg\").val(\"".htmlentities(str_replace('"','\"', $config['order_notifymessages']['canceled']), ENT_QUOTES)."\")'>
							<span class='label ".$States['canceled']['color']."'>
							  ". $States['canceled']['label'] ."
							</span>
						</label>
						<legend>$ModalNotifyLabel<b><span id='clientEmail'>EMAIL</span></b></legend>
						<textarea id='notifyMsg' name='message' style='width:95%' rows='3'></textarea>
						<span class='help-block'>$ModalNotifyHelper</span>
						</fieldset>
					</form>
				  </div>
				  <div class='modal-footer'>
				    <button class='btn' data-dismiss='modal' aria-hidden='true'>$ModalCloseBtn</button>
					<button id='submitState' class=\"btn  btn-primary\" type=\"submit\">$ModalSaveBtn</button>
				  </div>
				</div>
				<script>
					$('#submitState').click(function(e){
						e.preventDefault();
						$('#myModal form').submit();
					});				
				</script>
				";
			ksort($cum);
			$stats = array(
				'count' => 0,
				'total' => 0,
				'shipping' => 0,
				'avgSale' => 0,
				'period' => array (
					'count' => 0,
					'total' => 0,
					'shipping' => 0,
					'avgSale' => 0,
				),
				'dataset' => '',
				'htmlOrders' => $htmlOrders,
				'dsSales' => '',
				'dsCount' => '',
				'dsAvgSale' => ''
			);
			foreach ($cum as $key => $value) {
				$stats['count'] += $value['count'];
				$stats['total'] += $value['total'];
				$stats['shipping'] += $value['shipping'];
				if( $key >= $minDate ) {
					$stats['period']['count'] += $value['count'];
					$stats['period']['total'] += $value['total'];
					$stats['period']['shipping'] += $value['shipping'];
					$stats['dsSales'] .= "{x:new Date('$key'), y:".$value['total']."},";
					$stats['dsCount'] .= "{x:new Date('$key'), y:".$value['count']."},";
					if($value['count'] >0)
						$stats['dsAvgSale'] .= "{x:new Date('$key'), y:".$value['total']/$value['count']."},";
					else
						$stats['dsAvgSale'] .= "{x:new Date('$key'), y:0},";
				}
			}
			if( $stats['count'] > 0 )
				$stats['avgSale'] = $stats['total'] / $stats['count'];
			else
				$stats['avgSale'] = 0;
			if( $stats['period']['count'] > 0 )
				$stats['period']['avgSale'] = $stats['period']['total'] / $stats['period']['count'];
			else
				$stats['period']['avgSale'] = 0;
			return $stats;   
		}	
		function makeChart($id, $dataset) {
			global $daysFromNow;
			return 
				"<canvas id='$id' width='800' height='400'></canvas>
				<script>
				var ctx = document.getElementById('$id').getContext('2d');
				var myChart = new Chart(ctx, {
				    type: 'line', 
				    data: {
				        datasets: [{
				    		lineTension: 0,
				            data: [".$dataset."],
				            backgroundColor: [
				                'rgba(132, 99, 255, 0.2)',
				            ],
				            borderColor: [
				                'rgba(132, 99, 255, 1)',
				            ],
				            borderWidth: 2,
				            pointStyle: 'line',
				        }]
				    },
				    options: {
				    	legend: {
				    		display: false,
				    	},
				        scales: {
				            xAxes: [{
				                type: 'time',
				                time: {
				                    unit: 'day',
				                    displayFormats: {
				                    	day:'" . ($daysFromNow <= 7 ? 'D' : 'MMM D') . "',
				                    } 
				                }
				            }]
				        }
	            	}
				});
				</script>";
		}
		$delfile = isset($_REQUEST['delfile']) ? $_REQUEST['delfile'] : "";
		if( strlen($delfile) > 5 && strlen($delfile) < 22 && substr($delfile, -4) == ".txt" ) {
			@unlink("$datadir/$delfile");	
			@unlink(str_replace(".txt", ".html", "$datadir/$delfile"));	
			@unlink(str_replace(".txt", ".json", "$datadir/$delfile"));	
			@unlink(str_replace(".txt", ".state", "$datadir/$delfile"));	
		}
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
		if( $action == 'setOrderState' ) {
			$oid = isset( $_REQUEST['orderID'] ) ? $_REQUEST['orderID']:"";
			$newState = isset( $_REQUEST['newState'] ) ? $_REQUEST['newState']:"";
			$sendTo = isset( $_REQUEST['sendTo'] ) ? $_REQUEST['sendTo']:"";
			$message = isset( $_REQUEST['message'] ) ? $_REQUEST['message']:"";
			$currState = '';
			if(file_exists( "$datadir/$oid.state" ))
				$currState = substr( file_get_contents( "$datadir/$oid.state" ), 3);
			else
				$currState = "delivered"; 
			if ($newState != $currState ) {
				file_put_contents( "$datadir/$oid.state", "\xEF\xBB\xBF".$newState ); 
				if($message != '' && $sendTo != '') {
			    $config = loadGlobalConfig();
			    $config['order_notifymessages'][$newState] = $message;
			    saveGlobalConfig($config);
					@mail( 
						$sendTo, 
						'=?UTF-8?B?'.base64_encode(sprintf($NotifySubject, $oid, $States[$newState]['label'])).'?=',
						$message, 
						"MIME-Version: 1.0" . "\r\n" .
						"Content-Type: text/plain; charset=utf-8" . "\r\n" .
						"Content-Transfer-Encoding: 8bit" . "\r\n" .
						"From: $MERCHANT_FROM" . "\r\n" .
						"Return-Path: $MERCHANT_FROM" . "\r\n" .
						"X-Mailer: PHP/" . phpversion()
					);
				}
			}
		}
		$oarray = getOList();
		$stats = O2Stats($oarray, $daysFromNow);
		echo "<h1>$Title</h1>";
		echo "<div style=\"text-align:center;padding-bottom:1em;\">
				<div class='btn-group'>
				  <button class='btn".($daysFromNow==7?' active':'')."' onclick='window.location.href=\"./$scriptfilename?view=orders&daysFromNow=7\"'>$Periods[7]</button>
				  <button class='btn".($daysFromNow==30?' active':'')."' onclick='window.location.href=\"./$scriptfilename?view=orders&daysFromNow=30\"'>$Periods[30]</button>
				  <button class='btn".($daysFromNow==90?' active':'')."' onclick='window.location.href=\"./$scriptfilename?view=orders&daysFromNow=90\"'>$Periods[90]</button>
				  <button class='btn".($daysFromNow==180?' active':'')."' onclick='window.location.href=\"./$scriptfilename?view=orders&daysFromNow=180\"'>$Periods[180]</button>
				  <button class='btn".($daysFromNow==365?' active':'')."' onclick='window.location.href=\"./$scriptfilename?view=orders&daysFromNow=365\"'>$Periods[365]</button>
				  <button class='btn".($daysFromNow==1095?' active':'')."' onclick='window.location.href=\"./$scriptfilename?view=orders&daysFromNow=1095\"'>$Periods[1095]</button>
				</div>
			</div>";
		$pnlTotalSales = 
			"<div class='span4 panel'>".
				"<h2>$TotalSales</h2>".
				"<h3>".FormatPrice($stats['total'])."</h3>".
				"<h4>".($daysFromNow <= 30 ? sprintf($LastNDays, FormatPrice($stats['period']['total']), $daysFromNow) : sprintf($LastNMonths, FormatPrice($stats['period']['total']), $daysFromNow/30))."</h4>".
				makeChart('chtSales', $stats['dsSales']).
			"</div>";
		$pnlTotalOrders = 
			"<div class='span4 panel'>".
				"<h2>$TotalOrders</h2>".
				"<h3>".$stats['count']."</h3>".
				"<h4>".($daysFromNow <= 30 ? sprintf($LastNDays, strval($stats['period']['count']), $daysFromNow) : sprintf($LastNMonths, strval($stats['period']['count']), $daysFromNow/30))."</h4>".
				makeChart('chtCount', $stats['dsCount']).
			"</div>";
		$pnlAvgOrderValue = 
			"<div class='span4 panel'>".
				"<h2>$AvgOrderValue</h2>".
				"<h3>".FormatPrice($stats['avgSale'])."</h3>".
				"<h4>".($daysFromNow <= 30 ? sprintf($LastNDays, FormatPrice($stats['period']['avgSale']), $daysFromNow) : sprintf($LastNMonths, FormatPrice($stats['period']['avgSale']), $daysFromNow/30))."</h4>".
				makeChart('chtAgvSale', $stats['dsAvgSale']).
			"</div>";
		$panels = 
			"<div class='row-fluid panels'>$pnlTotalSales$pnlTotalOrders$pnlAvgOrderValue</div>";
		echo $panels;
		if( count($oarray) > 0 )
			echo $stats['htmlOrders'];
	}
	if( count($oarray) == 0 )
		echo "<h5 style='text-align:center'>$noroderfiles&nbsp;</h5><br/><br/>";
	closedir($dir_handle);  
?>
