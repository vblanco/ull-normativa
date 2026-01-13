# ULL Normativa v2.0 - ¡ACTUALIZADO CON MEJORAS DE VISUALIZACIÓN!

## 🎉 Novedades en la Versión 2.0

Esta actualización integra **mejoras significativas en la visualización de versiones** manteniendo toda la funcionalidad original del plugin.

---

## ✨ Nuevas Características v2.0

### 1. Badge de Versión Mejorado

**Versión Vigente:**
- Badge verde con gradiente profesional
- Texto: "VERSIÓN VIGENTE" + número de versión
- Efecto hover elegante
- Posición destacada antes del título

**Versión Histórica:**
- Badge naranja con gradiente
- Texto: "VERSIÓN HISTÓRICA" + número de versión
- Alerta visual clara para el usuario

### 2. Banner de Versión Histórica Rediseñado

Cuando se visualiza una versión anterior (`?version=X.X`):
- **Icono de reloj** para identificación rápida
- **Fondo amarillo con gradiente** para destacar
- **Información clara**: "Está visualizando una versión anterior"
- **Detalles**: Número de versión y fecha de vigencia
- **Botón CTA** destacado: "Ver versión vigente"
- **Botón de cierre** opcional
- **Animación suave** al aparecer

### 3. Información de Versión Estructurada

En la pestaña "Información":
- **Versión actual** destacada en verde
- **Contador de versiones** disponibles
- **Enlace directo** al historial completo
- **Diseño limpio** con separadores visuales

---

## 📂 Archivos Añadidos

```
ull-normativa/
├── includes/
│   └── class-version-display.php         ← NUEVO: Lógica de visualización
└── assets/
    └── css/
        └── version-display.css            ← NUEVO: Estilos mejorados
```

---

## 🔄 Cambios en Archivos Existentes

### `ull-normativa.php`
- ✅ Versión actualizada a 2.0.0
- ✅ Incluye `class-version-display.php`
- ✅ Encola `version-display.css`

### `public/class-shortcodes.php`
- ✅ Hook añadido antes del título: `do_action('ull_normativa_before_title')`
- ✅ Hook añadido después del título: `do_action('ull_normativa_after_title')`
- ✅ Hook añadido en pestaña Info: `do_action('ull_normativa_info_tab_content')`

---

## 🚀 Instalación/Actualización

### Actualizar desde v1.0

1. **Desactivar** el plugin actual (no eliminar)
2. **Subir** este nuevo archivo ZIP
3. **Activar** el plugin actualizado
4. **Limpiar** caché del navegador (Ctrl+F5)

### Instalación Nueva

1. Subir el ZIP en `Plugins` > `Añadir nuevo`
2. Activar el plugin
3. Listo, ¡todo funcionará automáticamente!

---

## ✅ Compatibilidad

- ✅ Todas las normativas existentes funcionarán sin cambios
- ✅ Shortcodes existentes siguen funcionando igual
- ✅ Base de datos NO requiere actualización
- ✅ No hay breaking changes
- ✅ Retrocompatible al 100%

---

## 🎨 Personalización de Colores

Si quieres adaptar los colores a tu paleta ULL, edita:
`assets/css/version-display.css`

```css
/* Badge versión vigente (línea ~30) */
.normativa-version-badge.current {
    background: linear-gradient(135deg, #TU-VERDE 0%, #TU-VERDE-OSCURO 100%);
}

/* Badge versión histórica (línea ~37) */
.normativa-version-badge.historical {
    background: linear-gradient(135deg, #TU-NARANJA 0%, #TU-NARANJA-OSCURO 100%);
}

/* Banner (línea ~63) */
.normativa-historical-banner {
    background: linear-gradient(135deg, #TU-AMARILLO 0%, #TU-AMARILLO-CLARO 100%);
    border-color: #TU-BORDE;
}

/* Botón CTA (línea ~129) */
.btn-view-current {
    background: #TU-COLOR;
}
```

