---
name: creador-de-skills
description: Genera nuevos skills para Antigravity con estructura estándar, YAML válido, workflow claro y formato de salida predecible. Usar cuando el usuario pida crear o documentar un skill nuevo.
---

# Skill: Creador de Skills para Antigravity

## Cuándo usar este skill

- Cuando el usuario pida "créame un skill para X"
- Cuando se necesite convertir un prompt largo en un procedimiento reutilizable
- Cuando el usuario quiera documentar un proceso repetitivo como skill
- Cuando se requiera estandarizar un formato o workflow recurrente
- Cuando el usuario mencione "quiero automatizar" o "quiero que siempre hagas X de esta forma"

## Inputs necesarios

| Input | Obligatorio | Descripción |
|-------|-------------|-------------|
| Nombre del skill | ✅ Sí | Nombre corto, lowercase, con guiones (máx. 40 chars) |
| Objetivo del skill | ✅ Sí | Qué problema resuelve o qué tarea automatiza |
| Triggers de activación | ✅ Sí | Cuándo debe activarse automáticamente |
| Pasos del workflow | ⚠️ Parcial | Si no se dan, inferir de la descripción |
| Recursos adicionales | ❌ No | Solo si aportan valor real (plantillas, ejemplos) |

**Regla:** Si falta un input obligatorio, **preguntar antes de generar**.

## Workflow

### Fase 1: Plan
1. Confirmar nombre del skill (validar formato: lowercase, guiones, ≤40 chars)
2. Definir objetivo en una frase clara
3. Identificar triggers específicos de activación
4. Listar inputs que el skill necesitará

### Fase 2: Validación
5. Verificar que no exista un skill duplicado en `.agent/skills/`
6. Determinar nivel de libertad:
   - **Alta** → brainstorming, ideas (heurísticas)
   - **Media** → documentos, copys (plantillas)
   - **Baja** → scripts, cambios técnicos (pasos exactos)

### Fase 3: Ejecución
7. Crear carpeta `.agent/skills/<nombre-del-skill>/`
8. Generar `SKILL.md` con estructura completa
9. Crear recursos opcionales solo si aportan valor

### Fase 4: Revisión
10. Validar que el YAML frontmatter sea correcto
11. Confirmar que el workflow tiene 3-6 pasos (simple) o fases (complejo)
12. Verificar que el output está claramente definido

## Checklist de Calidad

Antes de entregar el skill, verificar:

- [ ] El `name` es lowercase, con guiones, ≤40 caracteres
- [ ] La `description` está en español, tercera persona, ≤220 caracteres
- [ ] Los triggers son concretos y reconocibles
- [ ] El workflow tiene pasos claros y numerados
- [ ] El formato de output está definido exactamente
- [ ] No hay archivos innecesarios (mantener estructura mínima)

## Instrucciones de Generación

### Estructura de carpeta
```
.agent/skills/<nombre-del-skill>/
├── SKILL.md           (obligatorio)
├── recursos/          (opcional - guías, plantillas, tokens)
├── scripts/           (opcional - utilidades ejecutables)
└── ejemplos/          (opcional - implementaciones de referencia)
```

### Plantilla de SKILL.md
```markdown
---
name: <nombre-en-lowercase-con-guiones>
description: <descripción en español, tercera persona, máx 220 chars>
---

# Skill: <Título Descriptivo>

## Cuándo usar este skill
- <trigger 1>
- <trigger 2>

## Inputs necesarios
| Input | Obligatorio | Descripción |
|-------|-------------|-------------|
| ... | ✅/❌ | ... |

## Workflow
1. <paso>
2. <paso>
3. <paso>

## Instrucciones
<reglas específicas del skill>

## Output (formato exacto)
<definir estructura exacta: lista, tabla, JSON, markdown, etc.>

## Manejo de errores
- Si el output no cumple el formato → volver al paso 2, ajustar y re-generar
- Si hay ambigüedad → preguntar antes de asumir
- Si falta información crítica → solicitar al usuario
```

## Output (formato exacto)

Cuando generes un skill, la respuesta debe tener este formato:

```
### Carpeta
`.agent/skills/<nombre-del-skill>/`

### SKILL.md
[contenido completo del archivo]

### Recursos (si aplica)
- `recursos/<archivo>.md` - <descripción>
- `scripts/<archivo>.sh` - <descripción>
```

## Niveles de Libertad por Tipo de Skill

| Tipo de Skill | Nivel | Ejemplo |
|---------------|-------|---------|
| Brainstorming, ideas | Alta libertad | `generar-ideas-contenido` |
| Documentos, copys | Media libertad | `escribir-emails-cliente` |
| Scripts, comandos | Baja libertad | `deploy-produccion` |
| Auditorías, validaciones | Media-Baja | `auditar-landing` |
| Diseño, UI | Media (con tokens) | `estilo-marca` |

**Regla:** Cuanto más riesgo tiene la operación, más específico debe ser el skill.

## Manejo de errores

1. **Output incorrecto:** Volver a la Fase 2, revisar restricciones, re-generar
2. **Ambigüedad en inputs:** Preguntar al usuario antes de asumir
3. **Skill duplicado:** Proponer actualizar el existente o crear variante
4. **Nombre inválido:** Sugerir alternativa válida (lowercase, guiones, ≤40 chars)

## Skills sugeridos (para inspiración)

Si el usuario no sabe qué skill crear, sugerir:

- `estilo-marca` → tokens de diseño y voz de marca
- `planificar-video` → estructura de guiones y storyboards
- `auditar-landing` → checklist de revisión de páginas
- `debug-aplicacion` → pasos sistemáticos de debugging
- `responder-emails` → plantillas con tono de marca
- `documentar-api` → formato estándar de documentación
- `crear-componente` → estructura para nuevos componentes UI
