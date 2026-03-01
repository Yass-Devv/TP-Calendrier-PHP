<?php
// Connexion à ma base de données en local (MAMP)
$host = 'localhost';
$db   = 'calendrier_db';
$user = 'root';
$pass = 'root'; 
$port = '3306'; 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Je crée un identifiant unique pour chaque utilisateur avec un cookie
if (!isset($_COOKIE['user_id_calendrier'])) {
    $id_auto = bin2hex(random_bytes(16)); 
    setcookie('user_id_calendrier', $id_auto, time() + (86400 * 30), "/");
    $unique_user_id = $id_auto;
} else {
    $unique_user_id = $_COOKIE['user_id_calendrier'];
}

session_start();

// Je récupère le mois et l’année (URL en priorité, sinon cookie, sinon date actuelle)
if (isset($_GET['month'])) {
    $current_month = (int)$_GET['month'];
} elseif (isset($_COOKIE['current_month'])) {
    $current_month = (int)$_COOKIE['current_month'];
} else {
    $current_month = (int)date('n');
}

if (isset($_GET['year'])) {
    $current_year = (int)$_GET['year'];
} elseif (isset($_COOKIE['current_year'])) {
    $current_year = (int)$_COOKIE['current_year'];
} else {
    $current_year = (int)date('Y');
}

// Je garde le mois et l’année en mémoire pour la navigation
setcookie('current_month', $current_month, time()+60*60*24*30); 
setcookie('current_year', $current_year, time()+60*60*24*30); 

// Gestion de l’ajout ou modification d’un événement
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save' && isset($_REQUEST['date'])) {
    $title = isset($_REQUEST['title']) ? $_REQUEST['title'] : '';
    $image_name = null;

    // Si une image est envoyée, je vérifie que c’est bien un JPEG
    if (isset($_FILES['image']) && $_FILES['image']['size']) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (finfo_file($finfo, $_FILES['image']['tmp_name']) == 'image/jpeg') {
            move_uploaded_file($_FILES['image']['tmp_name'], 'upload/' . $_FILES['image']['name']);
            $image_name = $_FILES['image']['name'];
        }
    }

    // Si un id existe → je fais un update, sinon j’insère un nouvel événement
    if (!empty($_POST['event_id'])) {
        $sql = $pdo->prepare("UPDATE events SET title = ?, event_date = ?, image_path = IFNULL(?, image_path) WHERE id = ? AND user_id = ?");
        $sql->execute([$title, $_REQUEST['date'], $image_name, $_POST['event_id'], $unique_user_id]);
    } else {
        $sql = $pdo->prepare("INSERT INTO events (title, event_date, image_path, user_id) VALUES (?, ?, ?, ?)");
        $sql->execute([$title, $_REQUEST['date'], $image_name, $unique_user_id]);
    }

    // Redirection après enregistrement pour éviter les doublons
    header("Location: " . $_SERVER['PHP_SELF'] . "?month=$current_month&year=$current_year");
    exit();
}

// Suppression d’un événement si demandé
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $sql = $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
    $sql->execute([$_GET['id'], $unique_user_id]);

    // Redirection après suppression
    header("Location: " . $_SERVER['PHP_SELF'] . "?month=$current_month&year=$current_year");
    exit();
}

// Je récupère tous les événements du mois sélectionné
$sql = $pdo->prepare("SELECT * FROM events WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?");
$sql->execute([$current_month, $current_year]);
$res = $sql->fetchAll(PDO::FETCH_ASSOC);

// Je classe les événements par date pour l’affichage dans le calendrier
$events = [];
foreach ($res as $row) {
    $events[$row['event_date']][] = $row;
}

