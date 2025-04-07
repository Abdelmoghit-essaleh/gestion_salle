<?php
require_once("Model.php");
require_once("admins.php");
function adminAccess(){
	
	if($_SESSION["user"]["permission"]!="Admin"){
		header("location:index.php");
	}
}
function authentifier(){
	/**	Cette action test si l'utilisateur envoie un token
		1-Si oui, on vérifie sa validité
		et selon la validité du token on accepte
		ou on rejette l'utilisateur.
	*/
	if (isset($_REQUEST["token"])) {
		$token = $_REQUEST["token"];	
		$user =getUserByToken($token);	 
		if(!$user) throw new Exception ("Token non valide ou expiree!!..");	
		$_SESSION["user"]=$user;;
          if(in_array($_SESSION["user"]["mail"],$GLOBALS["admins"])){
			$_SESSION["user"]["permission"]="Admin";
		  }
		 
		header("location:index.php");
	}
	/** 2- Si aucun token n'est envoyé, on affiche le formulaire
		3- On le valide,
		4- et on génère un token puis l'envoyer à l'utilisateur
	*/
	if ($_SERVER["REQUEST_METHOD"]=="POST") {	
		$email = $_POST["email"];			
		if(empty($email))    $erreur["email"] ="L'e-mail ne peut être vide !..."   ;
		elseif(substr(strtolower($email),-12,12)!="@usmba.ac.ma")    $erreur["email"] ="Utilisez votre mail académique!!..."   ;
		if (!isset($erreur)) {									
			generateUserToken($email);
			header ("location: index.php");	
		}
	}

		$data =["email" => $email  ?? "" ,
				"erreur"=> $erreur ?? ""
			   ];
		afficher ("vFormLogin.php",$data);
}


 function isValid($date, $format = 'Y-m-d'){
    $d =date("Y-m-d",strtotime($date));
    return $d==$date;
}

function index(){
	/**
		Cette action permet de gérer le formulaire
		de la figure 2
	*/
	$Reservation = ["idSalle"=>"","date"=>"","creneau"=>""];
	if ($_SERVER["REQUEST_METHOD"]=="POST") {	
	$Reservation = $_POST;
	//valider les champs du formulaire		
	if(empty($Reservation["date"]) or !isValid($Reservation["date"]) or $Reservation["date"] < Date("Y-m-d H:i:s"))  $erreur["date"] ="Date de réservation invalide !..."   ;
	elseif(empty($Reservation["creneau"]))    $erreur["creneau"] ="Choisissez un creneau !..."   ;
	elseif(empty($Reservation["idSalle"]))  $erreur["salle"] ="Choisissez une salle !..."   ;
							
	if (!isset($erreur)) {		
		$s = $Reservation["idSalle"];
		$d = $Reservation["date"];
		$c = $Reservation["creneau"];	
		print_r([$s,$d,$c]);	
		if(isPossible([$s,$d,$c]))  afficher("vSalleDisponible.php",["salle"=>$s,"date"=>$d,"creneau"=>$c]);
		else                     afficher("vSalleNonDisponible.php",["salle"=>$s,"date"=>$d,"creneau"=>$c]);
	exit;
	}
}
$data =["reservation" => $Reservation,
		"erreur"      => $erreur ?? "" ,
		"salles"      => getAllSalles()
   	];
afficher("vIndex.php", $data);
}


function GenerateUserToken ($email) {
	/**
		Cette fonction utilitaire n'est pas en fait une action.
		Elle reçoit l'email d'un utilisateur et génère
		un token correspondant. Et il l'envoie à l'utilisateur
	*/
	date_default_timezone_set('Africa/Casablanca');
	$timeExpiration =  date("Y-m-d H:i:s", strtotime('+4 hours')) ; // Expire dans  4h à partir de maintenant
	$token = sha1 ($email. $timeExpiration . rand(0,999999999)) ; //une chaine aléatoire
	//insérer le token dans la BD
	if(userExist($email))
	updateUserToken([$token,$timeExpiration,$email]); 
	else
	ajouterUserToken([$email,$token,$timeExpiration,"etudiant"]); 
	
	//Envoyer un email
	//$lien = "http://www.fsdm.usmba.ac.ma/ReservationSalles/index.php?action=authentifier&token=$token";
	$lien = "index.php?action=authentifier&token=$token";
	$to= $email;
	$subject = "Lien pour vous connecter" ;
	$message =" Veuillez cliquer sur le lien suivant pour vous connecter à l'application de réservations. Notez bien que ce lien va expirer le <b> : $timeExpiration </b>. <br> <a href ='$lien'>$lien</a>.<br /> Vous pouvez aussi copier/coller ce token: <b>$token</b> dans l'interface d'authentification de l'application";  
	
	
	//mail($to,$subject,$message);
	
	//affichage alternatif; juste pour tester sans utiliser l'email
	require ("Views/vEmailTest.php"); exit;
		
		
}
function listeReservations(){		
	afficher("vListeReservations.php", ["Reservations"=>getAllActiveReservations($_SESSION["user"]["mail"])]);	
}
function listeSalles(){		
	adminAccess();
	afficher("vListeSalles.php", ["Salles"=>getAllSalles()]);	
}