---

## 🔍 Cómo Probar las Mejoras

### Probar Badge de Versión Vigente
1. Ir a cualquier normativa
2. Ver el badge verde en la parte superior
3. Verificar que muestra "VERSIÓN VIGENTE X.X"

### Probar Banner de Versión Histórica
1. Ir a una normativa
2. Añadir `?version=1.0` a la URL (o cualquier versión anterior que exista)
3. Ver el banner amarillo con el botón "Ver versión vigente"
4. Clicar el botón para volver a la versión actual

### Probar Información de Versión
1. Ir a una normativa
2. Clicar en la pestaña "Información"
3. Ver la sección de versión al final con datos organizados

---

## 📱 Diseño Responsive

Las mejoras están optimizadas para todos los dispositivos:

- **Desktop** (>1024px): Diseño completo con 3 columnas
- **Tablet** (768-1024px): Diseño adaptado
- **Mobile** (<768px): Diseño en columna única, botones de ancho completo

---

## ♿ Accesibilidad

Cumple con WCAG AA:
- ✅ Navegación por teclado completa
- ✅ Focus visible en todos los elementos
- ✅ Contraste de colores adecuado
- ✅ ARIA labels en botones
- ✅ Compatible con lectores de pantalla

---

## 📋 Funcionalidades Originales (Mantenidas)

Toda la funcionalidad de v1.0 se mantiene intacta:

✅ Custom post type 'norma'
✅ Taxonomías (tipo, categoría, materia, órgano)
✅ Meta boxes completos
✅ Control de versiones
✅ Relaciones entre normas
✅ Import/Export (CSV, JSON, XML)
✅ Búsqueda avanzada
✅ Shortcodes: `[ull_normativa_listado]`, `[ull_normativa_buscador]`, `[ull_norma]`, `[ull_normativa_archivo]`
✅ Exportación a PDF
✅ Sanitización HTML
✅ Sistema de pestañas

---

## 🆘 Solución de Problemas

### Badge no aparece
**Causa**: Caché del navegador
**Solución**: Ctrl+F5 (hard refresh)

### Banner no funciona
**Causa**: URL sin parámetro de versión
**Solución**: Asegurar que la URL tiene `?version=X.X`

### Estilos se ven raros
**Causa**: Conflicto con tema
**Solución**: Aumentar especificidad CSS o usar `!important`

### Después de actualizar no veo cambios
**Solución**:
1. Limpiar caché de WordPress (si usas plugin de caché)
2. Ctrl+F5 en navegador
3. Verificar que la versión es 2.0.0 en Plugins

---

## 📞 Soporte

Si tienes problemas con la actualización:

1. Verifica que estés en la versión 2.0.0 (ver en listado de plugins)
2. Limpia caché completamente
3. Revisa la consola del navegador (F12) por errores
4. Desactiva temporalmente otros plugins para descartar conflictos

---

## 🎯 Próximas Mejoras (v2.1)

Planeadas para futuras versiones:
- Comparador visual de versiones
- Timeline interactivo de cambios
- Notificaciones de nuevas versiones
- Descarga batch de versiones históricas

---

## 📝 Changelog

### Versión 2.0.0 (Diciembre 2025)
- ✨ Badge de versión modernizado con gradientes
- ✨ Banner de versión histórica rediseñado
- ✨ Información de versión estructurada
- ✨ Diseño responsive optimizado
- ✨ Mejoras de accesibilidad (WCAG AA)
- 🔧 Sistema de hooks integrado
- 🔧 CSS modular para fácil personalización
- 📚 Documentación mejorada

### Versión 1.0.0 (Diciembre 2025)
- Versión inicial del plugin

---

## ✨ ¡Disfruta de las Mejoras!

El plugin ahora ofrece una experiencia visual mucho más profesional y clara para tus usuarios cuando visualizan normativas y sus versiones.

**Universidad de La Laguna**  
Diciembre 2025
