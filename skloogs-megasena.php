<?php
/*
Plugin Name: Skloogs MegaSena
Plugin URI: http://tec.skloogs.com/dev/plugins/skloogs-megasena
Description: Este plugin permite apresentar os resultados da mega-sena no seu blog, assim como várias estatísticas sobre o jogo.
Version: 3.1.1
Author: Philippe Hilger
Author URI: http://tec.skloogs.com

  Copyright 2009  Philippe Hilger  (hilgerph@yahoo.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $SkMSVersion;
global $SkMSDBVersion;
global $SkMSDBNumsVersion;
global $SkMSDBStatsVersion;
global $SkMSDomain;
global $SkMSDebug;

$SkMSDomain = "skloogs-megasena";
$SkMSVersion = "3.1.1";
$SkMSDBVersion = "1.0";
$SkMSDBNumsVersion = "1.0";
$SkMSDBStatsVersion = "1.0";
$SkMSNextPoll = "01/01/1996";
$SkHostOffset = "0";
$SkMaxNumbers = "8";
$SkMSConcurso = "1";
$SkMSIsSetup=0;
$SkMSPrice=2.0;
$SkMSFoldCnt=0;

/*
 * MegaSena Zip archive reader
 */
function SkMSZipRead($fname) {
	global $wp_filesystem,$SkMSDomain;

	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');

	if ( ! $wp_filesystem || ! is_object($wp_filesystem) )
		WP_Filesystem();

	$aname=WP_PLUGIN_DIR."/".$SkMSDomain.'/d_megase.zip';
	if (!file_exists($aname)) $aname=download_url($fname);
	
	$content = "";
	if (is_wp_error($aname))
		wp_die($aname);
	$archive=new PclZip($aname);
	$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
	if ($archive_files==false)
		wp_die("error");
	foreach ($archive_files as $file) {
		if (!strcmp($file['filename'],'D_MEGA.HTM')) {
			$content = $file['content'];
		};
	}
	unlink($aname);

	return $content;
}


