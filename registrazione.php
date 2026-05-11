<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accesso non autorizzato');
}

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

function pulisci($dato) {//stessa funzione
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

$nome          = pulisci($_POST['nome']          ?? '');
$cognome       = pulisci($_POST['cognome']       ?? '');
$email         = pulisci($_POST['email']         ?? '');
$psswd         = password_hash($_POST['psswd'] ?? '', PASSWORD_BCRYPT);//la psswd deve serre hash
$telefono      = pulisci($_POST['telefono']      ?? '');
$eta           = pulisci($_POST['eta']           ?? '');
$area          = pulisci($_POST['area']          ?? '');
$disponibilita = pulisci($_POST['disponibilita'] ?? '');
$esperienze    = pulisci($_POST['esperienze']    ?? '');
$motivazione   = pulisci($_POST['motivazione']   ?? '');

if (empty($nome) || empty($email) || empty($telefono)) {//questi campi sono obbligatori
    echo json_encode(['successo' => false, 'messaggio' => 'Nome, email e telefono sono obbligatori']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {//cntrollo formato email
    echo json_encode(['successo' => false, 'messaggio' => 'Email non valida']);
    exit;
}

$areaDescrizione = match($area) {//se sceglie una cosa del menu lo associa a questo
    'donazione'      => 'Donazione di Sangue e Organi',
    'ospedali'       => 'Supporto negli Ospedali',
    'assistenza'     => 'Assistenza a Persone con Malattie',
    'primo-soccorso' => 'Primo Soccorso e Emergenze',
    'altro'          => 'Altro',
    default          => $area
};

//db
$db_host = 'localhost';
$db_name = 'volontariato';
$db_user = 'root';       
$db_pass = '';           

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //emaoil duplicata, per vedere se è registrato o deve fare l'accesso
    $checkStmt = $pdo->prepare("SELECT id FROM registrazioni WHERE email = :email");
    $checkStmt->execute([':email' => $email]);
    if($checkStmt->rowCount() > 0) {
    echo json_encode([
        'successo' => false,
        'messaggio' => 'Email già registrata! <a href="accesso.html">Clicca qui per fare il login</a>'
    ]);
    exit;
}
   //query per aggiungere l'utente al db
   $sql = "INSERT INTO registrazioni (nome, cognome, email, psswd, telefono, eta, area, disponibilita, esperienze, motivazione)
            VALUES (:nome, :cognome, :email, :psswd, :telefono, :eta, :area, :disponibilita, :esperienze, :motivazione)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome'          => $nome,
        ':cognome'       => $cognome,
        ':email'         => $email,
        ':psswd'         => $psswd,
        ':telefono'      => $telefono,
        ':eta'           => $eta,
        ':area'          => $area,
        ':disponibilita' => $disponibilita,
        ':esperienze'    => $esperienze,
        ':motivazione'   => $motivazione
    ]);

} catch (PDOException $e) {//errore durante l'inerimento di un utente al db
    echo json_encode([
        'successo' => false,
        'messaggio' => 'Errore nel salvataggio dei dati.',
        'debug' => $e->getMessage()
    ]);
    exit;
}

//emial
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'dany200764@gmail.com';
    $mail->Password   = 'rkyx zbtj oiih miap'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('dany200764@gmail.com', 'Volontariato Sanitario');
    $mail->addAddress($email, $nome);
    $mail->Subject = 'Registrazione Volontario - Conferma Ricevuta';
    $mail->isHTML(true);

    //aspetto dell'email
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 30px; background: #f9f9f9; border: 1px solid #e0e0e0; border-top: none; }
        .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea; border-radius: 4px; }
        .info-row { padding: 8px 0; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: #667eea; display: inline-block; width: 180px; }
        .footer { text-align: center; padding: 20px; font-size: 0.85em; color: #666; background: #f0f0f0; border-radius: 0 0 10px 10px; border: 1px solid #e0e0e0; border-top: none; }
        ul { line-height: 2; }
        .tip { background: #e3f2fd; padding: 15px; border-radius: 5px; border-left: 4px solid #2196f3; }
    </style>
    </head>
    <body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin:0'>🎉 Benvenuto/a, $nome!</h1>
            <p style='margin:10px 0 0'>Grazie per la tua registrazione</p>
        </div>
        <div class='content'>
            <p>Ciao <strong>$nome</strong>,</p>
            <p>Abbiamo ricevuto la tua richiesta di diventare volontario sanitario. Il tuo contributo fa la differenza!</p>
            <div class='info-box'>
                <h3 style='color:#667eea;margin-top:0'>📋 Riepilogo dati</h3>
                <div class='info-row'><span class='label'>👤 Nome:</span> $nome</div>
                <div class='info-row'><span class='label'>📧 Email:</span> $email</div>
                <div class='info-row'><span class='label'>📱 Telefono:</span> $telefono</div>
                <div class='info-row'><span class='label'>🎂 Età:</span> $eta anni</div>
                <div class='info-row'><span class='label'>❤️ Area:</span> $areaDescrizione</div>
                <div class='info-row'><span class='label'>⏰ Disponibilità:</span> $disponibilita ore/sett.</div>
            </div>
            <h3 style='color:#667eea'>🎯 Prossimi passi</h3>
            <ul>
                <li>✅ Il nostro team esaminerà la tua candidatura</li>
                <li>📞 Ti contatteremo entro <strong>48 ore</strong></li>
                <li>📚 Riceverai info sui corsi di formazione</li>
            </ul>
            <div class='tip'><strong>💡 Suggerimento:</strong> Controlla anche la cartella spam per non perdere le nostre comunicazioni.</div>
        </div>
        <div class='footer'>
            <p><strong>Volontariato Sanitario</strong></p>
            <p>© " . date('Y') . " – Email automatica di conferma</p>
        </div>
    </div>
    </body>
    </html>";

    //se a chi arriva l'email non supporta html
    $mail->AltBody = "Ciao $nome,\n\nRegistrazione ricevuta!\n\nNome: $nome\nEmail: $email\nTelefono: $telefono\nEtà: $eta\nArea: $areaDescrizione\nDisponibilità: $disponibilita\n\nTi contatteremo entro 48 ore.\n\nVolontariato Sanitario";

    $mail->send();

    //registrazione tutto ok
    echo json_encode(['successo' => true, 'messaggio' => 'Registrazione completata! Controlla la tua email per i dettagli.']);

} catch (Exception $e) {//no email ma ok il salvataggio nel db
    $errore = $mail->ErrorInfo;
    error_log("PHPMailer error: " . $errore);
    echo json_encode([
        'successo' => true,
        'messaggio' => 'Registrazione salvata! (Invio email non riuscito, ti contatteremo noi)',
        'debug' => $errore
    ]);
}
?>
