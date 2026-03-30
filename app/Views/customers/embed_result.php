<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente guardado</title>
</head>

<body>
    <script>
        window.parent.postMessage({
            type: 'customer-form-saved',
            message: <?= json_encode($message ?? 'Guardado correctamente') ?>,
            action: <?= json_encode($action ?? 'updated') ?>,
            customer: <?= json_encode($customer ?? null) ?>
        }, window.location.origin);
    </script>
</body>

</html>
