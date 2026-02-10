---
name: planificacion-pro
description: Convierte una idea en un plan ejecutable por fases, con checklist, riesgos y entregables. Úsalo cuando haya que pasar de idea a acción sin improvisar.
---

# Planificación Pro

## Cuándo usar esta habilidad

- Cuando el usuario pida un plan paso a paso, una estrategia o una hoja de ruta
- Cuando haya que entregar algo (landing, vídeo, proyecto, lanzamiento) con tiempos
- Cuando el usuario tenga muchas tareas sueltas y quiera ordenarlas
- Cuando se mencione "cómo hago esto", "necesito un plan para...", "organízame esto"

## Inputs necesarios

| Input | Obligatorio | Descripción |
|-------|-------------|-------------|
| Resultado final | ✅ Sí | Qué significa "terminado" |
| Fecha límite o ritmo | ✅ Sí | Hoy, esta semana, sin prisa |
| Recursos disponibles | ⚠️ Parcial | Herramientas, equipo, presupuesto, tiempo diario |
| Criterios de éxito | ⚠️ Parcial | Qué debe cumplir para estar bien |

**Regla:** Si falta un input obligatorio, preguntar antes de planificar.

## Workflow

### 1. Definir resultado final
- Escribir el resultado en 1 frase clara
- Listar 3 criterios de éxito medibles

### 2. Dividir en fases (máximo 4)

| Fase | Descripción |
|------|-------------|
| **Preparación** | Investigación, recursos, setup inicial |
| **Producción / Ejecución** | Trabajo principal, desarrollo |
| **Revisión / QA** | Validación, correcciones, feedback |
| **Publicación / Entrega** | Deploy, lanzamiento, handoff |

### 3. Detallar cada fase
Para cada fase incluir:
- **Tareas en orden** (secuencia lógica)
- **Entregable claro** (qué sale de esa fase)
- **Tiempo estimado** por tarea (aproximado)
- **Dependencias** si las hay

### 4. Identificar riesgos
Añadir 3–5 riesgos con mitigación:
> "Si pasa X → hago Y"

### 5. Checklist final
Crear lista de verificación antes de dar por terminado.

## Reglas de calidad

1. **Evitar planes infinitos:** Priorizar lo que desbloquea lo siguiente
2. **Marcar dependencias:** "Esto depende de X"
3. **Adaptar al nivel:**
   - Principiante → menos pasos, opciones simples
   - Avanzado → optimizaciones y atajos
4. **Tiempos realistas:** Mejor pasarse que quedarse corto

## Output (formato exacto)

```markdown
## Resultado final
[1 frase clara de qué significa "terminado"]

### Criterios de éxito
- [ ] [Criterio 1 medible]
- [ ] [Criterio 2 medible]
- [ ] [Criterio 3 medible]

---

## Plan por fases

### Fase 1: Preparación
| Tarea | Tiempo estimado | Entregable |
|-------|-----------------|------------|
| [Tarea 1] | [Xh / Xd] | [Output] |
| [Tarea 2] | [Xh / Xd] | [Output] |

### Fase 2: Producción / Ejecución
| Tarea | Tiempo estimado | Entregable |
|-------|-----------------|------------|
| [Tarea 1] | [Xh / Xd] | [Output] |
| [Tarea 2] | [Xh / Xd] | [Output] |

### Fase 3: Revisión / QA
| Tarea | Tiempo estimado | Entregable |
|-------|-----------------|------------|
| [Tarea 1] | [Xh / Xd] | [Output] |

### Fase 4: Publicación / Entrega
| Tarea | Tiempo estimado | Entregable |
|-------|-----------------|------------|
| [Tarea 1] | [Xh / Xd] | [Output] |

**Tiempo total estimado:** [X horas / X días]

---

## Riesgos y mitigación

| Riesgo | Mitigación |
|--------|------------|
| Si [pasa X] | → [hago Y] |
| Si [pasa X] | → [hago Y] |
| Si [pasa X] | → [hago Y] |

---

## Checklist final

- [ ] [Verificación 1]
- [ ] [Verificación 2]
- [ ] [Verificación 3]
- [ ] [Verificación 4]
- [ ] [Verificación 5]
```

## Manejo de errores

- Si el plan es demasiado largo → dividir en sub-proyectos
- Si faltan recursos → proponer alternativas con menos dependencias
- Si el usuario rechaza el plan → pedir feedback específico sobre qué ajustar
- Si hay ambigüedad en el resultado → preguntar antes de planificar
