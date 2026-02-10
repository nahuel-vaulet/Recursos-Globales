<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if editing
$id = $_GET['id'] ?? null;
$personal = null;
$isEdit = false;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM personal WHERE id_personal = ?");
    $stmt->execute([$id]);
    $personal = $stmt->fetch(PDO::FETCH_ASSOC);
    $isEdit = (bool)$personal;
}

// Fetch cuadrillas for dropdown
$cuadrillas = $pdo->query("SELECT * FROM cuadrillas WHERE estado_operativo = 'Activa' ORDER BY nombre_cuadrilla")->fetchAll(PDO::FETCH_ASSOC);

// Eliminar 'Chofer' del array de roles, ahora se determina por carnet
$roles = ['Oficial', 'Ayudante', 'Administrativo', 'Supervisor'];
$grupos_sanguineos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$talles = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
$tipos_carnet = ['A1', 'A2', 'A3', 'B1', 'B2', 'C1', 'C2', 'C3', 'D1', 'D2', 'E1', 'E2', 'F', 'G'];
$lista_tareas = ['Albañilería', 'Pintura', 'Electricidad', 'Plomería', 'Gas', 'Soldadura', 'Carpintería', 'Durlock', 'Limpieza', 'Excavación', 'Hormigón', 'Vial', 'Chofer/Conducción', 'Logística', 'Administrativo'];

// Tareas to array
$tareas_seleccionadas = [];
if (!empty($personal['tareas_desempenadas'])) {
    $tareas_seleccionadas = json_decode($personal['tareas_desempenadas'], true) ?? explode(',', $personal['tareas_desempenadas']);
}
?>

