<?php
// EJERCICIO 3 - SISTEMA DE REEMBOLSOS

// VALIDACIÓN: Límites de seguridad para prevenir ataques DoS (Denial of Service)
define('MAX_DESCRIPCION_LENGTH', 400);  // Máximo caracteres en descripción
define('MAX_NOMBRE_LENGTH', 150);       // Máximo caracteres en nombre
define('MAX_IMPORTE', 200.00);          // Máximo importe a reembolsar
define('MIN_IMPORTE', 5.00);            // Mínimo importe a reembolsar
define('MAX_DIAS_RESOLUCION', 10.0);    // Máximo días para resolución
define('MIN_DIAS_RESOLUCION', 0.5);     // Mínimo días para resolución

$resultado = null;
$errores = [];
$formData = [];
$mostrarResumen = false;
$mensajeError = null;

// Tipos de incidencia disponibles
$tiposIncidencia = [
    "Cancelación de clase",
    "Equipamiento defectuoso",
    "Instructor ausente",
    "Sobreventa de plazas",
    "Otros"
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos - SANITIZACIÓN: trim() elimina espacios innecesarios
    $formData = [
        'nombre' => isset($_POST['nombre']) ? trim($_POST['nombre']) : '',
        'membresía' => isset($_POST['membresía']) ? trim($_POST['membresía']) : '',
        'fecha_actividad' => isset($_POST['fecha_actividad']) ? trim($_POST['fecha_actividad']) : '',
        'hora_inicio' => isset($_POST['hora_inicio']) ? trim($_POST['hora_inicio']) : '',
        'incidencia' => isset($_POST['incidencia']) ? trim($_POST['incidencia']) : '',
        'importe' => isset($_POST['importe']) ? trim($_POST['importe']) : '',
        'dias_resolucion' => isset($_POST['dias_resolucion']) ? trim($_POST['dias_resolucion']) : '',
        'descripcion' => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : ''
    ];
    
    $errores = [];
    
    // Validar Nombre del socio
    if (empty($formData['nombre'])) {
        $errores['nombre'] = 'El nombre del socio es requerido';
    } elseif (strlen($formData['nombre']) > 150) {
        $errores['nombre'] = 'El nombre no puede superar 150 caracteres';
    }
    
    // Validar Número de membresía
    if (empty($formData['membresía'])) {
        $errores['membresía'] = 'El número de membresía es requerido';
    } elseif (!preg_match('/^SOC\d{6}$/', $formData['membresía'])) {
        $errores['membresía'] = 'Formato incorrecto. Debe ser: SOC + 6 dígitos (ej: SOC123456)';
    }
    
    // Validar Fecha de actividad
    if (empty($formData['fecha_actividad'])) {
        $errores['fecha_actividad'] = 'La fecha de la actividad es requerida';
    } else {
        // Validación usando las funciones requeridas: checkdate(), date_create_from_format() y date_diff()
        
        // PASO 1: Parsear la fecha en formato DD/MM/YYYY
        $fechaParts = explode('/', $formData['fecha_actividad']);
        
        if (count($fechaParts) === 3) {
            // SANITIZACIÓN: intval() castea a entero (defensa en profundidad)
            $dia = intval($fechaParts[0]);
            $mes = intval($fechaParts[1]);
            $año = intval($fechaParts[2]);
            
            // FUNCIÓN REQUERIDA 1: checkdate() - Valida que la fecha sea correcta
            // Verifica que el día, mes y año correspondan a una fecha válida
            if (!checkdate($mes, $dia, $año)) {
                $errores['fecha_actividad'] = 'La fecha no es válida';
            } else {
                // FUNCIÓN REQUERIDA 2: date_create_from_format()
                // Convierte la cadena en formato DD/MM/YYYY a un objeto DateTime
                $fechaObj = date_create_from_format('d/m/Y', $formData['fecha_actividad']);
                
                // Validar que la conversión fue exitosa
                if ($fechaObj === false) {
                    $errores['fecha_actividad'] = 'No se pudo procesar la fecha';
                } else {
                    // Crear objeto DateTime para hoy
                    $hoy = new DateTime();
                    // Establecer la hora a las 23:59:59 para incluir todo el día
                    $hoy->setTime(23, 59, 59);
                    
                    // Crear objeto DateTime para hace 30 días
                    $hace30Dias = new DateTime();
                    $hace30Dias->modify('-30 days');
                    // Establecer la hora a las 00:00:00 para empezar desde el principio del día
                    $hace30Dias->setTime(0, 0, 0);
                    
                    // FUNCIÓN REQUERIDA 3: date_diff()
                    // Calcula la diferencia entre la fecha ingresada y hoy
                    $diff_hoy = date_diff($fechaObj, $hoy);
                    
                    // Calcula la diferencia entre la fecha ingresada y hace 30 días
                    $diff_30dias = date_diff($fechaObj, $hace30Dias);
                    
                    // Validación 1: La fecha NO puede ser posterior al día actual
                    // Si la diferencia es negativa, significa que $fechaObj es mayor que $hoy (futura)
                    if ($diff_hoy->invert) {
                        $errores['fecha_actividad'] = 'La fecha no puede ser posterior al día actual';
                    }
                    // Validación 2: La fecha NO puede ser anterior a 30 días desde hoy
                    // Si la diferencia es mayor a 30 días (en negativo), está fuera del rango
                    elseif ($diff_30dias->days > 30) {
                        $errores['fecha_actividad'] = 'La fecha no puede ser anterior a 30 días desde hoy';
                    }
                }
            }
        } else {
            $errores['fecha_actividad'] = 'Formato de fecha incorrecto. Use DD/MM/YYYY';
        }
    }
    
    // Validar Hora de inicio
    if (empty($formData['hora_inicio'])) {
        $errores['hora_inicio'] = 'La hora de inicio es requerida';
    } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $formData['hora_inicio'])) {
        $errores['hora_inicio'] = 'Formato incorrecto. Use HH:MM (24h)';
    } else {
        // Extraer hora y minuto del formato HH:MM
        list($hora, $minuto) = explode(':', $formData['hora_inicio']);
        $horaInt = intval($hora);
        $minutoInt = intval($minuto);
        
        // VALIDACIÓN: Rango horario de atención (9:00 a 18:00)
        // Convertir a minutos totales para comparación precisa
        $tiempoTotal = ($horaInt * 60) + $minutoInt;
        $horaInicio = (9 * 60);      // 9:00 = 540 minutos
        $horaFin = (18 * 60);         // 18:00 = 1080 minutos
        
        if ($tiempoTotal < $horaInicio || $tiempoTotal > $horaFin) {
            $errores['hora_inicio'] = 'El horario de atención es de 9:00 a 18:00';
        } 
        // VALIDACIÓN: Día laboral (lunes-viernes)
        // Acceder a $fechaObj si fue creado exitosamente en validación de fecha
        elseif (isset($fechaObj) && $fechaObj !== false) {
            $diaSemana = $fechaObj->format('N');
            if ($diaSemana > 5) { // Si es mayor que 5, es sábado (6) o domingo (7)
                $errores['hora_inicio'] = 'La actividad debe ser en día laboral (lunes-viernes)';
            }
        }
    }
    
    // Validar Tipo de incidencia
    if (empty($formData['incidencia'])) {
        $errores['incidencia'] = 'Debe seleccionar un tipo de incidencia';
    } elseif (!in_array($formData['incidencia'], $tiposIncidencia)) {
        $errores['incidencia'] = 'Tipo de incidencia no válido';
    }
    
    // Validar Importe a reembolsar
    if (empty($formData['importe'])) {
        $errores['importe'] = 'El importe a reembolsar es requerido';
    } else {
        // Comprueba que la cadena contenga un valor numérico válido
        if (!is_numeric($formData['importe'])) {
            $errores['importe'] = 'El importe debe ser un número válido';
        }
        // Valida que tenga máximo 2 decimales
        elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $formData['importe'])) {
            $errores['importe'] = 'Formato incorrecto. Use números con máximo 2 decimales';
        } else {
            // Cast seguro a float para realizar comparaciones numéricas
            $importe = floatval($formData['importe']);
            
            if ($importe < 5.00 || $importe > 200.00) {
                $errores['importe'] = 'El importe debe estar entre 5.00 y 200.00 euros';
            }
        }
    }
    
    // Validar Días laborables para resolución
    if (empty($formData['dias_resolucion'])) {
        $errores['dias_resolucion'] = 'Los días para resolución son requeridos';
    } elseif (!preg_match('/^\d+(\.\d)?$/', $formData['dias_resolucion'])) {
        $errores['dias_resolucion'] = 'Formato incorrecto. Use números con máximo 1 decimal';
    } else {
        $dias = floatval($formData['dias_resolucion']);
        if ($dias < 0.5 || $dias > 10.0) {
            $errores['dias_resolucion'] = 'Los días deben estar entre 0.5 y 10.0';
        }
    }
    
    // Validar Descripción
    if (empty($formData['descripcion'])) {
        $errores['descripcion'] = 'La descripción de la incidencia es requerida';
    } else {
        $descripcionLimpia = trim($formData['descripcion']);
        if (empty($descripcionLimpia)) {
            $errores['descripcion'] = 'La descripción no puede contener solo espacios';
        } 
        elseif (strlen($descripcionLimpia) > 400) {
            $errores['descripcion'] = 'La descripción no puede superar 400 caracteres';
        } else {
            $formData['descripcion'] = strtoupper($descripcionLimpia);
        }
    }
    
    // Si no hay errores, calcular y mostrar resumen
    if (empty($errores)) {
        $mostrarResumen = true;
        
        // Calcular fecha límite de resolución (solo días laborales)
        // Convierte la fecha DD/MM/YYYY a objeto DateTime para manipulación
        $fechaActividad = DateTime::createFromFormat('d/m/Y', $formData['fecha_actividad']);
        $diasResolucion = floatval($formData['dias_resolucion']);
        
        // Separar días enteros de fraccionarios (ej: 2.5 = 2 días + 0.5 horas)
        // Las horas fraccionarias (0.5) se representan como parte del día,
        // pero en el cálculo actual solo se utilizan los días laborales enteros
        $diasAMover = intval($diasResolucion);  // Parte entera (2 de 2.5)
        $horasAMover = ($diasResolucion - $diasAMover) * 8;  // Parte fraccionaria en horas (0.5 * 8 = 4 horas)
        
        // Clonar la fecha de actividad para no modificar el original
        $fechaLimite = clone $fechaActividad;
        
        // Calcular fecha límite moviendo solo días laborables (lunes-viernes)
        // El algoritmo suma días iterativamente, saltando sábados y domingos
        $diasMovidos = 0;
        while ($diasMovidos < $diasAMover) {
            $fechaLimite->modify('+1 day');  // Sumar un día calendario
            // Verificar si es día laboral: format('N') retorna 1-7 (1=lunes, 7=domingo)
            // Mantener solo si es de lunes a viernes (valores 1-5)
            if ($fechaLimite->format('N') < 6) {
                $diasMovidos++;  // Contar solo los días laborables
            }
        }
        
        $resultado = [
            'nombre' => $formData['nombre'],
            'membresía' => $formData['membresía'],
            'fecha_actividad' => $formData['fecha_actividad'],
            'hora_inicio' => $formData['hora_inicio'],
            'incidencia' => $formData['incidencia'],
            'importe' => $formData['importe'],
            'fecha_limite' => $fechaLimite->format('d/m/Y'),  //convertir el objeto DateTime a cadena con formato
            'descripcion' => $formData['descripcion'] 
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejercicio 3 - Sistema de Reembolsos</title>
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
            max-width: 900px;
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
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        
        input[type="text"]:focus,
        input[type="date"]:focus,
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
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
        
        .resumen {
            background-color: #d4edda;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .resumen h2 {
            color: #155724;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .resumen-item {
            background-color: white;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        
        .resumen-item strong {
            color: #155724;
            display: block;
            margin-bottom: 5px;
        }
        
        .resumen-item .valor {
            font-size: 16px;
            color: #333;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
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
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .info-box h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }
        
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Reembolsos - Instalaciones Deportivas</h1>
        
        <?php if (!empty($mensajeError)): ?>
            <!-- VALIDACIÓN: Mostrar error de límite excedido -->
            <div class="error-box">
                <p><?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!$mostrarResumen): ?>
            <div class="info-box">
                <h3>Información importante:</h3>
                <p><strong>Horario de atención:</strong> 9:00 a 18:00 de lunes a viernes</p>
                <p><strong>Membresía:</strong> Formato SOC + 6 dígitos (ej: SOC123456)</p>
                <p><strong>Fecha de actividad:</strong> Formato DD/MM/YYYY, máximo 30 días atrás</p>
                <p><strong>Hora:</strong> Formato HH:MM en horario 24h</p>
            </div>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre del socio:</label>
                        <input type="text" id="nombre" name="nombre" maxlength="150"
                            value="<?php echo htmlspecialchars($formData['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo isset($errores['nombre']) ? 'campo-invalido' : ''; ?>">
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en atributo value -->
                        <?php if (isset($errores['nombre'])): ?>
                            <span class="error-message"><?php echo $errores['nombre']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="membresía">Número de membresía:</label>
                        <input type="text" id="membresía" name="membresía" placeholder="SOC123456" maxlength="9"
                            value="<?php echo htmlspecialchars($formData['membresía'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo isset($errores['membresía']) ? 'campo-invalido' : ''; ?>">
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS; valor validado con preg_match -->
                        <?php if (isset($errores['membresía'])): ?>
                            <span class="error-message"><?php echo $errores['membresía']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_actividad">Fecha de la actividad afectada (DD/MM/YYYY):</label>
                        <input type="text" id="fecha_actividad" name="fecha_actividad" placeholder="10/01/2025"
                            value="<?php echo htmlspecialchars($formData['fecha_actividad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo isset($errores['fecha_actividad']) ? 'campo-invalido' : ''; ?>">
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS; valor validado con checkdate -->
                        <?php if (isset($errores['fecha_actividad'])): ?>
                            <span class="error-message"><?php echo $errores['fecha_actividad']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora_inicio">Hora de inicio de la actividad (HH:MM):</label>
                        <input type="text" id="hora_inicio" name="hora_inicio" placeholder="10:30"
                            value="<?php echo htmlspecialchars($formData['hora_inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo isset($errores['hora_inicio']) ? 'campo-invalido' : ''; ?>">
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS; valor validado con preg_match -->
                        <?php if (isset($errores['hora_inicio'])): ?>
                            <span class="error-message"><?php echo $errores['hora_inicio']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="incidencia">Tipo de incidencia:</label>
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en option value y contenido -->
                        <select id="incidencia" name="incidencia"
                            class="<?php echo isset($errores['incidencia']) ? 'campo-invalido' : ''; ?>">
                            <option value="">Sin Valor</option>
                            <?php foreach ($tiposIncidencia as $tipo): ?>
                                <option value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ($formData['incidencia'] ?? '') === $tipo ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errores['incidencia'])): ?>
                            <span class="error-message"><?php echo $errores['incidencia']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="importe">Importe a reembolsar (€):</label>
                        <input type="text" id="importe" name="importe" placeholder="25.50"
                            value="<?php echo htmlspecialchars($formData['importe'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo isset($errores['importe']) ? 'campo-invalido' : ''; ?>">
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS; valor validado con preg_match -->
                        <?php if (isset($errores['importe'])): ?>
                            <span class="error-message"><?php echo $errores['importe']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dias_resolucion">Días laborables para resolución:</label>
                        <input type="text" id="dias_resolucion" name="dias_resolucion" placeholder="2.0"
                            value="<?php echo htmlspecialchars($formData['dias_resolucion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="<?php echo isset($errores['dias_resolucion']) ? 'campo-invalido' : ''; ?>">
                        <!-- SANITIZACIÓN: htmlspecialchars() previene XSS; valor validado con preg_match -->
                        <?php if (isset($errores['dias_resolucion'])): ?>
                            <span class="error-message"><?php echo $errores['dias_resolucion']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group full">
                    <label for="descripcion">Descripción de la incidencia (máx. 400 caracteres):</label>
                    <textarea id="descripcion" name="descripcion"
                        class="<?php echo isset($errores['descripcion']) ? 'campo-invalido' : ''; ?>"
                        placeholder="Describa lo ocurrido durante la actividad..."><?php echo htmlspecialchars($formData['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en textarea -->
                    <?php if (isset($errores['descripcion'])): ?>
                        <span class="error-message"><?php echo $errores['descripcion']; ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit">Procesar Solicitud de Reembolso</button>
            </form>
        
        <?php else: ?>
            <!-- Resumen de la solicitud -->
            <div class="resumen">
                <h2>Solicitud de Reembolso Procesada</h2>
                
                <div class="resumen-item">
                    <strong>Socio:</strong>
                    <div class="valor"><?php echo htmlspecialchars($resultado['nombre'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($resultado['membresía'], ENT_QUOTES, 'UTF-8'); ?>)</div>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en salida de nombre y membresía -->
                </div>
                
                <div class="resumen-item">
                    <strong>Actividad afectada:</strong>
                    <div class="valor"><?php echo htmlspecialchars($resultado['fecha_actividad'], ENT_QUOTES, 'UTF-8'); ?> a las <?php echo htmlspecialchars($resultado['hora_inicio'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en fecha y hora -->
                </div>
                
                <div class="resumen-item">
                    <strong>Tipo de incidencia:</strong>
                    <div class="valor"><?php echo htmlspecialchars($resultado['incidencia'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en incidencia -->
                </div>
                
                <div class="resumen-item">
                    <strong>Importe del reembolso:</strong>
                    <div class="valor"><?php 
                        $importe = floatval($resultado['importe']);
                        // SANITIZACIÓN: floatval() castea a float; number_format() formatea de forma segura
                        echo htmlspecialchars(number_format($importe, 2, ',', '.') . '€', ENT_QUOTES, 'UTF-8'); 
                    ?></div>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en importe formateado -->
                </div>
                
                <div class="resumen-item">
                    <strong>Fecha límite de resolución:</strong>
                    <div class="valor"><?php echo htmlspecialchars($resultado['fecha_limite'], ENT_QUOTES, 'UTF-8'); ?> - 9:00</div>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en fecha límite -->
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">(Se resolverá a primera hora del día límite)</div>
                </div>
                
                <div class="resumen-item">
                    <strong>Descripción de la incidencia:</strong>
                    <div class="valor"><?php echo htmlspecialchars($resultado['descripcion'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <!-- SANITIZACIÓN: htmlspecialchars() previene XSS en descripción capitalizada -->
                </div>
            
            <form method="GET" action="">
                <button type="submit">Nueva Solicitud</button>
            </form>
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
