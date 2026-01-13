# Changelog - ULL Normativa

## [2.5.0] - 2024-12-25

### ✨ Añadido

#### Exportación PDF Unificada
- Nueva clase `ULL_Unified_PDF_Export` que unifica la generación de PDF para normas y códigos
- Soporte para logos personalizados en la cabecera del PDF
- Configuración de colores personalizables:
  - Color de fondo de cabecera
  - Color de texto de cabecera
  - Color de títulos en el contenido
- Opción para mostrar/ocultar fecha de generación en el pie de página
- Configuración centralizada de cabecera y pie de página personalizable

#### Configuración PDF Mejorada
- Campo de carga de logo mediante el Media Library de WordPress
- Selectores de color para personalización visual
- Vista previa del logo seleccionado
- Validación y sanitización de colores hexadecimales
- Valores por defecto institucionales de la ULL

### 🔄 Cambiado

#### Estructura de Menús
- **BREAKING**: Eliminado submenú "Exportación PDF" de `ull-normativa-pdf`
- **BREAKING**: Eliminado submenú "PDF" bajo el post type "norma"
- Consolidado en un único submenú "Configuración PDF" bajo el menú principal "Normativa"
- Mejor organización y accesibilidad de las opciones de configuración

#### Gestión de Configuración
- Estructura de opciones actualizada en `ull_pdf_options`:
  - Márgenes ahora se guardan como array en lugar de valores individuales
  - Nuevos campos: `header_logo`, `header_bg_color`, `header_text_color`, `title_color`, `show_generation_date`
  - Renombrados: `orientation` → `pdf_orientation`, `paper_size` → `pdf_paper_size`, `font_size` → `pdf_font_size`
- Valores por defecto mejorados con textos institucionales

#### Generación de PDF
- Tabla de contenidos ahora se genera de forma unificada
- Mejor procesamiento de relaciones normativas
- Estilos CSS más robustos y personalizables
- Soporte mejorado para tablas con estilos inline

### 🐛 Corregido

- Eliminada duplicación de tabla de contenidos en PDFs de códigos
- Corregida la eliminación incompleta del shortcode `[ull_tabla_contenidos]`
- Mejorada la normalización de atributos HTML `bgcolor` y `width` para DOMPDF
- Corregida la preservación de estilos inline en tablas
- Mejor manejo de encabezados vacíos en la generación de TOC

### 🗑️ Deprecado

Los siguientes archivos ya no se utilizan pero se mantienen por compatibilidad:
- `/includes/class-pdf-export.php`
- `/codigos/includes/class-pdf-export.php`
- `/codigos/admin/class-admin-settings.php` (configuración PDF)

**Nota**: Estos archivos se eliminarán en la versión 3.0.0

### 📝 Notas de Migración

#### Para Desarrolladores

Si has personalizado las clases de exportación PDF:

**Antes:**
```php
$pdf_export = new ULL_PDF_Export();
$url = ULL_PDF_Export::get_pdf_url($post_id);
```

**Ahora:**
```php
$pdf_export = new ULL_Unified_PDF_Export();
$url = ULL_Unified_PDF_Export::get_pdf_url($post_id);
```

#### Para Usuarios

1. La configuración existente se migra automáticamente
2. Revisa **Normativa > Configuración PDF** para personalizar:
   - Logo institucional
   - Colores corporativos
   - Textos de cabecera y pie
3. El menú "Exportación PDF" anterior ya no está disponible

### 🔧 Cambios Internos

#### Arquitectura
- Eliminación de código duplicado (~800 líneas)
- Mejor separación de responsabilidades
- Código más mantenible y testeable
- Documentación inline mejorada

#### Rendimiento
- Reducción de consultas a base de datos
- Mejor cacheo de configuración
- Generación de HTML más eficiente

#### Seguridad
- Validación mejorada de inputs del usuario
- Sanitización consistente de opciones
- Uso de `sanitize_hex_color()` para colores
- Escapado seguro de salidas HTML

### 📦 Dependencias

- WordPress: >= 5.6
- PHP: >= 7.4
- DOMPDF: Compatible con versiones instaladas vía Composer o instalador del plugin

### 🧪 Testing

Se recomienda probar:
- [ ] Generación de PDF de normas individuales
- [ ] Generación de PDF de códigos (colecciones)
- [ ] Exportación XML de normas
- [ ] Configuración de logo personalizado
- [ ] Cambio de colores corporativos
- [ ] Diferentes configuraciones de papel y orientación
- [ ] Visualización en diferentes navegadores

---

## [2.4.0] - Versiones anteriores

Ver `README-v2.0.md` y `README-DOMPDF.md` para cambios en versiones anteriores.
