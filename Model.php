<?php 

function getCn(){	
	static $cn;
	if(!$cn) $cn= new PDO("mysql:host=localhost;dbname=examsmi", "root", "");
	return $cn;
}

function getAllSalles(){
	return getCn()->query("select* from Salles")->fetchAll();
}

function isPossible($reservation) {
	/**	teste la disponibilité d'une salle $s
		pour une date $d et un créneau $c 
		retourn boolean 
	*/
	$Rq= getCn()->prepare("select count(*) from reservations where idSalle = ? and date = ? and creneau = ? and etat = 'Active'");
	$Rq->execute($reservation);
	return !($Rq->fetchColumn());	
}

function getReservationByUser($user){
	$Rq= getCn()->prepare("select id from reservations  where email = ? ");
	$Rq->execute([$user]);
	return $Rq->fetchColumn();
}
function ismyReservation($id,$mail){

	$Rq=getCn()->prepare("select count(id) as id  from Reservations where id=? and email=?" );
	$Rq->execute([$id,$mail]);
	return $Rq->fetchColumn();
}
function getAllActiveReservations(){

	$Rq=getCn()->prepare("select*  from Reservations where etat =? and date >= ? " );
	$Rq->execute(["Active",Date("Y-m-d H:i:s")]);
	return $Rq->fetchAll();
}


function getReservationByID($id){
	$Rq= getCn()->prepare("select * from Reservations where id = ? ");
	$Rq->execute([$id]);
	return $Rq->fetch();
}
function getSalleById($id){
	$Rq= getCn()->prepare("select * from salles where id = ? ");
	$Rq->execute([$id]);
	return $Rq->fetch();
}
function ajouterUserToken(array $t) {
	// insère un token dans la BD, (user est défint par son email)
	$Rq= getCn()->prepare("insert into userTokens (mail,token, expire,permission) values(?,?,?,?)");
	$Rq->execute($t);	
}
function updateUserToken(array $t) {
	// insère un token dans la BD, (user est défint par son email)
	$Rq= getCn()->prepare("update userTokens set token=?, expire=? where mail=? ");
	$Rq->execute($t);	
}
function getUserByToken($token){
	//retourne l'email correspondant à un token valide
	$Rq= getCn()->prepare("select mail,permission from userTokens where token = ? and expire >= '" . Date("Y-m-d H:i:s")."'");
	$Rq->execute([$token]);
	return $Rq->fetch();
}
function AjouterReservation($R){
	$Reservation=[$R["email"],$R["motif"],$R["idSalle"],$R["date"],$R["creneau"],"Active"];
	$Rq= getCn()->prepare("insert into Reservations (email,motif,idSalle,date,creneau,etat) values(?,?,?,?,?,?)");
	$Rq->execute($Reservation);
	return getCn()->lastInsertID() ; //pour retourner l'id automatique de la resérvation nouvellement insérée
}
function AddSalle($R){
	
	$Rq= getCn()->prepare("insert into salles (nom) values(?)");
	$Rq->execute([$R["nom"]]);
	return getCn()->lastInsertID() ; //pour retourner l'id automatique de la resérvation nouvellement insérée
}
function activateReservation($id){
	$Rq= getCn()->prepare("update Reservations set etat = 'Active' where id = ? ");
	$Rq->execute([$id]);
}
function userExist($email){
	$Rq= getCn()->prepare("select mail from userTokens where mail=?");
	$Rq->execute([$email]);
	return $Rq->fetchColumn();
}
function deleteReservation($id){
	$Rq= getCn()->prepare("delete from Reservations where id = ? ");
	$Rq->execute([$id]);
}

function deleteSale($id){
	$Rq= getCn()->prepare("delete from Salles where id = ? ");
	$Rq->execute([$id]);
}

function updateSalle($R){
	$Rq= getCn()->prepare("update  Salles set nom=? where  id = ? ");
	$Rq->execute([$R["nom"],$R["id"]]);
}