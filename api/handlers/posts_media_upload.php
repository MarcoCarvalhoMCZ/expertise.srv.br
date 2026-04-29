<?php
$user = requireAuth();
$postId = (int)($_REQUEST['id'] ?? 0);

$check = dbFetchAll(dbQuery("SELECT id FROM posts WHERE id = ?", [$postId]));
if (empty($check)) {
    http_response_code(404);
    echo json_encode(['error' => 'Post não encontrado.']);
    exit;
}

if (empty($_FILES['files'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nenhum arquivo enviado.']);
    exit;
}

$uploadsDir = __DIR__ . '/../uploads';
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

$mediaRecords = [];
$files = $_FILES['files'];

// Normalizar para array múltiplo
if (!is_array($files['name'])) {
    $files = [
        'name' => [$files['name']],
        'tmp_name' => [$files['tmp_name']],
        'type' => [$files['type']],
        'size' => [$files['size']],
        'error' => [$files['error']]
    ];
}

for ($i = 0; $i < count($files['name']); $i++) {
    $originalName = $files['name'][$i];
    $tmpName = $files['tmp_name'][$i];
    $mimeType = $files['type'][$i];
    $fileSize = $files['size'][$i];

    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

    // Determinar tipo de mídia
    $mediaType = 'image';
    if (strpos($mimeType, 'video/') === 0) $mediaType = 'video';
    elseif (strpos($mimeType, 'audio/') === 0) $mediaType = 'audio';

    // Organizar por tipo
    $subdir = $uploadsDir . '/' . $mediaType . 's';
    if (!is_dir($subdir)) mkdir($subdir, 0755, true);

    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $uniqueName = time() . '-' . mt_rand(100000000, 999999999) . '.' . $ext;
    $filePath = $subdir . '/' . $uniqueName;

    if (move_uploaded_file($tmpName, $filePath)) {
        $fileUrl = '/api/uploads/' . $mediaType . 's/' . $uniqueName;

        $stmt = dbQuery(
            "INSERT INTO post_media (post_id, media_type, file_url, file_name, file_size)
             OUTPUT INSERTED.id
             VALUES (?, ?, ?, ?, ?)",
            [$postId, $mediaType, $fileUrl, $originalName, $fileSize]
        );
        $newId = dbLastInsertId($stmt);
        $mediaRecords[] = ['id' => $newId, 'file_url' => $fileUrl, 'media_type' => $mediaType];
    }
}

// Atualizar post_type
dbQuery("UPDATE posts SET post_type = 'mixed', updated_at = GETDATE() WHERE id = ?", [$postId]);

http_response_code(201);
echo json_encode(['message' => count($mediaRecords) . ' arquivo(s) enviado(s).', 'media' => $mediaRecords]);