<?php
// ── Config DB ──────────────────────────────────────────────────────────────────
$db_host = 'localhost';
$db_name = 'volontariato';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Errore connessione DB: ' . $e->getMessage());
}

// ── Controllo accesso ──────────────────────────────────────────────────────────
// Se non c'è il parametro verified, redirect al login
if (!isset($_GET['access']) || $_GET['access'] !== 'verified') {

    // Se sta arrivando dal form di login, verifica le credenziali
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'LOGIN') {
        $email = trim($_POST['email'] ?? '');
        $psswd = $_POST['psswd'] ?? '';

        if (empty($email) || empty($psswd)) {
            header('Location: admin-login.php?errore=vuoti');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($psswd, $admin['psswd'])) {
            header('Location: admin-login.php?errore=credenziali');
            exit;
        }

        // Credenziali ok → redirect alla dashboard con access=verified
        header('Location: admin.php?access=verified');
        exit;
    }

    // Nessun accesso → torna al login
    header('Location: admin-login.php');
    exit;
}

// ── Da qui in poi: accesso verificato ─────────────────────────────────────────

function pulisci($dato) {
    return htmlspecialchars(trim($dato ?? ''), ENT_QUOTES, 'UTF-8');
}

$azione = $_POST['azione'] ?? $_GET['azione'] ?? null;
$volontario_da_modificare = null;
$messaggio = null;

$AREA_LABEL = [
    'donazione'      => 'Donazione Sangue/Organi',
    'ospedali'       => 'Supporto Ospedali',
    'assistenza'     => 'Assistenza Malattie',
    'primo-soccorso' => 'Primo Soccorso',
    'altro'          => 'Altro',
];

// ── CRUD ──────────────────────────────────────────────────────────────────────
switch ($azione) {

    case 'UPDATE':
        $id            = (int)($_POST['id'] ?? 0);
        $nome          = pulisci($_POST['nome']);
        $cognome       = pulisci($_POST['cognome']);
        $email         = pulisci($_POST['email']);
        $telefono      = pulisci($_POST['telefono']);
        $eta           = (int)($_POST['eta'] ?? 0);
        $area          = pulisci($_POST['area']);
        $disponibilita = pulisci($_POST['disponibilita']);
        $esperienze    = pulisci($_POST['esperienze']);
        $motivazione   = pulisci($_POST['motivazione']);

        $pdo->prepare("UPDATE registrazioni SET nome=:nome, cognome=:cognome, email=:email,
                        telefono=:telefono, eta=:eta, area=:area, disponibilita=:disponibilita,
                        esperienze=:esperienze, motivazione=:motivazione WHERE id=:id")
            ->execute([':nome'=>$nome,':cognome'=>$cognome,':email'=>$email,':telefono'=>$telefono,
                       ':eta'=>$eta,':area'=>$area,':disponibilita'=>$disponibilita,
                       ':esperienze'=>$esperienze,':motivazione'=>$motivazione,':id'=>$id]);

        $messaggio = ['tipo'=>'success', 'testo'=>'Volontario aggiornato con successo!'];
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM registrazioni WHERE id = :id")->execute([':id' => $id]);
            $messaggio = ['tipo'=>'success', 'testo'=>'Volontario eliminato.'];
        }
        break;

    case 'EDIT_VIEW':
        $stmt = $pdo->prepare("SELECT * FROM registrazioni WHERE id = :id");
        $stmt->execute([':id' => (int)($_GET['id'] ?? 0)]);
        $volontario_da_modificare = $stmt->fetch(PDO::FETCH_ASSOC);
        break;

    case 'READ':
        // ricerca per ID
        break;
}