function SkMSGetStats($clear=0,$lastgame=0) {
	global $wpdb,$SkMSDomain,$SkMSVersion;

	$saidas_max=0;
	$saidas_min=0;
	$atraso_max=0;
	$atraso_min=0;
	$senas_max=0;
	$senas_min=0;
	$quinas_max=0;
	$quinas_min=0;
	$quadras_max=0;
	$quadras_min=0;
	$combi_max=0;
	$combi_min=0;
	
	$skmstable = $wpdb->prefix . "skmegasena";		
	$skmstable2 = $wpdb->prefix . "skmegasena_nums";		
	$skmstable3 = $wpdb->prefix . "skmegasena_stats";		
	
	$content = SkMSZipRead('http://www1.caixa.gov.br/loterias/_arquivos/loterias/D_megase.zip');
	$content = preg_replace('/^.*<table[^>]*>/i','',$content);
	$content = preg_replace('/<\/table>.*$/i','',$content);
	$content = preg_replace('/<\/?[^\/t][^>]+>/i','',$content);
	$content = preg_replace('/[\n\r]+/','',$content);
	$content = preg_replace('/<th[^>]*>([^<]*)<\/th>/i','',$content);
	$content = preg_replace('/<t[dh][^>]*>([^<]*)<\/t[dh]>/i','\1;',$content);
	$content = preg_replace('/<tr[^>]*>([^<]*)<\/tr>/i','\1|',$content);
	$content = preg_replace('/<\/?[^>]+>/i','',$content);
	//echo "content-type: text/plain\n\n";
	//echo $content;
	$concursos=split('\|',$content);
	$log = "";

	if ($clear) {
		$wpdb->query($wpdb->prepare("TRUNCATE TABLE ".$skmstable));
	}
	$wpdb->query($wpdb->prepare("TRUNCATE TABLE ".$skmstable2));
	$wpdb->query($wpdb->prepare("TRUNCATE TABLE ".$skmstable3));

	for ($i=1; $i<=60; $i++) {
		$numndx[$i]=$i;
		$saidas[$i]=0;
		$senas[$i]=0;
		$quinas[$i]=0;
		$quadras[$i]=0;
		$combi[$i]=0;
		$jogos[$i]=0;
		$pos1[$i]=0;
		$pos2[$i]=0;
		$pos3[$i]=0;
		$pos4[$i]=0;
		$pos5[$i]=0;
		$pos6[$i]=0;
		$atraso[$i]=0;
	}
	$totjogos=0;
	$totvsena=0;
	$totvquina=0;
	$totvquadra=0;
	$totgsena=0;
	$totgquina=0;
	$totgquadra=0;
	$totsena=0;
	$totquina=0;
	$totquadra=0;

	foreach ($concursos as $conc) {
		if ($conc=='') continue;
		$log .= "conc: ".$conc."<br/>\n";
		list($number,
			$thedate,
			$num1,
			$num2,
			$num3,
			$num4,
			$num5,
			$num6,
			$arrecad,
			$gsena,
			$vsena,
			$gquina,
			$vquina,
			$gquadra,
			$vquadra,
			$cumulou,
			$cumul) = explode(';',$conc);

		$vsena=str_replace('.','',$vsena);
		$vsena=str_replace(',','.',$vsena);
		$vquina=str_replace('.','',$vquina);
		$vquina=str_replace(',','.',$vquina);
		$vquadra=str_replace('.','',$vquadra);
		$vquadra=str_replace(',','.',$vquadra);
		$result=$num1.'-'.$num2.'-'.$num3.'-'.$num4.'-'.$num5.'-'.$num6;
		$aresult=explode('-',$result);
		sort($aresult,SORT_NUMERIC);
		$oresult=implode('-',$aresult);
		$cumul=str_replace('.','',$cumul);
		$cumul=str_replace(',','.',$cumul);

		// update DB with results
		$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$skmstable." SET number = %d, 
			cumul = %d,
			result = %s,
			oresult = %s,
			gsena = %d,
			vsena = %f,
			gquina = %d,
			vquina = %f,
			gquadra = %d,
			vquadra = %f,
			thedate = %s,
			place = %s,
			nextval = %f,
			nextdate = %s,
			proxcumul = %f,
			proxfinal = %s,
			fimano = %d,
			num1 = %d,
			num2 = %d,
			num3 = %d,
			num4 = %d,
			num5 = %d,
			num6 = %d",
			$number,
			$cumul,
			$result,
			$oresult,
			$gsena,
			$vsena,
			$gquina,
			$vquina,
			$gquadra,
			$vquadra,
			$thedate,
			'',
			'',
			'',
			'',
			'',
			'',
			$num1,
			$num2,
			$num3,
			$num4,
			$num5,
			$num6));
			
		//remove front zero
		$num1+=0;
		$num2+=0;
		$num3+=0;
		$num4+=0;
		$num5+=0;
		$num6+=0;
		// update nums and stats
		$totjogos++;
		$pos1[$num1]++;
		$pos2[$num2]++;
		$pos3[$num3]++;
		$pos4[$num4]++;
		$pos5[$num5]++;
		$pos6[$num6]++;
		$saidas[$num1]++;
		$saidas[$num2]++;
		$saidas[$num3]++;
		$saidas[$num4]++;
		$saidas[$num5]++;
		$saidas[$num6]++;
		$senas[$num1]+=$gsena;
		$senas[$num2]+=$gsena;
		$senas[$num3]+=$gsena;
		$senas[$num4]+=$gsena;
		$senas[$num5]+=$gsena;
		$senas[$num6]+=$gsena;
		$quinas[$num1]+=$gquina;
		$quinas[$num2]+=$gquina;
		$quinas[$num3]+=$gquina;
		$quinas[$num4]+=$gquina;
		$quinas[$num5]+=$gquina;
		$quinas[$num6]+=$gquina;
		$quadras[$num1]+=$gquadra;
		$quadras[$num2]+=$gquadra;
		$quadras[$num3]+=$gquadra;
		$quadras[$num4]+=$gquadra;
		$quadras[$num5]+=$gquadra;
		$quadras[$num6]+=$gquadra;
		$combi[$num1]+=($gsena*1540+$gquina*55+$gquadra)/3;
		$combi[$num2]+=($gsena*1540+$gquina*55+$gquadra)/3;
		$combi[$num3]+=($gsena*1540+$gquina*55+$gquadra)/3;
		$combi[$num4]+=($gsena*1540+$gquina*55+$gquadra)/3;
		$combi[$num5]+=($gsena*1540+$gquina*55+$gquadra)/3;
		$combi[$num6]+=($gsena*1540+$gquina*55+$gquadra)/3;
		$atraso[$num1]=$number;
		$atraso[$num2]=$number;
		$atraso[$num3]=$number;
		$atraso[$num4]=$number;
		$atraso[$num5]=$number;
		$atraso[$num6]=$number;
		$totvsena += $vsena; 
		$totvquina += $vquina; 
		$totvquadra += $vquadra; 
		$totgsena += $gsena; 
		$totgquina += $gquina; 
		$totgquadra += $gquadra; 
		$totsena += ($gsena * $vsena);
		$totquina += ($gquina * $vquina);
		$totquadra += ($gquadra * $vquadra);
	}
	for ($i=1; $i<=60; $i++) {
		$jogos[$i]=$combi[$i]/$saidas[$i];
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$skmstable2." SET num = %d,
			saidas = %d,
			senas = %d,
			quinas = %d,
			quadras = %d,
			combi = %d,
			jogos = %d,
			pos1 = %d,
			pos2 = %d,
			pos3 = %d,
			pos4 = %d,
			pos5 = %d,
			pos6 = %d,
			atraso = %d",
			$i,
			$saidas[$i],
			$senas[$i],
			$quinas[$i],
			$quadras[$i],
			$combi[$i],
			$jogos[$i],
			$pos1[$i],
			$pos2[$i],
			$pos3[$i],
			$pos4[$i],
			$pos5[$i],
			$pos6[$i],
			$atraso[$i]));
		if ($saidas[$i]>$saidas_max) $saidas_max=$saidas[$i];
		if (!$saidas_min || $saidas[$i]<$saidas_min) $saidas_min=$saidas[$i];
		if ($atraso[$i]>$atraso_max) $atraso_max=$atraso[$i];
		if (!$atraso_min || $atraso[$i]<$atraso_min) $atraso_min=$atraso[$i];
		if ($senas[$i]>$senas_max) $senas_max=$senas[$i];
		if (!$senas_min || $senas[$i]<$senas_min) $senas_min=$senas[$i];
		if ($quinas[$i]>$quinas_max) $quinas_max=$quinas[$i];
		if (!$quinas_min || $quinas[$i]<$quinas_min) $quinas_min=$quinas[$i];
		if ($quadras[$i]>$quadras_max) $quadras_max=$quadras[$i];
		if (!$quadras_min || $quadras[$i]<$quadras_min) $quadras_min=$quadras[$i];
		if ($combi[$i]>$combi_max) $combi_max=$combi[$i];
		if (!$combi_min || $combi[$i]<$combi_min) $combi_min=$combi[$i];
	}
	// colors
	$SkBgRed=0xf3;
	$SkBgGreen=0xe9;
	$SkBgBlue=0x9e;
	$SkQdRed=0xd8;
	$SkQdGreen=0xd6;
	$SkQdBlue=0xb5;
	$SkLnRed=0x68;
	$SkLnGreen=0x5e;
	$SkLnBlue=0x61;
	$SkLn2Red=0x0;
	$SkLn2Green=0x5e;
	$SkLn2Blue=0x61;
	$SkLn3Red=0x68;
	$SkLn3Green=0x0;
	$SkLn3Blue=0x61;
	$SkLn4Red=0x68;
	$SkLn4Green=0x5e;
	$SkLn4Blue=0x0;
	$SkFiRed=0xf6;
	$SkFiGreen=0xff;
	$SkFiBlue=0xfd;
	$SkLfRed=0xd6;
	$SkLfGreen=0x8e;
	$SkLfBlue=0x69;
	$SkTxRed=0xd6;
	$SkTxGreen=0;
	$SkTxBlue=0;
	$SkTiRed=0;
	$SkTiGreen=0;
	$SkTiBlue=0;
	$SkCpRed=0;
	$SkCpGreen=0x5f;
	$SkCpBlue=0;
	$SkGrYLines=10;
	$SkGrWidth=600;
	$SkGrHeight=400;
	$SkMgLeft=40;
	$SkMgRight=5;
	$SkMgBottom=40;
	$SkMgTop=5;
	
	// create numbers stats graph
	$numbers_graph=imagecreate($SkGrWidth,$SkGrHeight);	$atraso_graph=imagecreate($SkGrWidth,$SkGrHeight);
	// allocate colors
	$bgcolor=imagecolorallocate($numbers_graph,$SkBgRed,$SkBgGreen,$SkBgBlue);
	$qdcolor=imagecolorallocate($numbers_graph,$SkQdRed,$SkQdGreen,$SkQdBlue);
	$lncolor=imagecolorallocate($numbers_graph,$SkLnRed,$SkLnGreen,$SkLnBlue);
	$ficolor=imagecolorallocate($numbers_graph,$SkFiRed,$SkFiGreen,$SkFiBlue);
	$lfcolor=imagecolorallocate($numbers_graph,$SkLfRed,$SkLfGreen,$SkLfBlue);
	$txcolor=imagecolorallocate($numbers_graph,$SkTxRed,$SkTxGreen,$SkTxBlue);
	$ticolor=imagecolorallocate($numbers_graph,$SkTiRed,$SkTiGreen,$SkTiBlue);
	$cpcolor=imagecolorallocate($numbers_graph,$SkCpRed,$SkCpGreen,$SkCpBlue);
	$black=imagecolorallocate($numbers_graph,0,0,0);
	$white=imagecolorallocate($numbers_graph,255,255,255);
	imagesetthickness($numbers_graph,1);
	// draw rectangle
	imagerectangle($numbers_graph,0,0,$SkGrWidth-1,$SkGrHeight-1,$black);
	imagefilledrectangle($numbers_graph,$SkMgLeft,$SkMgTop,$SkGrWidth-$SkMgRight-1,$SkGrHeight-$SkMgBottom,$ficolor);
	imagerectangle($numbers_graph,$SkMgLeft,$SkMgTop,$SkGrWidth-$SkMgRight-1,$SkGrHeight-$SkMgBottom,$qdcolor);
	$saidas_yscale=($SkGrHeight-$SkMgTop-$SkMgBottom)/(($saidas_max-$saidas_min)*1.10);
	$saidas_yint=floor(($saidas_max-$saidas_min)/$SkGrYLines);
	$saidas_xscale=($SkGrWidth-$SkMgLeft-$SkMgRight)/61;
	$saidas_xint=$saidas_xscale/61;
	$dashline=array($qdcolor, IMG_COLOR_TRANSPARENT);
	imagesetstyle($numbers_graph,$dashline);
	for ($i=0; $i<=$SkGrYLines; $i++) {
		imageline($numbers_graph,
		$SkMgLeft,$SkGrHeight-($SkMgBottom+(($saidas_max-$saidas_min)*0.05+$i*$saidas_yint)*$saidas_yscale),
		$SkGrWidth-$SkMgRight-1,$SkGrHeight-($SkMgBottom+(($saidas_max-$saidas_min)*0.05+$i*$saidas_yint)*$saidas_yscale),
		IMG_COLOR_STYLED);
		imagestring($numbers_graph,4,$SkMgLeft/3-1,
			$SkGrHeight-7-($SkMgBottom+(($saidas_max-$saidas_min)*0.05+$i*$saidas_yint)*$saidas_yscale)-1,
			sprintf("%3d",$saidas_min+$i*$saidas_yint),
			$white);
		imagestring($numbers_graph,4,$SkMgLeft/3,
			$SkGrHeight-7-($SkMgBottom+(($saidas_max-$saidas_min)*0.05+$i*$saidas_yint)*$saidas_yscale),
			sprintf("%3d",$saidas_min+$i*$saidas_yint),
			$txcolor);
	}
	$saidas_gr=array();
	//$xxx.='max:'.$saidas_max." - min:".$saidas_min." ";
	for($i=1;$i<=60;$i++) {
		$saidas_gr[2*($i-1)]=$SkMgLeft+$i*$saidas_xscale;
		$saidas_gr[2*$i-1]=$SkGrHeight-($SkMgBottom+($saidas[$i]-$saidas_min+($saidas_max-$saidas_min)*0.05)*$saidas_yscale);
		//$xxx.='('.$saidas_gr[2*($i-1)].'/'.$saidas_gr[2*$i-1].')';
	}
	//wp_die($xxx);
	$saidas_gr[120]=$saidas_gr[118];
	$saidas_gr[121]=$SkGrHeight-$SkMgBottom;
	$saidas_gr[122]=$saidas_gr[0];
	$saidas_gr[123]=$SkGrHeight-$SkMgBottom;
	for ($i=0;$i<124;$i++){ $saidas_sh[$i]=$saidas_gr[$i]+1; }
	imagefilledpolygon($numbers_graph,$saidas_sh,62,$black);
	imagefilledpolygon($numbers_graph,$saidas_gr,62,$lfcolor);
	for($i=1;$i<=60;$i++) {
		imageline($numbers_graph,
		$SkMgLeft+$i*$saidas_xscale,$SkGrHeight-$SkMgBottom,
		$SkMgLeft+$i*$saidas_xscale,
		$SkGrHeight+1-($SkMgBottom+($saidas[$i]-$saidas_min+($saidas_max-$saidas_min)*0.05)*$saidas_yscale),
		($i==1 || $i%5==0)?$txcolor:$bgcolor);
		if ($i==1 || $i%5==0) {
			imagestring($numbers_graph,4,$SkMgLeft+$i*$saidas_xscale-($i<10?3:8)-1,$SkGrHeight-$SkMgBottom+4-1,$i,$white);
			imagestring($numbers_graph,4,$SkMgLeft+$i*$saidas_xscale-($i<10?3:8),$SkGrHeight-$SkMgBottom+4,$i,$txcolor);
		}
	}
	imagesetthickness($numbers_graph,2);
	imagepolygon($numbers_graph,$saidas_gr,62,$lncolor);
	imagesetthickness($numbers_graph,1);
	$title=utf8_decode(__('Numbers appearances until game #%d',$SkMSDomain));
	imagestring($numbers_graph,7,
	($SkGrWidth-10*strlen($title))/2,$SkGrHeight-$SkMgBottom/2,
	sprintf($title,$totjogos),$white);
	imagestring($numbers_graph,7,
	($SkGrWidth-10*strlen($title))/2-1,$SkGrHeight-$SkMgBottom/2-1,
	sprintf($title,$totjogos),$ticolor);
	$copy='Skloogs Megasena '.$SkMSVersion;
	imagestring($numbers_graph,5,$SkGrWidth-10*strlen($copy),$SkGrHeight-$SkMgBottom-20,
	$copy,$ficolor);
	imagestring($numbers_graph,5,$SkGrWidth-10*strlen($copy)-1,$SkGrHeight-$SkMgBottom-20-1,
	$copy,$cpcolor);
	imagepng($numbers_graph,WP_PLUGIN_DIR."/".$SkMSDomain."/skms_numbers.png");
	imagedestroy($numbers_graph);

	// create "atraso" stats graph
	$atraso_graph=imagecreate($SkGrWidth,$SkGrHeight);	$atraso_graph=imagecreate($SkGrWidth,$SkGrHeight);
	// allocate colors
	$bgcolor=imagecolorallocate($atraso_graph,$SkBgRed,$SkBgGreen,$SkBgBlue);
	$qdcolor=imagecolorallocate($atraso_graph,$SkQdRed,$SkQdGreen,$SkQdBlue);
	$lncolor=imagecolorallocate($atraso_graph,$SkLnRed,$SkLnGreen,$SkLnBlue);
	$ficolor=imagecolorallocate($atraso_graph,$SkFiRed,$SkFiGreen,$SkFiBlue);
	$lfcolor=imagecolorallocate($atraso_graph,$SkLfRed,$SkLfGreen,$SkLfBlue);
	$txcolor=imagecolorallocate($atraso_graph,$SkTxRed,$SkTxGreen,$SkTxBlue);
	$ticolor=imagecolorallocate($atraso_graph,$SkTiRed,$SkTiGreen,$SkTiBlue);
	$cpcolor=imagecolorallocate($atraso_graph,$SkCpRed,$SkCpGreen,$SkCpBlue);
	$black=imagecolorallocate($atraso_graph,0,0,0);
	$white=imagecolorallocate($atraso_graph,255,255,255);
	imagesetthickness($atraso_graph,1);
	// draw rectangle
	imagerectangle($atraso_graph,0,0,$SkGrWidth-1,$SkGrHeight-1,$black);
	imagefilledrectangle($atraso_graph,$SkMgLeft,$SkMgTop,$SkGrWidth-$SkMgRight-1,$SkGrHeight-$SkMgBottom,$ficolor);
	imagerectangle($atraso_graph,$SkMgLeft,$SkMgTop,$SkGrWidth-$SkMgRight-1,$SkGrHeight-$SkMgBottom,$qdcolor);
	$atraso_yscale=($SkGrHeight-$SkMgTop-$SkMgBottom)/(($atraso_max-$atraso_min)*1.10);
	$atraso_yint=floor(($atraso_max-$atraso_min)/$SkGrYLines);
	$atraso_xscale=($SkGrWidth-$SkMgLeft-$SkMgRight)/61;
	$atraso_xint=$atraso_xscale/61;
	$dashline=array($qdcolor, IMG_COLOR_TRANSPARENT);
	imagesetstyle($atraso_graph,$dashline);
	for ($i=0; $i<=$SkGrYLines; $i++) {
		imageline($atraso_graph,
		$SkMgLeft,$SkGrHeight-($SkMgBottom+(($atraso_max-$atraso_min)*0.05+$i*$atraso_yint)*$atraso_yscale),
		$SkGrWidth-$SkMgRight-1,$SkGrHeight-($SkMgBottom+(($atraso_max-$atraso_min)*0.05+$i*$atraso_yint)*$atraso_yscale),
		IMG_COLOR_STYLED);
		imagestring($atraso_graph,4,$SkMgLeft/6-1,
			$SkGrHeight-7-($SkMgBottom+(($atraso_max-$atraso_min)*0.05+$i*$atraso_yint)*$atraso_yscale)-1,
			sprintf("%4d",$atraso_min+$i*$atraso_yint),
			$white);
		imagestring($atraso_graph,4,$SkMgLeft/6,
			$SkGrHeight-7-($SkMgBottom+(($atraso_max-$atraso_min)*0.05+$i*$atraso_yint)*$atraso_yscale),
			sprintf("%4d",$atraso_min+$i*$atraso_yint),
			$txcolor);
	}
	$atraso_gr=array();
	//$xxx.='max:'.$atraso_max." - min:".$atraso_min." ";
	for($i=1;$i<=60;$i++) {
		$atraso_gr[2*($i-1)]=$SkMgLeft+$i*$atraso_xscale;
		$atraso_gr[2*$i-1]=$SkGrHeight-($SkMgBottom+($atraso[$i]-$atraso_min+($atraso_max-$atraso_min)*0.05)*$atraso_yscale);
		//$xxx.='('.$atraso_gr[2*($i-1)].'/'.$atraso_gr[2*$i-1].')';
	}
	//wp_die($xxx);
	$atraso_gr[120]=$atraso_gr[118];
	$atraso_gr[121]=$SkGrHeight-$SkMgBottom;
	$atraso_gr[122]=$atraso_gr[0];
	$atraso_gr[123]=$SkGrHeight-$SkMgBottom;
	for ($i=0;$i<124;$i++){ $atraso_sh[$i]=$atraso_gr[$i]+1; }
	imagefilledpolygon($atraso_graph,$atraso_sh,62,$black);
	imagefilledpolygon($atraso_graph,$atraso_gr,62,$lfcolor);
	for($i=1;$i<=60;$i++) {
		imageline($atraso_graph,
		$SkMgLeft+$i*$atraso_xscale,$SkGrHeight-$SkMgBottom,
		$SkMgLeft+$i*$atraso_xscale,
		$SkGrHeight+1-($SkMgBottom+($atraso[$i]-$atraso_min+($atraso_max-$atraso_min)*0.05)*$atraso_yscale),
		($i==1 || $i%5==0)?$txcolor:$bgcolor);
		if ($i==1 || $i%5==0) {
			imagestring($atraso_graph,4,$SkMgLeft+$i*$atraso_xscale-($i<10?3:8)-1,$SkGrHeight-$SkMgBottom+4-1,$i,$white);
			imagestring($atraso_graph,4,$SkMgLeft+$i*$atraso_xscale-($i<10?3:8),$SkGrHeight-$SkMgBottom+4,$i,$txcolor);
		}
	}
	imagesetthickness($atraso_graph,2);
	imagepolygon($atraso_graph,$atraso_gr,62,$lncolor);
	imagesetthickness($atraso_graph,1);
	$title=utf8_decode(__('Numbers last appearances',$SkMSDomain));
	imagestring($atraso_graph,7,
	($SkGrWidth-10*strlen($title))/2,$SkGrHeight-$SkMgBottom/2,
	$title,$white);
	imagestring($atraso_graph,7,
	($SkGrWidth-10*strlen($title))/2-1,$SkGrHeight-$SkMgBottom/2-1,
	$title,$ticolor);
	$copy='Skloogs Megasena '.$SkMSVersion;
	imagestring($atraso_graph,5,$SkGrWidth-10*strlen($copy),$SkGrHeight-$SkMgBottom-20,
	$copy,$ficolor);
	imagestring($atraso_graph,5,$SkGrWidth-10*strlen($copy)-1,$SkGrHeight-$SkMgBottom-20-1,
	$copy,$cpcolor);
	imagepng($atraso_graph,WP_PLUGIN_DIR."/".$SkMSDomain."/skms_atraso.png");
	imagedestroy($atraso_graph);

	// create winners stats graph (logarithmic)
	$winners_graph=imagecreate($SkGrWidth,$SkGrHeight);	$atraso_graph=imagecreate($SkGrWidth,$SkGrHeight);
	// allocate colors
	$bgcolor=imagecolorallocate($winners_graph,$SkBgRed,$SkBgGreen,$SkBgBlue);
	$qdcolor=imagecolorallocate($winners_graph,$SkQdRed,$SkQdGreen,$SkQdBlue);
	$lncolor=imagecolorallocate($winners_graph,$SkLnRed,$SkLnGreen,$SkLnBlue);
	$ln2color=imagecolorallocate($winners_graph,$SkLn2Red,$SkLn2Green,$SkLn2Blue);
	$ln3color=imagecolorallocate($winners_graph,$SkLn3Red,$SkLn3Green,$SkLn3Blue);
	$ln4color=imagecolorallocate($winners_graph,$SkLn4Red,$SkLn4Green,$SkLn4Blue);
	$ficolor=imagecolorallocate($winners_graph,$SkFiRed,$SkFiGreen,$SkFiBlue);
	$lfcolor=imagecolorallocate($winners_graph,$SkLfRed,$SkLfGreen,$SkLfBlue);
	$txcolor=imagecolorallocate($winners_graph,$SkTxRed,$SkTxGreen,$SkTxBlue);
	$ticolor=imagecolorallocate($winners_graph,$SkTiRed,$SkTiGreen,$SkTiBlue);
	$cpcolor=imagecolorallocate($winners_graph,$SkCpRed,$SkCpGreen,$SkCpBlue);
	$black=imagecolorallocate($winners_graph,0,0,0);
	$white=imagecolorallocate($winners_graph,255,255,255);
	imagesetthickness($winners_graph,1);
	// draw rectangle
	imagerectangle($winners_graph,0,0,$SkGrWidth-1,$SkGrHeight-1,$black);
	imagefilledrectangle($winners_graph,$SkMgLeft,$SkMgTop,$SkGrWidth-$SkMgRight-1,$SkGrHeight-$SkMgBottom,$ficolor);
	imagerectangle($winners_graph,$SkMgLeft,$SkMgTop,$SkGrWidth-$SkMgRight-1,$SkGrHeight-$SkMgBottom,$qdcolor);
	// force scale
	$winners_min=$senas_min;
	$winners_max=$quadras_max;
	$winners_range=log($winners_max,10)-log($winners_min,10);
	$winners_yscale=($SkGrHeight-$SkMgTop-$SkMgBottom)/($winners_range*1.10);
	//$winners_yint=(float)(log($winners_max,10)-log($winners_min,10))/$SkGrYLines;
	$winners_xscale=($SkGrWidth-$SkMgLeft-$SkMgRight)/61;
	$winners_xint=$winners_xscale/61;
	$dashline=array($qdcolor, IMG_COLOR_TRANSPARENT);
	imagesetstyle($winners_graph,$dashline);
	for ($j=0; $j<=3*log($winners_max,10); $j++) {
		switch($j%3) {
			case 0:
				$i=floor($j/3);
				break;
			case 1:
				$i=floor($j/3)+log(2,10);
				break;
			case 2:
				$i=floor($j/3)+log(5,10);
				break;
		}
		if (log($winners_max,10)>$i && log($winners_min,10)<$i) {
			imageline($winners_graph,
			$SkMgLeft,$SkGrHeight-($SkMgBottom+($winners_range*0.05+$i-log($winners_min,10))*$winners_yscale),
			$SkGrWidth-$SkMgRight-1,$SkGrHeight-($SkMgBottom+($winners_range*0.05+$i-log($winners_min,10))*$winners_yscale),
			IMG_COLOR_STYLED);
			$scale_val=ereg_replace('000$',"K",ereg_replace('000000$',"M",round(pow(10,$i))));
			$scale_val=sprintf("%4s",$scale_val);
			imagestring($winners_graph,4,$SkMgLeft-5*7-1,
				$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+$i-log($winners_min,10))*$winners_yscale)-1,
				$scale_val,
				$white);
			imagestring($winners_graph,4,$SkMgLeft-5*7,
				$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+$i-log($winners_min,10))*$winners_yscale),
				$scale_val,
				$txcolor);
		}
	}
	$senas_gr=array();
	$quinas_gr=array();
	$quadras_gr=array();
	$combi_gr=array();
	//$xxx.='max:'.$senas_max." - min:".$senas_min." ";
	for($i=1;$i<=60;$i++) {
		$senas_gr[2*($i-1)]=$SkMgLeft+$i*$winners_xscale;
		$senas_gr[2*$i-1]=$SkGrHeight-($SkMgBottom+($winners_range*0.05+log($senas[$i],10)-log($winners_min,10))*$winners_yscale);
		$quinas_gr[2*($i-1)]=$SkMgLeft+$i*$winners_xscale;
		$quinas_gr[2*$i-1]=$SkGrHeight-($SkMgBottom+($winners_range*0.05+log($quinas[$i],10)-log($winners_min,10))*$winners_yscale);
		$quadras_gr[2*($i-1)]=$SkMgLeft+$i*$winners_xscale;
		$quadras_gr[2*$i-1]=$SkGrHeight-($SkMgBottom+($winners_range*0.05+log($quadras[$i],10)-log($winners_min,10))*$winners_yscale);
		$combi_gr[2*($i-1)]=$SkMgLeft+$i*$winners_xscale;
		$combi_gr[2*$i-1]=$SkGrHeight-($SkMgBottom+($winners_range*0.05+log($combi[$i],10)-log($winners_min,10))*$winners_yscale);
		//$xxx.='('.$senas_gr[2*($i-1)].'/'.$senas_gr[2*$i-1].')';
	}
	//wp_die($xxx);
	$senas_gr[120]=$senas_gr[118];
	$senas_gr[121]=$SkGrHeight-$SkMgBottom;
	$senas_gr[122]=$senas_gr[0];
	$senas_gr[123]=$SkGrHeight-$SkMgBottom;
	for ($i=0;$i<124;$i++){ $senas_sh[$i]=$senas_gr[$i]+1; }
	$quinas_gr[120]=$quinas_gr[118];
	$quinas_gr[121]=$SkGrHeight-$SkMgBottom;
	$quinas_gr[122]=$quinas_gr[0];
	$quinas_gr[123]=$SkGrHeight-$SkMgBottom;
	for ($i=0;$i<124;$i++){ $quinas_sh[$i]=$quinas_gr[$i]+1; }
	$quadras_gr[120]=$quadras_gr[118];
	$quadras_gr[121]=$SkGrHeight-$SkMgBottom;
	$quadras_gr[122]=$quadras_gr[0];
	$quadras_gr[123]=$SkGrHeight-$SkMgBottom;
	for ($i=0;$i<124;$i++){ $quadras_sh[$i]=$quadras_gr[$i]+1; }
	$combi_gr[120]=$combi_gr[118];
	$combi_gr[121]=$SkGrHeight-$SkMgBottom;
	$combi_gr[122]=$combi_gr[0];
	$combi_gr[123]=$SkGrHeight-$SkMgBottom;
	for ($i=0;$i<124;$i++){ $combi_sh[$i]=$combi_gr[$i]+1; }
	for($i=1;$i<=60;$i++) {
		imageline($winners_graph,
		$SkMgLeft+$i*$winners_xscale,$SkGrHeight-$SkMgBottom,
		$SkMgLeft+$i*$winners_xscale,
		$SkGrHeight+1-($SkMgBottom+(log($quadras[$i],10)-log($winners_min,10)+$winners_range*0.05)*$winners_yscale),
		($i==1 || $i%5==0)?$txcolor:$bgcolor);
		if ($i==1 || $i%5==0) {
			imagestring($winners_graph,4,$SkMgLeft+$i*$winners_xscale-($i<10?3:8)-1,$SkGrHeight-$SkMgBottom+4-1,$i,$white);
			imagestring($winners_graph,4,$SkMgLeft+$i*$winners_xscale-($i<10?3:8),$SkGrHeight-$SkMgBottom+4,$i,$txcolor);
		}
	}
	imagesetthickness($winners_graph,2);
	imagepolygon($winners_graph,$senas_gr,62,$lncolor);
	imagepolygon($winners_graph,$quinas_gr,62,$ln2color);
	imagepolygon($winners_graph,$quadras_gr,62,$ln3color);
	//imagepolygon($winners_graph,$combi_gr,62,$ln4color);
	imagesetthickness($winners_graph,1);
	imagestring($winners_graph,5,$SkMgLeft+20-1,
		$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+log($senas_max,10)+.2-log($winners_min,10))*$winners_yscale)-1,
		__('Senas Winners',$SkMSDomain),
		$bgcolor);
	imagestring($winners_graph,5,$SkMgLeft+20,
		$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+log($senas_max,10)+.2-log($winners_min,10))*$winners_yscale),
		__('Senas Winners',$SkMSDomain),
		$lncolor);
	imagestring($winners_graph,5,$SkMgLeft+20-1,
		$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+log($senas_max,10)+.5-log($winners_min,10))*$winners_yscale)-1,
		__('Quinas Winners',$SkMSDomain),
		$bgcolor);
	imagestring($winners_graph,5,$SkMgLeft+20,
		$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+log($senas_max,10)+.5-log($winners_min,10))*$winners_yscale),
		__('Quinas Winners',$SkMSDomain),
		$ln2color);
	imagestring($winners_graph,5,$SkMgLeft+20-1,
		$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+log($senas_max,10)+.8-log($winners_min,10))*$winners_yscale)-1,
		__('Quadras Winners',$SkMSDomain),
		$bgcolor);
	imagestring($winners_graph,5,$SkMgLeft+20,
		$SkGrHeight-7-($SkMgBottom+($winners_range*0.05+log($senas_max,10)+.8-log($winners_min,10))*$winners_yscale),
		__('Quadras Winners',$SkMSDomain),
		$ln3color);
	$title=utf8_decode(__('Winners until game #%d',$SkMSDomain));
	imagestring($winners_graph,7,
	($SkGrWidth-10*strlen($title))/2,$SkGrHeight-$SkMgBottom/2,
	sprintf($title,$totjogos),$white);
	imagestring($winners_graph,7,
	($SkGrWidth-10*strlen($title))/2-1,$SkGrHeight-$SkMgBottom/2-1,
	sprintf($title,$totjogos),$ticolor);
	$copy='Skloogs Megasena '.$SkMSVersion;
	imagestring($winners_graph,5,$SkGrWidth-10*strlen($copy),$SkGrHeight-$SkMgBottom-20,
	$copy,$ficolor);
	imagestring($winners_graph,5,$SkGrWidth-10*strlen($copy)-1,$SkGrHeight-$SkMgBottom-20-1,
	$copy,$cpcolor);
	imagepng($winners_graph,WP_PLUGIN_DIR."/".$SkMSDomain."/skms_winners.png");
	imagedestroy($winners_graph);

