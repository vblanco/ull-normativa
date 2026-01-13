# 📚 Shortcode: Tabla de Contenidos Automática

## Nuevo en v2.0

El plugin ULL Normativa ahora incluye un shortcode para generar **tablas de contenidos automáticas** basadas en los encabezados (h1-h6) del texto de la normativa.

---

## 🎯 Uso Básico

```
[ull_tabla_contenidos]
```

Simplemente añade este shortcode al inicio del contenido de tu normativa y se generará automáticamente una tabla de contenidos con todos los encabezados.

---

## ⚙️ Atributos Disponibles

### `titulo`
- **Descripción**: Título de la tabla de contenidos
- **Por defecto**: "Índice de contenidos"
- **Ejemplo**: `titulo="Índice"`

### `niveles`
- **Descripción**: Niveles de encabezados a incluir (1=h1, 2=h2, etc.)
- **Por defecto**: "1,2,3,4" (incluye h1, h2, h3, h4)
- **Ejemplo**: `niveles="1,2,3"` (solo h1, h2, h3)

### `estilo`
- **Descripción**: Estilo de la lista
- **Valores**: `lista` o `numerado`
- **Por defecto**: "lista"
- **Ejemplo**: `estilo="numerado"`

### `contraer`
- **Descripción**: Permite contraer/expandir la tabla
- **Valores**: `si` o `no`
- **Por defecto**: "no"
- **Ejemplo**: `contraer="si"`

---

## 📝 Ejemplos de Uso

### Ejemplo 1: Tabla Básica
```
[ull_tabla_contenidos]
```
**Resultado**: Tabla con título "Índice de contenidos", niveles h1-h4, estilo lista

### Ejemplo 2: Tabla Personalizada
```
[ull_tabla_contenidos titulo="Contenido" niveles="1,2,3" estilo="numerado"]
```
**Resultado**: Título "Contenido", solo h1-h3, con numeración

### Ejemplo 3: Tabla Colapsable
```
[ull_tabla_contenidos contraer="si"]
```
**Resultado**: Tabla con botón para mostrar/ocultar

### Ejemplo 4: Solo Secciones Principales
```
[ull_tabla_contenidos titulo="Secciones" niveles="1,2"]
```
**Resultado**: Solo muestra h1 y h2

---

## 🎨 Cómo Funciona

### 1. Escaneo de Encabezados
El shortcode busca todos los encabezados HTML en tu contenido:
```html
<h1>Capítulo 1: Introducción</h1>
<h2>Sección 1.1: Objetivo</h2>
<h3>Apartado 1.1.1: Alcance</h3>
```

### 2. Generación de IDs
Automáticamente añade IDs únicos a cada encabezado:
```html
<h1 id="toc-capitulo-1-introduccion-1">Capítulo 1: Introducción</h1>
<h2 id="toc-seccion-1-1-objetivo-2">Sección 1.1: Objetivo</h2>
```

### 3. Creación de Enlaces
Genera enlaces que apuntan a cada sección:
```html
<ul class="ull-toc-list">
  <li><a href="#toc-capitulo-1-introduccion-1">Capítulo 1: Introducción</a>
    <ul>
      <li><a href="#toc-seccion-1-1-objetivo-2">Sección 1.1: Objetivo</a></li>
    </ul>
  </li>
</ul>
```

---

## 📄 Integración con PDF

### ✅ Funciona Automáticamente

Cuando exportas una normativa a PDF que contiene `[ull_tabla_contenidos]`:

1. **El shortcode se procesa** igual que en el frontend
2. **Los enlaces funcionan** en el PDF (si el visor lo soporta)
3. **Los estilos se adaptan** automáticamente para impresión
4. **La tabla se mantiene junta** (no se parte entre páginas)

### Estilos Específicos para PDF

La tabla de contenidos en PDF tiene:
- Fondo gris claro
- Borde en color institucional
- Fuente optimizada para lectura
- Sin botón de contraer/expandir
- Enlaces funcionales (en la mayoría de visores PDF)

---

## 💡 Mejores Prácticas

### ✅ Recomendado

```
✓ Coloca el shortcode al inicio del contenido
✓ Usa encabezados coherentes (h1, h2, h3 en orden)
✓ Escribe títulos descriptivos en los encabezados
✓ Usa niveles=1,2,3 para documentos largos
```

### ❌ Evitar

```
✗ No uses el shortcode múltiples veces en la misma norma
✗ No saltes niveles de encabezados (h1 → h3)
✗ No uses encabezados vacíos
✗ No uses imágenes o HTML complejo en títulos
```

---

## 🏗️ Estructura de Ejemplo

