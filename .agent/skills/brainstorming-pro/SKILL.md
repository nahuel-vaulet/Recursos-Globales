---
name: brainstorming-pro
description: Genera ideas de calidad con estructura, filtros y selección final. Úsalo cuando necesites opciones creativas con criterio y una recomendación clara.
---

# Brainstorming Pro

## Cuándo usar esta habilidad

- Cuando el usuario pida ideas, variantes, conceptos, hooks, nombres, formatos o enfoques
- Cuando haya bloqueo creativo o demasiadas opciones y haga falta ordenar
- Cuando el usuario necesite ideas "buenas para ejecutar", no solo ocurrencias
- Cuando se mencione "dame opciones", "qué alternativas hay", "necesito ideas para..."

## Inputs necesarios

| Input | Obligatorio | Descripción |
|-------|-------------|-------------|
| Objetivo exacto | ✅ Sí | Qué se quiere conseguir |
| Público / contexto | ✅ Sí | Para quién es y dónde se usa |
| Restricciones | ⚠️ Parcial | Tiempo, presupuesto, tono, formato, herramientas |
| Ejemplos SÍ/NO | ❌ No | Preferencias del usuario si las tiene |

**Regla:** Si falta un input obligatorio, hacer 3-5 preguntas rápidas antes de generar.

## Workflow

### 1. Aclarar el encargo
Formular 3–5 preguntas rápidas (solo si faltan datos críticos).

### 2. Generar ideas en 4 tandas

| Tanda | Cantidad | Enfoque |
|-------|----------|---------|
| **A) Rápidas** | 10 ideas | Claras y ejecutables |
| **B) Diferentes** | 5 ideas | Ángulos no obvios |
| **C) Low effort** | 5 ideas | Rápidas de producir |
| **D) High impact** | 3 ideas | Más ambiciosas, más potentes |

### 3. Filtrar y puntuar
Evaluar cada idea del 1–5 en:
- **Impacto** → ¿Mueve la aguja?
- **Claridad** → ¿Se entiende al instante?
- **Novedad** → ¿Es fresco o ya visto?
- **Esfuerzo** → ¿Cuánto cuesta producirlo?
- **Viabilidad** → ¿Se puede hacer con los recursos disponibles?

### 4. Seleccionar TOP 5
Entregar las 5 mejores ideas con:
- Idea (1 línea)
- Por qué funciona (2 líneas)
- Primer paso (1 línea)

## Reglas de calidad

1. **Nada genérico:** Prohibido "mejorar tu productividad". Concretar siempre.
2. **Hooks/títulos:** Cortos, con tensión o curiosidad.
3. **Formatos:** Incluir estructura + ejemplo de primer minuto.
4. **Incertidumbre:** Si una idea depende de algo incierto, decirlo y ofrecer alternativa.

## Output (formato exacto)

```markdown
## Preguntas rápidas
(Solo si faltan datos)
1. ...
2. ...

---

## Ideas generadas

### A) Ideas rápidas (10)
1. [Idea concreta y ejecutable]
2. ...

### B) Ideas diferentes (5)
1. [Ángulo no obvio]
2. ...

### C) Ideas low effort (5)
1. [Rápida de producir]
2. ...

### D) Ideas high impact (3)
1. [Ambiciosa y potente]
2. ...

---

## TOP 5 recomendado

| # | Idea | Puntuación | Por qué funciona | Primer paso |
|---|------|------------|------------------|-------------|
| 1 | [Idea] | ⭐ 4.5 | [2 líneas] | [1 línea] |
| 2 | ... | ... | ... | ... |
| 3 | ... | ... | ... | ... |
| 4 | ... | ... | ... | ... |
| 5 | ... | ... | ... | ... |
```

## Manejo de errores

- Si el output es genérico → volver al paso 2, añadir restricciones específicas
- Si hay ambigüedad en el objetivo → preguntar antes de generar
- Si el usuario rechaza todas las ideas → pedir feedback específico y regenerar tanda B
