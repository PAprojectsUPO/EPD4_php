<?php
// EJERCICIO 2 - GESTOR DE LOGS DE INVENTARIO

// VALIDACIÓN: Límites de seguridad para prevenir ataques DoS (Denial of Service)
define('MAX_LOGS_SIZE', 50000);      // Máximo tamaño en bytes (~50KB)
define('MAX_LINEAS', 1000);           // Máximo número de líneas
define('MAX_LINEA_LENGTH', 150);      // Máximo caracteres por línea

$resultado = null;
$errores = [];
$logsInput = '';
$mensajeError = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logs'])) {
    $logsInput = $_POST['logs'];
    
    // VALIDACIÓN: Sanitizar y validar tamaño total
    // Previene: Ataque de Denegación de Servicio (DoS) mediante Resource Exhaustion
    if (strlen($logsInput) > MAX_LOGS_SIZE) {
        $mensajeError = "El contenido es demasiado grande. Máximo " . (MAX_LOGS_SIZE / 1024) . " KB permitidos.";
    } else {
        $lineas = explode("\n", $logsInput);
        
        // VALIDACIÓN: Verificar número de líneas
        if (count($lineas) > MAX_LINEAS) {
            $mensajeError = "El número de líneas (" . count($lineas) . ") excede el máximo permitido (" . MAX_LINEAS . ").";
        } else {
            $lineasConError = [];
            $datosValidos = [];
            
            foreach ($lineas as $numLinea => $linea) {
                $linea = trim($linea); // SANITIZACIÓN: trim() elimina espacios innecesarios
                if (empty($linea)) continue;
                
                // Validar longitud de línea
                if (strlen($linea) > MAX_LINEA_LENGTH) {
                    $lineasConError[$numLinea + 1] = $linea;
                    continue;
                }
                
                // Validar formato con regex
                if (!preg_match('/^[^#]+#\d+#\d+#\d+$/', $linea)) {
                    $lineasConError[$numLinea + 1] = $linea;
                    continue;
                }
                
                // Procesar línea válida
                $partes = explode('#', $linea);
                $producto = trim($partes[0]); // SANITIZACIÓN: trim() nuevamente para el producto
                $pasillo = intval($partes[1]); // SANITIZACIÓN: intval() castea a entero (defensa en profundidad)
                $estanteria = intval($partes[2]); // SANITIZACIÓN: intval() castea a entero (defensa en profundidad)
                $cantidad = intval($partes[3]); // SANITIZACIÓN: intval() castea a entero (defensa en profundidad)
                
                if (!isset($datosValidos[$producto])) {
                    $datosValidos[$producto] = [];
                }
                
                $datosValidos[$producto][] = [
                    'pasillo' => $pasillo,
                    'estanteria' => $estanteria,
                    'cantidad' => $cantidad
                ];
            }
            
            if (!empty($lineasConError)) {
                $resultado = [
                    'tipo' => 'error',
                    'lineasConError' => $lineasConError
                ];
            } else {
                $resultado = [
                    'tipo' => 'exito',
                    'datos' => $datosValidos
                ];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejercicio 2 - Gestor de Logs de Inventario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            min-height: 200px;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .info-box h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }
        
        .info-box p {
            margin: 5px 0;
            color: #333;
        }
        
        .info-box code {
            background-color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d9534f;
        }
        
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #0056b3;
        }
        
        .resultado {
            margin-top: 30px;
            padding: 20px;
            border-radius: 4px;
        }
        
        .resultado-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .resultado-exito {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .resultado h2 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .lineas-error {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .lineas-error h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .linea-error {
            background-color: white;
            padding: 8px;
            margin: 5px 0;
            border-left: 3px solid #dc3545;
            font-family: 'Courier New', monospace;
        }
        
        .linea-error strong {
            color: #dc3545;
        }
        
        .productos {
            margin-top: 20px;
        }
        
        .producto-item {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        
        .producto-item h3 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .producto-total {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .ubicacion {
            padding: 8px;
            margin: 5px 0;
            background-color: white;
            border-radius: 3px;
            padding-left: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .error-box {
            background-color: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .error-box p {
            margin: 0;
            font-weight: bold;
        }
        
        .ejemplo {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestor de Logs de Inventario</h1>
        
        <?php if (!empty($mensajeError)): ?>
            <!-- VALIDACIÓN: Mostrar error de límite excedido -->
            <div class="error-box">
                <p><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>Formato esperado:</h3>
            <p><code>Producto#Num_pasillo#Num_estanteria#Cantidad</code></p>
            <div class="ejemplo">Producto1#2#3#10</div>
            <p style="margin-top: 10px;">
                <strong>Restricciones:</strong>
                <ul style="margin-left: 20px; margin-top: 5px;">
                    <li>Máximo 150 caracteres por línea</li>
                    <li>Máximo <?php echo intval(MAX_LINEAS); ?> líneas</li>
                    <li>Máximo <?php echo intval(MAX_LOGS_SIZE / 1024); ?> KB de contenido</li>
                    <li>Formato válido: producto#pasillo#estantería#cantidad</li>
                    <li>Pasillo, estantería y cantidad deben ser números</li>
                </ul>
            </p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="logs">Introduzca los datos del inventario:</label>
                <textarea id="logs" name="logs" placeholder="Producto1#2#3#10&#10;Producto1#3#4#5&#10;Producto2#2#3#1" required><?php echo htmlspecialchars($logsInput, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en textarea (previene inyección de JavaScript) -->
            </div>
            
            <button type="submit">Procesar Logs</button>
            <?php if ($resultado): ?>
                <a href="" class="back-link">← Procesar nuevos logs</a>
            <?php endif; ?>
        </form>
        
        <?php if ($resultado): ?>
            <?php if ($resultado['tipo'] === 'error'): ?>
                <div class="resultado resultado-error">
                    <h2>Error en el formato</h2>
                    <p>La información propuesta no está bien formateada. Hay errores en las siguientes líneas:</p>
                    
                    <div class="lineas-error">
                        <h3>Líneas con error:</h3>
                        <?php foreach ($resultado['lineasConError'] as $numLinea => $linea): ?>
                            <div class="linea-error">
                                <strong>Línea <?php echo intval($numLinea); ?>:</strong> <?php echo htmlspecialchars($linea, ENT_QUOTES, 'UTF-8'); ?>
                                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS; intval() valida número de línea -->
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p><strong>Revise la información.</strong></p>
                </div>
            <?php else: ?>
                <div class="resultado resultado-exito">
                    <h2>Procesamiento completado</h2>
                    
                    <div class="productos">
                        <?php foreach ($resultado['datos'] as $producto => $ubicaciones): ?>
                            <?php
                            $totalUnidades = 0;
                            foreach ($ubicaciones as $ub) {
                                $totalUnidades += $ub['cantidad'];
                            }
                            
                            // Agrupar por pasillo y estantería
                            $grupos = [];
                            foreach ($ubicaciones as $ub) {
                                $clave = $ub['pasillo'] . '_' . $ub['estanteria'];
                                if (!isset($grupos[$clave])) {
                                    $grupos[$clave] = [
                                        'pasillo' => $ub['pasillo'],
                                        'estanterias' => [],
                                        'cantidad_total' => 0
                                    ];
                                }
                                $grupos[$clave]['estanterias'][] = $ub['estanteria'];
                                $grupos[$clave]['cantidad_total'] += $ub['cantidad'];
                            }
                            ?>
                            
                            <div class="producto-item">
                                <h3><?php echo htmlspecialchars($producto, ENT_QUOTES, 'UTF-8'); ?>:</h3>
                                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en nombre de producto -->
                                <div class="producto-total">
                                    Total: <?php echo intval($totalUnidades); ?> 
                                    <!-- SANITIZACIÓN: intval() valida que sea número (defensa en profundidad) -->
                                    <?php echo $totalUnidades === 1 ? 'unidad' : 'unidades'; ?>
                                </div>
                                
                                <?php foreach ($grupos as $idx => $grupo): ?>
                                    <?php 
                                    $estanterias = array_unique($grupo['estanterias']);
                                    $cantidadGrupo = intval($grupo['cantidad_total']); // SANITIZACIÓN: intval()
                                    sort($estanterias);
                                    ?>
                                    <div class="ubicacion">
                                        <?php echo $cantidadGrupo; ?> 
                                        <!-- SANITIZACIÓN: intval() valida cantidad como número -->
                                        <?php echo $cantidadGrupo === 1 ? 'unidad' : 'unidades'; ?>
                                        en el pasillo <?php echo intval($grupo['pasillo']); ?>, 
                                        <!-- SANITIZACIÓN: intval() valida pasillo como número -->
                                        estanter<?php echo count($estanterias) === 1 ? 'ía' : 'ías'; ?> 
                                        <?php 
                                            if (count($estanterias) === 1) {
                                                echo intval($estanterias[0]); // SANITIZACIÓN: intval()
                                            } else {
                                                echo htmlspecialchars(implode(' y ', array_map('intval', $estanterias)), ENT_QUOTES, 'UTF-8');
                                                // SANITIZACIÓN: intval() en cada estantería + htmlspecialchars()
                                            }
                                        ?>.
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
<footer>
    <nav>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="Ej1.php">Ejercicio 1</a></li>
            <li><a href="Ej2.php">Ejercicio 2</a></li>
            <li><a href="Ej3.php">Ejercicio 3</a></li>
        </ul>
    </nav>
</footer>
</html>
