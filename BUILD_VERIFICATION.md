# 🔧 EIPSI Forms - Verificación de Build Clínico

Este documento explica cómo verificar que EIPSI Forms se pueda construir correctamente y que todos los artefactos críticos estén presentes antes de instalarlo en un entorno clínico.

---

## 📋 Requisitos Previos

Antes de ejecutar la verificación de build, asegúrate de tener instalado:

- **Node.js:** >= 14.x (recomendado 16.x o superior)
- **npm:** >= 7.x (recomendado 8.x o superior)
- **Git:** Para clonar el repositorio

### Verificar requisitos

```bash
node --version    # Debe mostrar v14.x o superior
npm --version     # Debe mostrar 7.x o superior
git --version     # Cualquier versión reciente
```

---

## 🚀 Uso Rápido

### Linux / macOS

1. **Clonar el repositorio:**
   ```bash
   git clone https://github.com/Minorett/EIPSI-Forms-Plugin.git
   cd EIPSI-Forms-Plugin
   ```

2. **Ejecutar script de verificación:**
   ```bash
   ./scripts/verify-build.sh
   ```

   El script automáticamente:
   - Instala las dependencias necesarias
   - Ejecuta el build de producción
   - Verifica que todos los artefactos críticos existan
   - Muestra mensajes claros de éxito o error

### Windows (PowerShell)

1. **Clonar el repositorio:**
   ```powershell
   git clone https://github.com/Minorett/EIPSI-Forms-Plugin.git
   cd EIPSI-Forms-Plugin
   ```

2. **Ejecutar script de verificación:**
   ```powershell
   .\scripts\verify-build.ps1
   ```

   > **Nota:** Si PowerShell muestra un error de política de ejecución, ejecuta:
   > ```powershell
   > Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   > ```

---

## 🔍 Qué Valida el Script

El script de verificación realiza tres verificaciones críticas:

### 1️⃣ Instalación de Dependencias
- Ejecuta `npm install --legacy-peer-deps`
- Verifica que no haya errores críticos
- Instala todas las dependencias necesarias para el build

### 2️⃣ Build de Producción
- Ejecuta `npm run build`
- Compila todos los bloques de Gutenberg
- Genera los assets CSS y JavaScript
- Verifica que el comando termine con exit code 0 (sin errores)

### 3️⃣ Verificación de Artefactos Críticos
Comprueba que los siguientes archivos existan y tengan tamaño > 0 bytes:

| Archivo | Descripción | Tamaño Esperado |
|---------|-------------|-----------------|
| `build/index.js` | JavaScript compilado de todos los bloques | ~87 KB |
| `build/index.css` | Estilos del editor de Gutenberg | ~42 KB |
| `build/style-index.css` | Estilos frontend del formulario | ~26 KB |

**Total de artefactos críticos:** ~155 KB

---

## ✅ Salida Esperada

### Éxito (Exit Code 0)

```
╔════════════════════════════════════════════════════════════╗
║  EIPSI Forms - Verificación de Build Clínico             ║
╚════════════════════════════════════════════════════════════╝

[1/3] Instalando dependencias...
✓ Dependencias instaladas correctamente

[2/3] Ejecutando build de producción...
✓ Build ejecutado correctamente

[3/3] Verificando artefactos críticos del plugin...

✓ build/index.js (87K)
✓ build/index.css (42K)
✓ build/style-index.css (26K)

╔════════════════════════════════════════════════════════════╗
║  ✓ BUILD CLÍNICO VERIFICADO CORRECTAMENTE                 ║
╚════════════════════════════════════════════════════════════╝

El plugin EIPSI Forms está listo para uso clínico.
```

### Error - Falta un archivo

```
[3/3] Verificando artefactos críticos del plugin...

✗ FALTA: build/index.js
✓ build/index.css (42K)
✓ build/style-index.css (26K)

╔════════════════════════════════════════════════════════════╗
║  ERROR: Faltan 1 archivo(s) crítico(s)                    ║
╚════════════════════════════════════════════════════════════╝
```

---

