<?php
session_start();

// Připojení k databázi
require_once 'init.php';

// 1. Zpracování POST požadavků (PRG Pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message = '';
    $status = 'success';

    if ($action === 'add') {
        $newInterest = trim($_POST['interest'] ?? '');
        
        if (empty($newInterest)) {
            $message = "Pole nesmí být prázdné.";
            $status = 'error';
        } else {
            // Kontrola duplicit (case-insensitive i když UNIQUE omezení to jistí pro přesné shody)
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM interests WHERE LOWER(name) = LOWER(?)");
            $stmtCheck->execute([$newInterest]);
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                $message = "Tento zájem už existuje.";
                $status = 'error';
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO interests (name) VALUES (?)");
                    $stmt->execute([$newInterest]);
                    $message = "Zájem byl úspěšně přidán.";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // Ošetřené UNIQUE Constraint failed
                        $message = "Tento zájem už existuje.";
                    } else {
                        $message = "Došlo k chybě: " . $e->getMessage();
                    }
                    $status = 'error';
                }
            }
        }
    } 
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? -1;
        $stmt = $db->prepare("DELETE FROM interests WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Zájem byl odstraněn.";
        } else {
            $message = "Zájem se nepodařilo odstranit.";
            $status = 'error';
        }
    }
    elseif ($action === 'edit') {
        $id = $_POST['id'] ?? -1;
        $newValue = trim($_POST['new_value'] ?? '');
        
        if (empty($newValue)) {
            $message = "Pole nesmí být prázdné.";
            $status = 'error';
        } else {
            // Kontrola duplicit (zda nový název už neexistuje u jiného ID)
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM interests WHERE LOWER(name) = LOWER(?) AND id != ?");
            $stmtCheck->execute([$newValue, $id]);
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                $message = "Tento zájem už existuje.";
                $status = 'error';
            } else {
                try {
                    $stmt = $db->prepare("UPDATE interests SET name = ? WHERE id = ?");
                    $stmt->execute([$newValue, $id]);
                    $message = "Zájem byl úspěšně upraven.";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $message = "Tento zájem už existuje.";
                    } else {
                        $message = "Došlo k chybě: " . $e->getMessage();
                    }
                    $status = 'error';
                }
            }
        }
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_status'] = $status;
    header("Location: index.php");
    exit;
}

// 2. Načtení dat pro zobrazení
$stmt = $db->query("SELECT * FROM interests");
$interests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flashMessage = $_SESSION['flash_message'] ?? '';
$flashStatus = $_SESSION['flash_status'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_status']);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Správa zájmů - IT Profil 5.0</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Správa zájmů</h1>

            <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashStatus; ?>">
                    <?php echo htmlspecialchars($flashMessage); ?>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST" class="add-form">
                <input type="hidden" name="action" value="add">
                <input type="text" name="interest" placeholder="Zadejte nový zájem..." required>
                <button type="submit" class="btn btn-primary">Přidat</button>
            </form>

            <ul class="interest-list">
                <?php foreach ($interests as $interest): ?>
                    <li class="interest-item-container">
                        <div class="interest-item" id="item-<?php echo $interest['id']; ?>">
                            <span class="interest-text"><?php echo htmlspecialchars($interest['name']); ?></span>
                            <div class="actions">
                                <button class="btn btn-edit" onclick="toggleEdit(<?php echo $interest['id']; ?>)">Upravit</button>
                                <form action="index.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $interest['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Smazat</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Edit Form -->
                        <div class="edit-mode hidden" id="edit-<?php echo $interest['id']; ?>">
                            <form action="index.php" method="POST" class="edit-form">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $interest['id']; ?>">
                                <input type="text" name="new_value" value="<?php echo htmlspecialchars($interest['name']); ?>" required>
                                <button type="submit" class="btn btn-primary">Uložit</button>
                                <button type="button" class="btn btn-secondary" onclick="toggleEdit(<?php echo $interest['id']; ?>)">Zrušit</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        function toggleEdit(id) {
            const displayItem = document.getElementById('item-' + id);
            const editMode = document.getElementById('edit-' + id);
            
            if (editMode.classList.contains('hidden')) {
                editMode.classList.remove('hidden');
                displayItem.classList.add('hidden');
            } else {
                editMode.classList.add('hidden');
                displayItem.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