//if ($wpdb->get_var("SELECT COUNT(id) FROM ".$skmstable3." WHERE id=0") > 0) {
//		$sqlcmdi="UPDATE " ;
//		$sqlcmde=" WHERE id=0";	
//	} else {
		$sqlcmdi="INSERT INTO ";
		$sqlcmde="";
//	}
	$wpdb->query( $wpdb->prepare( $sqlcmdi.$skmstable3." SET id=0,
		totvsena = %f,
		totvquina = %f,
		totvquadra = %f,
		totgsena = %d,
		totgquina = %d,
		totgquadra = %d,
		totsena = %f,
		totquina = %f,
		totquadra = %f,
		totjogos = %d".$sqlcmde,
		$totvsena, 
		$totvquina, 
		$totvquadra, 
		$totgsena, 
		$totgquina, 
		$totgquadra, 
		$totsena,
		$totquina,
		$totquadra,
		$totjogos));
	//wp_die($log);
}

class SkMegaSena {	
	var $number='0'; // número sorteio
	var $cumul='N.D.'; // valor acumulado
	var $vsena='N.D.'; // prêmio sena
	var $gsena='N.D.'; // ganhadores sena
	var $vquina='N.D.'; // prêmio quina
	var $gquina='N.D.'; // ganhadores quina
	var $vquadra='N.D.'; // prêmio quadra
	var $gquadra='N.D.'; // ganhadores quadra
	var $place='N.D.'; // lugar sorteio
	var $thedate='N.D.'; // data sorteio
	var $nextval='N.D.'; // valor próximo prêmio
	var $nextdate='N.D.'; // próximo sorteio
	var $result='00-00-00-00-00-00'; // resultado
	var $oresult='00-00-00-00-00-00'; // resultado ordenado
	var $proxcumul='N.D.'; // valor cumulado para próximo final
	var $proxfinal='N.D.'; // valor final
	var $fimano='N.D.'; // cumul final do ano
	var $num1='00'; // primeiro número
	var $num2='00'; // segundo número
	var $num3='00'; // terceiro número
	var $num4='00'; // quarto número
	var $num5='00'; // quinto número
	var $num6='00'; // sexto número
	var $saidas; // saídas por número (array)
	var $senas; // saídas em senas por número (array)
	var $quinas; // saídas em quinas por número (array)
	var $quadras; // saídas em quadras por número (array)
	var $combi; // saídas em senas-quinas-quadras ponderadas por número (array)
	var $jogos; // razão ganhado/saído por número (array)
	var $pos1; // saídas na primeira posição por número (array)
	var $pos2; // saídas na segunda posição por número (array)
	var $pos3; // saídas na terceira posição por número (array)
	var $pos4; // saídas na quarta posição por número (array)
	var $pos5; // saídas na quinta posição por número (array)
	var $pos6; // saídas na sexta posição por número (array)
	var $totjogos; // número de jogos analisados
	var $numndx; // indíce de números
	var $atraso; // atraso por número (array)
	var $totvsena=0;
	var $totvquina=0;
	var $totvquadra=0;
	var $totgsena=0;
	var $totgquina=0;
	var $totgquadra=0;
	var $totsena=0;
	var $totquina=0;
	var $totquadra=0;
	