## 🛠️ Verificación Manual (Sin Script)

Si prefieres ejecutar los pasos manualmente:

### 1. Clonar repositorio
```bash
git clone https://github.com/Minorett/EIPSI-Forms-Plugin.git
cd EIPSI-Forms-Plugin
git checkout main  # Asegurar que estás en la rama main
```

### 2. Instalar dependencias
```bash
npm install --legacy-peer-deps
```

> **Nota:** El flag `--legacy-peer-deps` es necesario para evitar conflictos de dependencias con algunas versiones de npm.

### 3. Ejecutar build
```bash
npm run build
```

Deberías ver una salida similar a:
```
> vas-dinamico-forms@1.2.2 build
> wp-scripts build

webpack 5.103.0 compiled successfully in 4809 ms
```

### 4. Verificar artefactos
```bash
# Linux/macOS
ls -lh build/index.js build/index.css build/style-index.css

# Windows (PowerShell)
Get-ChildItem build\index.js,build\index.css,build\style-index.css
```

Todos los archivos deben existir y tener tamaño > 0 bytes.

---

## 🐛 Solución de Problemas

### Error: `npm: command not found`
**Causa:** Node.js/npm no están instalados.

**Solución:**
- **macOS:** `brew install node`
- **Linux (Ubuntu/Debian):** `sudo apt install nodejs npm`
- **Windows:** Descargar de [nodejs.org](https://nodejs.org)

### Error: `npm ERR! peer dependencies`
**Causa:** Conflictos de dependencias de peer.

**Solución:** Usa el flag `--legacy-peer-deps`:
```bash
npm install --legacy-peer-deps
```

### Error: `webpack compiled with X errors`
**Causa:** Problemas en el código fuente o dependencias faltantes.

**Solución:**
1. Limpia node_modules y reinstala:
   ```bash
   rm -rf node_modules package-lock.json
   npm install --legacy-peer-deps
   ```
2. Si persiste, verifica que estás en la rama `main`:
   ```bash
   git checkout main
   git pull origin main
   ```

### Error: Build exitoso pero faltan archivos en `build/`
**Causa:** Configuración de webpack incorrecta o archivos `.gitignore`.

**Solución:**
1. Verifica que `build/` existe: `ls -la build/`
2. Re-ejecuta el build: `npm run build`
3. Si `build/` no existe, créalo: `mkdir build && npm run build`

---

## 📦 Siguientes Pasos

Una vez que la verificación sea exitosa:

1. **Empaquetar el plugin** (manual):
   ```bash
   # Crear zip sin archivos de desarrollo
   zip -r eipsi-forms-plugin.zip . \
     -x "*.git*" "node_modules/*" "src/*" ".eslint*" "package*.json"
   ```

2. **Instalar en WordPress:**
   - Ir a **Plugins → Añadir nuevo → Subir plugin**
   - Seleccionar el archivo `.zip`
   - Activar el plugin

3. **Verificar instalación:**
   - Crear un nuevo post/página
   - Verificar que los bloques "EIPSI" aparezcan en el editor de bloques
   - Crear un formulario de prueba y verificar que funcione

---

## 🎯 Filosofía de Verificación

Este proceso de verificación garantiza que:

✅ **Zero Data Loss:** Todos los artefactos críticos están presentes  
✅ **Zero Fear:** Proceso reproducible y claro  
✅ **Zero Friction:** Un solo comando para verificar todo  

> «Por fin alguien entendió cómo trabajo de verdad con mis pacientes»

Para un psicólogo clínico, la confianza en la herramienta es fundamental. Este proceso de verificación garantiza que el plugin funcionará correctamente en entornos clínicos reales.

---

## 📞 Soporte

Si encuentras problemas durante la verificación:

- **GitHub Issues:** https://github.com/Minorett/EIPSI-Forms-Plugin/issues
- **Documentación completa:** Ver `README.md`
- **Hotfix crítico (v1.2.2):** Ver `HOTFIX_v1.2.2_AUTO_DB_SCHEMA_REPAIR.md`
