# Plan de Implementación: Módulo de Gestión de Tareas y Calendario

## Objetivo
Crear un módulo CRUD de "Tareas" que permita:
1.  Crear tareas simples y recurrentes (Diarias, Semanales, Mensuales).
2.  Visualizar tareas en un Calendario.
3.  Integrar tareas urgentes en el Dashboard Gerencial existente.

## Esquema de Base de Datos
Necesitamos dos estructuras principales:

### 1. `tareas_definicion` (Plantillas de recurrencia)
Define "qué" hay que hacer y "cada cuánto".
- `id_definicion`
- `titulo`
- `descripcion`
- `prioridad` (Normal, Urgente)
- `tipo_recurrencia` (Unica, Diaria, Semanal, Mensual)
- `parametro_recurrencia` (ej: 'Monday' para semanal, '5' para día del mes)
- `fecha_inicio`
- `fecha_fin` (opcional)
- `ultimo_generado` (fecha de la última instancia generada)

### 2. `tareas_instancia` (Tareas reales)
Son las tareas que aparecen en el calendario y dashboard.
- `id_tarea`
- `id_definicion` (FK)
- `titulo` (Copiado de definición)
- `fecha_vencimiento`
- `estado` (Pendiente, Completada, Cancelada)
- `prioridad`

## Frontend (UI/UX)
- **Ruta:** `modules/tareas/`
- **`index.php`**: Vista dual.
    - Tab 1: Lista CRUD (DataTables/Cards).
    - Tab 2: Calendario (Simple grid month view o FullCalendar si está disponible, asumo vanilla grid para ser ligero).
- **`form.php`**: Formulario para crear tarea y configurar recurrencia.

## Backend (Lógica de Generación)
- Al entrar al módulo o mediante un "cron" simulado en el login:
    - Revisar `tareas_definicion`.
    - Si `tipo_recurrencia` != 'Unica' y `ultimo_generado` + intervalo <= Hoy:
        - Generar nueva `tareas_instancia`.
        - Actualizar `ultimo_generado`.

## Integración Dashboard
- Modificar `modules/reportes/api/get_dashboard_kpis.php`:
    - En sección "Urgentes": Hacer `UNION` de `odt_maestro` (Urgentes) con `tareas_instancia` (Urgentes + Pendientes + Fecha <= Hoy).
    - En sección "Bandeja": `UNION` similar.

## Paso a Paso
1.  Crear tablas SQL (`sql/create_tasks_tables.sql`).
2.  Crear estructura de carpetas `modules/tareas`.
2.- [x] Create `modules/cuadrillas/herramientas.php`
- [x] Create `modules/cuadrillas/generar_responsabilidad.php`
- [ ] REFACTOR: Switch to Global `tipos_trabajos` table
    - [ ] Update `cuadrilla_tipos_trabajo` schema (FK to `id_tipologia`)
    - [ ] Update `form.php` to source from `tipos_trabajos`
    - [ ] Update `save.php` logic
    - [ ] Update `index.php` query
3.  Implementar `form.php` (Alta de tareas).
4.  Implementar lógica de generación de instancias (`includes/tasks_generator.php`).
5.  Implementar `index.php` con vista Calendario.
6.  Actualizar API del Dashboard.