	function SkMegaSena() {
		global $wpdb;
		global $SkMSNextPoll;
		global $SkMSConcurso;
		global $SkHostOffset;
		global $SkMaxNumbers;
		
		$skmstable = $wpdb->prefix . "skmegasena";		
		$skmstable2 = $wpdb->prefix . "skmegasena_nums";		
		$skmstable3 = $wpdb->prefix . "skmegasena_stats";		
		
		// check if poll needed
		$SkMSNextPoll=get_option('SkMSNextPoll');
		$SkHostOffset=get_option('SkHostOffset');
		$SkMaxNumbers=get_option('SkMaxNumbers');
		if (!$SkMaxNumbers) $SkMaxNumbers=8;
		update_option('SkMaxNumbers',$SkMaxNumbers);
		
		if ( '' == $SkMSNextPoll ) {
			$nxtstamp=0;
		} else {
			$nxtdate = explode('/',$SkMSNextPoll);
			$nxtstamp = mktime(21,00,0,$nxtdate[1],$nxtdate[0],$nxtdate[2]);
			$nxtstamp -= (3600*$SkHostOffset);	
		}
		$thisstamp=time();
		/* echo "<!-- nxtdate=".$nxtdate.
			" - nxtstamp=".$nxtstamp." - SkMSNextPoll=".$SkMSNextPoll.
			" - thisstamp=".$thisstamp." -->"; */
		
		$skupdtime=get_option('SkMSUpdTime');
		$sksena = $wpdb->get_var("SELECT MAX(number) FROM ".$skmstable);
		$sksenacnt = $wpdb->get_var("SELECT COUNT(number) FROM ".$skmstable);
		if ($thisstamp > $nxtstamp || ($skupdtime && $thisstamp>$skupdtime+900) || $sksenacnt<$sksena) {
			//if ($sksenacnt<$sksena && !$skupdtime) SkMSGetStats();
			if ($thisstamp > $nxtstamp && !$skupdtime) SkMSGetStats(1);		
			
			$allowfopen=ini_get('allow_url_fopen');
			ini_set('allow_url_fopen',1);
			$fp=@fopen("http://www1.caixa.gov.br/loterias/loterias/megasena/megasena_pesquisa_new.asp?f_megasena=$thisstamp","r");
			ini_set('allow_url_fopen',$allowfopen);
			if (!$fp) return null;
			while (!feof($fp)) {
				$contents .= fgets($fp,8192);
			}
			fclose($fp);
			$res=explode("|",$contents);
			//print_r($res);
			$this->number = $res[0];
			$res[1] = str_replace('.',"",$res[1]);
			$this->cumul = str_replace(',','.',$res[1]);
			$res[2] = ereg_replace("(<[^>]+>)+","-",$res[2]);
			$this->result = ereg_replace('^-|-$',"",$res[2]);
			list($this->num1,$this->num2,$this->num3,$this->num4,$this->num5,$this->num6) =
				explode('-',$this->result);
			$res[20] = ereg_replace("(<([^>]+)>)+","-",$res[20]);
			$this->oresult = ereg_replace('^-|-$',"",$res[20]);
			$this->gsena = str_replace('.',"",$res[3]);
			$res[4] = str_replace('.',"",$res[4]);
			$this->vsena = str_replace(',','.',$res[4]);
			$this->gquina = str_replace('.',"",$res[5]);
			$res[6] = str_replace('.',"",$res[6]);
			$this->vquina = str_replace(',','.',$res[6]);
			$this->gquadra = str_replace('.',"",$res[7]);
			$res[8] = str_replace('.',"",$res[8]);
			$this->vquadra = str_replace(',','.',$res[8]);
			$this->thedate = $res[11];
			$this->place = $res[12]."/".$res[13];
			$res[21] = str_replace('.',"",$res[21]);
			$this->nextval = str_replace(',','.',$res[21]);
			$this->nextdate = $res[22];
			$res[18] = str_replace('.',"",$res[18]);
			$this->proxcumul = str_replace(',','.',$res[18]);
			$this->proxfinal = $res[17];
			$this->fimano = str_replace('.',"",$res[23]);
			
			if ($this->number > $sksena) {
				$pre='INSERT INTO ';
				$post='';
			} else {
				$pre='UPDATE ';
				$post=" WHERE number = '".$this->number."'";
			}
			$wpdb->query( $wpdb->prepare($pre.$skmstable." SET number = %d, 
			cumul = %d,
			result = %s,
			oresult = %s,
			gsena = %d,
			vsena = %f,
			gquina = %d,
			vquina = %f,
			gquadra = %d,
			vquadra = %f,
			thedate = %s,
			place = %s,
			nextval = %f,
			nextdate = %s,
			proxcumul = %f,
			proxfinal = %s,
			fimano = %f,
			num1 = %d,
			num2 = %d,
			num3 = %d,
			num4 = %d,
			num5 = %d,
			num6 = %d".$post,
			$this->number,
			$this->cumul,
			$this->result,
			$this->oresult,
			$this->gsena,
			$this->vsena,
			$this->gquina,
			$this->vquina,
			$this->gquadra,
			$this->vquadra,
			$this->thedate,
			$this->place,
			$this->nextval,
			$this->nextdate,
			$this->proxcumul,
			$this->proxfinal,
			$this->fimano,
			$this->num1,
			$this->num2,
			$this->num3,
			$this->num4,
			$this->num5,
			$this->num6));
			
			update_option('SkMSNextPoll',$this->nextdate);
			update_option('SkMSConcurso',$this->number);
			delete_option('SkMSUpdTime');
			if ($this->nextval==0) add_option('SkMSUpdTime',time());
			delete_option('SkMSNewResults');
		}	
		if ( $thisstamp <= $nxtstamp ) {
			if (get_option('SkMSNewResults')=="") {
				SkMSGetStats(0);
				add_option('SkMSNewResults',1);
			} 
			$SkMSConcurso = get_option('SkMSConcurso');
			$sksena = $wpdb->get_row("SELECT * FROM ".$skmstable." WHERE number = $SkMSConcurso");
			$this->number = $sksena->number;
			$this->cumul = $sksena->cumul;
			$this->result = $sksena->result;
			$this->oresult = $sksena->oresult;
			$this->gsena = $sksena->gsena;
			$this->vsena = $sksena->vsena;
			$this->gquina = $sksena->gquina;
			$this->vquina = $sksena->vquina;
			$this->gquadra = $sksena->gquadra;
			$this->vquadra = $sksena->vquadra;
			$this->thedate = $sksena->thedate;
			$this->place = $sksena->place;
			$this->nextval = $sksena->nextval;
			$this->nextdate = $sksena->nextdate;
			$this->proxcumul = $sksena->proxcumul;
			$this->proxfinal = $sksena->proxfinal;
			$this->fimano = $sksena->fimano;
			$this->num1 = $sksena->num1;
			$this->num2 = $sksena->num2;
			$this->num3 = $sksena->num3;
			$this->num4 = $sksena->num4;
			$this->num5 = $sksena->num5;
			$this->num6 = $sksena->num6;
			if ($this->nextval==0) add_option('SkMSUpdTime',time());
		}
	}
	
