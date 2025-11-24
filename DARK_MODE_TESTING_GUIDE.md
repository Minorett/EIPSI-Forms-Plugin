# 🌙 Dark Mode Universal - Guía de Testing Clínico

## ⚡ Quick Start (5 minutos)

### 1. Crear un formulario de prueba
```
1. Abrir WordPress Admin → Pages → Add New
2. Agregar bloque "EIPSI Form Container"
3. Ingresar Form ID: "dark-mode-test"
4. Sidebar derecha → Theme Presets → Seleccionar "Clinical Blue" (default)
5. Agregar bloques:
   - Form Page (Página 1)
   - Text Field (Nombre completo)
   - Radio Field (¿Cómo te sientes? Opción 1: Bien, Opción 2: Mal)
   - VAS Slider (Nivel de estrés, 0-100)
6. Publish
```

### 2. Probar en Frontend
```
1. View Page (en navegador incógnito para evitar cache)
2. Verificar que aparece:
   - Header con título "Dark Mode Test" y botón "🌙 Nocturno"
   - Campos con fondo blanco, textos negros
   - Fondo general blanco
3. Click en "🌙 Nocturno"
   - ✅ Fondo cambia a azul oscuro (#0f172a)
   - ✅ Textos cambian a gris claro (#e2e8f0)
   - ✅ Botón cambia a "☀️ Diurno"
   - ✅ Campos input/textarea/radio PERMANECEN blancos (#ffffff)
   - ✅ Slider: track se oscurece, thumb permanece claro
4. Recargar página (F5)
   - ✅ Dark mode persiste
5. Click en "☀️ Diurno"
   - ✅ Todo vuelve a light mode
```

---

## 🎨 Testing por Preset

### Preset: Clinical Blue
1. Editor → Theme Presets → Clinical Blue
2. Frontend → Dark Mode ON
3. Verificar:
   - Fondo: `#0f172a` (azul muy oscuro, casi negro)
   - Texto: `#e2e8f0` (gris claro azulado)
   - Primary: `#60a5fa` (azul neón suave)
   - Botones: `#3b82f6` (azul medio brillante)

### Preset: Minimal White
1. Editor → Theme Presets → Minimal White
2. Frontend → Dark Mode ON
3. Verificar:
   - Fondo: `#0f172a` (negro azulado)
   - Texto: `#f1f5f9` (gris casi blanco)
   - Primary: `#94a3b8` (gris medio neutro)
   - Botones: `#475569` (gris slate oscuro)

### Preset: Warm Neutral
1. Editor → Theme Presets → Warm Neutral
2. Frontend → Dark Mode ON
3. Verificar:
   - Fondo: `#1a1714` (marrón muy oscuro, casi negro)
   - Texto: `#e8e3db` (beige claro)
   - Primary: `#d4b896` (dorado tostado)
   - Botones: `#8b6f47` (marrón cálido)

### Preset: Serene Teal
1. Editor → Theme Presets → Serene Teal
2. Frontend → Dark Mode ON
3. Verificar:
   - Fondo: `#0c1821` (negro azul verdoso)
   - Texto: `#e0f2fe` (celeste muy claro)
   - Primary: `#5eead4` (teal neón)
   - Botones: `#0e7490` (teal profundo)

---

## 📱 Testing Responsive

### Desktop (1920×1080)
- Toggle aparece en header, inline a la derecha del título
- Tamaño: ~10px padding, 13px font-size
- Hover: se eleva (-2px translateY), sombra suave

### Tablet (768×1024)
- Toggle aparece en header, inline (NO flotante aún)
- Tamaño: 8px padding, 12px font-size

### Mobile (375×667)
- Toggle aparece **flotante bottom-right**
- Tamaño: 48×48 px (44×44 en < 480px)
- Forma: círculo
- Z-index: 999 (sobre todo el contenido)
- Posición: 20px desde bottom/right (16px en < 480px)

---

## ♿ Testing Accesibilidad

### 1. Contraste de color (Axe DevTools)
```
1. Instalar extensión Axe DevTools en Chrome
2. Abrir formulario en dark mode
3. Click en "Analyze"
4. Verificar: "0 critical issues" en sección "Color Contrast"
5. Manualmente verificar ratio:
   - Texto sobre fondo: mínimo 4.5:1
   - Títulos grandes: mínimo 3:1
   - Inputs claros sobre fondo oscuro: verificar que no hay deslumbramiento
```

### 2. Navegación por teclado
```
1. Abrir formulario
2. Presionar Tab hasta llegar al toggle
3. Presionar Enter → cambia dark/light
4. Presionar Ctrl+Shift+D (o Cmd+Shift+D en Mac)
   - ✅ Toggle funciona con atajo de teclado
```

### 3. Screen reader (NVDA/JAWS/VoiceOver)
```
1. Activar screen reader
2. Navegar a toggle
3. Verificar que anuncia:
   - "Nocturno, button" (light mode)
   - "Diurno, button" (dark mode)
   - Aria-label: "Switch to dark mode" / "Switch to light mode"
```