// ── Carica tutti i volontari (o cerca per ID) ─────────────────────────────────
if ($azione === 'READ' && isset($_GET['id_ricerca']) && $_GET['id_ricerca'] !== '') {
    $stmt = $pdo->prepare("SELECT * FROM registrazioni WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id_ricerca']]);
    $risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $risultati = $pdo->query("SELECT * FROM registrazioni ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

$totale = count($pdo->query("SELECT id FROM registrazioni")->fetchAll());
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pannello Admin - Volontariato Sanitario</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-table th {
            background-color: #2c62b5;
            color: white;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 12px 14px;
        }
        .admin-table td { padding: 11px 14px; vertical-align: middle; font-size: 0.88rem; }
        .admin-table tbody tr:hover { background-color: #f0f5ff; }

        .badge-area {
            background: #e8f0fe; color: #2c62b5;
            border: 1px solid #c5d8f8;
            border-radius: 20px; padding: 3px 10px;
            font-size: 0.72rem; font-weight: 600; white-space: nowrap;
        }
        .badge-disp {
            background: #e6f4ea; color: #2e7d32;
            border: 1px solid #b7dfc0;
            border-radius: 20px; padding: 3px 10px;
            font-size: 0.72rem; font-weight: 600;
        }
        .user-initials {
            width: 34px; height: 34px; border-radius: 8px;
            background: linear-gradient(135deg, #3b7ddd, #2c62b5);
            color: white; font-size: 0.75rem; font-weight: 700;
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-box {
            background: white; border: 1px solid #e0e8f5;
            border-radius: 10px; padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-box .ico {
            width: 46px; height: 46px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .stat-box .val { font-size: 1.6rem; font-weight: 800; color: #2c3e50; line-height: 1; }
        .stat-box .lbl { font-size: 0.75rem; color: #7d8590; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .nav-admin-badge {
            background: rgba(255,255,255,0.18); border-radius: 4px;
            padding: 2px 8px; font-size: 0.7rem; color: white;
            text-transform: uppercase; letter-spacing: 1px; margin-left: 8px;
        }
    </style>
</head>
<body>

<!-- ── HEADER ── -->
<header>
    <div class="container-fluid">
        <div class="logo text-center py-3">
            <img src="logo.png" alt="Logo Volontariato Sanitario" class="img-fluid" style="max-height: 80px;">
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #2c62b5;">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <span class="nav-link active">
                            <i class="fas fa-user-shield me-2"></i>Pannello Admin
                            <span class="nav-admin-badge">Admin</span>
                        </span>
                    </li>
                    <li class="nav-item ms-4">
                        <a href="index.html" class="nav-link">
                            <i class="fas fa-home me-1"></i>Sito
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin-login.php" class="nav-link text-warning">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- ── MAIN ── -->
<main class="py-4">
    <div class="container">
        <div class="content">

            <!-- Titolo -->
            <h1 class="mb-1">
                <i class="fas fa-users text-primary me-2"></i>Gestione Volontari
            </h1>
            <p class="text-muted mb-4">Visualizza, modifica ed elimina le registrazioni</p>

            <?php if ($messaggio): ?>
                <div class="alert alert-<?= $messaggio['tipo'] ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $messaggio['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= $messaggio['testo'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stat boxes -->
            <?php
                $dispCounts = array_count_values(array_column(
                    $pdo->query("SELECT disponibilita FROM registrazioni")->fetchAll(PDO::FETCH_ASSOC),
                    'disponibilita'
                ));
                $areeCounts = array_count_values(array_column(
                    $pdo->query("SELECT area FROM registrazioni")->fetchAll(PDO::FETCH_ASSOC),
                    'area'
                ));
                $topArea = $areeCounts ? array_keys($areeCounts, max($areeCounts))[0] : '—';
            ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="ico" style="background:#e8f0fe"><i class="fas fa-users" style="color:#2c62b5"></i></div>
                        <div>
                            <div class="val"><?= $totale ?></div>
                            <div class="lbl">Totale iscritti</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="ico" style="background:#fff3e0"><i class="fas fa-heart" style="color:#e65100"></i></div>
                        <div>
                            <div class="val" style="font-size:0.9rem;padding-top:4px"><?= htmlspecialchars($AREA_LABEL[$topArea] ?? $topArea) ?></div>
                            <div class="lbl">Area più scelta</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="ico" style="background:#e6f4ea"><i class="fas fa-clock" style="color:#2e7d32"></i></div>
                        <div>
                            <div class="val"><?= $dispCounts['8+'] ?? 0 ?></div>
                            <div class="lbl">Disponibili 8+ h</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="ico" style="background:#fce4ec"><i class="fas fa-ambulance" style="color:#c62828"></i></div>
                        <div>
                            <div class="val"><?= $areeCounts['primo-soccorso'] ?? 0 ?></div>
                            <div class="lbl">Primo Soccorso</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form modifica (appare solo se si clicca Modifica) -->
            <?php if ($volontario_da_modificare): ?>
            <div class="card border-primary shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Modifica Volontario — ID <?= $volontario_da_modificare['id'] ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin.php?access=verified">
                        <input type="hidden" name="azione" value="UPDATE">
                        <input type="hidden" name="id" value="<?= $volontario_da_modificare['id'] ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nome</label>
                                <input type="text" name="nome" class="form-control"
                                       value="<?= htmlspecialchars($volontario_da_modificare['nome'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cognome</label>
                                <input type="text" name="cognome" class="form-control"
                                       value="<?= htmlspecialchars($volontario_da_modificare['cognome'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($volontario_da_modificare['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Telefono</label>
                                <input type="text" name="telefono" class="form-control"
                                       value="<?= htmlspecialchars($volontario_da_modificare['telefono'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Età</label>
                                <input type="number" name="eta" class="form-control" min="18" max="99"
                                       value="<?= htmlspecialchars($volontario_da_modificare['eta'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Area di interesse</label>
                                <select name="area" class="form-select">
                                    <?php foreach ($AREA_LABEL as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= ($volontario_da_modificare['area'] ?? '') === $val ? 'selected' : '' ?>>
                                            <?= $lbl ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Disponibilità settimanale</label>
                                <select name="disponibilita" class="form-select">
                                    <?php foreach (['2-4'=>'2-4 ore','4-8'=>'4-8 ore','8+'=>'Più di 8 ore'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= ($volontario_da_modificare['disponibilita'] ?? '') === $val ? 'selected' : '' ?>>
                                            <?= $lbl ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Esperienze precedenti</label>
                                <textarea name="esperienze" class="form-control" rows="2"><?= htmlspecialchars($volontario_da_modificare['esperienze'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Motivazione</label>
                                <textarea name="motivazione" class="form-control" rows="2"><?= htmlspecialchars($volontario_da_modificare['motivazione'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Salva modifiche
                            </button>
                            <a href="admin.php?access=verified" class="btn btn-outline-secondary">Annulla</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Barra cerca + tabella -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h5 class="mb-0 text-primary"><i class="fas fa-table me-2"></i>Elenco Volontari</h5>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="access" value="verified">
                    <input type="hidden" name="azione" value="READ">
                    <input type="number" name="id_ricerca" class="form-control form-control-sm"
                           placeholder="Cerca per ID..." style="width:160px"
                           value="<?= htmlspecialchars($_GET['id_ricerca'] ?? '') ?>">
                    <button type="submit" class="btn btn-dark btn-sm">
                        <i class="fas fa-search me-1"></i>Cerca
                    </button>
                    <a href="admin.php?access=verified" class="btn btn-outline-secondary btn-sm">Reset</a>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Volontario</th>
                            <th>Telefono</th>
                            <th>Età</th>
                            <th>Area</th>
                            <th>Disponibilità</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($risultati)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nessun volontario trovato</td></tr>
                        <?php else: ?>
                            <?php foreach ($risultati as $r): ?>
                            <tr>
                                <td class="text-muted" style="font-size:0.8rem"><?= $r['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-initials">
                                            <?= strtoupper(mb_substr($r['nome'],0,1) . mb_substr($r['cognome'],0,1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold" style="font-size:0.88rem"><?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?></div>
                                            <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($r['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($r['telefono']) ?></td>
                                <td><?= htmlspecialchars($r['eta']) ?> anni</td>
                                <td><span class="badge-area"><?= htmlspecialchars($AREA_LABEL[$r['area']] ?? $r['area']) ?></span></td>
                                <td><span class="badge-disp"><?= htmlspecialchars($r['disponibilita']) ?> h/sett</span></td>
                                <td class="text-nowrap">
                                    <a href="admin.php?access=verified&azione=EDIT_VIEW&id=<?= $r['id'] ?>"
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-pen me-1"></i>Modifica
                                    </a>
                                    <a href="admin.php?access=verified&azione=DELETE&id=<?= $r['id'] ?>"
                                       class="btn btn-sm btn-danger ms-1"
                                       onclick="return confirm('Vuoi eliminare <?= htmlspecialchars($r['nome'] . ' ' . $r['cognome'], ENT_QUOTES) ?>?')">
                                        <i class="fas fa-trash me-1"></i>Elimina
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- /content -->
    </div>
</main>

<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="contact-info text-center">
            <h3 class="mb-3">Contatti</h3>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-2"><p><i class="fas fa-envelope me-2"></i>Email: info@volontariatosanitario.it</p></div>
                <div class="col-md-4 mb-2"><p><i class="fas fa-phone me-2"></i>Telefono: 06 1234567</p></div>
                <div class="col-md-4 mb-2"><p><i class="fas fa-map-marker-alt me-2"></i>Via della Solidarietà, 123 - Roma</p></div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