	function get() {
		global $SkMSDomain, $SkMSVersion,$SkHostOffset;

		if ($this->result == '' || $this->result == '00-00-00-00-00-00') $this->SkMegaSena();
		$content = "<div class='SkMegaSena'><p>";
		$content .= "<table class='SkMSTable'><tr>";
		$content .= "<th>".__("Game#:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSConcurso'>".$this->number."</td>";
		$content .= "</tr><tr>";
		$content .= "<th>".__("Date:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSData'>".$this->thedate
		."<br/>(".$this->place.")</td>";
		$content .= "</tr><tr>";
		$content .= "<th>".__("MegaSena:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSResult'>".$this->show_bolas($this->result)."<br/><hr/>".
			$this->show_bolas($this->oresult)."</td>";
		$content .= "</tr><tr>";
		$content .= "<th>".__("Sena Winners:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSGanhadores'>";
		if ($this->vsena != 0) $content .= $this->show_gain($this->vsena)." (".$this->gsena.")";
		else $content .= __('Cumulated!',$SkMSDomain);
		$content .= "</td>";
		$content .= "</tr><tr>";
		$content .= "<th>".__("Quina Winners:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSGanhadores'>".$this->show_gain($this->vquina)." (".$this->gquina.")</td>";
		$content .= "</tr><tr>";
		$content .= "<th>".__("Quadra Winners:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSGanhadores'>".$this->show_gain($this->vquadra)." (".$this->gquadra.")</td>";
		$content .= "</tr><tr>";
		$content .= "<th>".__("Next Reward:",$SkMSDomain)."</th>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSReward'>".$this->show_gain($this->nextval)
		."<br/>(".$this->nextdate.")</td>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSCopy'><small>Plugin <a href='http://tec.skloogs.com/dev/plugins/skloogs-megasena' alt='Skloogs MegaSena v.".$SkMSVersion."'>Skloogs Megasena v.".$SkMSVersion."</a><br/>by Philippe Hilger".$SkMSDebug."</small></td>";
		$content .= "</tr></table></p></div>";
		return $content;
	}
	
	function show() {
		echo $this->get();
	}

	function show_bola($num) {
		return sprintf("<div class='SkMSBola'>%02d</div>",$num);	
	}	
	
	function show_bolas($result) {
		$bolas=explode('-',$result);
		foreach ($bolas as $k => $val) {
			$bolas[$k]=$this->show_bola($val);
		}
		return implode('-',$bolas);
	}
	
	function price($num) {
		global $SkMSPrice;
		$val=1;
		while ($num>6) {
			$val *= ($num/($num-6));
			$num--;
		}
		return str_replace('.',',',sprintf('R$ %6.2f',$val*$SkMSPrice));	
	}
	
	function show_numbers($order,$alist,$maxnum = 0) {
		global $SkMaxNumbers;
		if (!$maxnum) $maxnum=$SkMaxNumbers; 
		if ($order) asort($alist,SORT_NUMERIC);
		else arsort($alist,SORT_NUMERIC);
		for ($i=6; $i<=$maxnum; $i++) {
			$j=0;
			$jogos[$i] = "";
			foreach ($alist as $k => $val) {
				$jogos[$i] .= $this->show_bola($this->numndx[$k]);
				$j++;
				if ($j<$i) {
					if ($i<9 || ($j % 9)<8) $jogos[$i] .= '-';
					else $jogos[$i] .= '<br/>';	
				}
				else break;
			}
		}
		return $jogos;
	}
	
	function show_gain($val) {
		$str=sprintf('%2.2f',($val-floor($val)));
		$str=str_replace('0.',',',$str);
		$val=floor($val);
		while ($val>1000) {
			$v=floor($val/1000);
			$r=$val-1000*$v;
			$str='.'.sprintf('%03d',$r).$str;
			$val=$v;
		}
		return 'R$ '.$val.$str;
	}
	
	function show_winners($val) {
		$str='';
		while ($val>1000) {
			$v=floor($val/1000);
			$r=$val-1000*$v;
			$str='.'.sprintf('%03d',$r).$str;
			$val=$v;
		}
		return $val.$str;
	}
	
	function list_gains() {
		global $SkMSDomain, $SkMSVersion, $SkMSFoldCnt, $wpdb;
		
		$skmstable3 = $wpdb->prefix . "skmegasena_stats";
		$stats = $wpdb->get_row("SELECT * FROM ".$skmstable3." WHERE id=0");	
		$this->totvsena=$stats->totvsena;
		$this->totvquina=$stats->totvquina;
		$this->totvquadra=$stats->totvquadra;
		$this->totgsena=$stats->totgsena;
		$this->totgquina=$stats->totgquina;
		$this->totgquadra=$stats->totgquadra;
		$this->totsena=$stats->totsena;
		$this->totquina=$stats->totquina;
		$this->totquadra=$stats->totquadra;
		$this->totjogos=$stats->totjogos;
		$SkMSFoldCnt++;
		$content = "<div class='SkMegaSena'>";
		$content .= '<table class="SkMSGains">';
		if (!$this->totjogos) {
			$content .= '<tr><td>'.__("Megasena Gains Stats Unavailable.",$SkMSDomain).'</td></tr>';
		} else {
			$content .= '<tr><th class="SkMSGainSec">'.__('Global Stats',$SkMSDomain).'</th></tr>';
			$content .= '<tr><th>'.__('Total Winners',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSGanhadores">'.
				$this->show_winners($this->totgsena+$this->totgquina+$this->totgquadra).
				'</td></tr>';
			$content .= '<tr><th>'.__('Total Gains',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain($this->totsena+$this->totquina+$this->totquadra).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Gain per Winner',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totsena+$this->totquina+$this->totquadra)/
				($this->totgsena+$this->totgquina+$this->totgquadra)).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Total Gain per Game',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totsena+$this->totquina+$this->totquadra)/
				($this->totjogos)).
				'</td></tr>';
			$content .= '<tr><th class="SkMSGainSec"><div class="SkMSFold" clicktotoggle="senagain'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'senagain'.$SkMSFoldCnt.'\');">'.__('Sena Stats',$SkMSDomain).'</div></th></tr>';
			$content .= '<tr><td><div id="senagain'.$SkMSFoldCnt.'" style="display:none;"><table><tr><th>'.__('Sena Winners',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSGanhadores">'.
				$this->show_winners($this->totgsena).
				'</td></tr>';
			$content .= '<tr><th>'.__('Sena Gains',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain($this->totsena).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Gain per Sena Winner',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totsena)/
				($this->totgsena)).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Sena Gain per Game',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totvsena)/
				($this->totjogos)).
				'</td></tr></table></div></td></tr>';
			$content .= '<tr><th class="SkMSGainSec"><div class="SkMSFold" clicktotoggle="quinagain'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'quinagain'.$SkMSFoldCnt.'\');">'.__('Quina Stats',$SkMSDomain).'</div></th></tr>';
			$content .= '<tr><td><div id="quinagain'.$SkMSFoldCnt.'" style="display:none;"><table><tr><th>'.__('Quina Winners',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSGanhadores">'.
				$this->show_winners($this->totgquina).
				'</td></tr>';
			$content .= '<tr><th>'.__('Quina Gains',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain($this->totquina).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Gain per Quina Winner',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totquina)/
				($this->totgquina)).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Quina Gain per Game',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totvquina)/
				($this->totjogos)).
				'</td></tr></table></div></td></tr>';
			$content .= '<tr><th class="SkMSGainSec"><div class="SkMSFold" clicktotoggle="quadragain'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'quadragain'.$SkMSFoldCnt.'\');">'.__('Quadra Stats',$SkMSDomain).'</div></th></tr>';
			$content .= '<tr><td><div id="quadragain'.$SkMSFoldCnt.'" style="display:none;"><table><tr><th>'.__('Quadra Winners',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSGanhadores">'.
				$this->show_winners($this->totgquadra).
				'</td></tr>';
			$content .= '<tr><th>'.__('Quadra Gains',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain($this->totquadra).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Gain per Quadra Winner',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totquadra)/
				($this->totgquadra)).
				'</td></tr>';
			$content .= '<tr><th>'.__('Average Quadra Gain per Game',$SkMSDomain).'</th></tr>';
			$content .= '<tr><td class="SkMSValor">'.
				$this->show_gain(($this->totvquadra)/
				($this->totjogos)).
				'</td></tr></table></div></td></tr>';
		}
		$content .= "<tr><td class='SkMSAnalisado'>".__('Analyzed Games:',$SkMSDomain)."&nbsp;".$this->totjogos."</td></tr>";
		$content .= "<tr><td class='SkMSCopy'><small>Plugin <a href='http://tec.skloogs.com/dev/plugins/skloogs-megasena' alt='Skloogs MegaSena v.".$SkMSVersion."'>Skloogs Megasena v.".$SkMSVersion."</a><br/>by Philippe Hilger".$SkMSDebug."</small></td></tr>";
		$content .= "</table></div>";
		return $content;		
			
	}
	
	function list_jogos() {
		global $SkMSDomain, $SkMSVersion, $wpdb, $SkMSFoldCnt;

		$SkMSFoldCnt++;
		$skmstable = $wpdb->prefix . "skmegasena";		

		$cururl = $_SERVER['REQUEST_URI'];
		if ($_SERVER['QUERY_STRING'] == '') $cururl .= '?';
		else $cururl .= '?'.preg_replace('/\&?sort=[^\&]+/','',$_SERVER['QUERY_STRING']).'&';
		$cururl = str_replace('?&','?',$cururl);

		$ord=preg_replace('[^a-z]','',$_GET['sort']);

		$content = "<div class='SkMegaSena'>";
		$content .= '<table class="SkMSGames">';
//		if (!$this->totjogos) {
//			$content .= '<tr><td colspan=6>'.__("Megasena Games List Unavailable.",$SkMSDomain).'</td></tr>';
//		} else {
			$content .= '<tr>';
			$content .= '<th>'.__('Game#',$SkMSDomain).'</th>';
			$content .= '<th>'.__('Date',$SkMSDomain).'</th>';
			$content .= '<th>'.__('Result',$SkMSDomain). ' (';
			if ($ord == 'n') {
				$content .= '<a href="'.$cururl.'sort=y'.'">'.__("Ordered",$SkMSDomain).'</a>';
			} else {
				$content .= '<a href="'.$cururl.'sort=n'.'">'.__("Unordered",$SkMSDomain).'</a>';
			}
			$content .= ')</th>';
			$content .= '<th>'.__('Senas',$SkMSDomain).'</th>';
			$content .= '<th>'.__('Quinas',$SkMSDomain).'</th>';
			$content .= '<th>'.__('Quadras',$SkMSDomain).'</th>';
			$content .= '</tr>';
			$sql = 'SELECT * FROM '.$skmstable.' ORDER BY number DESC';
			$res = $wpdb->get_results($sql);
			$j=0;
			$dt=0;
			foreach ($res as $jogo) {
				$dta=preg_replace('=[0-9]{2}/[0-9]{2}/=','',$jogo->thedate);
				if ($dta!=$dt) {
					if ($dt>0) $content .= '</table></div></td></tr>';
					$dt=$dta;
					$content .= '<tr><th class="SkMSYear" colspan=6><div class="SkMSFold" clicktotoggle="year'.$dt.'_'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'year'.$dt.'_'.$SkMSFoldCnt.'\');">'.
						sprintf(__('Results for Year %s',$SkMSDomain),$dt).
						'</div></th></tr><tr><td colspan=6><div id="year'.$dt.'_'.$SkMSFoldCnt.'" style="display:none;"><table>';
				}
				$content .= '<tr class="SkMSRow'.($j%2+1).'">';
				$content .= '<td class="SkMSConcurso">'.$jogo->number.'</td>';
				$content .= '<td class="SkMSData">'.
					preg_replace('=/[0-9]{4}=','',$jogo->thedate).'</td>';
				$content .= '<td class="SkMSResultado">';
				if ($ord == 'n') {
					$content .= $this->show_bolas($jogo->result);
				} else {
					$content .= $this->show_bolas($jogo->oresult);
				}
				$content .= '</td>';
				$content .= '<td class="SkMSSena">';
				if ($jogo->vsena != 0) $content .= $this->show_gain($jogo->vsena);
				else $content .= __('Cumulated!',$SkMSDomain);
				$content .= '</td>';
				$content .= '<td class="SkMSQuina">'.$this->show_gain($jogo->vquina).'</td>';
				$content .= '<td class="SkMSQuadra">'.$this->show_gain($jogo->vquadra).'</td>';
				$content .= '</tr>';
				$j++;
			}
//		}
		if ($dt>0) $content .= '</table></div></td></tr>';
		$content .= "<tr><td colspan=6 class='SkMSCopy'><small>Plugin <a href='http://tec.skloogs.com/dev/plugins/skloogs-megasena' alt='Skloogs MegaSena v.".$SkMSVersion."'>Skloogs Megasena v.".$SkMSVersion."</a><br/>by Philippe Hilger".$SkMSDebug."</small></td></tr>";
		$content .= "</table></div>";
		return $content;		
			
	}
	
	function numbers_suggest($maxnum = 0) {
		global $SkMSDomain, $SkMSVersion, $SkMSFoldCnt,$SkMaxNumbers,$wpdb;

		$skmstable2 = $wpdb->prefix . "skmegasena_nums";
		$skmstable3 = $wpdb->prefix . "skmegasena_stats";
		
		if (!$maxnum) $maxnum=$SkMaxNumbers;
		
		$totjogos = $wpdb->get_var("SELECT totjogos FROM ".$skmstable3);	
		if (!$this->numndx) {
			$stats = $wpdb->get_results("SELECT * FROM ".$skmstable2);
			foreach ($stats as $nstat) {
				$i=$nstat->num;
				$this->numndx[$i]=$i;
				$this->saidas[$i]=$nstat->saidas;
				echo "<!-- saidas de $i = ".$nstat->saidas." -->";
				$this->senas[$i]=$nstat->senas;
				$this->quinas[$i]=$nstat->quinas;
				$this->quadras[$i]=$nstat->quadras;
				$this->combi[$i]=$nstat->combi;
				$this->jogos[$i]=$nstat->jogos;
				$this->pos1[$i]=$nstat->pos1;
				$this->pos2[$i]=$nstat->pos2;
				$this->pos3[$i]=$nstat->pos3;
				$this->pos4[$i]=$nstat->pos4;
				$this->pos5[$i]=$nstat->pos5;
				$this->pos6[$i]=$nstat->pos6;
				$this->atraso[$i]=$nstat->atraso;
			}
		}		
		$SkMSFoldCnt++;
		$content = "<div class='SkMegaSena'>";
		$content .= '<table class="SkMSSuggest">';
		if (!$this->numndx) {
			$content .= '<tr><td colspan=3>'.__("Megasena Suggestions Unavailable.",$SkMSDomain).'</td></tr>';
		} else {
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="mostcnt'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'mostcnt'.$SkMSFoldCnt.'\');">'.__('Most Counts',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="mostcnt'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->saidas, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="lesscnt'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'lesscnt'.$SkMSFoldCnt.'\');">'.__('Less Counts',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="lesscnt'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->saidas, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="moresena'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'moresena'.$SkMSFoldCnt.'\');">'.__('More Senas',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="moresena'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->senas, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="lesssena'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'lesssena'.$SkMSFoldCnt.'\');">'.__('Less Senas',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="lesssena'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->senas, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="morequina'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'morequina'.$SkMSFoldCnt.'\');">'.__('More Quinas',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="morequina'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->quinas, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="lessquina'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'lessquina'.$SkMSFoldCnt.'\');">'.__('Less Quinas',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="lessquina'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->quinas, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="morequadra'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'morequadra'.$SkMSFoldCnt.'\');">'.__('More Quadras',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="morequadra'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->quadras, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="lessquadra'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'lessquadra'.$SkMSFoldCnt.'\');">'.__('Less Quadras',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="lessquadra'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->quadras, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="morecomb'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'morecomb'.$SkMSFoldCnt.'\');">'.__('More Combination',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="morecomb'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->combi, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="lesscomb'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'lesscomb'.$SkMSFoldCnt.'\');">'.__('Less Combination',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="lesscomb'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->combi, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="morewin'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'morewin'.$SkMSFoldCnt.'\');">'.__('More Wins/Count',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="morewin'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->jogos, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="lesswin'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'lesswin'.$SkMSFoldCnt.'\');">'.__('Less Wins/Count',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="lesswin'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->jogos) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="oldest'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'oldest'.$SkMSFoldCnt.'\');">'.__('Oldest',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="oldest'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(1,$this->atraso) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= '<tr><th colspan=3><div class="SkMSFold" clicktotoggle="newest'.$SkMSFoldCnt.'" onclick="javascript:skfold(\'newest'.$SkMSFoldCnt.'\');">'.__('Most Recent',$SkMSDomain).'</div></th></tr>';
			$row=0;
			$content .= '<tr><td><div id="newest'.$SkMSFoldCnt.'" style="display:none;"><table>';
			foreach ($this->show_numbers(0,$this->atraso, $maxnum) as $k => $jogo) {
				$content .= '<tr class="SkMSRow'.($row%2+1).'">';
				$content .= '<td class="SkMSNumJogo">'.$k.'</td><td class="SkMSJogo">'.$jogo.'</td>';
				$content .= '<td class="SkMSPreco">'.$this->price($k).'</td>';
				$content .= '</tr>';
				$row++;		
			}
			$content .= '</table></div></td></tr>';
			$content .= "<tr><td class='SkMSAnalisado'>".__('Analyzed Games:',$SkMSDomain)."&nbsp;".$totjogos."</td></tr>";
		}
		$content .= "<tr><td colspan=3 class='SkMSCopy'><small>Plugin <a href='http://tec.skloogs.com/dev/plugins/skloogs-megasena' alt='Skloogs MegaSena v.".$SkMSVersion."'>Skloogs Megasena v.".$SkMSVersion."</a><br/>by Philippe Hilger".$SkMSDebug."</small></td></tr>";
		$content .= "</table></div>";
		return $content;		
	}
	
	function numbers_stats($graf=0) {
		global $SkMSDomain,$SkMSVersion,$wpdb;

		$skmstable2=$wpdb->prefix . "skmegasena_nums";
		$skmstable3=$wpdb->prefix . "skmegasena_stats";

		$totjogos = $wpdb->get_var("SELECT totjogos FROM ".$skmstable3);	
		
		if (!$this->numndx) {
			$stats = $wpdb->get_results("SELECT * FROM ".$skmstable2);
			foreach ($stats as $nstat) {
				$i=$nstat->num;
				$this->numndx[$i]=$i;
				$this->saidas[$i]=$nstat->saidas;
				$this->senas[$i]=$nstat->senas;
				$this->quinas[$i]=$nstat->quinas;
				$this->quadras[$i]=$nstat->quadras;
				$this->combi[$i]=$nstat->combi;
				$this->jogos[$i]=$nstat->jogos;
				$this->pos1[$i]=$nstat->pos1;
				$this->pos2[$i]=$nstat->pos2;
				$this->pos3[$i]=$nstat->pos3;
				$this->pos4[$i]=$nstat->pos4;
				$this->pos5[$i]=$nstat->pos5;
				$this->pos6[$i]=$nstat->pos6;
				$this->atraso[$i]=$nstat->atraso;
			}
		}		
		
		$cururl = $_SERVER['REQUEST_URI'];
		if ($_SERVER['QUERY_STRING'] == '') $cururl .= '?';
		else $cururl .= '?'.preg_replace('/\&?sort=[^\&]+/','',$_SERVER['QUERY_STRING']).'&';
		$cururl = str_replace('?&','?',$cururl);

		$content = "<div class='SkMegaSena'>";
		$content .= '<table class="SkMSStats">';
		if ($graf) {
			if (!file_exists(WP_PLUGIN_DIR."/".$SkMSDomain."/skms_numbers.png")
			|| !file_exists(WP_PLUGIN_DIR."/".$SkMSDomain."/skms_atraso.png")
			|| !file_exists(WP_PLUGIN_DIR."/".$SkMSDomain."/skms_winners.png")) SkMSGetStats(0);
			$content.='<tr><td colspan=8><img width='.(($graf==1)?'100%':150).' src="'.WP_PLUGIN_URL."/".$SkMSDomain.'/skms_numbers.png" alt="'.__('Numbers Total Appearances',$SkMSDomain).'"></td></tr>';
			$content.='<tr><td colspan=8><img width='.(($graf==1)?'100%':150).' src="'.WP_PLUGIN_URL."/".$SkMSDomain.'/skms_atraso.png" alt="'.__('Numbers Last Apperances',$SkMSDomain).'"></td></tr>';
			$content.='<tr><td colspan=8><img width='.(($graf==1)?'100%':150).' src="'.WP_PLUGIN_URL."/".$SkMSDomain.'/skms_winners.png" alt="'.__('Winners Stats',$SkMSDomain).'"></td></tr>';
		} else {
			if (!$this->numndx) {
				$content .= '<tr><td colspan=8>'.__("Megasena Statistics Unavailable.",$SkMSDomain).'</td></tr>';
			} else {
				$content .= '<tr><th><a href="'.$cururl.'sort=nb'.
					'" alt="'.__('Sort by Number',$SkMSDomain).'">'.__('Number',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=cn'.
					'" alt="'.__('Sort by Count',$SkMSDomain).'">'.__('Count',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=sn'.
					'" alt="'.__('Sort by Senas',$SkMSDomain).'">'.__('In Senas',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=qn'.
					'" alt="'.__('Sort by Quinas',$SkMSDomain).'">'.__('In Quinas',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=qd'.
					'" alt="'.__('Sort by Quadras',$SkMSDomain).'">'.__('In Quadras',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=cb'.
					'" alt="'.__('Sort by Combination',$SkMSDomain).'">'.__('Weighted Combination',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=wc'.
					'" alt="'.__('Sort by Wins/Count',$SkMSDomain).'">'.__('Wins/Count',$SkMSDomain).'</a></th>';
				$content .= '<th><a href="'.$cururl.'sort=ls'.
					'" alt="'.__('Sort by LastSeen',$SkMSDomain).'">'.__('Last Seen',$SkMSDomain).'</a></th>';
				$content .= '</tr>';
				switch(preg_replace('[^a-z]','',$_GET['sort'])) {
					case 'cn':
						arsort($this->saidas,SORT_NUMERIC);
						$sortarray =& $this->saidas;
						break;
					case 'sn':
						arsort($this->senas,SORT_NUMERIC);
						$sortarray =& $this->senas;
						break;
					case 'qn':
						arsort($this->quinas,SORT_NUMERIC);
						$sortarray =& $this->quinas;
						break;
					case 'qd':
						arsort($this->quadras,SORT_NUMERIC);
						$sortarray =& $this->quadras;
						break;
					case 'cb':
						arsort($this->combi,SORT_NUMERIC);
						$sortarray =& $this->combi;
						break;
					case 'wc':
						arsort($this->jogos,SORT_NUMERIC);
						$sortarray =& $this->jogos;
						break;
					case 'ls':
						arsort($this->atraso,SORT_NUMERIC);
						$sortarray =& $this->atraso;
						break;
					case 'nb':
					default:
						asort($this->numndx,SORT_NUMERIC);
						$sortarray =& $this->numndx;
						break;
				}
				$j=0;
				foreach ($sortarray as $i => $val) {
					$content .= '<tr class="SkMSRow'.($j%2+1).'">';
					$content .= '<td class="SkMSNumbers">'.$this->show_bola($this->numndx[$i]).'</td>';
					$content .= '<td class="SkMSSaidas">'.$this->saidas[$i].'</td>';
					$content .= '<td class="SkMSSenas">'.$this->senas[$i].'</td>';
					$content .= '<td class="SkMSQuinas">'.$this->quinas[$i].'</td>';
					$content .= '<td class="SkMSQuadras">'.$this->quadras[$i].'</td>';
					$content .= '<td class="SkMSCombi">'.round($this->combi[$i]).'</td>';
					$content .= '<td class="SkMSJogos">'.round($this->jogos[$i]).'</td>';
					$content .= '<td class="SkMSConcurso">'.round($this->atraso[$i]).'</td>';
					$content .= '</tr>';
					$j++;
				}
				$content .= '<tr><td class="SkMSAnalisado" colspan=8>'.__('Analyzed Games:',$SkMSDomain).' '.$totjogos.'</td></tr>';
			}
		}
		$content .= "<tr><td class='SkMSCopy' colspan=8><small>Plugin <a href='http://tec.skloogs.com/dev/plugins/skloogs-megasena' alt='Skloogs MegaSena v.".$SkMSVersion."'>Skloogs Megasena v.".$SkMSVersion."</a><br/>by Philippe Hilger".$SkMSDebug."</small></td></tr>";
		$content .= "</table></div>";
		return $content;
	}

	function random_show($title,$numsarray) {
		global $SkMSVersion;

		sort($numsarray, SORT_NUMERIC);
		$content = "<div class='SkMegaSena'>";
		$content .= "<table class='SkMSTable'><tr>";
		$content .= "<th colspan=2>".$title."</th>";
		$content .= "</tr><tr>";
		$content .= "<td>&nbsp;</td><td class='SkMSNumbers'>";
		$cnt=1;
		for ($i=0; $i<count($numsarray); $i++) {
			$content .= $this->show_bola($numsarray[$i]);
			if ($cnt<10 && $i<count($numsarray)-1) $content .= "-";
			else if ($i<count($numsarray)-1) {
				$content .= "<br/>";
				$cnt=0;
			}
			$cnt++;	
		}
		$content .= "</td>";
		$content .= "</tr><tr>";
		$content .= "<td class='SkMSCopy' colspan=2><small>Plugin <a href='http://tec.skloogs.com/dev/plugins/skloogs-megasena' alt='Skloogs MegaSena v.".$SkMSVersion."'>Skloogs Megasena v.".$SkMSVersion."</a><br/>by Philippe Hilger".$SkMSDebug."</small></td>";
		$content .= "</tr>";
		$content .= "</table></div>";
		return $content;
		
	}
	
	function random_sena() {
		global $SkMSDomain;
		return $this->random_show(__("Random Sena:",$SkMSDomain),$this->random(1,60,6));
	}	
	
	function random_quina() {
		global $SkMSDomain;
		return $this->random_show(__("Random Quina:",$SkMSDomain),$this->random(1,80,5));
	}
	
	function random_facil() {
		global $SkMSDomain;
		return $this->random_show(__("Random Lotofácil:",$SkMSDomain),$this->random(1,25,15));
	}
	
	function random_mania() {
		global $SkMSDomain;
		return $this->random_show(__("Random Lotomania:",$SkMSDomain),$this->random(0,99,50));
	}
	
	function random($min,$max,$num) {
		for ($i=$min; $i<=$max; $i++) {
			$numbers[$i]=sprintf("%02d",$i);
		}
		if ($num>$max-$min+1) $num=$max-$min+1;
		for ($i=1; $i<=$num; $i++) {
			$rnum=mt_rand($min,$max);
			$randnum[$i]=$numbers[$rnum];
			if ($rnum<$max) {
				$numbers[$rnum]=$numbers[$max];
			}
			$max--;
		}
		return $randnum;
	}

}

function SkMegaSena($act = "") {
	global $skmegasena;
	switch ($act) {
		case 'suggest':
			echo $skmegasena->numbers_suggest();
			break;
		case 'random':
			echo $skmegasena->random_sena();
			break;
		case 'gains':
			echo $skmegasena->random_sena();
			break;
		case 'graphs':
			echo $skmegasena->numbers_stats(2);
			break;
		default:
			$skmegasena->show();
			break;	
	}
}

function SkMSGains() {
	global $skmegasena;
	echo $skmegasena->list_gains();	
}

function widget_skmegasena($args) {
	global $SkMSDomain;
    extract($args);
	echo $before_widget;
	echo $before_title;
	echo __('MegaSena Result',$SkMSDomain);
	echo $after_title;
	SkMegaSena();
	echo $after_widget;
}

function widget_skmegasena_random($args) {
	global $SkMSDomain;
    extract($args);
	echo $before_widget;
	echo $before_title;
	echo __('Random MegaSena',$SkMSDomain);
	echo $after_title;
	SkMegaSena('random');
	echo $after_widget;
}

function widget_skmegasena_gains($args) {
	global $SkMSDomain;
    extract($args);
	echo $before_widget;
	echo $before_title;
	echo __('MegaSena Gains',$SkMSDomain);
	echo $after_title;
	SkMegaSena('gains');
	echo $after_widget;
}

function widget_skmegasena_suggest($args) {
	global $SkMSDomain;
    extract($args);
	echo $before_widget;
	echo $before_title;
	echo __('MegaSena Suggest',$SkMSDomain);
	echo $after_title;
	SkMegaSena('suggest');
	echo $after_widget;
}

function widget_skmegasena_graphs($args) {
	global $SkMSDomain;
    extract($args);
	echo $before_widget;
	echo $before_title;
	echo __('MegaSena Graphs',$SkMSDomain);
	echo $after_title;
	SkMegaSena('graphs');
	echo $after_widget;
}

function SkMS_loader() {
	global $skmegasena,$SkMSIsSetup,$SkMSDomain;
	$skmegasena = new SkMegaSena();
	//add_action( 'admin_menu', array( &$polldaddy_object, 'admin_menu' ) );

	// init DB
	//SkMSGetStats();

	
	if($SkMSIsSetup) {
		return;
	} 
	load_plugin_textdomain($SkMSDomain, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
	$SkMSIsSetup=1;

	// register widget
	register_sidebar_widget('Skloogs Megasena', 'widget_skmegasena');
	register_sidebar_widget('Skloogs Random Sena', 'widget_skmegasena_random');
	register_sidebar_widget('Skloogs MegaSena Gains', 'widget_skmegasena_gains');
	register_sidebar_widget('Skloogs MegaSena Suggest', 'widget_skmegasena_suggest');
	register_sidebar_widget('Skloogs MegaSena Graphs', 'widget_skmegasena_graphs');


}

function SkMS_filter($content) {
	global $skmegasena;
	
	//if ($skmegasena->number == 'N.D.') SkMS_loader();
	
    if (ereg('[megasena]',$content)) $skm1=$skmegasena->get();
    if (ereg('[megasena random]',$content)) $skm2=$skmegasena->random_sena();
    if (ereg('[quina random]',$content)) $skm3=$skmegasena->random_quina();
    if (ereg('[lotofacil random]',$content)) $skm4=$skmegasena->random_facil();
    if (ereg('[lotomania random]',$content)) $skm5=$skmegasena->random_mania();
    if (ereg('[megasena count]',$content)) $skm6=$skmegasena->numbers_stats();
    if (ereg('[megasena suggest]',$content)) $skm7=$skmegasena->numbers_suggest();
    if (ereg('[megasena games]',$content)) $skm8=$skmegasena->list_jogos();
    if (ereg('[megasena gains]',$content)) $skm9=$skmegasena->list_gains();
    if (ereg('[megasena numbers_graph]',$content)) $skm10=$skmegasena->numbers_stats(1); // graphs
    $content = str_replace('[megasena]',$skm1,$content);
    $content = str_replace('[megasena random]',$skm2,$content);
    $content = str_replace('[quina random]',$skm3,$content);
    $content = str_replace('[lotofacil random]',$skm4,$content);
    $content = str_replace('[lotomania random]',$skm5,$content);
    $content = str_replace('[megasena count]',$skm6,$content);
    $content = str_replace('[megasena suggest]',$skm7,$content);
    $content = str_replace('[megasena games]',$skm8,$content);
    $content = str_replace('[megasena gains]',$skm9,$content);
    $content = str_replace('[megasena numbers_graph]',$skm10,$content);
    return $content;
}

function SkMS_style() {
	global $SkMSDomain;
	if (file_exists(WP_PLUGIN_DIR."/".$SkMSDomain.'/style.css')) {
		echo "<link rel='stylesheet' href='".plugins_url($SkMSDomain)."/style.css' type='text/css' />";
	} else {
		echo "<link rel='stylesheet' href='".plugins_url($SkMSDomain)."/default-style.css' type='text/css' />";		
	}
	echo '<script language="Javascript"><!--
				function skfold(id) {
					el=document.getElementById(id);
					if (el.style.display=="block") el.style.display="none";
					else el.style.display="block";
				}
				// --></script>';
}

/*
 * Plugins desinstallation
 */
function SkMS_uninstall() {
	global $wpdb;
	global $SkMSDBVersion,$SkMSDBNumsVersion,$SkMSDBStatsVersion,$SkMSNextPoll,$SkMSConcurso;

	delete_option('SkMSDBVersion');
	delete_option('SkMSDBNumsVersion');
	delete_option('SkMSDBStatsVersion');
	delete_option('SkMSNextPoll');
	delete_option('SkMSConcurso');
	delete_option('SkMaxNumbers');

	$skmstable = $wpdb->prefix . "skmegasena";
	$sql="DROP TABLE ".$skmstable;
	$wpdb->query($sql);
	$skmstable = $wpdb->prefix . "skmegasena_nums";
	$sql="DROP TABLE ".$skmstable;
	$wpdb->query($sql);
	$skmstable = $wpdb->prefix . "skmegasena_stats";
	$sql="DROP TABLE ".$skmstable;
	$wpdb->query($sql);
	//$wpdb->query($sql);
}

/*
 * Plugins installation / DB Table creation
 */
function SkMS_install() {
	global $wpdb;
	global $SkMSDBVersion;
	global $SkMSDBNumsVersion;
	global $SkMSDBStatsVersion;
	global $SkMSNextPoll;
	global $SkMSConcurso;
	global $SkHostOffset;
	global $SkMaxNumbers;
	
	$skmstable = $wpdb->prefix . "skmegasena";
	$skmstable2 = $wpdb->prefix . "skmegasena_nums";
	$skmstable3 = $wpdb->prefix . "skmegasena_stats";
	
	// check games table existence
	if($wpdb->get_var("SHOW TABLES LIKE '".$skmstable."'") != $skmstable) {
		// table do not exist: create!
		$sql = "CREATE TABLE " . $skmstable . " (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  number smallint(5) NOT NULL,
		  cumul decimal(11,2) NOT NULL,
		  vsena decimal(11,2) NOT NULL,
		  vquina decimal(11,2) NOT NULL,
		  vquadra decimal(11,2) NOT NULL,
		  gsena mediumint(7) NOT NULL,
		  gquina mediumint(7) NOT NULL,
		  gquadra mediumint(7) NOT NULL,
		  place VARCHAR(40) NOT NULL,
		  thedate VARCHAR(12) NOT NULL,
		  nextval decimal(11,2) NOT NULL,
		  nextdate VARCHAR(12) NOT NULL,
		  result VARCHAR(30) NOT NULL,
		  oresult VARCHAR(30) NOT NULL,
		  proxcumul decimal(11,2) NOT NULL,
		  proxfinal varchar(1) NOT NULL,
		  fimano decimal(11,2) NOT NULL,
		  num1 smallint(3) NOT NULL,
		  num2 smallint(3) NOT NULL,
		  num3 smallint(3) NOT NULL,
		  num4 smallint(3) NOT NULL,
		  num5 smallint(3) NOT NULL,
		  num6 smallint(3) NOT NULL,
		  UNIQUE KEY id (id),	  
		  UNIQUE KEY number (number)	  
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option('SkMSDBVersion', $SkMSDBVersion);
		add_option('SkMSNextPoll', $SkMSNextPoll);
		add_option('SkMSConcurso', $SkMSConcurso);
		add_option('SkHostOffset', $SkHostOffset);
		add_option('SkMaxNumbers', $SkMaxNumbers);
	}
	// check DB update
	$installed_ver = get_option( 'SkMSDBVersion' );
	if( $installed_ver != $SkMSDBVersion ) {
		$sql = "CREATE TABLE " . $skmstable . " (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  number smallint(5) NOT NULL,
		  cumul decimal(11,2) NOT NULL,
		  vsena decimal(11,2) NOT NULL,
		  vquina decimal(11,2) NOT NULL,
		  vquadra decimal(11,2) NOT NULL,
		  gsena mediumint(7) NOT NULL,
		  gquina mediumint(7) NOT NULL,
		  gquadra mediumint(7) NOT NULL,
		  place VARCHAR(40) NOT NULL,
		  thedate VARCHAR(12) NOT NULL,
		  nextval decimal(11,2) NOT NULL,
		  nextdate VARCHAR(12) NOT NULL,
		  result VARCHAR(30) NOT NULL,
		  oresult VARCHAR(30) NOT NULL,
		  proxcumul decimal(11,2) NOT NULL,
		  proxfinal varchar(1) NOT NULL,
		  fimano decimal(11,2) NOT NULL,
		  num1 smallint(3) NOT NULL,
		  num2 smallint(3) NOT NULL,
		  num3 smallint(3) NOT NULL,
		  num4 smallint(3) NOT NULL,
		  num5 smallint(3) NOT NULL,
		  num6 smallint(3) NOT NULL,
		  UNIQUE KEY id (id),	  
		  UNIQUE KEY number (number)	  
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
		// clean DB
		$sql='TRUNCATE TABLE '.$skmstable;
		$wpdb->query($sql);		
		update_option('SkMSDBVersion', $SkMSDBVersion);
	}
	
	// check numbers table existence
	if($wpdb->get_var("SHOW TABLES LIKE '".$skmstable2."'") != $skmstable2) {
		// table do not exist: create!
		$sql = "CREATE TABLE " . $skmstable2 . " (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  num smallint(3) NOT NULL,
		  saidas smallint(5) NOT NULL,
		  senas mediumint(11) NOT NULL,
		  quinas mediumint(11) NOT NULL,
		  quadras mediumint(11) NOT NULL,
		  combi mediumint(11) NOT NULL,
		  jogos mediumint(11) NOT NULL,
		  pos1 smallint(5) NOT NULL,
		  pos2 smallint(5) NOT NULL,
		  pos3 smallint(5) NOT NULL,
		  pos4 smallint(5) NOT NULL,
		  pos5 smallint(5) NOT NULL,
		  pos6 smallint(5) NOT NULL,
		  atraso smallint(5) NOT NULL,
		  UNIQUE KEY id (id),
		  UNIQUE KEY num (num)	  
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option('SkMSDBNumsVersion', $SkMSDBNumsVersion);
	}
	// check Nums DB update
	$installed_ver = get_option( 'SkMSDBNumsVersion' );
	if( $installed_ver != $SkMSDBNumsVersion ) {
		$sql = "CREATE TABLE " . $skmstable2 . " (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  num smallint(3) NOT NULL,
		  saidas smallint(5) NOT NULL,
		  senas mediumint(11) NOT NULL,
		  quinas mediumint(11) NOT NULL,
		  quadras mediumint(11) NOT NULL,
		  combi mediumint(11) NOT NULL,
		  jogos mediumint(11) NOT NULL,
		  pos1 smallint(5) NOT NULL,
		  pos2 smallint(5) NOT NULL,
		  pos3 smallint(5) NOT NULL,
		  pos4 smallint(5) NOT NULL,
		  pos5 smallint(5) NOT NULL,
		  pos6 smallint(5) NOT NULL,
		  atraso smallint(5) NOT NULL,
		  UNIQUE KEY id (id),
		  UNIQUE KEY num (num)	  
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// clean DB
		$sql='TRUNCATE TABLE '.$skmstable2;
		$wpdb->query($sql);		
		update_option('SkMSDBNumsVersion', $SkMSDBNumsVersion);
	}
	
	// check stats table existence
	if($wpdb->get_var("SHOW TABLES LIKE '".$skmstable3."'") != $skmstable3) {
		// table do not exist: create!
		$sql = "CREATE TABLE " . $skmstable3 . " (
			id mediumint(9) NOT NULL,
			totvsena decimal(15,2) NOT NULL,
			totvquina decimal(15,2) NOT NULL,
			totvquadra decimal(15,2) NOT NULL,
			totgsena mediumint(11) NOT NULL,
			totgquina mediumint(11) NOT NULL,
			totgquadra mediumint(11) NOT NULL,
			totsena decimal(15,2) NOT NULL,		
			totquina decimal(15,2) NOT NULL,		
			totquadra decimal(15,2) NOT NULL,
			totjogos smallint(5) NOT NULL,
			UNIQUE KEY id (id)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option('SkMSDBStatsVersion', $SkMSDBStatsVersion);
	}
	// check Stats DB update
	$installed_ver = get_option( 'SkMSDBStatsVersion' );
	if( $installed_ver != $SkMSDBStatsVersion ) {
		$sql = "CREATE TABLE " . $skmstable3 . " (
			id mediumint(9) NOT NULL,
			totvsena decimal(15,2) NOT NULL,
			totvquina decimal(15,2) NOT NULL,
			totvquadra decimal(15,2) NOT NULL,
			totgsena mediumint(11) NOT NULL,
			totgquina mediumint(11) NOT NULL,
			totgquadra mediumint(11) NOT NULL,
			totsena decimal(15,2) NOT NULL,		
			totquina decimal(15,2) NOT NULL,		
			totquadra decimal(15,2) NOT NULL,
			totjogos smallint(5) NOT NULL,
			UNIQUE KEY id (id)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// clean DB
		$sql='TRUNCATE TABLE '.$skmstable3;
		$wpdb->query($sql);		
		update_option('SkMSDBStatsVersion', $SkMSDBStatsVersion);
	}

	// Update DB with current megasena
	SkMS_loader();
}

function SkMSOptions() {
	global $SkMSNextPoll, $SkMSDomain,$SkMSConcurso,$SkHostOffset;

	if ($_POST['updated'] == 'true') SkMS_loader();
?>
	<div class="wrap">
	<h2>Skloogs MegaSena</h2>
	
	<form method="post" action="options.php">
	<?php settings_fields('skloogs-megasena'); ?>
	
	<table class="form-table">
	
	<tr valign="top">
	<th scope="row"><?php echo __('Configuration Menu',$SkMSDomain); ?></th>
	<td><input type="radio" name="SkMenuMode" value="Settings"<?php if (get_option('SkMenuMode')=='Settings') echo ' checked'; ?> /><?php echo __('Settings',$SkMSDomain); ?><br />
	<input type="radio" name="SkMenuMode" value="Skloogs"<?php if (get_option('SkMenuMode')=='Skloogs') echo ' checked'; ?> /><?php echo __('Top Level',$SkMSDomain); ?>
	</td>
	</tr>
	 
	<tr valign="top">
	<th scope="row"><?php echo __('Last Loaded Poll',$SkMSDomain); ?></th>
	<td><input type="text" name="SkMSConcurso" value="<?php echo get_option('SkMSConcurso'); ?>" /></td>
	</tr>
	 
	<tr valign="top">
	<th scope="row"><?php echo __('Next Poll Date',$SkMSDomain); ?></th>
	<td><input type="text" name="SkMSNextPoll" value="<?php echo get_option('SkMSNextPoll'); ?>" /></td>
	</tr>

	<tr valign="top">
	<th scope="row"><?php echo __('Hosting Time Offset (hours)',$SkMSDomain); ?></th>
	<td><input type="text" name="SkHostOffset" value="<?php echo get_option('SkHostOffset'); ?>" />
		<?php echo ' ('.__('Hosting Time:',$SkMSDomain).' '.date('H:i',mktime()).' / '.
			__('Corrected Time:',$SkMSDomain).' '.date('H:i',mktime()+3600*get_option('SkHostOffset')).')'; ?></td>
	</tr>

	<tr valign="top">
	<th scope="row"><?php echo __('Max Numbers for Suggestions',$SkMSDomain); ?></th>
	<td><input type="text" name="SkMaxNumbers" value="<?php echo get_option('SkMaxNumbers'); ?>" />( 6 - 15 )</td>
	</tr>

	</table>
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>
	</div>
<?php
	echo SkMegaSena();

}

function SkMS_regoptions() {
	register_setting('skloogs-megasena','SkMSConcurso');
	register_setting('skloogs-megasena','SkMSNextPoll');
	register_setting('skloogs-megasena','SkHostOffset');
	register_setting('skloogs-megasena','SkMaxNumbers');
	register_setting('skloogs-megasena','SkMenuMode');
}
function SkMS_menu() {
	global $SkMSDomain;
	
	$SkMenuMode=get_option('SkMenuMode');
	switch($SkMenuMode) {
		case 'Skloogs':
			if (!function_exists('SkOptions')) {
				function SkOptions() {
				  echo '<div class="wrap">';
				  echo '<p>'.__('This section provides access to all options for the Skloogs plugins '
				  . 'you have installed.',$SkMSDomain).'</p>';
				  echo '</div>';
				}
				function SkOptionsFile() {
					return __FILE__;
				}
				add_menu_page('Skloogs Plugins', 'Skloogs', 8, __FILE__, 'SkOptions');
			}
			$SkMenuMode='Skloogs';
			$SkMenu=SkOptionsFile();
			break;
		case 'Settings':
		default:
			$SkMenuMode='Settings';
			$SkMenu='options-general.php';
			break;
	}
	update_option('SkMenuMode',$SkMenuMode);
	add_submenu_page($SkMenu, 'Skloogs Megasena', 'Megasena', 8, __FILE__, 'SkMSOptions');

	
}

add_action('plugins_loaded', 'SkMS_loader');
add_filter('the_content', 'SkMS_filter');
add_filter('comment_text', 'SkMS_filter');
add_action('wp_head','SkMS_style');
add_action('admin_head','SkMS_style');
if (is_admin()) {
	register_activation_hook(__FILE__,'SkMS_install');
	register_deactivation_hook(__FILE__,'SkMS_uninstall');
	add_action('admin_menu', 'SkMS_menu');
	add_action('admin_init', 'SkMS_regoptions');
}
?>
