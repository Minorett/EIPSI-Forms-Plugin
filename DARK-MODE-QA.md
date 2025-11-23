# EIPSI Forms - Dark Mode Quality Assurance Guide

## ✅ CHECKLIST COMPLETO DE PRUEBAS

### 1. **Verificar activación del Dark Mode**

- [ ] El toggle de dark mode es visible en la esquina superior derecha (desktop/tablet)
- [ ] En mobile (< 768px), el toggle aparece fijo en bottom-right como botón circular con emoji 🌙
- [ ] Al hacer clic en el toggle, el tema cambia inmediatamente
- [ ] El label del toggle cambia:
  - Light → Dark: muestra "🌙 Nocturno"
  - Dark → Light: muestra "☀️ Diurno"
- [ ] El toggle se mantiene accesible con teclado (Tab + Enter/Space)
- [ ] El estado se persiste en localStorage (`eipsi-theme`)
- [ ] Al recargar la página, el modo seleccionado se mantiene

### 2. **Verificar paleta de colores dark**

#### **Fondos y superficies**
- [ ] Fondo principal del formulario: `#0f172a` (azul oscuro profundo)
- [ ] Cards (radio, checkbox, likert): `#1e293b` (gris-azul oscuro)
- [ ] Hover en cards: `#334155` (ligeramente más claro)
- [ ] Progress bar background: `#1e293b`

#### **Inputs (CRÍTICO: deben permanecer claros)**
- [ ] Inputs de texto: fondo **blanco** (`#ffffff`)
- [ ] Textarea: fondo **blanco** (`#ffffff`)
- [ ] Select: fondo **blanco** (`#ffffff`)
- [ ] Texto dentro de inputs: **oscuro** (`#2c3e50`) para máximo contraste

#### **Texto y bordes**
- [ ] Texto principal: `#e2e8f0` (gris claro)
- [ ] Texto muted: `#94a3b8` (gris medio)
- [ ] Bordes: `#334155` y `#475569` (grises oscuros)
- [ ] Primary color: `#60a5fa` (azul claro brillante)

#### **Botones**
- [ ] Botón primario (Next/Submit): `#3b82f6` (azul brillante)
- [ ] Botón primario hover: `#2563eb` (azul más intenso)
- [ ] Botón secundario (Previous): fondo adaptado, borde visible

### 3. **Verificar VAS Slider**

- [ ] Contenedor del slider: fondo oscuro (`#1e3a5c`)
- [ ] Track del slider: gris oscuro
- [ ] Thumb del slider: azul brillante (`#60a5fa`)
- [ ] Thumb hover: shadow azul brillante visible
- [ ] Valor numérico: color azul brillante sobre fondo semi-transparente

### 4. **Verificar navegación y progress**

- [ ] Progress bar (ej: "Página 1 de 3"): fondo oscuro, texto claro, highlight azul
- [ ] Botones de navegación claramente visibles
- [ ] Focus state con outline azul brillante (`#60a5fa`)

### 5. **Verificar mensajes y thank-you page**

- [ ] Success message: verde brillante (`#86efac`) sobre fondo oscuro
- [ ] Error message: rojo pastel (`#fca5a5`) sobre fondo oscuro
- [ ] Thank-you page: fondo de tarjeta oscuro, texto legible

### 6. **Verificar campos condicionales y bloques descriptivos**

- [ ] Campos que aparecen/desaparecen mantienen el tema dark
- [ ] Bloques de descripción: fondo `#1e293b`, borde azul izquierdo

### 7. **Pruebas de contraste WCAG AA**

#### **Con herramientas automáticas**
- [ ] Usar Chrome DevTools → Lighthouse → Accessibility
- [ ] Verificar que todos los ratios de contraste cumplan WCAG AA (mínimo 4.5:1)
- [ ] Verificar específicamente:
  - Input text on white: debe ser ≥ 7:1 (AAA)
  - Body text (`#e2e8f0`) on dark (`#0f172a`): debe ser ≥ 4.5:1
  - Primary buttons: azul sobre blanco ≥ 4.5:1
  - Focus indicators: shadow visible en todos los elementos