// Si on clique sur modifier, je récupère l’événement concerné
$event_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $sql = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
    $sql->execute([$_GET['id'], $unique_user_id]);
    $event_edit = $sql->fetch(PDO::FETCH_ASSOC);
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <style class="cp-pen-styles" type="text/css">
    * { -webkit-font-smoothing: antialiased; }
    body { font-family: 'helvetica neue'; background-color: #A25200; margin: 0; }
    .wrapp { width: 450px; margin: 30px auto; flex-direction: row; flex-wrap: wrap; justify-content: center; align-content: center; align-items: center; box-shadow: 0 0 10px rgba(54, 27, 0, 0.5); }
    .flex-calendar .days,.flex-calendar .days .day.selected,.flex-calendar .month,.flex-calendar .week{ display:-webkit-box; display:-webkit-flex; display:-ms-flexbox; }
    .flex-calendar{ width:100%; min-height:50px; color:#FFF; font-weight:200 }
    .flex-calendar .month { position:relative; display:flex; flex-direction:row; flex-wrap: nowrap; justify-content:space-between; align-content:flex-start; align-items:flex-start; background-color:#ffb835; }
    .flex-calendar .month .arrow,.flex-calendar .month .label { height:60px; order:0; flex:0 1 auto; align-self:auto; line-height:60px; font-size:20px; }
    .flex-calendar .month .arrow { width:50px; box-sizing:border-box; background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAABqUlEQVR4Xt3b0U3EMBCE4XEFUAolHB0clUFHUAJ0cldBkKUgnRDh7PWsd9Z5Tpz8nyxFspOCJMe2bU8AXgG8lFIurMcurIE8x9nj3wE8AvgE8MxCkAf4Ff/jTEOQBjiIpyLIAtyJpyFIAjTGUxDkADrjhxGkAIzxQwgyAIPxZgQJAFJ8RbgCOJVS6muy6QgHiIyvQqEA0fGhAArxYQAq8SEASvHTAdTipwIoxk8DUI2fAqAc7w6gHu8KkCHeDSBLvAtApng6QLZ4KkDGeBpA1ngKQOb4YYDs8UMAK8SbAVaJNwGsFN8NsFq8FeADwEPTmvPxSXV/v25xNy9fD97v8PLuVeF9FiyD0A1QKVdCMAGshGAGWAVhCGAFhGGA7AgUgMwINICsCFSAjAh0gGwILgCZENwAsiC4AmRAcAdQR5gCoIwwDUAVYSqAIsJ0ADWEEAAlhDAAFYRQAAWEcIBoBAkAIsLX/rV48291MgAEhO747o0Rr82J23GNS+6meEkAw0wwx8sCdCAMxUsDNCAMx8sD/INAiU8B8AcCLT4NwA3CG4Az68/xOu43keZ+UGLOkN4AAAAASUVORK5CYII=) no-repeat; background-size:contain; background-origin:content-box; padding:15px 5px; cursor:pointer; }
    .flex-calendar .month .arrow:last-child { -webkit-transform:rotate(180deg); transform:rotate(180deg); }
    .flex-calendar .days,.flex-calendar .week { line-height:25px; font-size:16px; display:flex; -webkit-flex-wrap: wrap; flex-wrap: wrap; }
    .flex-calendar .days { background-color:#FFF; }
    .flex-calendar .week { background-color:#faac1c; }
    .flex-calendar .days .day,.flex-calendar .week .day { flex-grow:0; -webkit-flex-basis: calc( 100% / 7 ); min-width: calc( 100% / 7 ); text-align:center; }
    .flex-calendar .days .day { min-height:60px; box-sizing:border-box; position:relative; line-height:60px; border-top:1px solid #FCFCFC; background-color:#fff; color:#8B8B8B; -webkit-transition:all .3s ease; transition:all .3s ease; }
    .flex-calendar .days .day.out { background-color:#fCFCFC; }
    .flex-calendar .days .day.disabled.today,.flex-calendar .days .day.today { color:#FFB835; border:1px solid; }
    .flex-calendar .days .day.selected { display:flex; flex-direction:row; flex-wrap:nowrap; -webkit-justify-content:center; justify-content:center; align-content:center; -webkit-align-items:center; align-items:center; }
    .flex-calendar .days .day.selected .number { width:40px; height:40px; background-color:#FFB835; border-radius:100%; line-height:40px; color:#FFF; }
    .flex-calendar .days .day.event:before { content:""; width:6px; height:6px; border-radius:100%; background-color:#faac1c; position:absolute; bottom:10px; left:50%; margin-left:-3px; }
    .flex-calendar .days .day .infos{ padding: 5px 10px; position: absolute; left: 50%; top: 100%; -webkit-transform: translateX(-50%); transform: translateX(-50%); z-index: 1; background: #faac1c; color: white; font-size: 14px; font-weight: bold; line-height: normal; white-space: nowrap; opacity: 0; pointer-events: none; -webkit-transition:all .3s ease; transition:all .3s ease; }
    .flex-calendar .days .day:hover .infos{ opacity: 1 }
    form{ padding: 20px; position: relative; background: white; box-sizing: border-box; }
    form p{ margin: 0; margin-bottom: 20px; }
    form label{ color: #8B8B8B }
    form input{ height: 30px; font-size: 12px; }
    form button{ padding: 10px 20px; background: #faac1c; border: none; color: white; font-size: 18px; cursor: pointer; }
    #events_list{ padding: 20px; background: white; color: #8b8b8b }
    #events_list h2{ margin: 0; font-weight: normal }
    #events_list a{ font-size: 12px; color: #faac1c; text-decoration: none; margin-left: 10px; }
    </style>
    <title>Calendar</title>
</head>
<body>
    <div class="wrapp">
        <div class="flex-calendar">
            <?php
                // Calcul du mois précédent et du mois suivant pour la navigation
                $this_month_ts = strtotime($current_year . '-' . $current_month . '-01');
                $prev_m = date('n', strtotime('previous month', $this_month_ts));
                $prev_y = date('Y', strtotime('previous month', $this_month_ts));
                $next_m = date('n', strtotime('next month', $this_month_ts));
                $next_y = date('Y', strtotime('next month', $this_month_ts));
            ?>
            <div class="month">
                <a href="?month=<?php echo $prev_m ?>&year=<?php echo $prev_y ?>" class="arrow visible"></a>
                <div class="label"><?php echo date( 'F Y', $this_month_ts ) ?></div>
                <a href="?month=<?php echo $next_m ?>&year=<?php echo $next_y ?>" class="arrow visible"></a>
            </div>

            <div class="week">
                <div class="day">M</div><div class="day">T</div><div class="day">W</div><div class="day">T</div><div class="day">F</div><div class="day">S</div><div class="day">S</div>
            </div>

            <div class="days">
            <?php
                $first_day = date('N', strtotime('first day of ' . $current_year . '-' . $current_month));
                $last_day = date('t', strtotime('last day of ' . $current_year . '-' . $current_month));
                $today = new DateTime('today');
                
                for ($i = 1; $i < $first_day; $i++) echo '<div class="day out"></div>';
                
                for ($i = 1; $i <= $last_day; $i++) {
                    $infos = ''; 
                    $classes = 'day';
                    $current_day = new DateTime($current_year . '-' . $current_month . '-' . $i);

                    if ($current_day == $today) $classes .= ' selected';
                    
                    if (isset($events[$current_day->format('Y-m-d')])) {
                        $classes .= ' event'; 
                        $txt = '';
                        foreach ($events[$current_day->format('Y-m-d')] as $event) {
                            $txt .= htmlspecialchars($event['title']) . '<br />';
                        }
                        $infos = '<div class="infos">' . $txt . '</div>';
                    }

                    echo '<div class="' . $classes . '"><div class="number">' . $i . '</div>' . $infos . '</div>';
                }
            ?>
            </div>
        </div>
    </div>
    
    <form class="wrapp" method="post" enctype="multipart/form-data">
        <h3><?php echo $event_edit ? "Modifier" : "Ajouter" ?></h3>
        <input type="hidden" name="action" value="save" />
        <input type="hidden" name="event_id" value="<?php echo $event_edit ? $event_edit['id'] : '' ?>" />
        <p>
            <label for="date">Date</label>
            <input type="date" name="date" id="date" value="<?php echo $event_edit ? $event_edit['event_date'] : date('Y-m-d') ?>" required />
        </p>
        <p>
            <label for="title">Titre</label>
            <input type="text" name="title" id="title" size="40" value="<?php echo $event_edit ? htmlspecialchars($event_edit['title']) : '' ?>" />
        </p>
        <p>
            <label for="image">Image</label>
            <input type="file" name="image" id="image" />
        </p>
        <button type="submit">Valider</button>
        <?php if($event_edit): ?>
            <a href="?month=<?php echo $current_month ?>&year=<?php echo $current_year ?>">Annuler</a>
        <?php endif; ?>
    </form>
    
    <div class="wrapp" id="events_list">
        <h2>Evénements du mois</h2>
        <ul>
            <?php foreach ($events as $date => $day_events): ?>
                <?php foreach ($day_events as $event): ?>
                    <li>
                        <em><?php echo date('d.m.Y', strtotime($date)) ?></em> - <?php echo htmlspecialchars($event['title']) ?>
                        <?php if ($event['user_id'] == $unique_user_id): ?>
                            <a href="?action=edit&id=<?php echo $event['id'] ?>&month=<?php echo $current_month ?>&year=<?php echo $current_year ?>" style="color:orange;">Modifier</a>
                            <a href="?action=delete&id=<?php echo $event['id'] ?>&month=<?php echo $current_month ?>&year=<?php echo $current_year ?>" style="color:red;" onclick="return confirm('Sûr?')">Supprimer</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>