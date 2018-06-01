<?php
/* / */
header('Content-type: application/json');
set_time_limit(600);
include "simple_html_dom.php";
$date_de_debut_de_recolte=20180601;
$html = contents_get_html(file_get_contents("https://www.louvre.fr/data/manifestation2"));
$evenements_audito=array();
function strip_single_tag($str,$tag){
    $str1=preg_replace('/<\/'.$tag.'>/i', '', $str);
    if($str1 != $str){
        $str=preg_replace('/<'.$tag.'[^>]*>/i', '', $str1);
    }
    return $str;
}
foreach($html->find('tr ') as $tr){
	$props=array(
		"id"=>'',
		"titre"=>'',
		"URL_titre"=>'',
		"teaser"=>'',
		"img"=>'',
		"seances"=>'',
		"tarif"=>'',
		"seances_lieu"=>'',
		"categories"=>'',
		"poste"=>'',
		"maj"=>'',
		"auditorium"=>false,
		"description"=>''
	);
	$cpt=0;
	foreach($tr->find('td ') as $td){
		// Récolte des métadonnées de l'événement
		switch ($cpt){
			case 0:{ // Nid
				$props["id"]=trim(strip_tags($td));
			}break;
			case 1:{ // Titre
				foreach($td->find("a") as $link){
					$props["titre"]=strip_tags($link);
					$props["URL_titre"]="https://www.louvre.fr".$link->href;
				}
			}break;
			case 2:{ // Teaser
				$props["teaser"]=trim(strip_single_tag($td,"td"));
			}break;
			case 3:{ // Vignette
				foreach($td->find("img") as $link){
					$props["img"]=$link->src;
				}
			}break;
			case 6:{ // Séances (date et lieu)
				foreach($td->find("h2") as $h2){
					if ($props["seances"]!="")
						$props["seances"].="|";
					$h2=strip_tags($h2);
					if (strlen($h2))
						$props["seances"].=substr($h2,strlen($h2)-28,18);
				}
				foreach($td->find('div[class=field-field-lieu] div[class=odd]') as $lieu){
					$props["seances_lieu"]=trim(strip_tags($lieu));
					if ($props["seances_lieu"]=="Auditorium du Louvre")
						$props["auditorium"]=true;
				}
			}break;
			case 7:{ // All term (Catégories)
				foreach($td->find("a") as $cat){
					if ($props["categories"]!="")
						$props["categories"].="|";
					$props["categories"].=strip_tags($cat);
				}
			}break;
			case 8:{ // Posté le
				$props["poste"]=trim(strip_tags($td));
			}break;
			case 9:{ // Date de mise à jour
				$props["maj"]=trim(strip_tags($td));
			}break;
		}
		$cpt++;
	}
	// Récolte du contenu de l'événement et métadonnées complémentaires
	if ($props["URL_titre"]!=""){
		$html_manif = contents_get_html(file_get_contents($props["URL_titre"]));
		foreach($html_manif->find('div[id=wysiwyg]') as $wysiwyg){
			$props["description"]=htmlspecialchars_decode($wysiwyg);
		}
		foreach($html_manif->find('div[class=box-informations]') as $informations){
			$tarif_debut=strpos($informations,"<p>Tarif");
			$tarif_fin=strpos($informations,"<p>R&eacute;servation");
			$props["tarif"]=trim(strip_tags(substr($informations,$tarif_debut,$tarif_fin-$tarif_debut)));
		}
	}
	if ($props["auditorium"]){
		$s=explode("|",$props["seances"]);
		$date_ok=false;
		for ($i=0;$i<count($s);$i++)
			if ($date_de_debut_de_recolte<=substr($s[$i],6,4).substr($s[$i],3,2).substr($s[$i],0,2))
				$date_ok=true;
		if ($date_ok)
			$evenements_audito[]=$props;
	}
}
echo  json_encode($evenements_audito);
// CodePlayList - Vamos tremer https://open.spotify.com/user/sponagon/playlist/6rhFE5KzY5Hc7AN8rdtvtS?si=zs7Y6tVDTgij_dZFzXvDmg
?>