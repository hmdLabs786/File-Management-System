<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$email = isset($_SESSION["email"]) ? $_SESSION["email"] : "user@example.com";

$stmtSize = $pdo->prepare("SELECT SUM(file_size) as total FROM files WHERE
user_id = ? AND status != 'deleted'");
$stmtSize->execute([$user_id]);
$active_size = $stmtSize->fetch()["total"] ?? 0;
$stmtTrashSize = $pdo->prepare("SELECT SUM(file_size) as total FROM files WHERE user_id = ? AND
status = 'deleted'");
$stmtTrashSize->execute([$user_id]);
$trash_size = $stmtTrashSize->fetch()["total"] ?? 0;
function formatSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . " GB";
    }
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . " MB";
    }
    return number_format($bytes / 1024, 2) . " KB";
}
$view = isset($_GET["view"]) ? $_GET["view"] : "home";
$search = isset($_GET["search"]) ? $_GET["search"] : "";
$page_title = "Dashboard";
$query = 'SELECT * FROM files WHERE
user_id = ?';
$params = [$user_id];
$order_limit = ' ORDER BY upload_date
DESC';
if (!empty($search)) {
    $query .= " AND original_name LIKE ? AND
status != 'deleted'";
    $params[] = "%$search%";
    $page_title = "Search Results";
} else {
    switch ($view) {
        case "trash":
            $query .= " AND status = 'deleted'";
            $page_title = "Trash";
            break;
        case "starred":
            $query .= " AND status = 'active'
AND is_starred = 1";
            $page_title = "Starred";
            break;
        case "recent":
            $query .= "
AND status = 'active'";
            $order_limit = " ORDER BY upload_date DESC LIMIT 15";
            $page_title = "Recent";
            break;
        case "home":
            $query .= " AND status = 'active'";
            $order_limit = " ORDER BY upload_date DESC LIMIT 5";
            $page_title = "Home";
            break;
        default:
            $query .= " AND status = 'active'";
            $page_title = "My Files";
            break;
    }
}
$stmt = $pdo->prepare($query . $order_limit);
$stmt->execute($params);
$files = $stmt->fetchAll();
if ($view == "home") {
    $stmtStats = $pdo->prepare("SELECT COUNT(*) as count, file_type FROM files WHERE user_id = ?
AND status='active' GROUP BY file_type");
    $stmtStats->execute([$user_id]);
    $stats = $stmtStats->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>FUMS - <?= $page_title ?></title>
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
            rel="stylesheet"
        />
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        />
        <style>
            :root {
                --dark-green: #004d40;
                --light-green: #00796b;
                --bg-white: #ffffff;
                --sidebar-gray: #f8f9fa;
            }
            body {
                background-color: var(--bg-white);
                font-family: "Jost", sans-serif;
                overflow: hidden;
            }
            .navbar {
                height: 65px;
                border-bottom: 1px solid #dee2e6;
                padding: 0 20px;
            }
            .wrapper {
                display: flex;
                height: calc(100vh - 65px);
            }

            .sidebar {
                width: 260px;
                background: var(--sidebar-gray);
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .nav-link {
                color: #3c4043;
                padding: 10px 15px;
                border-radius: 0 20px 20px 0;
                margin-bottom: 2px;
            }
            .nav-link.active {
                background-color: #e0f2f1;
                color: var(--dark-green);
                font-weight: bold;
            }

            .profile-avatar {
                width: 40px;
                height: 40px;
                background: var(--dark-green);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                position: relative;
            }
            .profile-avatar:hover::after {
                content: "<?= $username ?> (<?= $email ?>)";
                position: absolute;
                bottom: -45px;
                right: 0;
                background: #333;
                color: #fff;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 100;
            }

            .storage-box {
                background: white;
                padding: 15px;
                border-radius: 12px;
                border: 1px solid #dee2e6;
                font-size: 13px;
            }
            .progress {
                height: 8px;
                margin: 10px 0;
            }

            .stat-card {
                border-radius: 15px;
                border: none;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                transition: 0.3s;
            }
            .stat-card:hover {
                transform: translateY(-3px);
            }

            .main-content {
                flex: 1;
                padding: 30px;
                overflow-y: auto;
            }
            .file-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            .file-card {
                border: 1px solid #dee2e6;
                border-radius: 12px;
                padding: 20px;
                background: #fff;
                position: relative;
                transition: 0.2s;
                display: flex;
                flex-direction: column;
                align-items: center;
                min-height: 180px;
            }
            .file-card:hover {
                z-index: 5;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }
            .file-options {
                position: absolute;
                top: 10px;
                right: 10px;
            }
        </style>
    </head>
    <body>
        <nav
            class="navbar d-flex justify-content-between align-items-center bg-white"
        >
            <div class="d-flex align-items-center">
                <img
                    src="logo.png"
                    alt="Logo"
                    style="height: 35px; margin-right: 10px"
                />
                <span class="fs-4 fw-bold" style="color: var(--dark-green)"
                    >FUMS</span
                >
            </div>

            <form
                action="index.php"
                method="GET"
                class="flex-grow-1 d-flex justify-content-center"
            >
                <input
                    type="text"
                    name="search"
                    class="form-control border-0 bg-light w-50"
                    placeholder="Search in Drive..."
                    value="<?= htmlspecialchars($search) ?>"
                />
            </form>

            <div class="d-flex align-items-center gap-3">
                <div class="profile-avatar text-uppercase">
                    <?= substr( $username, 0, 1 ) ?>
                </div>
                <a href="logout.php" class="text-danger fs-5" title="Logout"
                    ><i class="fa fa-sign-out-alt"></i
                ></a>
            </div>
        </nav>

        <div class="wrapper">
            <div class="sidebar">
                <div>
                    <button
                        class="btn btn-new w-100 mb-4"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                    >
                        <i class="fa fa-plus me-2"></i> New
                    </button>
                    <nav class="nav flex-column">
                        <a
                            class="nav-link <?= $view === "home"
                                ? "active"
                                : "" ?>"
                            href="index.php?view=home"
                            ><i class="fa fa-home me-3"></i> Home</a
                        >
                        <a
                            class="nav-link <?= $view === "active"
                                ? "active"
                                : "" ?>"
                            href="index.php?view=active"
                            ><i class="fa fa-folder me-3"></i> My Files</a
                        >
                        <a
                            class="nav-link <?= $view === "recent"
                                ? "active"
                                : "" ?>"
                            href="index.php?view=recent"
                            ><i class="fa fa-clock me-3"></i> Recent</a
                        >
                        <a
                            class="nav-link <?= $view === "starred"
                                ? "active"
                                : "" ?>"
                            href="index.php?view=starred"
                            ><i class="fa fa-star me-3"></i> Starred</a
                        >
                        <a
                            class="nav-link <?= $view === "trash"
                                ? "active"
                                : "" ?>"
                            href="index.php?view=trash"
                            ><i class="fa fa-trash me-3"></i> Trash</a
                        >
                    </nav>
                </div>

                <div class="storage-box">
                    <?php
                    $limit = 100 * 1024 * 1024;
                    $usage_pct = ($active_size / $limit) * 100;
                    $bar_color = $usage_pct > 80 ? "bg-danger" : "bg-success";
                    ?>
                    <div class="fw-bold mb-1">Storage Used</div>
                    <div class="progress" style="height: 10px">
                        <div
                            class="progress-bar <?= $bar_color ?>"
                            role="progressbar"
                            style="width: <?= min($usage_pct, 100) ?>%"
                        ></div>
                    </div>
                    <div class="text-muted mt-2" style="font-size: 11px">
                        <i class="fa fa-hdd me-1"></i> Drive: <?= formatSize(
                        $active_size ) ?> <br />
                        <i class="fa fa-trash-alt me-1"></i> Trash: <?=
                        formatSize( $trash_size ) ?>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <?php if ($view == "home" && empty($search)): ?>
                <h2 class="mb-4">
                    Welcome, <?= htmlspecialchars( $username ) ?>!
                </h2>

                <div class="row g-3 mb-5">
                    <div class="col-md-4">
                        <div class="card stat-card p-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Total Files</h6>
                                    <h3><?= count( $files ) ?></h3>
                                </div>
                                <i
                                    class="fa fa-file-alt fa-2x text-success"
                                ></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card p-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Storage Used</h6>
                                    <h3><?= formatSize( $active_size ) ?></h3>
                                </div>
                                <i class="fa fa-hdd fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card p-3 bg-light">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted">Trash Size</h6>
                                    <h3><?= formatSize( $trash_size ) ?></h3>
                                </div>
                                <i
                                    class="fa fa-trash-alt fa-2x text-danger"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>
                <h5 class="mb-3">Most Recent Files</h5>
                <?php else: ?>
                <h4 class="mb-4"><?= $page_title ?></h4>
                <?php endif; ?>

                <div class="file-grid">
                    <?php if (empty($files)): ?>
                    <div class="text-center w-100 text-muted p-5">
                        No files found.
                    </div>
                    <?php endif; ?>

                    <?php foreach ($files as $file):
                        $ext = strtolower(
                            pathinfo($file["original_name"], PATHINFO_EXTENSION)
                        ); ?>
                    <div class="file-card">
                        <div class="dropdown file-options">
                            <button
                                class="btn btn-sm"
                                data-bs-toggle="dropdown"
                            >
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu shadow">
                                <?php if ($file["status"] == "deleted"): ?>
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="restore.php?id=<?= $file["id"] ?>"
                                        ><i class="fa fa-undo me-2"></i>
                                        Restore</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="dropdown-item text-danger"
                                        href="delete_perm.php?id=<?= $file[
                                            "id"
                                        ] ?>"
                                        ><i class="fa fa-times me-2"></i> Delete
                                        Forever</a
                                    >
                                </li>
                                <?php else: ?>
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="star_file.php?id=<?= $file[
                                            "id"
                                        ] ?>"
                                        ><i class="fa fa-star me-2"></i> Star</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="download.php?token=<?= $file[
                                            "secure_token"
                                        ] ?>"
                                        ><i class="fa fa-download me-2"></i>
                                        Download</a
                                    >
                                </li>
                                <li><hr class="dropdown-divider" /></li>
                                <li>
                                    <a
                                        class="dropdown-item text-danger"
                                        href="trash.php?id=<?= $file["id"] ?>"
                                        ><i class="fa fa-trash me-2"></i>
                                        Trash</a
                                    >
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="fs-1 text-success mb-2">
                            <div
                                class="file-icon"
                                style="
                                    height: 100px;
                                    width: 100%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    overflow: hidden;
                                    margin-bottom: 10px;
                                "
                            >
                                <?php if (
                                    in_array($ext, [
                                        "jpg",
                                        "jpeg",
                                        "png",
                                        "gif",
                                    ])
                                ): ?>
                                <img
                                    src="uploads/<?= $file["server_name"] ?>"
                                    style="
                                        max-width: 100%;
                                        max-height: 100px;
                                        object-fit: cover;
                                        border-radius: 8px;
                                    "
                                />
                                <?php elseif ($ext == "pdf"): ?>
                                <i
                                    class="fa fa-file-pdf text-danger"
                                    style="font-size: 50px"
                                ></i>
                                <?php elseif (
                                    in_array($ext, ["doc", "docx"])
                                ): ?>
                                <i
                                    class="fa fa-file-word text-primary"
                                    style="font-size: 50px"
                                ></i>
                                <?php elseif ($ext == "zip"): ?>
                                <i
                                    class="fa fa-file-archive text-warning"
                                    style="font-size: 50px"
                                ></i>
                                <?php else: ?>
                                <i
                                    class="fa fa-file-alt text-secondary"
                                    style="font-size: 50px"
                                ></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="file-name">
                            <?= htmlspecialchars( $file['original_name']) ?>
                        </div>
                    </div>
                    <?php
                    endforeach; ?>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
