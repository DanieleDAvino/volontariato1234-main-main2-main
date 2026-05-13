document.addEventListener("DOMContentLoaded", function() {

    const contentContainer = document.getElementById('content-container');
    if(contentContainer) {
        loadContent('introduzione.html');

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                document.querySelectorAll('.nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                });

                this.classList.add('active');
                const href = this.getAttribute('href');
                loadContent(href);
            });
        });
    }

       if (document.getElementById('login-form')) {
        initLoginForm();
    }

    if (document.getElementById('registration-form')) {
        initRegistrationForm();
    }


    function loadContent(url) {
        const container = document.getElementById('content-container');
        if(!container) return;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                container.innerHTML = data;

                //se deve fare la registrazione chiama la funzione
                if(url === 'registrazione.html') {
                    initRegistrationForm();
                }

               //vede se deve fare l'accesso chiama l'altra funzione
                if(url === 'accesso.html') {
                    initLoginForm();  
                }

                //image-card
                document.querySelectorAll('.image-card').forEach(card => {
                    card.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = this.getAttribute('href');

                        document.querySelectorAll('.nav-link').forEach(navLink => {
                            navLink.classList.remove('active');
                            if(navLink.getAttribute('href') === target) {
                                navLink.classList.add('active');
                            }
                        });

                        loadContent(target);
                    });
                });

               
                document.querySelectorAll('.btn[href]').forEach(button => {
                    if(button.type === 'submit' || button.closest('form')) return;
                    const target = button.getAttribute('href');
                    if(!target || !target.endsWith('.html')) return;

                    button.addEventListener('click', function(e) {
                        e.preventDefault();

                        document.querySelectorAll('.nav-link').forEach(navLink => {
                            navLink.classList.remove('active');
                            if(navLink.getAttribute('href') === target) {
                                navLink.classList.add('active');
                            }
                        });

                        loadContent(target);
                    });
                });
            })
            .catch(error => {
                console.error('Errore nel caricamento del contenuto:', error);
            });
    }

    function initRegistrationForm() {//funzione per la regitrazione
        const form = document.getElementById('registration-form');
        if(!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            //prende tutti i campi
            const nome          = document.getElementById('nome').value;
            const cognome       = document.getElementById('cognome').value;
            const email         = document.getElementById('email').value;
            const psswd         = document.getElementById('psswd').value;
            const telefono      = document.getElementById('telefono').value;
            const eta           = document.getElementById('eta').value;
            const area          = document.getElementById('area').value;
            const disponibilita = document.getElementById('disponibilita').value;
            const esperienze    = document.getElementById('esperienze').value;
            const motivazione   = document.getElementById('motivazione').value;

            // VALIDAZIONE
            let allertString = "";

            //const patternName = /^[A-Z]{1}[A-z]+/;
            const patternName = /^([A-Z]){1}([A-z]||\W)+/;
            if(!patternName.test(nome)){
                allertString += "- Nome non valido, deve iniziare con una lettera maiuscola\n";
            }

            const patternCognome = /^([A-Z]){1}([A-z]||\W)+/;
            if(!patternCognome.test(cognome)){
                allertString += "- Cognome non valido, deve iniziare con una lettera maiuscola\n";
            }

            const patternEmail = /^\w+\.?\w+?\@{1}[A-z]{1,}\.{1}[A-z]{2,}$/;
            if(!patternEmail.test(email)){
                allertString += "- Email non valida, formato richiesto: esempio@email.com\n";
            }

            const patternPassword = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]).{8,}$/;
            if(!patternPassword.test(psswd)){
                allertString += "- Password non valida: minimo 8 caratteri, almeno una maiuscola, una minuscola, un numero e un carattere speciale\n";
            }

            //const patternTelefono = /(\+39\s)?3\d{2}\-?\d{7}$/;
            const patternTelefono = /^(\+39\s?)?3\d{2}[\s\-]?\d{3}[\s\-]?\d{4}$/;
            if(!patternTelefono.test(telefono)){
                allertString += "- Telefono non valido, formato: +39 123 456 7890\n";
            }

            const patternEta = /\d{2,3}/;
            if(!patternEta.test(eta) || eta < 18){
                allertString += "- Età non valida, devi essere maggiorenne\n";
            }

            if(allertString !== ""){
                alert(allertString);
                return;
            }

            const btnSubmit = document.getElementById('btn-submit');
            const messaggioRisposta = document.getElementById('messaggio-risposta');

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Invio in corso...';
            messaggioRisposta.style.display = 'none';

            //dati da inviare al php
            const datiForm = new FormData();
            datiForm.append('nome', nome);
            datiForm.append('cognome', cognome);
            datiForm.append('email', email);
            datiForm.append('psswd', psswd);
            datiForm.append('telefono', telefono);
            datiForm.append('eta', eta);
            datiForm.append('area', area);
            datiForm.append('disponibilita', disponibilita);
            datiForm.append('esperienze', esperienze);
            datiForm.append('motivazione', motivazione);

            try {//ivia i dati al php
                const risposta = await fetch('registrazione.php', {
                    method: 'POST',
                    body: datiForm
                });

                const risultato = await risposta.json();

                messaggioRisposta.style.display = 'block';

                if(risultato.successo) {//messaggio di successo
                    messaggioRisposta.className = 'alert alert-success mt-3';
                    messaggioRisposta.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + risultato.messaggio;
                    form.reset();

                    //download file json
                    const datiJson = { nome, cognome, email, telefono, eta, area, disponibilita, esperienze, motivazione };
                    const blob = new Blob([JSON.stringify(datiJson, null, 2)], { type: 'application/json' });
                    const url  = URL.createObjectURL(blob);
                    const a    = document.createElement('a');
                    a.href     = url;
                    a.download = `iscrizione_${nome}_${cognome}.json`;
                    a.click();
                    URL.revokeObjectURL(url);
                } else {
                    messaggioRisposta.className = 'alert alert-danger mt-3';
                    let msg = '<i class="fas fa-exclamation-circle me-2"></i>' + risultato.messaggio;
                    if(risultato.debug) {
                        msg += '<br><small>Debug: ' + risultato.debug + '</small>';
                    }
                    messaggioRisposta.innerHTML = msg;
                }

            } catch (errore) {//errore di connesisone al server
                messaggioRisposta.style.display = 'block';
                messaggioRisposta.className = 'alert alert-danger mt-3';
                messaggioRisposta.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Errore di connessione. Riprova più tardi.';
                console.error('Errore:', errore);
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Invia Richiesta';
            }
        });
    }

    function initLoginForm() {//funzione per l'accesso
    const form = document.getElementById('login-form');
    if(!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const email         = document.getElementById('email').value;
        const psswd         = document.getElementById('psswd').value;
        const btnLogin      = document.getElementById('btn-login');
        const messaggioRisposta = document.getElementById('messaggio-risposta');

        // VALIDAZIONE
        let allertString = "";

        const patternEmail = /^\w+\.?\w+?\@{1}[A-z]{1,}\.{1}[A-z]{2,}$/;
        if(!patternEmail.test(email)) {
            allertString += "- Email non valida\n";
        }
        if(psswd.length < 8) {
            allertString += "- Password troppo corta\n";
        }
        if(allertString !== "") {
            alert(allertString);
            return;
        }

        btnLogin.disabled = true;
        btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Accesso in corso...';
        messaggioRisposta.style.display = 'none';

        //dati da inviare al php
        const datiForm = new FormData();
        datiForm.append('email', email);
        datiForm.append('psswd', psswd);

        try {//invia dati al php
            const risposta = await fetch('accesso.php', {
                method: 'POST',
                body: datiForm
            });

            const risultato = await risposta.json();
            messaggioRisposta.style.display = 'block';

            if(risultato.successo) {//messaggio di successo
                messaggioRisposta.className = 'alert alert-success mt-3';

                if(risultato.ruolo === 'admin') {
                    messaggioRisposta.innerHTML = '<i class="fas fa-user-shield me-2"></i>Accesso admin! Reindirizzamento...';
                    setTimeout(() => {
                        window.location.href = 'admin.php?access=verified';
                    }, 1000);
                } else {
                    messaggioRisposta.innerHTML = '<i class="fas fa-check-circle me-2"></i>Bentornato/a ' + risultato.nome + '! Reindirizzamento...';
                    setTimeout(() => {
                        window.location.href = 'eventi.html';
                    }, 1500);
                }

            } else {
                messaggioRisposta.className = 'alert alert-danger mt-3';
                messaggioRisposta.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + risultato.messaggio;
            }

        } catch(errore) {//errore di connessione al serber
            messaggioRisposta.style.display = 'block';
            messaggioRisposta.className = 'alert alert-danger mt-3';
            messaggioRisposta.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Errore di connessione. Riprova più tardi.';
        } finally {
            btnLogin.disabled = false;
            btnLogin.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Accedi';
        }
    });
}

});
