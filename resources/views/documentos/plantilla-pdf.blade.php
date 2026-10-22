{{-- Vista segura para PDF: escape con e(); saltos de linea con nl2br(e()). --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Documento</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.5; color: #111; }
        .contenido { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="contenido">{!! nl2br(e($contenido)) !!}</div>
</body>
</html>