<div class="container-fluid" style="padding: 0 20px; max-width: 1000px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px;">
        <a href="index.php" class="btn btn-outline" style="padding: 8px 12px;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="margin: 0;">
                <i class="fas fa-<?php echo $isEdit ? 'user-edit' : 'user-plus'; ?>"></i>
                <?php echo $isEdit ? 'Editar Personal' : 'Nuevo Personal'; ?>
            </h2>
            <p style="margin: 5px 0 0; color: var(--text-muted);">Legajo Digital del Empleado</p>
        </div>
    </div>

    <!-- Form Card with Tabs -->
    <div class="card" style="border-top: 4px solid var(--color-primary);">
        
        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button type="button" class="tab-btn active" onclick="showTab('basico')">
                <i class="fas fa-user"></i> Datos Básicos
            </button>
            <button type="button" class="tab-btn" onclick="showTab('seguridad')">
                <i class="fas fa-shield-alt"></i> Seguridad / EPP
            </button>
            <button type="button" class="tab-btn" onclick="showTab('salud')">
                <i class="fas fa-heartbeat"></i> Salud
            </button>
             <button type="button" class="tab-btn" onclick="showTab('familia')">
                <i class="fas fa-users"></i> Familia / Emergencia
            </button>
            <button type="button" class="tab-btn" onclick="showTab('admin')">
                <i class="fas fa-folder-open"></i> Administrativo
            </button>
             <button type="button" class="tab-btn" onclick="showTab('legajo')">
                <i class="fas fa-file-contract"></i> Legajo / Cierre
            </button>
        </div>

        <form action="save.php" method="POST" id="personalForm" enctype="multipart/form-data">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id_personal" value="<?php echo $personal['id_personal']; ?>">
            <?php endif; ?>

            <!-- TAB: Datos Básicos -->
            <div class="tab-content active" id="tab-basico">
                <h4 class="section-title"><i class="fas fa-id-card"></i> Información Personal</h4>
                
                <div class="form-row">
                    <div class="form-group" style="flex: 0 0 150px; text-align: center;">
                        <label for="foto_usuario_input" style="cursor: pointer;">
                            <div id="foto_preview" style="width: 120px; height: 120px; border-radius: 50%; background: var(--bg-tertiary); border: 2px dashed var(--text-muted); display: flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto 10px;">
                                <?php if (!empty($personal['foto_usuario'])): ?>
                                    <img src="../../uploads/personal/fotos/<?php echo $personal['foto_usuario']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-camera" style="font-size: 2em; color: var(--text-muted);"></i>
                                <?php endif; ?>
                            </div>
                            <span class="btn btn-sm btn-outline"><i class="fas fa-upload"></i> Subir Foto</span>
                        </label>
                        <input type="file" name="foto_usuario" id="foto_usuario_input" accept="image/*" style="display: none;" onchange="previewImage(this, 'foto_preview')">
                    </div>

                    <div style="flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="nombre_apellido">Nombre y Apellido *</label>
                            <input type="text" name="nombre_apellido" id="nombre_apellido" required
                                   class="form-control" placeholder="Ej: Juan Pérez"
                                   value="<?php echo htmlspecialchars($personal['nombre_apellido'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="dni">DNI *</label>
                            <input type="text" name="dni" id="dni" required
                                   class="form-control" placeholder="Ej: 30123456"
                                   value="<?php echo htmlspecialchars($personal['dni'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="cuil">CUIL</label>
                            <input type="text" name="cuil" id="cuil"
                                   class="form-control" placeholder="Ej: 20-30123456-7"
                                   value="<?php echo htmlspecialchars($personal['cuil'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                                   class="form-control" value="<?php echo $personal['fecha_nacimiento'] ?? ''; ?>">
                        </div>
                         <div class="form-group">
                            <label for="estado_civil">Estado Civil</label>
                            <select name="estado_civil" id="estado_civil" class="form-control">
                                <option value="">Seleccionar...</option>
                                <option value="Soltero/a" <?php echo ($personal['estado_civil'] ?? '') == 'Soltero/a' ? 'selected' : ''; ?>>Soltero/a</option>
                                <option value="Casado/a" <?php echo ($personal['estado_civil'] ?? '') == 'Casado/a' ? 'selected' : ''; ?>>Casado/a</option>
                                <option value="Divorciado/a" <?php echo ($personal['estado_civil'] ?? '') == 'Divorciado/a' ? 'selected' : ''; ?>>Divorciado/a</option>
                                <option value="Viudo/a" <?php echo ($personal['estado_civil'] ?? '') == 'Viudo/a' ? 'selected' : ''; ?>>Viudo/a</option>
                                <option value="Concubino/a" <?php echo ($personal['estado_civil'] ?? '') == 'Concubino/a' ? 'selected' : ''; ?>>Concubino/a</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="telefono_personal">Teléfono Personal</label>
                        <input type="text" name="telefono_personal" id="telefono_personal"
                               class="form-control" placeholder="Ej: 11-3456-7890"
                               value="<?php echo htmlspecialchars($personal['telefono_personal'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="domicilio">Domicilio</label>
                        <input type="text" name="domicilio" id="domicilio"
                               class="form-control" placeholder="Calle, Número, Localidad"
                               value="<?php echo htmlspecialchars($personal['domicilio'] ?? ''); ?>">
                    </div>
                </div>

                <h4 class="section-title"><i class="fas fa-hard-hat"></i> Asignación Laboral</h4>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="rol">Rol / Cargo *</label>
                        <select name="rol" id="rol" required class="form-control">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?php echo $r; ?>" <?php echo ($personal['rol'] ?? '') === $r ? 'selected' : ''; ?>>
                                    <?php echo $r; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_cuadrilla">Cuadrilla Asignada</label>
                        <select name="id_cuadrilla" id="id_cuadrilla" class="form-control">
                            <option value="">Sin asignar</option>
                            <?php foreach ($cuadrillas as $c): ?>
                                <option value="<?php echo $c['id_cuadrilla']; ?>" 
                                        <?php echo ($personal['id_cuadrilla'] ?? '') == $c['id_cuadrilla'] ? 'selected' : ''; ?>>
                                    <?php echo $c['nombre_cuadrilla']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tareas que desempeña (Selección múltiple)</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 6px; background: var(--bg-tertiary);">
                        <?php foreach ($lista_tareas as $tarea): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0; cursor: pointer;">
                                <input type="checkbox" name="tareas_desempenadas[]" value="<?php echo $tarea; ?>"
                                    <?php echo in_array($tarea, $tareas_seleccionadas) ? 'checked' : ''; ?>>
                                <?php echo $tarea; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_ingreso">Fecha de Ingreso</label>
                        <input type="date" name="fecha_ingreso" id="fecha_ingreso" class="form-control"
                               value="<?php echo $personal['fecha_ingreso'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <!-- TAB: Seguridad / EPP -->
            <div class="tab-content" id="tab-seguridad">
                <h4 class="section-title"><i class="fas fa-car"></i> Habilitación de Conducción</h4>
                
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="check_tiene_carnet" name="tiene_carnet" value="1"
                               <?php echo !empty($personal['tiene_carnet']) ? 'checked' : ''; ?>
                               onchange="toggleCarnetFields()">
                        <label class="custom-control-label" for="check_tiene_carnet" style="font-weight: 600;">¿Tiene Carnet de Conducir?</label>
                    </div>
                </div>

                <div id="carnet_fields" style="display: none; background: var(--bg-tertiary); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px;">
                    <div class="form-row three-cols">
                        <div class="form-group">
                            <label for="tipo_carnet">Tipo de Carnet</label>
                            <select name="tipo_carnet" id="tipo_carnet" class="form-control">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($tipos_carnet as $tc): ?>
                                    <option value="<?php echo $tc; ?>" <?php echo ($personal['tipo_carnet'] ?? '') === $tc ? 'selected' : ''; ?>>
                                        <?php echo $tc; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="vencimiento_carnet_conducir">Vencimiento</label>
                            <input type="date" name="vencimiento_carnet_conducir" id="vencimiento_carnet_conducir" class="form-control"
                                   value="<?php echo $personal['vencimiento_carnet_conducir'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                             <label for="foto_carnet">Foto Carnet</label>
                             <input type="file" name="foto_carnet" id="foto_carnet" class="form-control" accept="image/*">
                             <?php if (!empty($personal['foto_carnet'])): ?>
                                <small><a href="../../uploads/personal/carnets/<?php echo $personal['foto_carnet']; ?>" target="_blank">Ver actual</a></small>
                             <?php endif; ?>
                        </div>
                    </div>
                </div>

                <h4 class="section-title"><i class="fas fa-vest"></i> Elementos de Protección Personal (EPP)</h4>

                <div class="form-row three-cols">
                    <div class="form-group">
                        <label for="talle_camisa">Talle Camisa</label>
                        <select name="talle_camisa" id="talle_camisa" class="form-control">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($talles as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($personal['talle_camisa'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="talle_pantalon">Talle Pantalón</label>
                        <select name="talle_pantalon" id="talle_pantalon" class="form-control">
                             <option value="">Seleccionar...</option>
                            <?php foreach ($talles as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($personal['talle_pantalon'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                         <label for="talle_remera">Talle Remera</label>
                        <select name="talle_remera" id="talle_remera" class="form-control">
                             <option value="">Seleccionar...</option>
                            <?php foreach ($talles as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($personal['talle_remera'] ?? '') === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                 <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="talle_calzado">Talle Calzado</label>
                        <input type="text" name="talle_calzado" id="talle_calzado"
                               class="form-control" placeholder="Ej: 42"
                               value="<?php echo htmlspecialchars($personal['talle_calzado'] ?? ''); ?>">
                    </div>
                     <div class="form-group">
                        <label for="seguro_art">N° Seguro ART</label>
                        <input type="text" name="seguro_art" id="seguro_art"
                               class="form-control" placeholder="Número de póliza"
                               value="<?php echo htmlspecialchars($personal['seguro_art'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_ultima_entrega_epp">Fecha Entrega EPP</label>
                        <input type="date" name="fecha_ultima_entrega_epp" id="fecha_ultima_entrega_epp" class="form-control"
                               value="<?php echo $personal['fecha_ultima_entrega_epp'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                         <label for="planilla_epp">Adjuntar Planilla EPP Firmada</label>
                         <input type="file" name="planilla_epp" id="planilla_epp" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                          <?php if (!empty($personal['planilla_epp'])): ?>
                                <small><a href="../../uploads/personal/epp/<?php echo $personal['planilla_epp']; ?>" target="_blank"><i class="fas fa-file-pdf"></i> Ver planilla cargada</a></small>
                         <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($isEdit): ?>
                <div style="margin-top: 10px;">
                    <a href="generar_planilla_epp.php?id=<?php echo $personal['id_personal']; ?>" target="_blank" class="btn btn-outline btn-sm">
                        <i class="fas fa-print"></i> Generar Planilla EPP para Firmar
                    </a>
                </div>
                <?php endif; ?>

            </div>

             <!-- TAB: Salud -->
            <div class="tab-content" id="tab-salud">
                <h4 class="section-title"><i class="fas fa-hospital-user"></i> Información Médica y Obra Social</h4>

                <div class="form-row two-cols">
                     <div class="form-group">
                        <label for="obra_social">Obra Social</label>
                        <input type="text" name="obra_social" id="obra_social" class="form-control" placeholder="Nombre Obra Social"
                               value="<?php echo htmlspecialchars($personal['obra_social'] ?? ''); ?>">
                    </div>
                     <div class="form-group">
                        <label for="obra_social_telefono">Teléfono Obra Social</label>
                        <input type="text" name="obra_social_telefono" id="obra_social_telefono" class="form-control" placeholder="0800..."
                               value="<?php echo htmlspecialchars($personal['obra_social_telefono'] ?? ''); ?>">
                    </div>
                </div>
                 <div class="form-row">
                     <div class="form-group">
                        <label for="obra_social_lugar_atencion">Lugar de Atención Preferente</label>
                        <input type="text" name="obra_social_lugar_atencion" id="obra_social_lugar_atencion" class="form-control" placeholder="Clínica / Sanatorio..."
                               value="<?php echo htmlspecialchars($personal['obra_social_lugar_atencion'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="grupo_sanguineo">Grupo Sanguíneo</label>
                        <select name="grupo_sanguineo" id="grupo_sanguineo" class="form-control">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($grupos_sanguineos as $gs): ?>
                                <option value="<?php echo $gs; ?>" <?php echo ($personal['grupo_sanguineo'] ?? '') === $gs ? 'selected' : ''; ?>>
                                    <?php echo $gs; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="alergias_condiciones">Alergias / Condiciones Médicas</label>
                        <textarea name="alergias_condiciones" id="alergias_condiciones" rows="3"
                                  class="form-control" placeholder="Describir alergias, medicamentos, condiciones relevantes..."><?php echo htmlspecialchars($personal['alergias_condiciones'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- TAB: Familia -->
            <div class="tab-content" id="tab-familia">
                <h4 class="section-title"><i class="fas fa-users"></i> Grupo Familiar y Emergencias</h4>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="contacto_emergencia_nombre">Nombre Contacto Emergencia</label>
                        <input type="text" name="contacto_emergencia_nombre" id="contacto_emergencia_nombre"
                               class="form-control" placeholder="Nombre Completo"
                               value="<?php echo htmlspecialchars($personal['contacto_emergencia_nombre'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_emergencia">Teléfono Emergencia</label>
                        <input type="text" name="numero_emergencia" id="numero_emergencia"
                               class="form-control" placeholder="Número de contacto"
                               value="<?php echo htmlspecialchars($personal['numero_emergencia'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="contacto_emergencia_parentesco">Parentesco</label>
                        <input type="text" name="contacto_emergencia_parentesco" id="contacto_emergencia_parentesco"
                               class="form-control" placeholder="Ej: Esposa, Padre, Hermano..."
                               value="<?php echo htmlspecialchars($personal['contacto_emergencia_parentesco'] ?? ''); ?>">
                    </div>
                </div>
                
                 <div class="form-group">
                    <label for="personas_a_cargo">Personas a Cargo (Familiares o No)</label>
                    <textarea name="personas_a_cargo" id="personas_a_cargo" rows="4"
                                class="form-control" placeholder="Nombre y Apellido - DNI - Parentesco (Uno por línea)"><?php echo htmlspecialchars($personal['personas_a_cargo'] ?? ''); ?></textarea>
                </div>
            </div>


            <!-- TAB: Administrativo -->
            <div class="tab-content" id="tab-admin">
                <h4 class="section-title"><i class="fas fa-university"></i> Datos Bancarios</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cbu_alias">CBU / Alias</label>
                        <input type="text" name="cbu_alias" id="cbu_alias"
                               class="form-control" placeholder="CBU o Alias para transferencias"
                               value="<?php echo htmlspecialchars($personal['cbu_alias'] ?? ''); ?>">
                    </div>
                </div>

                <h4 class="section-title"><i class="fas fa-link"></i> Documentación Digital</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label for="link_legajo_digital">Link Legajo Digital (Google Drive, etc.)</label>
                        <input type="url" name="link_legajo_digital" id="link_legajo_digital"
                               class="form-control" placeholder="https://drive.google.com/..."
                               value="<?php echo htmlspecialchars($personal['link_legajo_digital'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- TAB: Legajo / Cierre Onboarding -->
            <div class="tab-content" id="tab-legajo">
                <h4 class="section-title"><i class="fas fa-user-check"></i> Finalización de Ingreso</h4>
                
                <div class="alert alert-info" style="background: rgba(100, 181, 246, 0.1); border-left: 4px solid var(--color-info); padding: 15px; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> <strong>Instrucciones:</strong>
                    <ol style="margin: 5px 0 0 20px;">
                        <li>Complete todos los datos del personal.</li>
                        <li>Haga clic en <strong>"Imprimir Ficha de Ingreso"</strong>.</li>
                        <li>Haga firmar la ficha (y declaración H&S) al empleado.</li>
                        <li>Escanee la ficha firmada y súbala aquí para finalizar la carga.</li>
                    </ol>
                </div>

                <div class="form-row two-cols">
                    <div class="form-group">
                        <label for="fecha_examen_preocupacional">Fecha Examen Preocupacional</label>
                        <input type="date" name="fecha_examen_preocupacional" id="fecha_examen_preocupacional" class="form-control"
                               value="<?php echo $personal['fecha_examen_preocupacional'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="empresa_examen_preocupacional">Empresa / Clínica</label>
                        <input type="text" name="empresa_examen_preocupacional" id="empresa_examen_preocupacional" class="form-control" placeholder="Nombre de la Institución"
                               value="<?php echo htmlspecialchars($personal['empresa_examen_preocupacional'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                         <label for="documento_preocupacional">Informe Médico (PDF/IMG)</label>
                         <input type="file" name="documento_preocupacional" id="documento_preocupacional" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                          <?php if (!empty($personal['documento_preocupacional'])): ?>
                                <small><a href="../../uploads/personal/legajos/<?php echo $personal['documento_preocupacional']; ?>" target="_blank"><i class="fas fa-file-medical"></i> Ver Informe Cargado</a></small>
                         <?php endif; ?>
                    </div>
                </div>

                <h4 class="section-title"><i class="fas fa-file-signature"></i> Cierre de Documentación</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_firma_hys">Fecha Firma H&S</label>
                        <input type="date" name="fecha_firma_hys" id="fecha_firma_hys" class="form-control"
                               value="<?php echo $personal['fecha_firma_hys'] ?? ''; ?>">
                    </div>
                    <div class="form-group" style="background: rgba(16, 185, 129, 0.1); padding: 10px; border-radius: 6px; border: 1px solid var(--color-success);">
                         <label for="documento_firmado" style="color: var(--color-success); font-weight: bold;">FICHA DE INGRESO FIRMADA *</label>
                         <input type="file" name="documento_firmado" id="documento_firmado" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                          <?php if (!empty($personal['documento_firmado'])): ?>
                                <small><a href="../../uploads/personal/legajos/<?php echo $personal['documento_firmado']; ?>" target="_blank"><i class="fas fa-file-signature"></i> Ver Ficha Firmada</a></small>
                                <div style="color: var(--color-success); font-weight: bold; margin-top: 5px;"><i class="fas fa-check-circle"></i> Legajo Completo</div>
                         <?php else: ?>
                                <div style="color: var(--color-danger); font-size: 0.9em; margin-top: 5px;"><i class="fas fa-exclamation-triangle"></i> Pendiente de carga</div>
                         <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Estado Actual:</label>
                    <span class="badge" style="background: <?php echo ($personal['estado_documentacion'] ?? 'Incompleto') == 'Completo' ? 'var(--color-success)' : 'var(--color-warning)'; ?>; color: #fff; padding: 5px 10px; border-radius: 4px;">
                        <?php echo $personal['estado_documentacion'] ?? 'Incompleto (Nuevo)'; ?>
                    </span>
                    <?php if (!empty($personal['motivo_pendiente'])): ?>
                        <div style="margin-top: 5px; font-style: italic; color: var(--color-danger);">
                            Motivo Pendiente: "<?php echo htmlspecialchars($personal['motivo_pendiente']); ?>"
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Form Actions (Custom Workflow) -->
             <div class="form-actions" style="justify-content: space-between; align-items: center;">
                <div>
                    <?php if ($isEdit): ?>
                        <a href="generar_ficha_ingreso.php?id=<?php echo $personal['id_personal']; ?>" target="_blank" class="btn btn-outline btn-action-print">
                            <i class="fas fa-print"></i> 1. Imprimir Ficha
                        </a>
                    <?php else: ?>
                        <small style="color: var(--text-muted);">Guarde primero para imprimir la ficha.</small>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <a href="index.php" class="btn btn-outline">Cancelar</a>
                    
                    <!-- Botón Saltar -->
                    <button type="button" class="btn btn-outline btn-action-skip" onclick="openSkipModal()">
                        <i class="fas fa-forward"></i> Saltar / Pendiente
                    </button>

                    <!-- Botón Guardar Normal -->
                    <button type="submit" name="action_type" value="save_partial" class="btn btn-primary" onclick="return validatePartial()">
                        <i class="fas fa-save"></i> Guardar Parcial
                    </button>
                    
                    <!-- Botón Confirmar Final -->
                    <button type="submit" name="action_type" value="finalize" class="btn btn-success btn-action-finalize" onclick="return validateFinalize()">
                        <i class="fas fa-check-circle"></i> Confirmar Carga
                    </button>
                </div>
            </div>

            <!-- Hidden inputs for Skip Logic -->
            <input type="hidden" name="motivo_pendiente" id="hidden_motivo_pendiente">
            <input type="hidden" name="estado_documentacion" id="hidden_estado_documentacion" value="Incompleto">

        </form>
    </div>
</div>

<!-- Modal Motivo Salto -->
<div id="skipModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); padding: 25px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: var(--shadow-lg); border: 1px solid var(--color-warning);">
        <h3 style="margin-top: 0; color: var(--color-warning);"><i class="fas fa-exclamation-triangle"></i> Carga Incompleta</h3>
        <p>Está por dejar este legajo como <strong>PENDIENTE</strong>. Esto generará una <strong>Urgencia</strong> en el panel hasta que se complete.</p>
        
        <div class="form-group">
            <label for="motivo_input">Motivo por el que salta la carga (Obligatorio):</label>
            <textarea id="motivo_input" class="form-control" rows="4" placeholder="Ej: No trajo fotocopia de DNI, falta firmar H&S..."></textarea>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button type="button" class="btn btn-outline" onclick="closeSkipModal()">Cancelar</button>
            <button type="button" class="btn btn-warning" style="background: var(--color-warning); color: #fff;" onclick="confirmSkip()">Confirmar Salto</button>
        </div>
    </div>
</div>

<style>
    /* Estilos Tab */
    .tab-nav { display: flex; gap: 5px; border-bottom: 2px solid rgba(255,255,255,0.1); margin-bottom: 25px; flex-wrap: wrap; }
    .tab-btn { background: none; border: none; padding: 12px 20px; font-size: 0.9em; color: var(--text-secondary); cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
    .tab-btn:hover { color: var(--color-primary); }
    .tab-btn.active { color: var(--color-primary); border-bottom-color: var(--color-primary); font-weight: 600; }
    .tab-content { display: none; animation: fadeIn 0.3s; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* Section & Forms */
    .section-title { color: var(--text-primary); font-size: 1.1em; margin: 25px 0 15px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .form-row { margin-bottom: 15px; display: flex; gap: 20px; flex-wrap: wrap; }
    .form-row.two-cols { display: grid; grid-template-columns: 1fr 1fr; }
    .form-row.three-cols { display: grid; grid-template-columns: 1fr 1fr 1fr; }
    .form-group { flex: 1; min-width: 200px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-secondary); font-size: 0.9em; }
    /* Fix form-control for dark mode default */
    .form-control { width: 100%; padding: 10px 12px; background: var(--bg-tertiary); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; font-size: 0.95em; color: var(--text-primary); }
    .form-control:focus { border-color: var(--accent-primary); outline: none; box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.15); }

    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
    
    /* Toggle Switch */
    .custom-switch { display: flex; align-items: center; gap: 10px; padding-left: 0; }
    .custom-control-input { margin-right: 5px; width: 20px; height: 20px; }

    @media (max-width: 768px) {
        .form-row.two-cols, .form-row.three-cols { grid-template-columns: 1fr; }
    }

    /* Light Mode Overrides (Specifics not handled by vars) */
    [data-theme="light"] .tab-nav { border-bottom-color: #e2e8f0; }
    [data-theme="light"] .section-title { border-bottom-color: #e2e8f0; }
    [data-theme="light"] .form-actions { border-bottom-color: #e2e8f0; }
    [data-theme="light"] .form-control { background: #ffffff; border-color: #d1d5db; color: #333; }
    [data-theme="light"] .checklist-container { border-color: #d1d5db !important; background: #ffffff !important; }

    /* Custom Action Buttons */
    .btn-action-print { 
        border-color: #0284c7; /* var(--color-info) fallback */
        color: #0284c7; 
        background: transparent;
    }
    .btn-action-print:hover {
        background: #0284c7;
        color: #fff !important;
    }

    .btn-action-skip {
        border-color: #d97706; /* var(--color-warning) fallback */
        color: #d97706;
        background: transparent;
    }
    .btn-action-skip:hover {
        background: #d97706;
        color: #000 !important;
    }

    .btn-action-finalize {
        background: #059669; /* var(--color-success) fallback */
        border-color: #059669;
        color: #fff;
    }
    .btn-action-finalize:hover {
        background: #047857;
        border-color: #047857;
    }

    /* Fix Outline Buttons in Light Mode */
    [data-theme="light"] .btn-outline { color: #555; border-color: #ccc; }
    [data-theme="light"] .btn-action-print { color: #0284c7; border-color: #0284c7; }
    [data-theme="light"] .btn-action-print:hover { color: #fff; }
    
    [data-theme="light"] .btn-action-skip { color: #d97706; border-color: #d97706; }
    [data-theme="light"] .btn-action-skip:hover { color: #fff; }
</style>

<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('tab-' + tabId).classList.add('active');
        event.target.closest('.tab-btn').classList.add('active');
    }

    function toggleCarnetFields() {
        const hasCarnet = document.getElementById('check_tiene_carnet').checked;
        document.getElementById('carnet_fields').style.display = hasCarnet ? 'block' : 'none';
        
        // Require fields if checked
        const inputs = document.querySelectorAll('#carnet_fields select, #carnet_fields input[type="date"]');
        inputs.forEach(input => {
            input.required = hasCarnet;
        });
    }

    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(previewId).innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Skip Modal Logic
    function openSkipModal() {
        document.getElementById('skipModal').style.display = 'flex';
    }

    function closeSkipModal() {
        document.getElementById('skipModal').style.display = 'none';
    }

    function confirmSkip() {
        const motivo = document.getElementById('motivo_input').value.trim();
        if (!motivo) {
            alert("Debe ingresar un motivo para saltar la carga.");
            return;
        }
        
        document.getElementById('hidden_motivo_pendiente').value = motivo;
        document.getElementById('hidden_estado_documentacion').value = 'Pendiente';
        
        // Change action type via hidden input injection (since we are triggering submit programmatically)
        const form = document.getElementById('personalForm');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action_type';
        input.value = 'skip';
        form.appendChild(input);
        
        form.submit();
    }

    function validateFinalize() {
        // Validation for "Confirmar Carga"
        const docFirmado = document.getElementById('documento_firmado');
        const fileExists = <?php echo !empty($personal['documento_firmado']) ? 'true' : 'false'; ?>;
        
        if (!fileExists && (!docFirmado.files || docFirmado.files.length === 0)) {
            alert("Para confirmar la carga, DEBE adjuntar la Ficha de Ingreso firmada.");
            showTab('legajo');
            return false;
        }
        return true;
    }

    function validatePartial() {
        return true;
    }

    // Init
    toggleCarnetFields();

    // Check URL parameters for tab
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam) {
        showTab(tabParam);
    }
</script>

<?php require_once '../../includes/footer.php'; ?>