<?php
require_once __DIR__ . '/includes/auth.php';
deconnecter();
start_session();
flash('Vous avez ete deconnecte.');
redirect('index.php');
