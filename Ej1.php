<?php
// EJERCICIO 1 - RESERVAS DE ACTIVIDADES DEPORTIVAS

// VALIDACIÓN: Límites de seguridad para prevenir ataques DoS (Denial of Service)
define('MAX_RESERVAS', 50); // Máximo de reservas permitidas
define('MIN_RESERVAS', 1);  // Mínimo de reservas permitidas

// Paso 1: Determinar el número de reservas
$numReservas = isset($_GET['numReservas']) ? intval($_GET['numReservas']) : null;
$mensajeError = null;
$errores = [];
$datos = [];
$mostrarTabla = false;

// VALIDACIÓN: Sanitizar y validar rango de numReservas
// Previene: Ataque de Denegación de Servicio (DoS) mediante Resource Exhaustion
if ($numReservas !== null && ($numReservas < MIN_RESERVAS || $numReservas > MAX_RESERVAS)) {
    $mensajeError = "El número de reservas debe estar entre " . MIN_RESERVAS . " y " . MAX_RESERVAS . ".";
    $numReservas = null;
}

// Actividades e instructores disponibles
$actividades = ["Spinning", "Yoga", "Natación", "Tenis", "Pilates", "Aeróbicos"];
$instructores = ["Ana López", "Miguel Torres", "Carmen Ruiz", "David García", "Laura Martín"];

