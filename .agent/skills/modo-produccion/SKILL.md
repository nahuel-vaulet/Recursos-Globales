---
name: modo-produccion
description: Revisa una app/landing, detecta problemas típicos, propone mejoras y aplica correcciones con un checklist fijo para dejarlo listo para enseñar o publicar.
---

# Modo Producción (QA + Fix)

## Cuándo usar esta habilidad

- Cuando ya tienes algo generado (landing/app) y quieres dejarlo "presentable"
- Cuando algo funciona "a medias" (móvil raro, imágenes rotas, botones sin acción, espaciados feos)
- Antes de enseñarlo a un cliente, grabarlo o publicarlo
- Cuando se mencione "revisa esto", "arregla los detalles", "déjalo listo para producción"

## Inputs necesarios

| Input | Obligatorio | Descripción |
|-------|-------------|-------------|
| Archivo principal | ✅ Sí | Ruta del archivo (ej: `index.html`) |
| Objetivo de revisión | ✅ Sí | "Lista para enseñar" o "lista para publicar" |
| Restricciones | ⚠️ Parcial | No cambiar branding / copy / estructura |

**Regla:** Si falta un input obligatorio, preguntar antes de revisar.

## Checklist de calidad (orden fijo)

### A) Funciona y se ve
- [ ] Abre la preview / localhost sin errores
- [ ] Imágenes cargan y no hay rutas rotas
- [ ] Tipografías y estilos se aplican correctamente
- [ ] Console sin errores críticos de JS

### B) Responsive (móvil primero)
- [ ] Se ve bien en móvil (no se corta, no hay scroll horizontal)
- [ ] Botones y textos tienen tamaños legibles (mínimo 16px body)
- [ ] Secciones con espaciado coherente
- [ ] Touch targets de al menos 44x44px

### C) Copy y UX básica
- [ ] Titular claro y coherente con la propuesta
- [ ] CTAs consistentes (mismo verbo, misma intención)
- [ ] No hay texto "placeholder" tipo lorem ipsum
- [ ] Flujo de navegación lógico

### D) Accesibilidad mínima
- [ ] Contraste razonable en textos (WCAG AA)
- [ ] Imágenes con alt descriptivo
- [ ] Estructura de headings (h1, h2) lógica
- [ ] Focus visible en elementos interactivos

## Workflow

### 1. Diagnóstico rápido
- Abrir el proyecto/archivo
- Listar problemas en 5–10 bullets (priorizados por impacto)

### 2. Plan de arreglos
- Máximo 8 cambios
- Formato: "qué cambio → por qué"

### 3. Aplicar cambios
- Modificar los archivos necesarios
- Respetar restricciones del usuario

### 4. Validación
- Volver a abrir preview
- Confirmar checklist pasado

### 5. Resumen final
- Cambios hechos (lista corta)
- Qué queda opcional para mejorar

## Reglas

1. **Respetar marca:** No cambies el estilo si existe skill `estilo-marca` activo
2. **Mínimo viable:** Corrige lo mínimo para ganar calidad rápido, no rehagas todo
3. **Claridad > Bonito:** Si hay conflicto, prioriza claridad
4. **Preguntar antes:** Si algo requiere decisión de diseño, consultar

## Output (formato exacto)

```markdown
## Diagnóstico (priorizado)

| # | Problema | Impacto | Categoría |
|---|----------|---------|-----------|
| 1 | [Descripción] | 🔴 Alto / 🟡 Medio / 🟢 Bajo | A/B/C/D |
| 2 | ... | ... | ... |

---

## Plan de arreglos

| Cambio | Por qué |
|--------|---------|
| [Qué cambio] | [Razón] |
| ... | ... |

---

## Cambios aplicados

- ✅ [Cambio 1 aplicado]
- ✅ [Cambio 2 aplicado]
- ...

---

## Resultado

**Estado:** ✅ OK para enseñar / ✅ OK para publicar

**Notas:**
- [Observación opcional]
- [Mejora futura sugerida]

**Checklist pasado:**
- [x] Funciona y se ve
- [x] Responsive
- [x] Copy y UX
- [x] Accesibilidad mínima
```

## Manejo de errores

- Si hay demasiados problemas → priorizar los 8 más críticos, listar el resto como "pendientes"
- Si las restricciones impiden arreglar algo importante → informar al usuario
- Si el proyecto no abre → diagnosticar error antes de continuar
- Si hay conflicto con skill de marca → respetar tokens de `estilo-marca`
