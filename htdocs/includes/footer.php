    </main>
    <footer class="site-footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>Adopte un Compagnon</h4>
                    <p>Plateforme d'adoption animale mettant en relation les refuges
                       et les futurs adoptants. Projet etudiant ESIEE — E4FG SI 2026.</p>
                </div>
                <div>
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="recherche.php">Adopter</a></li>
                        <li><a href="index.php#refuges">Nos refuges</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Mon espace</h4>
                    <ul>
                        <?php if (est_connecte()): ?>
                            <li><a href="logout.php">Se deconnecter</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Connexion</a></li>
                            <li><a href="register.php">Creer un compte</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> Adopte un Compagnon — Projet ESIEE E4FG SI.
            </div>
        </div>
    </footer>
</body>
</html>
