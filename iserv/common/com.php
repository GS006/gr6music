<?php
	function getRootUrlFromIServUrl() {
	  $currUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	  $iserv_pos = strpos($currUrl, '/iserv/');
	  if($iserv_pos !== false)
	  	return substr($currUrl, 0, $iserv_pos );
	  else
	  	return $currUrl;
	}
	function loadGlobalConfig() {
    if(file_exists('_config.php')) {
      return include '_config.php';
    }
    else {
		  $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		  $lang = in_array($lang, array('fr', 'en')) ? $lang : 'en';
			if($lang == 'fr') {
				return array(
					 'maintenance'=> array (
					 		'heading' => "Nous revenons bientôt !",
					 		'content' => "Désolé pour ce désagrément, nous effectuons actuellement des travaux de maintenance. Vous pouvez toutefois <a href=\"mailto:gr6studio@pm.me\">nous contacter</a> si besoin, mais nous serons de retour sous peu !",
					 		'sign' => "— L'équipe",
					 ),
					 'order_notifymessages' => array (
							'payment_wait' => 'Bonjour, nous avons bien reçu votre commande, nous commencerons à la préparer dès réception de votre paiement.',
							'payment_ok' => 'Bonjour, nous avons bien reçu votre paiement, nous vous informerons dès que la préparation de votre commande commencera.',
							'preparing' => 'Bonjour, nous vous informons que votre commande est en cours de préparation !',
							'ready_for_pickup' => 'Bonjour, votre commande est prête pour l\'enlèvement, vous pouvez venir la récupérer à notre boutique du lundi au vendredi de 9H à 18H.',
							'shipped' => 'Bonjour, votre commande a été expédiée, voici le numéro de suivi : [COLLER ICI LE LIEN DE SUIVI].',
							'delivered' => 'Bonjour, nous avons été informé que votre commande a été livrée, merci pour votre confiance !',
							'canceled' => 'Bonjour, votre commande a été annulée.',
						)
				);
			}
			else {
				return array(
					 'maintenance'=> array (
					 		'heading' => "We'll be back soon!",
					 		'content' => "Sorry for the inconvenience but we&rsquo;re performing some maintenance at the moment. If you need to you can always <a href=\"mailto:gr6studio@pm.me\">contact us</a>, otherwise we&rsquo;ll be back online shortly!",
					 		'sign' => "— The Team",
					 ),
					 'order_notifymessages' => array (
							'payment_wait' => 'Hello, we have received your order, we will start preparing it as soon as we receive your payment.',
							'payment_ok' => 'Hello, we have received your payment, we will inform you as soon as the preparation of your order begins.',
							'preparing' => 'Hello, we inform you that your order is being prepared!',
							'ready_for_pickup' => 'Hello, your order is ready for pickup, you can collect it at our shop Monday to Friday from 9am to 6pm.',
							'shipped' => 'Hello, your order has been shipped, here is the tracking number: [PASTE HERE TRACKING LINK].',
							'delivered' => 'Hello, we have been informed that your order has been delivered, thank you!',
							'canceled' => 'Hello, your order has been canceled.',
						)
				);
			}
	  }
	}
	function saveGlobalConfig($config) {
		file_put_contents('_config.php', '<?php return ' . var_export($config, true) . ';');
	}
	function actual_date( $datefmt, $UseTimeServer, $HourOffset ) {
		if ( $HourOffset < -12 || $HourOffset > 12 ) 
			$HourOffset = 0;
		$timestamp = time() + $HourOffset * 3600;
		if( $UseTimeServer )
			return date( $datefmt, $timestamp );  
		return gmdate( $datefmt, $timestamp );
	}
	function getCtr( $oid, $fmt ) {
		$SALT = 'a4795272';	
		return md5( $SALT.$oid.$SALT.$fmt.$SALT );
	}
	function checkIServDataDir( $relpath, $iserv, $diplayresult )
	{
		$idir = $relpath . $iserv . '/data';
		if( is_dir( $idir ) )
		{
			$result = "";
			if( file_exists( $idir .'/index.html' ) ) {
				$result = "$iserv ok";
			} else {
			    if( !$handle = fopen( $idir .'/index.html', 'w' ) ) {
					$result = "$iserv KO";
			    } else {
					if( fwrite($handle, " ") === FALSE ) {
						$result = "$iserv KO";
					} else {
						$result = "$iserv DONE";
					}					
					fclose($handle);
			    }
			}
			if( $iserv == "twsc" ) {
				if( !$handle = fopen( $idir .'/.htaccess', 'w' ) ) {
					unlink( $idir .'/.htaccess' );
					$result .= " (.htaccess deleted)";					
				} else {
					fwrite($handle, "IndexIgnore *");
					fclose($handle);
					$result .= " (.htaccess updated)";					
				}
			}
			if( $result !== "" )
				$result .= "<br>";
			if( $diplayresult )
				echo $result;
		}
	}
	function checkAllIServDataDirs( $relpath, $diplayresult )
	{
		if( $relpath == "" )
			$relpath = "../";
		checkIServDataDir( $relpath, 'twsc', $diplayresult );
		checkIServDataDir( $relpath, 'poll', $diplayresult );
		checkIServDataDir( $relpath, 'form2mail', $diplayresult );
		checkIServDataDir( $relpath, 'blog', $diplayresult );
		checkIServDataDir( $relpath, 'dfiles', $diplayresult );
	}
	function ExtractStringBetween($var1="",$var2="",$pool) {
		if( strstr($pool, $var1) === false )
			return "";
		$temp1 = strpos($pool,$var1)+strlen($var1);
		$result = substr($pool,$temp1,strlen($pool));
		$dd=strpos($result,$var2);
		if( $dd == 0 ) {
			$dd = strlen($result);
		}
		return substr($result,0,$dd);
	}	
	function remove_utf8_bom($text) {
		$bom = pack('H*','EFBBBF');
		$text = preg_replace("/^$bom/", '', $text);
		return $text;
	}
	function getOrderStatus( $oid, $default_state = "" ) { 
		$state = $default_state;
		$osfile = ( substr( $oid, -6 ) == ".state" ) ? $oid : "$oid.state";
		if( file_exists( $osfile ) ) {
			$state = file_get_contents( $osfile );
			if( $state == "" ) {
				$state = default_state;
			} else 
				$state = remove_utf8_bom( $state );
		}
		return( $state );
	}	
?>
