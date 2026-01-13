# Integración de DOMPDF - ULL Normativa v2.4.0

## Descripción

A partir de la versión 2.4.0, ULL Normativa incluye un sistema integrado de instalación de DOMPDF que permite generar PDFs de alta calidad sin necesidad de incluir la librería directamente en el plugin, evitando problemas de límite de tamaño de archivo.

## Características

### Sistema de Instalación Automática

- **Descarga automática**: DOMPDF se descarga directamente desde GitHub al activar o bajo demanda
- **Sin límites de tamaño**: La librería no se incluye en el plugin, evitando problemas con límites de WordPress
- **Gestión centralizada**: Interfaz de administración para instalar, verificar y desinstalar DOMPDF
- **Instalación de dependencias**: Incluye automáticamente las dependencias necesarias (php-font-lib, php-svg-lib, php-css-parser)

### Configuración Personalizable

Nueva página de configuración en **Normas > PDF** que permite:

1. **Orientación del documento**
   - Vertical (portrait)
   - Horizontal (landscape)

2. **Tamaño de papel**
   - A4 (210 × 297 mm)
   - A3 (297 × 420 mm)
   - Letter (8.5 × 11 in)
   - Legal (8.5 × 14 in)

3. **Tipografía**
   - Tamaño de fuente base: 8-20 puntos (por defecto: 12pt)
   - Los encabezados se ajustan automáticamente al tamaño base

4. **Márgenes personalizables**
   - Superior, derecho, inferior, izquierdo
   - Valores en milímetros (0-100mm)

5. **Elementos opcionales**
   - Tabla de contenidos
   - Números de página
   - Texto de encabezado personalizado
   - Texto de pie de página personalizado

## Cómo Usar

### Instalación de DOMPDF

1. Ve a **Normas > PDF** en el administrador de WordPress
2. Haz clic en el botón **"Instalar DOMPDF"**
3. Espera 1-2 minutos mientras se descarga e instala
4. Una vez completado, verás un mensaje de confirmación

### Configuración del PDF

1. Accede a **Normas > PDF**
2. Ajusta los parámetros según tus necesidades:
   - Orientación y tamaño de papel
   - Tamaño de fuente
   - Márgenes
3. Personaliza textos de encabezado y pie de página
4. Activa/desactiva tabla de contenidos y números de página
5. Guarda los cambios

### Generar PDFs

Una vez instalado DOMPDF, los PDFs se generarán automáticamente con:
- Alta calidad de renderizado
- Fuentes embebidas
- Soporte completo para CSS
- Enlaces internos funcionales (tabla de contenidos)
- Formato profesional

## Ubicación de Archivos

### DOMPDF se instala en:
```
/wp-content/uploads/ull-normativa-libs/dompdf/
```

### Caché de fuentes:
```
/wp-content/uploads/ull-normativa/dompdf-cache/
```

### Archivos temporales durante instalación:
```
/wp-content/uploads/ull-normativa-temp/
```
(Se eliminan automáticamente después de la instalación)

## Tamaño de la Instalación

- DOMPDF + dependencias: ~5-10 MB
- Caché de fuentes: Variable según uso
- Total aproximado: 10-15 MB

## Compatibilidad

### Sistema Antiguo (Composer)
Si ya tienes DOMPDF instalado vía Composer en `/vendor/autoload.php`, el plugin lo detectará automáticamente y lo usará con prioridad.

### Fallback HTML
Si DOMPDF no está instalado, el sistema genera automáticamente un HTML optimizado para impresión con un botón para imprimir/guardar como PDF desde el navegador.

## Desinstalación

Para desinstalar DOMPDF:

1. Ve a **Normas > PDF**
2. Haz clic en **"Desinstalar DOMPDF"**
3. Confirma la acción
4. Los archivos se eliminarán completamente

**Nota**: Desinstalar DOMPDF no afecta al plugin, solo volverá al modo de impresión HTML.

## Requisitos Técnicos

- PHP 7.4 o superior
- WordPress 5.6 o superior
- Conexión a internet (para la instalación inicial)
- Permisos de escritura en `/wp-content/uploads/`
- Funciones PHP: `file_get_contents`, `wp_remote_get`, `ZipArchive` o `unzip_file`

## Solución de Problemas

### Error al descargar
- Verifica tu conexión a internet
- Comprueba que GitHub esté accesible desde tu servidor
- Revisa los logs de PHP para más detalles

### Error de permisos
- Verifica que el directorio `/wp-content/uploads/` tenga permisos de escritura
- El servidor necesita poder crear subdirectorios

### Error de memoria
- Aumenta el límite de memoria de PHP en `wp-config.php`:
  ```php
  define('WP_MEMORY_LIMIT', '256M');
  ```

### Timeout en la instalación
- El proceso puede tardar 1-2 minutos
- Si falla por timeout, aumenta el límite en php.ini:
  ```
  max_execution_time = 300
  ```

## Seguridad

- DOMPDF se descarga desde el repositorio oficial de GitHub
- Las verificaciones de integridad se realizan automáticamente
- Solo usuarios con permisos de administrador pueden instalar/desinstalar
- Se usan nonces de WordPress para proteger las acciones

## Changelog

### v2.4.0 (2024-12-25)
- ✨ Nuevo: Sistema de instalación automática de DOMPDF
- ✨ Nuevo: Página de configuración de PDF con opciones avanzadas
- ✨ Nuevo: Configuración de tamaño de papel y orientación
- ✨ Nuevo: Márgenes personalizables
- ✨ Nuevo: Tamaño de fuente configurable
- ✨ Nuevo: Opciones de encabezado y pie de página
- ✨ Nuevo: Control de tabla de contenidos y números de página
- 🔧 Mejorado: Compatibilidad retroactiva con Composer
- 🔧 Mejorado: Sistema de caché para fuentes
- 🔧 Mejorado: Manejo de errores y mensajes informativos

## Créditos

- **DOMPDF**: https://github.com/dompdf/dompdf
- **Desarrollado por**: Universidad de La Laguna
- **Versión del plugin**: 2.4.0

## Soporte

Para reportar problemas o sugerencias:
- Usa el sistema de feedback de WordPress
- Contacta con el equipo de desarrollo de ULL

---

**Nota**: Esta funcionalidad está diseñada para facilitar la generación de PDFs de alta calidad sin comprometer el tamaño del plugin ni requerir configuración manual de Composer.