// Paso 2: Si se envía el formulario de reservas (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['numReservas'])) {
    $numReservas = intval($_POST['numReservas']);
    $datos = [];
    
    // Validar cada reserva
    for ($i = 0; $i < $numReservas; $i++) {
        $reserva = [];
        $campoErrors = [];
        
        // Validar Fecha
        $fecha = isset($_POST["fecha_$i"]) ? trim($_POST["fecha_$i"]) : '';
        $reserva['fecha'] = $fecha;
        
        if (empty($fecha)) {
            $campoErrors[] = 'Fecha requerida';
        } else {
            // VALIDACIÓN: Verificar que el formato sea dd/mm/yyyy
            if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $fecha)) {
                $campoErrors[] = 'Formato inválido. Use dd/mm/yyyy';
            } else {
                // VALIDACIÓN: Extraer día, mes y año del formato dd/mm/yyyy
                list($dia, $mes, $año) = explode('/', $fecha);
                $dia = intval($dia);
                $mes = intval($mes);
                $año = intval($año);
                
                // VALIDACIÓN: Verificar que la fecha sea válida usando checkdate()
                if (!checkdate($mes, $dia, $año)) {
                    $campoErrors[] = 'Fecha inválida';
                } else {
                    // VALIDACIÓN: Verificar que la fecha no sea anterior a hoy
                    $timestamp = mktime(0, 0, 0, $mes, $dia, $año);
                    $hoy = strtotime('today');
                    
                    if ($timestamp < $hoy) {
                        $campoErrors[] = 'La fecha no puede ser anterior a hoy';
                    } else {
                        // Guardar en formato ISO para procesamiento posterior
                        $fechaISO = date('Y-m-d', $timestamp);
                        
                        // Convertir de vuelta a dd/mm/yyyy para mostrar en formulario
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaISO)) {
                            $reserva['fecha'] = date('d/m/Y', strtotime($fechaISO));
                        }
                        
                        // Guardar también el formato ISO para procesamiento en tabla
                        $reserva['fechaISO'] = $fechaISO;
                    }
                }
            }
        }
        
        // Validar Actividad
        $actividad = isset($_POST["actividad_$i"]) ? trim($_POST["actividad_$i"]) : '';
        $reserva['actividad'] = $actividad;
        
        if (empty($actividad) || $actividad === '') {
            $campoErrors[] = 'Debe seleccionar una actividad';
        }
        
        // Validar Instructor
        $instructor = isset($_POST["instructor_$i"]) ? trim($_POST["instructor_$i"]) : '';
        $reserva['instructor'] = $instructor;
        
        if (empty($instructor) || $instructor === '') {
            $campoErrors[] = 'Debe seleccionar un instructor';
        }
        
        // Validar Duración
        $duracion = isset($_POST["duracion_$i"]) ? intval($_POST["duracion_$i"]) : 0;
        $reserva['duracion'] = $duracion;
        
        if ($duracion === 0 || $duracion < 30 || $duracion > 180) {
            $campoErrors[] = 'La duración debe estar entre 30 y 180 minutos';
        }
        
        // Validar Observaciones
        $observaciones = isset($_POST["observaciones_$i"]) ? trim($_POST["observaciones_$i"]) : '';
        $reserva['observaciones'] = $observaciones;
        
        if (strlen($observaciones) > 300) {
            $campoErrors[] = 'Las observaciones no pueden superar 300 caracteres';
        }
        
        // Validar Participantes
        $participantes = isset($_POST["participantes_$i"]) ? intval($_POST["participantes_$i"]) : 0;
        $reserva['participantes'] = $participantes;
        
        if ($participantes === 0 || $participantes < 1 || $participantes > 25) {
            $campoErrors[] = 'El número de participantes debe estar entre 1 y 25';
        }
        
        // VALIDACIÓN: Procesar errores y separar los específicos de fecha
        $tieneErrorFecha = false;
        $mensajesFecha = [];
        foreach ($campoErrors as $error) {
            if (stripos($error, 'fecha') !== false || stripos($error, 'formato') !== false || stripos($error, 'inválida') !== false) {
                $tieneErrorFecha = true;
                $mensajesFecha[] = $error;
            }
        }
        $reserva['tieneErrorFecha'] = $tieneErrorFecha;
        $reserva['mensajesFecha'] = $mensajesFecha;
        
        $reserva['errores'] = $campoErrors;
        $datos[] = $reserva;
        
        if (!empty($campoErrors)) {
            $errores[$i] = $campoErrors;
        }
    }
    
    // Si no hay errores, mostrar tabla
    if (empty($errores)) {
        $mostrarTabla = true;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejercicio 1 - Reservas de Actividades Deportivas</title>
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
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        
        input[type="date"]:focus,
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .campo-invalido {
            border-color: #dc3545 !important;
            background-color: #fff5f5;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .reserva-block {
            background-color: #f9f9f9;
            padding: 20px;
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .reserva-title {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        
        button:hover {
            background-color: #0056b3;
        }
        
        .button-group {
            text-align: center;
            margin-top: 30px;
        }
        
        /* Estilos para la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f0f0f0;
        }
        
        .totales {
            background-color: #e7f3ff;
            font-weight: bold;
        }
        
        .planning-info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .planning-info p {
            margin: 5px 0;
        }
        
        .step-form {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .step-form input {
            max-width: 200px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Reservas de Actividades Deportivas</h1>
        
        <?php if (!empty($mensajeError)): ?>
            <!-- VALIDACIÓN: Mostrar error de rango inválido -->
            <div class="error-box">
                <p><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($numReservas === null): ?>
            <!-- PASO 1: Solicitar número de reservas -->
            <div class="step-form">
                <h2>Paso 1: ¿Cuántas reservas desea procesar?</h2>
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="numReservas">Número de reservas:</label>
                        <input type="number" id="numReservas" name="numReservas" min="1" max="50" required>
                    </div>
                    <button type="submit">Continuar</button>
                </form>
            </div>
        
        <?php elseif (!$mostrarTabla): ?>
            <!-- PASO 2: Formulario de reservas -->
            <form method="POST" action="">
                <input type="hidden" name="numReservas" value="<?php echo $numReservas; ?>">
                
                <?php for ($i = 0; $i < $numReservas; $i++): ?>
                    <div class="reserva-block">
                        <div class="reserva-title">Reserva <?php echo $i + 1; ?> de <?php echo $numReservas; ?></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha_<?php echo $i; ?>">Fecha de la actividad (dd/mm/yyyy):</label>
                                <input type="text" id="fecha_<?php echo $i; ?>" name="fecha_<?php echo $i; ?>" 
                                    value="<?php echo htmlspecialchars($datos[$i]['fecha'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="dd/mm/yyyy"
                                    title="Formato: dd/mm/yyyy"
                                    class="<?php echo ($datos[$i]['tieneErrorFecha'] ?? false) ? 'campo-invalido' : ''; ?>">
                                <!-- OPERADOR ??: Evita Undefined array key warning si $datos[$i] no existe (carga inicial sin POST) -->
                                <?php if (($datos[$i]['tieneErrorFecha'] ?? false)): ?>
                                    <?php foreach (($datos[$i]['mensajesFecha'] ?? []) as $error): ?>
                                        <span class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                                <!-- SANITIZACIÓN: htmlspecialchars() previene ataques XSS convirtiendo caracteres especiales (< > " ') en entidades HTML -->
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="actividad_<?php echo $i; ?>">Actividad:</label>
                                <select id="actividad_<?php echo $i; ?>" name="actividad_<?php echo $i; ?>"
                                    class="<?php echo isset($errores[$i]) && in_array('Debe seleccionar una actividad', $errores[$i]) ? 'campo-invalido' : ''; ?>">
                                    <option value="">-- Sin valor --</option>
                                    <?php foreach ($actividades as $act): ?>
                                        <option value="<?php echo htmlspecialchars($act, ENT_QUOTES, 'UTF-8'); ?>" 
                                            <?php echo ($datos[$i]['actividad'] ?? '') === $act ? 'selected' : ''; ?>>
                                            <!-- OPERADOR ??: Evita Undefined array key si $datos[$i]['actividad'] no existe. Devuelve '' (string vacío) por defecto -->
                                            <?php echo htmlspecialchars($act, ENT_QUOTES, 'UTF-8'); ?>
                                            <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en atributos value y contenido de options -->
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errores[$i]) && in_array('Debe seleccionar una actividad', $errores[$i])): ?>
                                    <span class="error-message">Debe seleccionar una actividad</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="instructor_<?php echo $i; ?>">Instructor:</label>
                                <select id="instructor_<?php echo $i; ?>" name="instructor_<?php echo $i; ?>"
                                    class="<?php echo isset($errores[$i]) && in_array('Debe seleccionar un instructor', $errores[$i]) ? 'campo-invalido' : ''; ?>">
                                    <option value="">-- Seleccionar valor --</option>
                                    <?php foreach ($instructores as $inst): ?>
                                        <option value="<?php echo htmlspecialchars($inst, ENT_QUOTES, 'UTF-8'); ?>" 
                                            <?php echo ($datos[$i]['instructor'] ?? '') === $inst ? 'selected' : ''; ?>>
                                            <!-- OPERADOR ??: Evita Undefined array key si $datos[$i]['instructor'] no existe. Devuelve '' (string vacío) por defecto -->
                                            <?php echo htmlspecialchars($inst, ENT_QUOTES, 'UTF-8'); ?>
                                            <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en atributos value y contenido de options -->
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errores[$i]) && in_array('Debe seleccionar un instructor', $errores[$i])): ?>
                                    <span class="error-message">Debe seleccionar un instructor</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="duracion_<?php echo $i; ?>">Duración (minutos):</label>
                                <input type="number" id="duracion_<?php echo $i; ?>" name="duracion_<?php echo $i; ?>" 
                                    value="<?php echo ($datos[$i]['duracion'] ?? 0) > 0 ? htmlspecialchars($datos[$i]['duracion'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                    title="La duración debe estar entre 30 y 180 minutos"
                                    class="<?php echo isset($errores[$i]) && in_array('La duración debe estar entre 30 y 180 minutos', $errores[$i]) ? 'campo-invalido' : ''; ?>">
                                <!-- OPERADOR ??: Evita Undefined array key si $datos[$i]['duracion'] no existe. Devuelve 0 por defecto, luego verifica si > 0 -->
                                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en atributos de input -->
                                <?php if (isset($errores[$i]) && in_array('La duración debe estar entre 30 y 180 minutos', $errores[$i])): ?>
                                    <span class="error-message">La duración debe estar entre 30 y 180 minutos</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="participantes_<?php echo $i; ?>">Número de participantes:</label>
                                <input type="number" id="participantes_<?php echo $i; ?>" name="participantes_<?php echo $i; ?>" 
                                    value="<?php echo ($datos[$i]['participantes'] ?? 0) > 0 ? htmlspecialchars($datos[$i]['participantes'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                    title="Número de participantes debe estar entre 1 y 25"
                                    class="<?php echo isset($errores[$i]) && in_array('El número de participantes debe estar entre 1 y 25', $errores[$i]) ? 'campo-invalido' : ''; ?>">
                                <!-- OPERADOR ??: Evita Undefined array key si $datos[$i]['participantes'] no existe. Devuelve 0 por defecto, luego verifica si > 0 -->
                                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en atributos de input -->
                                <?php if (isset($errores[$i]) && in_array('El número de participantes debe estar entre 1 y 25', $errores[$i])): ?>
                                    <span class="error-message">Número de participantes debe estar entre 1 y 25</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-row full">
                            <div class="form-group">
                                <label for="observaciones_<?php echo $i; ?>">Observaciones (máx. 300 caracteres):</label>
                                <textarea id="observaciones_<?php echo $i; ?>" name="observaciones_<?php echo $i; ?>" rows="3"
                                    class="<?php echo isset($errores[$i]) && in_array('Las observaciones no pueden superar 300 caracteres', $errores[$i]) ? 'campo-invalido' : ''; ?>"><?php echo htmlspecialchars($datos[$i]['observaciones'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <!-- OPERADOR ??: Evita Undefined array key si $datos[$i]['observaciones'] no existe. Devuelve '' (string vacío) por defecto -->
                                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en contenido de textarea -->
                                <?php if (isset($errores[$i]) && in_array('Las observaciones no pueden superar 300 caracteres', $errores[$i])): ?>
                                    <span class="error-message">Las observaciones no pueden superar 300 caracteres</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
                
                <div class="button-group">
                    <button type="submit">Generar planning</button>
                </div>
            </form>
            
            <a href="/Ej1.php" class="back-link">← Volver a seleccionar número de reservas</a>
        
        <?php else: ?>
            <!-- PASO 3: Mostrar tabla de resumen -->
            <div class="planning-info">
                <p><strong>Fecha de generación del planning:</strong> <?php echo date('d/m/Y'); ?></p>
            </div>
            
            <?php
            // Procesar datos para la tabla
            $reservasOrdenadas = [];
            foreach ($datos as $reserva) {
                $reservasOrdenadas[] = $reserva;
            }
            
            // Ordenar por fecha y luego por instructor
            usort($reservasOrdenadas, function($a, $b) {
                // Usar fechaISO para comparación si existe, si no usar fecha
                // OPERADOR ??: Devuelve fechaISO (formato YYYY-MM-DD) si existe, sino devuelve fecha (dd/mm/yyyy)
                $fechaA = $a['fechaISO'] ?? $a['fecha'];
                $fechaB = $b['fechaISO'] ?? $b['fecha'];
                $cmp = strcmp($fechaA, $fechaB);
                if ($cmp !== 0) return $cmp;
                return strcmp($a['instructor'], $b['instructor']);
            });
            
            // Calcular totales
            $totalDuracion = 0;
            $totalParticipantes = 0;
            $instructoresUnicos = [];
            $actividadesUnicas = [];
            
            foreach ($reservasOrdenadas as $r) {
                $totalDuracion += $r['duracion'];
                $totalParticipantes += $r['participantes'];
                $instructoresUnicos[$r['instructor']] = true;
                $actividadesUnicas[$r['actividad']] = true;
            }
            ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Instructor</th>
                        <th>Actividad</th>
                        <th>Duración (min)</th>
                        <th>Participantes</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $fechaActual = null;
                    $instructorActual = null;
                    $rowspanFecha = [];
                    $rowspanInstructor = [];
                    
                    // Preparar rowspans
                    foreach ($reservasOrdenadas as $idx => $r) {
                        // OPERADOR ??: Devuelve fechaISO (YYYY-MM-DD) si existe para ordenación correcta, sino devuelve fecha (dd/mm/yyyy)
                        $fecha = $r['fechaISO'] ?? $r['fecha'];
                        $instructor = $r['instructor'];
                        
                        // Contar filas consecutivas con la misma fecha
                        if ($fecha !== $fechaActual) {
                            $rowspanFecha[$idx] = 1;
                            $fechaActual = $fecha;
                        } else {
                            $rowspanFecha[$idx] = 0;
                        }
                        
                        // Contar rowspan para instructor
                        if ($instructor !== $instructorActual) {
                            $rowspanInstructor[$idx] = 1;
                            $instructorActual = $instructor;
                        } else {
                            $rowspanInstructor[$idx] = 0;
                        }
                    }
                    
                    // Contar rowspans correctos
                    for ($i = 0; $i < count($reservasOrdenadas); $i++) {
                        if ($rowspanFecha[$i] == 0) continue;
                        
                        // OPERADOR ??: Devuelve fechaISO si existe (mejor para comparación), sino fecha
                        $fecha = $reservasOrdenadas[$i]['fechaISO'] ?? $reservasOrdenadas[$i]['fecha'];
                        $count = 1;
                        for ($j = $i + 1; $j < count($reservasOrdenadas); $j++) {
                            // OPERADOR ??: Devuelve fechaISO si existe, sino fecha (necesario para comparaciones consistentes)
                            $fechaJ = $reservasOrdenadas[$j]['fechaISO'] ?? $reservasOrdenadas[$j]['fecha'];
                            if ($fechaJ === $fecha) {
                                $count++;
                            } else {
                                break;
                            }
                        }
                        $rowspanFecha[$i] = $count;
                    }
                    
                    for ($i = 0; $i < count($reservasOrdenadas); $i++) {
                        if ($rowspanInstructor[$i] == 0) continue;
                        
                        $instructor = $reservasOrdenadas[$i]['instructor'];
                        // OPERADOR ??: Devuelve fechaISO si existe, sino fecha (para comparación correcta de fechas)
                        $fecha = $reservasOrdenadas[$i]['fechaISO'] ?? $reservasOrdenadas[$i]['fecha'];
                        $count = 1;
                        for ($j = $i + 1; $j < count($reservasOrdenadas); $j++) {
                            // OPERADOR ??: Devuelve fechaISO si existe, sino fecha (necesario para comparaciones consistentes)
                            $fechaJ = $reservasOrdenadas[$j]['fechaISO'] ?? $reservasOrdenadas[$j]['fecha'];
                            if ($reservasOrdenadas[$j]['instructor'] === $instructor && $fechaJ === $fecha) {
                                $count++;
                            } else {
                                break;
                            }
                        }
                        $rowspanInstructor[$i] = $count;
                    }
                    
                    foreach ($reservasOrdenadas as $idx => $r):
                    ?>
                        <tr>
                            <?php if ($rowspanFecha[$idx] > 0): ?>
                                <td rowspan="<?php echo $rowspanFecha[$idx]; ?>"><?php 
                                    // OPERADOR ??: Devuelve fechaISO (YYYY-MM-DD) si existe para conversión correcta, sino fecha (dd/mm/yyyy)
                                    $fechaMostrar = $r['fechaISO'] ?? $r['fecha'];
                                    echo htmlspecialchars(DateTime::createFromFormat('Y-m-d', $fechaMostrar)->format('d/m/Y'), ENT_QUOTES, 'UTF-8'); 
                                ?></td>
                                <!-- SANITIZACIÓN: htmlspecialchars() en salida de fecha con formato dd/mm/yyyy -->
                            <?php endif; ?>
                            <?php if ($rowspanInstructor[$idx] > 0): ?>
                                <td rowspan="<?php echo $rowspanInstructor[$idx]; ?>"><?php echo htmlspecialchars($r['instructor'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en nombre de instructor -->
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($r['actividad'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en nombre de actividad -->
                            <td><?php echo intval($r['duracion']); ?></td>
                            <!-- SANITIZACIÓN: intval() valida que duracion sea número (defensa en profundidad) -->
                            <td><?php echo intval($r['participantes']); ?></td>
                            <!-- SANITIZACIÓN: intval() valida que participantes sea número (defensa en profundidad) -->
                            <td><?php echo htmlspecialchars(ucwords(strtolower($r['observaciones'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en observaciones capitalizadas -->
                        </tr>
                    <?php endforeach; ?>
                    
                    <tr class="totales">
                        <td><strong>TOTALES</strong></td>
                        <td><strong><?php echo count($actividadesUnicas); ?> actividades</strong></td>
                        <td><strong><?php echo count($instructoresUnicos); ?> instructores</strong></td>
                        <td><strong><?php echo $totalDuracion; ?> min</strong></td>
                        <td><strong><?php echo $totalParticipantes; ?> participantes</strong></td>
                        
                    </tr>
                </tbody>
            </table>
            
            <div class="button-group">
                <a href="/Ej1.php" class="back-link">← Nueva reserva</a>
            </div>
        <?php endif; ?>
    </div>
</body>
<footer>
    <nav>
        <ul>
            <li><a href="index.html">Inicio</a></li>
            <li><a href="Ej1.php">Ejercicio 1</a></li>
            <li><a href="Ej2.php">Ejercicio 2</a></li>
            <li><a href="Ej3.php">Ejercicio 3</a></li>
        </ul>
    </nav>
</footer>
</html>
