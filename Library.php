<?php

function accessControl() {
	/**
		si l'utilisateur n'existe pas dans la session,
		on execute l'action authentifier
		pour afficher le formulaire de la figure 1
	*/
	session_start();
	if (!isset($_SESSION["user"])) {authentifier(); exit;}
}

function getRoute() {
	/**
		cette fonction analyse la requete pour savoir quelle
		est l'action voulue.
		elle retourne une action valide et prête à être appelée	
	*/		
	$action = $_GET["action"]  ?? "index";
	if (! is_callable($action)) throw new Exception ("Cette action $action n'est pas prise en charge sur ce serveur");
	return $action;
}

function afficher ($view, array $data =array()) {
		
	if (!file_exists("Views/".$view))
		throw new Exception("Fichier Views/$view introuvable");				
	extract($data);
	ob_start();	require ("Views/".$view);$view = ob_get_clean();	
	ob_start();	require ("Views/templates/template.php");
	echo ob_get_clean();						
}
