---
name: estilo-marca
description: Define los tokens de diseño, colores, tipografía y voz de marca para mantener consistencia visual en toda la UI y textos de Recursos Globales Business Company.
---

# Skill: Estilo y Marca - Recursos Globales

## Cuándo usar este skill

- Cuando generes cualquier componente de UI (botones, cards, formularios, navegación)
- Cuando escribas textos visibles para el usuario (títulos, labels, mensajes, CTAs)
- Cuando definas estilos CSS o variables de diseño
- Cuando crees mockups, wireframes o diseños visuales
- Cuando necesites validar si un diseño cumple con la identidad de marca

## Inputs necesarios

- Tipo de elemento a diseñar (componente UI, texto, página completa)
- Contexto de uso (landing, dashboard, formulario, etc.)
- Nivel de jerarquía visual requerido

## Tokens de Diseño

### Paleta de Colores

#### Colores Primarios (Azules de Marca)
| Token | HEX | Uso |
|-------|-----|-----|
| `--color-primary-dark` | #004A7F | Fondos principales, headers, énfasis fuerte |
| `--color-primary` | #0073A8 | Botones primarios, links activos, acentos |
| `--color-primary-light` | #009FD7 | Hover states, iconos, elementos secundarios |

#### Colores Neutros (Grises)
| Token | HEX | Uso |
|-------|-----|-----|
| `--color-neutral-dark` | #333333 | Texto principal, headings |
| `--color-neutral` | #666666 | Texto secundario, subtítulos |
| `--color-neutral-light` | #AAAAAA | Placeholders, bordes, texto deshabilitado |

#### Colores Semánticos
| Token | HEX | Uso |
|-------|-----|-----|
| `--color-success` | #28A745 | Estados exitosos, confirmaciones |
| `--color-warning` | #FFC107 | Alertas, precauciones |
| `--color-danger` | #DC3545 | Errores, acciones destructivas |
| `--color-background` | #F5F5F5 | Fondo general de la aplicación |
| `--color-surface` | #FFFFFF | Cards, modales, superficies elevadas |

### Tipografía

#### Escala Tipográfica
| Token | Tamaño | Peso | Uso |
|-------|--------|------|-----|
| `--font-heading-1` | 60px / 3.75rem | Bold | Títulos principales de página |
| `--font-heading-2` | 36px / 2.25rem | SemiBold | Secciones principales |
| `--font-heading-3` | 24px / 1.5rem | Medium | Subsecciones, títulos de cards |
| `--font-body` | 16px / 1rem | Regular | Texto de párrafos, contenido |
| `--font-small` | 14px / 0.875rem | Regular | Labels, captions, metadata |
| `--font-micro` | 12px / 0.75rem | Regular | Disclaimers, footnotes |

#### Familia Tipográfica
- **Primaria:** Sans-serif moderna (Inter, Roboto, o sistema)
- **Fallback:** system-ui, -apple-system, sans-serif

### Espaciado

| Token | Valor | Uso |
|-------|-------|-----|
| `--spacing-xs` | 4px | Separación mínima entre elementos inline |
| `--spacing-sm` | 8px | Padding interno de inputs, gaps pequeños |
| `--spacing-md` | 16px | Padding de cards, separación estándar |
| `--spacing-lg` | 24px | Separación entre secciones |
| `--spacing-xl` | 32px | Márgenes de contenedor principal |
| `--spacing-2xl` | 48px | Separación entre bloques mayores |

### Bordes y Sombras

| Token | Valor | Uso |
|-------|-------|-----|
| `--border-radius-sm` | 4px | Inputs, badges |
| `--border-radius-md` | 8px | Botones, cards |
| `--border-radius-lg` | 16px | Modales, containers grandes |
| `--shadow-sm` | 0 1px 3px rgba(0,0,0,0.12) | Cards, elementos elevados |
| `--shadow-md` | 0 4px 12px rgba(0,0,0,0.15) | Dropdowns, popovers |
| `--shadow-lg` | 0 8px 24px rgba(0,0,0,0.2) | Modales, overlays |

## Voz de Marca

### Tono
- **Profesional pero accesible:** Evitar jerga excesiva, ser claro y directo
- **Confiable:** Usar lenguaje que transmita seguridad y competencia
- **Global:** Vocabulario neutral, evitar regionalismos extremos

### Guías de Copywriting
1. **Títulos:** Concisos, orientados a acción o beneficio
2. **CTAs:** Verbos en infinitivo o imperativo suave ("Ver más", "Guardar cambios")
3. **Mensajes de error:** Explicar qué pasó + cómo solucionarlo
4. **Mensajes de éxito:** Breves y afirmativos ("Guardado correctamente")

## Workflow de Aplicación

1. **Identificar el contexto:** ¿Es UI, texto, o ambos?
2. **Seleccionar tokens:** Consultar las tablas de esta guía
3. **Aplicar jerarquía:** Usar la escala tipográfica correcta
4. **Validar contraste:** Asegurar legibilidad (WCAG AA mínimo)
5. **Revisar consistencia:** Comparar con componentes existentes

## Instrucciones de Uso

### Para CSS/Estilos
```css
:root {
  /* Colores Primarios */
  --color-primary-dark: #004A7F;
  --color-primary: #0073A8;
  --color-primary-light: #009FD7;
  
  /* Colores Neutros */
  --color-neutral-dark: #333333;
  --color-neutral: #666666;
  --color-neutral-light: #AAAAAA;
  
  /* Tipografía */
  --font-family: 'Inter', system-ui, -apple-system, sans-serif;
  --font-heading-1: 3.75rem;
  --font-heading-2: 2.25rem;
  --font-heading-3: 1.5rem;
  --font-body: 1rem;
}
```

### Para Componentes UI
- Botón primario: fondo `--color-primary`, texto blanco, hover `--color-primary-dark`
- Botón secundario: borde `--color-primary`, fondo transparente, texto `--color-primary`
- Cards: fondo `--color-surface`, sombra `--shadow-sm`, border-radius `--border-radius-md`

## Manejo de Conflictos

1. Si un color solicitado no está en la paleta → usar el más cercano de la paleta
2. Si un tamaño tipográfico no coincide → redondear al token más cercano
3. Si hay duda sobre el tono → preferir profesional sobre casual
4. Si falta información de marca → **preguntar al usuario antes de inventar**

## Output Esperado

Cuando apliques este skill, el resultado debe:
- Usar exclusivamente los tokens definidos (nunca colores hardcodeados)
- Mantener la jerarquía tipográfica consistente
- Respetar el espaciado definido
- Seguir las guías de voz para todo texto visible

## Recursos de Referencia

Los siguientes archivos contienen los assets visuales de la marca:
- `recursos/logo.png` - Logo oficial de Recursos Globales
- `recursos/paleta-colores.png` - Paleta de colores visual
- `recursos/tipografia.png` - Guía tipográfica visual