#### **Con prueba visual**
- [ ] Abrir formulario en una tablet en sala con luz normal
- [ ] ¿Se puede leer TODO el texto cómodamente?
- [ ] ¿Los inputs blancos destacan claramente del fondo oscuro?
- [ ] ¿Los botones son fáciles de identificar?

### 8. **Pruebas de accesibilidad táctil**

#### **Mobile (< 768px)**
- [ ] El toggle de dark mode es fácil de presionar con el pulgar (48×48px mínimo)
- [ ] Radio buttons: área clickeable ≥ 44×44px
- [ ] Checkboxes: área clickeable ≥ 44×44px
- [ ] Botones de navegación: altura ≥ 44px, ancho completo en mobile

#### **Focus keyboard navigation**
- [ ] Tab navega entre todos los controles
- [ ] El focus visible es claro (outline azul brillante)
- [ ] Enter/Space activa botones y opciones
- [ ] El toggle de dark mode funciona con teclado

### 9. **Pruebas cross-browser**

Probar dark mode en:
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (macOS/iOS)
- [ ] Chrome Android

Verificar en cada uno:
- [ ] Los colores se ven correctos
- [ ] Las transiciones son suaves
- [ ] No hay "flash" de tema claro al cargar

### 10. **Preferencias del sistema**

- [ ] Si el usuario tiene `prefers-color-scheme: dark` en el sistema, el formulario inicia en dark mode (solo si NO hay preferencia guardada en localStorage)
- [ ] Si el usuario cambia la preferencia manualmente, el formulario NO cambia automáticamente (respeta la elección del usuario)

### 11. **Reduced motion**

- [ ] Si `prefers-reduced-motion: reduce` está activo, las transiciones de color son instantáneas (< 50ms)
- [ ] El formulario sigue siendo usable sin animaciones

### 12. **Print styles**

- [ ] Imprimir desde dark mode: los colores son legibles (o se fuerzan a claro)
- [ ] El toggle de dark mode NO aparece en la impresión

## 🎯 CASOS DE PRUEBA PRÁCTICOS

### **Caso 1: Psicólogo en consultorio (luz baja, tablet)**
1. Abrir formulario en una tablet
2. Activar dark mode
3. Completar un formulario multipage con campos condicionales
4. **Esperado**: Todo el flujo es cómodo, sin reflejo de pantalla, inputs blancos fáciles de leer

### **Caso 2: Investigador revisando respuestas de noche**
1. Abrir admin panel "Results & Experience"
2. Activar dark mode del navegador (`prefers-color-scheme: dark`)
3. El formulario de previsualización debería abrir en dark mode automáticamente
4. **Esperado**: Cero esfuerzo visual al revisar datos

### **Caso 3: Paciente con sensibilidad visual**
1. Usuario con `prefers-contrast: high` activado
2. Abrir formulario en dark mode
3. **Esperado**: Bordes más gruesos (3px), contraste extra, elementos claramente diferenciados

### **Caso 4: Tablet en sala con ventana (luz natural fuerte)**
1. Abrir formulario en light mode
2. Completar la primera página
3. Activar dark mode
4. Continuar completando el formulario
5. **Esperado**: Transición suave, sin pérdida de datos, los inputs blancos contrastan con el fondo oscuro incluso con luz natural

## 📊 CHECKLIST PRE-RELEASE

Antes de marcar esta feature como completa:

- [ ] Los 12 puntos de QA arriba están verificados
- [ ] Los 4 casos prácticos se probaron en tablet real (no solo browser DevTools)
- [ ] Se ejecutó `npm run build` sin errores
- [ ] Se verificó que el bundle CSS no creció más de 10KB con las nuevas reglas dark
- [ ] Se actualizó la documentación de usuario (si corresponde)

## 🐛 ISSUES CONOCIDOS (SI LOS HAY)

_Documentar aquí cualquier limitación conocida o workaround necesario._

---

**Última actualización**: 2025-01-23  
**Versión**: Dark Mode Complete v1.0
