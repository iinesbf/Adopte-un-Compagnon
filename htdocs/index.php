<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
$titre = 'Adopte un Compagnon — Adoption animale';

// READ : les 6 especes + le nombre d'animaux disponibles par espece (croise 2 tables)
$especes = $pdo->query("
    SELECT e.id_espece, e.nom, e.emoji, e.image, e.description,
           COUNT(a.id_animal) AS nb
    FROM espece e
    LEFT JOIN animal a ON a.id_espece = e.id_espece AND a.statut = 'disponible'
    GROUP BY e.id_espece
    ORDER BY e.id_espece
")->fetchAll();

// READ : refuges geolocalises (pour les pings de la carte)
$refuges = $pdo->query("
    SELECT nom, adresse, ville, code_postal, region, latitude, longitude
    FROM refuge
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    ORDER BY region, nom
")->fetchAll();

// Liste des regions distinctes (pour le filtre)
$regions = $pdo->query("
    SELECT DISTINCT region FROM refuge WHERE region IS NOT NULL ORDER BY region
")->fetchAll(PDO::FETCH_COLUMN);

// READ : derniers animaux disponibles (croise animal + espece + refuge)
$recents = $pdo->query("
    SELECT a.id_animal, a.nom, a.race, a.age_annees, a.sexe, a.statut, a.photo,
           e.nom AS espece, e.emoji,
           COALESCE(r.ville, a.detenteur_ville) AS ville
    FROM animal a
    JOIN espece e ON e.id_espece = a.id_espece
    LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
    WHERE a.statut = 'disponible'
    ORDER BY a.date_publication DESC
    LIMIT 4
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<!-- ===================== HERO ===================== -->
<section class="hero" style="padding:0;">
    <div class="container hero-content">
        <span class="eyebrow">Projet d'ecole</span>
        <h1>Offrez une seconde vie a un compagnon</h1>
        <p>Des centaines d'animaux attendent une famille dans nos refuges partenaires.
           Trouvez celui qui fera battre votre coeur.</p>
        <a href="recherche.php" class="btn btn-light">Voir les animaux a adopter</a>
    </div>
</section>
<svg class="wave" viewBox="0 0 1440 70" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0,40 C360,90 1080,-10 1440,40 L1440,70 L0,70 Z"></path>
</svg>

<!-- ===================== 6 CARTES ESPECES ===================== -->
<section id="especes">
    <div class="container">
        <h2 class="section-title">Quel compagnon recherchez-vous ?</h2>
        <p class="section-sub">Choisissez une categorie pour decouvrir les animaux disponibles.</p>
        <div class="especes-grid">
            <?php foreach ($especes as $esp): ?>
                <a class="espece-card" href="recherche.php?espece=<?= (int) $esp['id_espece'] ?>">
                    <?php if (!empty($esp['image'])): ?>
                        <img class="espece-photo" src="<?= e($esp['image']) ?>" alt="<?= e($esp['nom']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="emoji"><?= e($esp['emoji']) ?></span>
                    <?php endif; ?>
                    <h3><?= e($esp['nom']) ?></h3>
                    <p><?= e($esp['description']) ?></p>
                    <div class="count"><?= (int) $esp['nb'] ?> a adopter</div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== CARTE DES REFUGES ===================== -->
<section class="map-section" id="refuges">
    <div class="container">
        <h2 class="section-title">Nos refuges partenaires</h2>
        <p class="section-sub">Filtrez par region, puis cliquez sur un refuge pour l'ouvrir dans Google Maps.</p>

        <!-- Filtres : recherche + region -->
        <div class="refuge-filter">
            <input type="text" id="refuge-search" placeholder="Rechercher un refuge, une ville...">
            <select id="region-filter">
                <option value="">Toutes les regions</option>
                <?php foreach ($regions as $reg): ?>
                    <option value="<?= e($reg) ?>"><?= e($reg) ?></option>
                <?php endforeach; ?>
            </select>
            <span id="refuge-count"></span>
        </div>

        <div class="map-wrap">
            <div id="map"></div>
        </div>

        <!-- Liste scrollable : chaque carte est un lien vers Google Maps -->
        <div class="refuge-scroll">
            <ul class="refuge-list" id="refuge-list">
                <?php foreach ($refuges as $r):
                    $query = urlencode(trim($r['nom'] . ', ' . $r['adresse'] . ', ' . $r['code_postal'] . ' ' . $r['ville']));
                    $maps = 'https://www.google.com/maps/search/?api=1&query=' . $query;
                    $search = strtolower($r['nom'] . ' ' . $r['ville'] . ' ' . $r['code_postal'] . ' ' . $r['region']);
                ?>
                    <li class="refuge-item" data-region="<?= e($r['region']) ?>" data-search="<?= e($search) ?>">
                        <a href="<?= e($maps) ?>" target="_blank" rel="noopener">
                            <span class="pin">📍</span>
                            <span class="refuge-info">
                                <strong><?= e($r['nom']) ?></strong>
                                <small><?= e($r['ville']) ?> (<?= e($r['code_postal']) ?>) — <?= e($r['region']) ?></small>
                            </span>
                            <span class="go">Maps →</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p id="refuge-empty" style="display:none;text-align:center;opacity:.7;padding:30px;">Aucun refuge ne correspond.</p>
        </div>
    </div>
</section>

<!-- ===================== DERNIERS ANIMAUX ===================== -->
<section id="recents">
    <div class="container">
        <h2 class="section-title">Ils viennent d'arriver</h2>
        <p class="section-sub">Les dernieres annonces publiees par nos refuges.</p>
        <div class="animaux-grid">
            <?php foreach ($recents as $a): ?>
                <a class="animal-card" href="animal.php?id=<?= (int) $a['id_animal'] ?>">
                    <div class="photo" <?= $a['photo'] ? 'style="background-image:url(\'' . e($a['photo']) . '\')"' : '' ?>>
                        <?= $a['photo'] ? '' : e($a['emoji']) ?>
                    </div>
                    <div class="body">
                        <span class="badge badge-<?= e($a['statut']) ?>"><?= e(statut_label($a['statut'])) ?></span>
                        <h3><?= e($a['nom']) ?></h3>
                        <div class="meta">
                            <?= e($a['espece']) ?><?= $a['race'] ? ' · ' . e($a['race']) : '' ?><br>
                            <?= $a['age_annees'] !== null ? (int) $a['age_annees'] . ' an(s)' : '' ?>
                            · <?= e(sexe_label($a['sexe'])) ?> · <?= e($a['ville']) ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:36px;">
            <a href="recherche.php" class="btn">Voir tous les animaux</a>
        </div>
    </div>
</section>

<!-- Carte interactive des refuges (Leaflet + OpenStreetMap, sans cle API) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Donnees des refuges injectees depuis la base de donnees PHP
    const refuges = <?= json_encode($refuges, JSON_UNESCAPED_UNICODE) ?>;

    const map = L.map('map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Cree un marqueur par refuge, en gardant sa region pour le filtrage
    const markers = [];
    refuges.forEach(function (r) {
        const lat = parseFloat(r.latitude), lng = parseFloat(r.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        const query = encodeURIComponent((r.nom + ', ' + r.code_postal + ' ' + r.ville).trim());
        const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + query;
        const m = L.marker([lat, lng])
            .bindPopup('<strong>' + r.nom + '</strong><br>' + r.ville + ' (' + r.code_postal + ')' +
                       '<br><a href="' + mapsUrl + '" target="_blank" rel="noopener">Ouvrir dans Maps &rarr;</a>');
        const search = (r.nom + ' ' + r.ville + ' ' + r.code_postal + ' ' + r.region).toLowerCase();
        markers.push({ marker: m, region: r.region, search: search, lat: lat, lng: lng });
    });

    const champRecherche = document.getElementById('refuge-search');
    const champRegion    = document.getElementById('region-filter');

    // Applique les filtres (region + recherche) a la carte ET a la liste
    function appliquerFiltre() {
        const region = champRegion.value;
        const q = champRecherche.value.trim().toLowerCase();

        // Liste
        let visibles = 0;
        document.querySelectorAll('.refuge-item').forEach(function (el) {
            const okRegion = !region || el.dataset.region === region;
            const okSearch = !q || el.dataset.search.indexOf(q) !== -1;
            const ok = okRegion && okSearch;
            el.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        document.getElementById('refuge-count').textContent = visibles + ' refuge(s)';
        document.getElementById('refuge-empty').style.display = visibles === 0 ? 'block' : 'none';

        // Carte
        const points = [];
        markers.forEach(function (m) {
            const okRegion = !region || m.region === region;
            const okSearch = !q || m.search.indexOf(q) !== -1;
            if (okRegion && okSearch) {
                m.marker.addTo(map);
                points.push([m.lat, m.lng]);
            } else {
                map.removeLayer(m.marker);
            }
        });
        if (points.length > 0) {
            map.fitBounds(points, { padding: [40, 40], maxZoom: 13 });
        } else {
            map.setView([46.6, 2.4], 5); // France entiere
        }
    }

    champRegion.addEventListener('change', appliquerFiltre);
    champRecherche.addEventListener('input', appliquerFiltre);

    appliquerFiltre(); // affichage initial : tous les refuges
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
