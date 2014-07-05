<?php 
# ------------------ BEGIN LICENSE BLOCK ------------------
#
# Copyright (c) 2013 SebSauvage
# See http://sebsauvage.net/paste/?36dbd6c6be607e0c#M5uR8ixXo5rXBpXx32gOATLraHPffhBJEeqiDl1dMhs
#
# Instructions d'utilisation:
# • Faites un require_once de ce script.
# • à l'endroit où vous testez la validité du mot de passe:
#     • Si ban_canLogin()==false, l'utilisateur est banni. Ne testez même pas le mot de passe: Rejetez l'utilisateur.
#    • Si ban_canLogin()==true, vérifiez le mot de passe.
#          • Si le mot de passe est ok, appelez ban_loginOk(), sinon appelez ban_loginFailed()
# La lib s'occupe de compter le nombre d'échecs et de gérer la durée de bannissement 
# (bannissement/levée de ban).
# Cette lib créé un sous-répertoire "data" qui contient les données de bannissement 
# (ipbans.php) et un log de connexion (log.txt).
#
# Exemple
#        if (!ban_canLogin()) { $pass=false; }
#        if($pass){ ban_loginOk(); echo connect("success",array("username"=>$this->username)); }
#        else{ ban_loginFailed(); echo connect("error","Incorrect Username or Password"); }
# ------------------- END LICENSE BLOCK -------------------

date_default_timezone_set('Europe/Paris');
$GLOBALS['config']['DATADIR'] = 'data'; // Data subdirectory
$GLOBALS['config']['IPBANS_FILENAME'] = $GLOBALS['config']['DATADIR'].'/ipbans.php'; // File storage for failures and bans.
$GLOBALS['config']['BAN_AFTER'] = 3; // Ban IP after this many failures.
$GLOBALS['config']['BAN_DURATION'] = 1800; // Ban duration for IP address after login failures (in seconds) (1800 sec. = 30 minutes)
if (!isset($GLOBALS['IPBANS'])) { $GLOBALS['IPBANS'] = array('FAILURES'=>array(),'BANS'=>array());}

if (!defined('BAN_DOCTYPE')) {define('BAN_DOCTYPE','<!DOCTYPE html><html lang="fr"><head><meta charset="utf8"></head><body><p>');}
if (!defined('MSG_COME_BACK_IN')) {define('MSG_COME_BACK_IN','Revenez nous voir dans');}
if (!defined('MSG_MIN_OR_NOT')) {define('MSG_MIN_OR_NOT','minutes ou pas...</p>');}
if (!defined('MSG_IF_NOT_SPAMMER')) {define('MSG_IF_NOT_SPAMMER','<p>Si vous n\'êtes pas un robot');}
if (!defined('CLICK_HERE')) {define('CLICK_HERE','cliquez ici');}
if (!defined('SECURITY_SALT')) {define('SECURITY_SALT',generateurMot(100));}
if (!defined('BAN_END')) {define('BAN_END','</p></body></html>');}

if (!is_dir($GLOBALS['config']['DATADIR'])) { mkdir($GLOBALS['config']['DATADIR'],0705); chmod($GLOBALS['config']['DATADIR'],0705); }
if (!is_file($GLOBALS['config']['DATADIR'].'/.htaccess')) { file_put_contents($GLOBALS['config']['DATADIR'].'/.htaccess',"Allow from none\nDeny from all\n"); } // Protect data files.

function logm($message)
{
    $t = strval(date('Y/m/d_H:i:s')).' - '.$_SERVER["REMOTE_ADDR"].' - '.strval($message)."\n";
    file_put_contents($GLOBALS['config']['DATADIR'].'/log.txt',$t,FILE_APPEND);
}

