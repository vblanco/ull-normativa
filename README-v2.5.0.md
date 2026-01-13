# ULL Normativa - Versión 2.5.0

## Cambios Principales

Esta versión unifica y mejora el sistema de exportación a PDF, eliminando duplicidades y añadiendo opciones de configuración avanzadas.

### 🔄 Unificación de Exportación PDF

**Antes:**
- Dos clases separadas para exportación PDF:
  - `ULL_PDF_Export` (para normas)
  - `ULL_Codigos_PDF_Export` (para códigos)
- Duplicación de código y funcionalidad

**Ahora:**
- Una sola clase unificada: `ULL_Unified_PDF_Export`
- Maneja tanto normas individuales como códigos (colecciones de normas)
- Código más mantenible y consistente
- Misma apariencia y funcionalidad para ambos tipos de documentos

### 🎨 Nuevas Opciones de Configuración PDF

Se han añadido las siguientes opciones configurables en **Normativa > Configuración PDF**:

#### Cabecera del PDF
- **Logo de cabecera**: Subir un logo personalizado para la cabecera
- **Texto de cabecera**: Texto personalizable (por defecto: "Universidad de La Laguna")
- **Color de fondo de cabecera**: Color del fondo de la barra de cabecera
- **Color de texto de cabecera**: Color del texto en la cabecera
- **Color de títulos**: Color de los títulos principales en el documento

#### Pie de Página
- **Texto de pie de página**: Texto personalizable para el pie
- **Mostrar fecha de generación**: Opción para incluir/excluir la fecha y hora de generación

#### Configuración General
- **Orientación**: Vertical u horizontal
- **Tamaño de papel**: A4, A3, Letter, Legal
- **Tamaño de fuente**: 8-20 puntos
- **Márgenes**: Configuración independiente para superior, inferior, izquierdo y derecho
- **Tabla de contenidos**: Activar/desactivar
- **Números de página**: Activar/desactivar

### 🗂️ Reorganización del Menú de Administración

**Antes:**
- Menú "Exportación PDF" en el menú principal de Normativa
- Menú "PDF" bajo el post type "norma"
- Configuración PDF dispersa en múltiples lugares

**Ahora:**
- Un único menú "Configuración PDF" bajo **Normativa** en el menú principal
- Configuración centralizada y unificada
- Eliminación de duplicidades

### 📁 Archivos Modificados

#### Nuevos archivos:
- `/includes/class-unified-pdf-export.php` - Nueva clase unificada de exportación PDF

#### Archivos modificados:
- `/ull-normativa.php` - Carga de la nueva clase unificada
- `/admin/class-pdf-settings.php` - Nuevos campos de configuración
- `/admin/class-admin-settings.php` - Eliminación de menú duplicado

#### Archivos deprecados (mantener por compatibilidad):
- `/includes/class-pdf-export.php` - Ya no se utiliza
- `/codigos/includes/class-pdf-export.php` - Ya no se utiliza

### 🔧 Instrucciones de Migración

1. **Actualizar el plugin** a la versión 2.5.0
2. **Revisar la configuración PDF** en Normativa > Configuración PDF
3. **Personalizar según necesidades**:
   - Subir logo institucional si se desea
   - Ajustar colores corporativos
   - Configurar textos de cabecera y pie de página
4. **Probar la generación de PDF** tanto para normas individuales como para códigos

### 💡 Características Destacadas

- **Estilos Corporativos**: Fácil personalización con los colores de la institución
- **Logo Institucional**: Añade profesionalidad a los documentos generados
- **Consistencia Visual**: Misma apariencia para todos los tipos de documentos
- **Flexibilidad**: Múltiples opciones de configuración para adaptarse a diferentes necesidades
- **Tabla de Contenidos Automática**: Generación automática del índice basado en encabezados
- **Relaciones Normativas**: Se incluyen automáticamente en los PDFs generados
- **Metadatos Completos**: Fechas, órganos emisores y otra información relevante

### 📝 Notas Técnicas

#### Compatibilidad con DOMPDF

La clase unificada mantiene compatibilidad con:
- DOMPDF instalado vía Composer
- DOMPDF instalado vía el instalador del plugin
- Fallback a HTML para impresión si no hay DOMPDF disponible

#### Estructura de Configuración

Las opciones se guardan en `ull_pdf_options` con la siguiente estructura:

```php
array(
    'pdf_orientation' => 'portrait', // 'portrait' | 'landscape'
    'pdf_paper_size' => 'A4',        // 'A4' | 'A3' | 'Letter' | 'Legal'
    'pdf_font_size' => 11,           // 8-20
    'pdf_margins' => array(
        'top' => 20,
        'right' => 15,
        'bottom' => 20,
        'left' => 15,
    ),
    'show_toc' => true,
    'show_page_numbers' => true,
    'show_generation_date' => true,
    'header_logo' => 123,             // Attachment ID
    'header_text' => 'Universidad de La Laguna',
    'header_bg_color' => '#003366',
    'header_text_color' => '#ffffff',
    'title_color' => '#003366',
    'footer_text' => 'Universidad de La Laguna - Normativa',
)
```

### 🐛 Correcciones de Bugs

- Eliminación de duplicación de tabla de contenidos en PDFs de códigos
- Mejor manejo de estilos inline en tablas
- Normalización de atributos HTML para mejor compatibilidad con DOMPDF
- Sanitización mejorada de contenido HTML

### 🔮 Mejoras Futuras Planificadas

- Plantillas de PDF personalizables
- Marca de agua opcional
- Numeración de secciones automática
- Exportación de múltiples normas en un solo PDF
- Programación de generación de PDFs

---

**Versión:** 2.5.0  
**Fecha:** Diciembre 2024  
**Autor:** Universidad de La Laguna