function reserver(){
	$Reservation = ["email"=>"","motif"=>"","idSalle"=>"","date"=>"","creneau"=>""];
if ($_SERVER["REQUEST_METHOD"]=="POST") {
	
	$Reservation = $_POST;

	//valider les champs du formulaire		
	if(empty($Reservation["motif"]))    $erreur["motif"] ="Entrez le motif de la réservation !..."   ;
	if(empty($Reservation["date"]) or $Reservation["date"] < Date("Y-m-d H:i:s"))  $erreur["date"] ="Date de réservation invalide !..."   ;
	elseif(empty($Reservation["creneau"]))    $erreur["creneau"] ="Choisissez un creneau !..."   ;
	elseif(empty($Reservation["idSalle"]))  $erreur["salle"] ="Choisissez une salle !..."   ;
	else {
		$s = $Reservation["idSalle"];
		$d = $Reservation["date"];
		$c = $Reservation["creneau"];			
		if(!isPossible([$s,$d,$c]))  $erreur["salle"] ="Cette Salle est déjà réservée pour la date et le créneau choisi !..."   ;
	}
					
	if (!isset($erreur)) {		
		//insérer la réservation dans la BD et récupérer son ID automatique
		//puis générer un token et envoyer le au createur pour pouvoir activer la réservation
	
		$Reservation["email"]=$_SESSION["user"]["mail"];
		
		$idResrvation = ajouterReservation ($Reservation);								
		
	      header ("location: index.php?action=listeReservations");
	}
}

/*si on arrive ici, ça veut dire que la méthode n'est pas "post", donc c'est le premier affichage du formulaire, oubien, les données envoyées par post ne sont pas valide (donc le tableau $erreur est rempli (isset($erreur)==true)*/
//dans ce cas, on affiche, ou réaffiche le formulaire

$data =[ "salles" => getAllSalles(),
		 "erreur" => $erreur ?? [] ,
		 "reservation" => $Reservation
		];
afficher("vFormReservation.php",$data);
}
function owenReservation($id){
	if($_SESSION["user"]["permission"]=="Admin")
	return;

	
	if(!ismyReservation($id,$_SESSION["user"]["mail"] )){
		throw new Exception ("vous n'avez pas le droit de supprimer ou editer ");
	}
	
}
function supprimerReservation() {	
	$id =$_GET["id"] ?? "";
	if(empty($id)) throw new Exception ("Il faut fournir l'id de la reservation à supprimer");	
	if(!getReservationById($id)) throw new Exception ("Aucune réservation active à supprimer!!..");
	owenReservation($id);
	$Reservation = deleteReservation($id);			

	header("location:index.php?action=listeReservations");
}
function supprimerSalle(){
	$id =$_GET["id"] ?? "";
	if(empty($id)) throw new Exception ("Il faut fournir l'id de la sale à supprimer");	
	if(!getSalleById($id)) throw new Exception ("Aucune sale active à supprimer!!..");;		
	$Reservation = deleteSale($id);			

	header("location:index.php?action=listeSalles");
}

function ajouterSalle(){
	adminAccess();
        $salle=["nom"=>""];
	if($_SERVER["REQUEST_METHOD"]=="POST"){
		$salle["nom"]=$_POST["nom"];
	        
		if(empty($salle["nom"])) $errors["nom"]="Entrez le nom de la réservation !...";
        
		if(!$errors){
			$idsalle = Addsalle ($salle);	
			header("location:index.php?action=listeSalles");
		}


	}
	$data =[ 
		 "erreur" => $errors ?? [] ,
		 "salle" => $salle
		];
	
	afficher("vFormSalle.php",$data);
}
function editerSalle(){
	adminAccess();
        $salle=["nom"=>"","id"=>""];
	if($_SERVER["REQUEST_METHOD"]=="POST"){
		$salle["nom"]=$_POST["nom"];
		$salle["id"]=$_POST["id"];
		if(empty($salle["nom"])) $errors["nom"]="Entrez le nom de la réservation !...";
        
		if(!$errors){
			$idsalle = updateSalle ($salle);	
			header("location:index.php?action=listeSalles");
		}


	}
	else {
		
		$salle["id"]=$_GET["id"];
		$salle=getSalleById($salle["id"]);
	}
	$data =[ 
		 "erreur" => $errors ?? [] ,
		 "salle" => $salle
		];
	
	afficher("vFormSalle.php",$data);
}


	
function deconnexion() {
	session_destroy();
	header("location: index.php");
}