function generateurMot($longueur = 8,$nbCaracteres = 4,$caracteresSup = array(),$nombresSup = array(),$voyellesSup = array(),$consonnesSupp = array()) {
    $mot = '';
    $consonnes = array('b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','z');
    $voyelles = array('a','e','i','o','u','y');
    $caracteres = array('@','#','?','!','+','=','-','%','&','*');
    $nombres = array('0','1','2','3','4','5','6','7','8','9');
    $caracteresDejaChoisis = array();
    
    if (!empty($consonnesSupp)) {
        $consonnes = array_diff($this->consonnes,$consonnesSupp);
    }
    if (!empty($voyellesSup)) {
        $voyelles = array_diff($this->voyelles,$voyellesSup);
    }
    if (!empty($caracteresSup)) {
        $caracteres = array_diff($this->caracteres,$caracteresSup);
    }
    if (!empty($nombresSup)) {
        $nombres = array_diff($this->nombres,$nombresSup);
    }
    
    if (empty($consonnes)) {
        $consonnes = array('b');
    }
    if (empty($voyelles)) {
        $voyelles = $this->consonnes;
    }
    if (empty($nombres)) {
        $nombres = $this->consonnes;
    }
    
    if ($nbCaracteres == 0) {
        $caracteres = $this->consonnes;
    }
    $choix = array('0'=>$consonnes,'1'=>$voyelles,'2'=>$caracteres,'3'=>$nombres);
    $j = 0;
    for($i=0;$i<$longueur;$i++) {
        if (count($caracteresDejaChoisis) == $nbCaracteres) {
            $caracteres = $caracteresDejaChoisis;
        }
        //choix aléatoire entre consonnes et voyelles
        $rand = array_rand($choix,1);
        $tab = $choix[$rand];
        //on recherche l'index d'une lettre, au hasard dans le tableau choisi
        $lettre = array_rand($tab,1);
        if (in_array($lettre, $caracteresDejaChoisis)) {
            $lettre = array_rand($consonnes,1);
            $tab = $consonnes;
        }
        //On ajoute le caractère au tableau des caractères déjà choisis
        if ($tab == $caracteres) {
            $caracteresDejaChoisis[] = $lettre;
        }
        //on recherche la dernière lettre du mot généré
        if (strlen($mot) > 0) {
            $derniereLettre = $mot[strlen($mot)-1];
        } else {
            $derniereLettre = '';
        }
        
        //si la lettre choisie est déjà à la fin du mot généré, on relance la boucle
        if ($tab[$lettre] == $derniereLettre || in_array($derniereLettre,$tab)) {
            $i--;
        } else {//sinon on l'ajoute au mot généré
            $maj = mt_rand(0,10);
            if ($maj<2) {
                $mot .= strtoupper($tab[$lettre]);  
            } else {
                $mot .= $tab[$lettre];  
            }
        }
    }
    
    return $mot;
}

// ------------------------------------------------------------------------------------------
// Brute force protection system
// Several consecutive failed logins will ban the IP address for 30 minutes.
if (!is_file($GLOBALS['config']['IPBANS_FILENAME'])) file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export(array('FAILURES'=>array(),'BANS'=>array()),true).";\n?>");
include $GLOBALS['config']['IPBANS_FILENAME'];
// Signal a failed login. Will ban the IP if too many failures:
function ban_loginFailed()
{
    $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
    if (!isset($gb['FAILURES'][$ip])) $gb['FAILURES'][$ip]=0;
    $gb['FAILURES'][$ip]++;
    if ($gb['FAILURES'][$ip]>($GLOBALS['config']['BAN_AFTER']-1))
    {
        $gb['BANS'][$ip]=time()+$GLOBALS['config']['BAN_DURATION'];
        if (!isset($gb['NOTSPAM'][$ip])) {
            $notSpamCode = str_replace('/','_',base64_encode($ip.time().SECURITY_SALT));
            $gb['NOTSPAM'][$ip]=$notSpamCode;
        }
        if (empty($gb['NOTSPAM'][$ip])) {
            $notSpamCode = str_replace('/','_',base64_encode($ip.time().SECURITY_SALT));
            $gb['NOTSPAM'][$ip]=$notSpamCode;
        }
        $notSpamCode = str_replace('/','_',base64_encode($ip.time().SECURITY_SALT));
        logm('IP address banned from login');
        file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$IPBANS=".var_export($gb,true).";\n?>");
        echo BAN_DOCTYPE.MSG_COME_BACK_IN.'&nbsp;'.($GLOBALS['config']['BAN_DURATION']/60).'&nbsp;'.MSG_MIN_OR_NOT;
        echo MSG_IF_NOT_SPAMMER.'<a href=login/'.(isset($gb['NOTSPAM'][$ip])? $gb['NOTSPAM'][$ip]:$notSpamCode).'>&nbsp;'.CLICK_HERE.'</a>'.BAN_END;
        exit();
    }
    $GLOBALS['IPBANS'] = $gb;
    file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
}
# ------------------ BEGIN LICENSE BLOCK ------------------
#
# Copyright (c) 2009 - 2014 Cyril MAGUIRE, (!Pragmagiciels)
# Licensed under the CeCILL v2.1 license.
# See http://www.cecill.info/licences.fr.html
#
# ------------------- END LICENSE BLOCK -------------------

// Signals a successful login. Resets failed login counter.
function ban_loginOk()
{
    $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
    unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
    $GLOBALS['IPBANS'] = $gb;
    file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    logm('Login ok.');
}

// Checks if the user CAN login. If 'true', the user can try to login.
function ban_canLogin($notSpamCode='')
{
    $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
    if (isset($gb['BANS'][$ip]))
    {
        // User is banned. Check if the ban has expired:
        if ($gb['BANS'][$ip]<=time())
        { // Ban expired, user can try to login again.
            logm('Ban lifted.');
            unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
            file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
            return true; // Ban has expired, user can login.
        }
        // User is banned. Check if he clicked on notspam link
        if ($notSpamCode == $gb['NOTSPAM'][$ip]) {
            // Ban expired, user can try to login again.
            logm('Ban lifted.');
            unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);unset($gb['NOTSPAM'][$ip]);
            file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
            return true; // Ban has expired, user can login.
        }
        return false; // User is banned.
    }
    return true; // User is not banned.
}

?>