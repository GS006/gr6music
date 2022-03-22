<?php
	@require_once("../common/com.php");
	$oid = isset( $_REQUEST['oid'] ) ? stripslashes( $_REQUEST['oid'] ):"";
	$fmt = isset( $_REQUEST['fmt'] ) ? stripslashes( $_REQUEST['fmt'] ):"";
	$ctr = isset( $_REQUEST['ctr'] ) ? stripslashes( $_REQUEST['ctr'] ):"";
	if( $ctr != getCtr( $oid, $fmt ) )
		die( 'error 99' );
	$result = "Lo sentimos, el pedido $oid no fue encontrado. Por favor, póngase en contacto con el administrador del sitio web para más información.";
	$result = "<!doctype html><head><meta charset='utf-8'></head><body>".$result."</body></html>";
	$ofile = "./data/$oid.$fmt";
	if( file_exists( $ofile ) ) {
		if (function_exists('file_get_contents')) 
		{
			$result = file_get_contents( $ofile );
		} 
		else 
		{
			$fp = fopen( $ofile, 'rb' );
			$result = fread( $fp, filesize( $ofile ) );
			fclose( $fp );
		}
	}
	if( $fmt == "txt" )
		$result = str_replace( "\n", '<br/>', $result );
	echo $result;
?>
