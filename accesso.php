<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accesso non autorizzato');
}

header('Content-Type: application/json');

function pulisci($dato) {//plisce il dato, leva gli spazi all'inizio e alla fine + specialchars
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

$email = pulisci($_POST['email'] ?? '');
$psswd = $_POST['psswd'] ?? '';

if(empty($email) || empty($psswd)) {
    echo json_encode(['successo' => false, 'messaggio' => 'Email e password sono obbligatori']);
    exit;
}

//credenziali del db
$db_host = 'localhost';
$db_name = 'volontariato';
$db_user = 'root';
$db_pass = '';

//connessione al db
try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //controlla prima se è admin
    $stmtAdmin = $pdo->prepare("SELECT * FROM admin WHERE email = :email");
    $stmtAdmin->execute([':email' => $email]);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($psswd, $admin['psswd'])) {
        echo json_encode([
            'successo' => true,
            'ruolo'    => 'admin',
            'messaggio'=> 'Accesso admin effettuato!'
        ]);
        exit;
    }

    //cerca l'utente tramite email
    $stmt = $pdo->prepare("SELECT * FROM registrazioni WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$utente) {
        echo json_encode(['successo' => false, 'messaggio' => 'Email non trovata. <a href="registrazione.html">Registrati qui</a>']);
        exit;
    }

    //confronto password
    if(!password_verify($psswd, $utente['psswd'])) {
        echo json_encode(['successo' => false, 'messaggio' => 'Password errata']);
        exit;
    }

    echo json_encode([//tutto ok per il login
        'successo' => true,
        'ruolo'    => 'utente',
        'messaggio' => 'Accesso effettuato!',
        'nome' => $utente['nome']
    ]);

} catch(PDOException $e) {//errore di connessione al db
    echo json_encode(['successo' => false, 'messaggio' => 'Errore del server', 'debug' => $e->getMessage()]);
}
?>
