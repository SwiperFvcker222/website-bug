<?php
// save_to_github.php
// Backend untuk menyimpan data ke GitHub

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Konfigurasi GitHub
$config = [
    'owner' => 'SwiperFvcker222', // Ganti dengan username GitHub Anda
    'repo' => 'website-bug',  // Ganti dengan nama repository
    'token' => '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',    // Ganti dengan Personal Access Token
    'branch' => 'main',
    'filePath' => 'users.php'
];

function saveToGitHub($usersData, $config) {
    $url = "https://api.github.com/repos/{$config['owner']}/{$config['repo']}/contents/{$config['filePath']}";
    
    // Convert to PHP array format
    $phpContent = "<?php\n\n// Dark Cursed System Users Database\n// Auto-generated on " . date('Y-m-d H:i:s') . "\n\n\$users = array(\n";
    
    foreach ($usersData as $username => $userData) {
        $phpContent .= "    '{$username}' => array(\n";
        $phpContent .= "        'password' => '{$userData['password']}',\n";
        $phpContent .= "        'role' => '{$userData['role']}',\n";
        $phpContent .= "        'bCoin' => {$userData['bCoin']},\n";
        $phpContent .= "        'expiredDate' => '{$userData['expiredDate']}'\n";
        $phpContent .= "    ),\n";
    }
    
    $phpContent .= ");\n\n// End of users database\n?>";
    
    // Get current file SHA if exists
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . $config['token'],
        'User-Agent: Dark-Cursed-System'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $sha = '';
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $sha = $data['sha'];
    }
    
    // Prepare data for update
    $data = [
        'message' => 'Update users database - ' . date('Y-m-d H:i:s'),
        'content' => base64_encode($phpContent),
        'branch' => $config['branch']
    ];
    
    if ($sha) {
        $data['sha'] = $sha;
    }
    
    // Send update request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . $config['token'],
        'Content-Type: application/json',
        'User-Agent: Dark-Cursed-System'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200 || $httpCode === 201,
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

function loadFromGitHub($config) {
    $url = "https://api.github.com/repos/{$config['owner']}/{$config['repo']}/contents/{$config['filePath']}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . $config['token'],
        'User-Agent: Dark-Cursed-System'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $content = base64_decode($data['content']);
        
        // Extract PHP array and convert to JSON
        if (preg_match('/\$users\s*=\s*array\s*\((.*?)\)\s*;/s', $content, $matches)) {
            $phpArray = $matches[0];
            
            // Simple conversion from PHP array to JSON
            $json = $phpArray;
            $json = preg_replace('/array\s*\(/', '{', $json);
            $json = preg_replace('/\)\s*,/', '},', $json);
            $json = preg_replace('/\)\s*$/', '}', $json);
            $json = preg_replace('/=>/', ':', $json);
            $json = preg_replace('/\'([^\']+)\'\s*:/', '"$1":', $json);
            $json = preg_replace('/:\s*\'([^\']+)\'/', ': "$1"', $json);
            $json = preg_replace('/:\s*(\d+)/', ': $1', $json);
            
            // Remove the PHP variable declaration
            $json = preg_replace('/^\$users\s*=\s*/', '', $json);
            $json = preg_replace('/;\s*$/', '', $json);
            
            $users = json_decode($json, true);
            
            return [
                'success' => true,
                'users' => $users
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'Failed to load users from GitHub'
    ];
}

// Main logic
try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'save':
                    if (isset($input['users'])) {
                        $result = saveToGitHub($input['users'], $config);
                        echo json_encode($result);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'No users data provided']);
                    }
                    break;
                    
                case 'load':
                    $result = loadFromGitHub($config);
                    echo json_encode($result);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Invalid action']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'No action specified']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>