```html
[ull_tabla_contenidos titulo="Índice" niveles="1,2,3"]

<h1>Capítulo I: Disposiciones Generales</h1>
<p>Contenido del capítulo...</p>

<h2>Artículo 1: Objeto</h2>
<p>Texto del artículo...</p>

<h3>Apartado a) Alcance</h3>
<p>Detalle del apartado...</p>

<h2>Artículo 2: Ámbito de Aplicación</h2>
<p>Texto del artículo...</p>

<h1>Capítulo II: Organización</h1>
<p>Contenido del capítulo...</p>

<h2>Artículo 3: Estructura</h2>
<p>Texto del artículo...</p>
```

**Resultado**: Tabla de contenidos con 2 capítulos, múltiples artículos y apartados, todos enlazados.

---

## 🎯 Casos de Uso

### Reglamentos
```
[ull_tabla_contenidos titulo="Índice del Reglamento" niveles="1,2"]
```
Perfecto para reglamentos con capítulos y artículos.

### Normativas Técnicas
```
[ull_tabla_contenidos titulo="Contenido Técnico" niveles="1,2,3,4" estilo="numerado"]
```
Ideal para documentos técnicos con muchos niveles.

### Acuerdos Breves
```
[ull_tabla_contenidos titulo="Puntos del Acuerdo" niveles="1,2"]
```
Para documentos cortos con secciones principales.

### Manuales
```
[ull_tabla_contenidos titulo="Índice del Manual" niveles="1,2,3" contraer="si"]
```
Para manuales largos con opción de ocultar.

---

## 🔧 Personalización Avanzada

### Cambiar Estilos CSS

Si quieres personalizar la apariencia, edita:
```
/assets/css/frontend.css
```

Busca la sección:
```css
/* TABLA DE CONTENIDOS AUTOMÁTICA (v2.0) */
```

### Colores Institucionales

Los estilos usan las variables CSS del tema:
- `--ull-primary`: Color principal
- `--ull-secondary`: Color secundario
- `--ull-border`: Color de bordes

---

## 🐛 Solución de Problemas

### La tabla no aparece

**Causa**: No hay encabezados en el contenido
**Solución**: Añade encabezados h1, h2, h3, etc.

### Los enlaces no funcionan

**Causa**: Los encabezados ya tienen IDs personalizados
**Solución**: El shortcode respeta IDs existentes, verifica que coincidan

### Formato extraño en PDF

**Causa**: Contenido HTML complejo en títulos
**Solución**: Usa solo texto plano en los encabezados

### Tabla muy larga

**Causa**: Muchos encabezados incluidos
**Solución**: Reduce niveles, ej: `niveles="1,2"`

---

## 📊 Comparación de Estilos

### Estilo Lista (`estilo="lista"`)
```
→ Capítulo I: Introducción
  → Sección 1.1: Objetivo
    → Apartado a) Alcance
→ Capítulo II: Desarrollo
```

### Estilo Numerado (`estilo="numerado"`)
```
1. Capítulo I: Introducción
   a. Sección 1.1: Objetivo
      i. Apartado a) Alcance
2. Capítulo II: Desarrollo
```

---

## ✨ Características Destacadas

✅ **Automático**: No necesitas mantener la tabla manualmente
✅ **Responsive**: Se adapta a móviles, tablets y escritorio
✅ **Accesible**: Navegación por teclado y compatible con lectores de pantalla
✅ **PDF Ready**: Funciona perfectamente en exportaciones PDF
✅ **Colapsable**: Opción de mostrar/ocultar
✅ **Personalizable**: Múltiples estilos y configuraciones
✅ **Sin JavaScript**: Funciona incluso con JS desactivado
✅ **Animado**: Transiciones suaves en hover

---

## 📱 Responsive

La tabla de contenidos se adapta automáticamente:

- **Desktop**: Tabla completa con todos los detalles
- **Tablet**: Espaciado ajustado
- **Mobile**: Tamaño de fuente reducido, márgenes optimizados

---

## 🔍 Accesibilidad

El shortcode cumple con WCAG AA:
- ✅ Contraste de colores adecuado
- ✅ Navegación por teclado
- ✅ Etiquetas ARIA cuando corresponde
- ✅ Compatible con lectores de pantalla
- ✅ Foco visible en enlaces

---

## 📚 Recursos Adicionales

- **Documentación general**: Ver `README-v2.0.md`
- **Estilos CSS**: Ver `/assets/css/frontend.css` (línea ~1535)
- **Código fuente**: Ver `/public/class-shortcodes.php` (función `tabla_contenidos_shortcode`)

---

## 💬 Soporte

Si encuentras problemas o tienes sugerencias:
1. Verifica que los encabezados estén bien formados
2. Prueba con diferentes valores de `niveles`
3. Revisa la consola del navegador (F12) por errores
4. Contacta con soporte técnico ULL

---

**Versión**: 2.0.0  
**Fecha**: Diciembre 2025  
**Plugin**: ULL Normativa