### 4. Reducción de movimiento
```
1. Activar "Reduce motion" en sistema operativo:
   - Windows: Settings → Ease of Access → Display → Show animations
   - Mac: System Preferences → Accessibility → Display → Reduce motion
   - Linux: gsettings set org.gnome.desktop.interface enable-animations false
2. Activar/desactivar dark mode
3. Verificar: transición es instantánea (no hay animación de 0.3s)
```

---

## 🐛 Casos Edge a Verificar

### 1. Formulario legacy (sin data-preset)
```
Simulación:
1. Abrir DevTools → Elements
2. Buscar div.vas-dinamico-form
3. Remover atributo data-preset
4. Activar dark mode
5. Verificar:
   - JS automáticamente agrega data-preset="Clinical Blue"
   - Dark mode funciona correctamente
```

### 2. localStorage corrupto
```
Simulación:
1. DevTools → Application → Local Storage
2. Buscar key "eipsi-theme"
3. Cambiar valor a "invalid"
4. Recargar página
5. Verificar:
   - Formulario carga en light mode (default)
   - Toggle funciona correctamente
```

### 3. prefers-color-scheme: dark (sistema)
```
Simulación:
1. DevTools → Console
2. Ejecutar: localStorage.removeItem('eipsi-theme')
3. DevTools → Rendering → Emulate CSS media feature prefers-color-scheme: dark
4. Recargar página
5. Verificar:
   - Formulario carga en dark mode automáticamente
6. Ejecutar: localStorage.removeItem('eipsi-theme')
7. Cambiar emulación a "prefers-color-scheme: light"
8. Recargar página
9. Verificar:
   - Formulario carga en light mode
```

### 4. Múltiples formularios en misma página
```
Simulación:
1. Crear página con 2 bloques EIPSI Form Container
2. Form 1: preset "Clinical Blue"
3. Form 2: preset "Warm Neutral"
4. Frontend → activar dark mode
5. Verificar:
   - AMBOS formularios cambian a dark mode
   - Form 1 usa Clinical Blue Dark
   - Form 2 usa Warm Neutral Dark
   - Toggle afecta a ambos simultáneamente
```

---

## ✅ Checklist Final (Antes de Release)

### Funcionalidad
- [ ] Dark mode funciona en todos los presets (Clinical Blue, Minimal, Warm, Serene)
- [ ] Inputs permanecen claros en dark mode (input, textarea, radio, checkbox, select)
- [ ] VAS Slider: track se oscurece, thumb permanece claro
- [ ] Toggle persiste en localStorage
- [ ] Toggle respeta prefers-color-scheme como inicial
- [ ] Atajo de teclado Ctrl+Shift+D funciona
- [ ] Toggle es responsive (header desktop, flotante mobile)

### Accesibilidad
- [ ] Contraste WCAG 2.1 AA en todos los presets dark (4.5:1 mínimo)
- [ ] Focus visible en toggle (outline 3px)
- [ ] Screen reader anuncia estado correcto
- [ ] Reducción de movimiento funciona (transition 0.01ms)

### Compatibilidad
- [ ] Chrome/Edge (Windows/Mac/Linux)
- [ ] Firefox (Windows/Mac/Linux)
- [ ] Safari (Mac/iOS)
- [ ] Chrome Mobile (Android)

### Edge Cases
- [ ] Formularios legacy (sin data-preset) cargan dark mode correctamente
- [ ] localStorage corrupto no rompe nada
- [ ] prefers-color-scheme se respeta correctamente
- [ ] Múltiples formularios en misma página funcionan

### Performance
- [ ] Toggle responde en < 200ms
- [ ] Transición suave (0.3s) sin lag
- [ ] No hay flash de contenido sin estilo (FOUC)

---

## 📸 Capturas de Pantalla Requeridas (para Docs)

1. **Clinical Blue Light + Dark**
   - Captura lado a lado, mismo formulario
   - Incluir: header, input text, radio, VAS slider, botones

2. **Minimal White Light + Dark**
   - Misma estructura

3. **Warm Neutral Light + Dark**
   - Misma estructura

4. **Serene Teal Light + Dark**
   - Misma estructura

5. **Mobile Toggle**
   - Captura de toggle flotante en bottom-right
   - iPhone/Android

6. **Accessibility**
   - Captura de Axe DevTools con "0 issues"
   - Captura de focus visible en toggle

---

## 🚨 Red Flags (Detener Release si...)

1. **Inputs oscuros en dark mode**
   - Los pacientes no pueden leer lo que escriben
   - CRÍTICO: inputs DEBEN ser claros siempre

2. **Contraste < 4.5:1**
   - Axe DevTools reporta errores críticos
   - Textos ilegibles en dark mode

3. **Toggle no persiste**
   - localStorage no funciona
   - Paciente pierde preferencia al recargar

4. **Flash de contenido sin estilo (FOUC)**
   - Formulario "parpadea" entre light/dark al cargar
   - Experiencia visual pobre

5. **Toggle no funciona en mobile**
   - Botón flotante no aparece
   - Paciente en tablet no puede cambiar modo

---

## 📞 Contacto para Dudas

- **Developer:** Mathias Rojas (GitHub: @roofkat)
- **QA:** [Agregar nombre]
- **Documentación:** Este archivo + `PR_DARK_MODE_UNIVERSAL.md`
