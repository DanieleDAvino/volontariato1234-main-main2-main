<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Volontariato Sanitario</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="container-fluid">
        <div class="logo text-center py-3">
            <img src="logo.png" alt="Logo Volontariato Sanitario" class="img-fluid" style="max-height: 80px;">
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #2c62b5;">
        <div class="container">
            <div class="collapse navbar-collapse justify-content-center">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="index.html" class="nav-link">
                            <i class="fas fa-arrow-left me-2"></i>Torna al sito
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="content">
                    <h1 class="text-center mb-4">
                        <i class="fas fa-user-shield text-primary me-2"></i>Area Admin
                    </h1>

                    <?php if (isset($_GET['errore'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php
                                if ($_GET['errore'] === 'credenziali') echo 'Credenziali non valide.';
                                if ($_GET['errore'] === 'vuoti')       echo 'Inserisci email e password.';
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="admin.php">
                        <input type="hidden" name="azione" value="LOGIN">

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope text-primary me-2"></i>Email
                            </label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg"
                                   placeholder="admin@esempio.com" required>
                        </div>

                        <div class="mb-4">
                            <label for="psswd" class="form-label">
                                <i class="fas fa-lock text-primary me-2"></i>Password
                            </label>
                            <input type="password" name="psswd" id="psswd" class="form-control form-control-lg"
                                   placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Accedi
                        </button>
                    </form>
                </div>
            </div>
        </div>
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
