<?php
/**
 * generate_icons.php
 * Genera todos los íconos PNG requeridos por el manifest.json
 * Abre este archivo en el navegador UNA SOLA VEZ para crear los íconos.
 * Requiere: extensión GD activada en PHP (php.ini: extension=gd)
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$iconsDir = __DIR__ . '/icons/';

if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

$errors = [];
$created = [];

foreach ($sizes as $size) {
    $filename = $iconsDir . 'icon-' . $size . '.png';

    // Crear imagen
    $img = imagecreatetruecolor($size, $size);
    if (!$img) {
        $errors[] = "No se pudo crear la imagen para $size×$size";
        continue;
    }

    // Activar transparencia / antialiasing
    imagealphablending($img, true);
    imagesavealpha($img, true);
    imageantialias($img, true);

    // Colores
    $green      = imagecolorallocate($img, 4, 170, 109);   // #04AA6D
    $white      = imagecolorallocate($img, 255, 255, 255); // #FFFFFF
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

    // Fondo transparente
    imagefill($img, 0, 0, $transparent);

    // ── Fondo circular ──────────────────────────────────────
    $margin = (int)($size * 0.02);
    imagefilledellipse($img, $size / 2, $size / 2, $size - $margin * 2, $size - $margin * 2, $green);

    // ── Burbuja de chat (rectángulo redondeado blanco) ──────
    $bx1 = (int)($size * 0.18);
    $by1 = (int)($size * 0.25);
    $bx2 = (int)($size * 0.82);
    $by2 = (int)($size * 0.63);
    $radius = (int)($size * 0.06);

    imagefilledrectangle($img, $bx1 + $radius, $by1, $bx2 - $radius, $by2, $white);
    imagefilledrectangle($img, $bx1, $by1 + $radius, $bx2, $by2 - $radius, $white);
    imagefilledellipse($img, $bx1 + $radius, $by1 + $radius, $radius * 2, $radius * 2, $white);
    imagefilledellipse($img, $bx2 - $radius, $by1 + $radius, $radius * 2, $radius * 2, $white);
    imagefilledellipse($img, $bx1 + $radius, $by2 - $radius, $radius * 2, $radius * 2, $white);
    imagefilledellipse($img, $bx2 - $radius, $by2 - $radius, $radius * 2, $radius * 2, $white);

    // ── Cola de la burbuja ───────────────────────────────────
    $tailPoints = [
        (int)($size * 0.25), (int)($size * 0.63),
        (int)($size * 0.19), (int)($size * 0.76),
        (int)($size * 0.36), (int)($size * 0.63),
    ];
    imagefilledpolygon($img, $tailPoints, 3, $white);

    // ── Tres puntos de chat (verde sobre blanco) ─────────────
    $dotY    = (int)(($by1 + $by2) / 2);
    $dotR    = (int)($size * 0.047);
    $dotGap  = (int)(($bx2 - $bx1) / 4);
    foreach ([1, 2, 3] as $i) {
        $dotX = $bx1 + $dotGap * $i;
        imagefilledellipse($img, $dotX, $dotY, $dotR * 2, $dotR * 2, $green);
    }

    // ── Guardar PNG ──────────────────────────────────────────
    if (imagepng($img, $filename, 9)) {
        $created[] = "icon-{$size}.png generado correctamente";
    } else {
        $errors[] = "Error al guardar icon-{$size}.png";
    }
    imagedestroy($img);
}

// ── Respuesta ────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Generador de íconos PWA</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }
    h1   { color: #04AA6D; }
    .ok  { color: #04AA6D; }
    .err { color: #cc0000; }
    img  { margin: 5px; border: 1px solid #ddd; border-radius: 8px; }
  </style>
</head>
<body>
  <h1>Generador de íconos PWA — UTC Chatbot</h1>

  <?php if ($errors): ?>
    <h2 class="err">⚠ Errores</h2>
    <ul><?php foreach ($errors as $e): ?><li class="err"><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    <p>Verifica que la extensión <strong>GD</strong> esté habilitada en <code>php.ini</code>.</p>
  <?php endif; ?>

  <?php if ($created): ?>
    <h2 class="ok">✅ Íconos generados</h2>
    <ul><?php foreach ($created as $c): ?><li class="ok"><?= htmlspecialchars($c) ?></li><?php endforeach; ?></ul>
    <h3>Vista previa:</h3>
    <div>
      <?php foreach ($sizes as $s): ?>
        <img src="icons/icon-<?= $s ?>.png" width="<?= min($s, 96) ?>" height="<?= min($s, 96) ?>" alt="<?= $s ?>px" title="<?= $s ?>×<?= $s ?>">
      <?php endforeach; ?>
    </div>
    <p><strong>Puedes cerrar esta página.</strong> Los íconos ya están en la carpeta <code>icons/</code>.</p>
  <?php endif; ?>
</body>
</html>
