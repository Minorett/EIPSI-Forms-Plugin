# Fix del Proceso de Clonación - EIPSI Forms

## 📋 Resumen del Problema

El script `build-automation.ps1` fallaba al intentar clonar el repositorio debido a:

1. **Repositorio Privado**: El repositorio `https://github.com/Minorett/EIPSI-Forms-Plugin.git` requiere autenticación
2. **Sin Manejo de Autenticación**: El script no estaba preparado para manejar prompts de credenciales
3. **Variables sin Uso**: `$targetPath` y `$folderName` estaban definidas pero nunca se utilizaban
4. **Sin Verificación Previa**: No había comprobación de accesibilidad antes de intentar clonar

## ✅ Soluciones Implementadas

### 1. Pre-flight Check (Verificación Previas)

```powershell
# Verificar accesibilidad antes de clonar
git ls-remote --exit-code --heads $repoUrl
```

Esta verificación:
- Detecta si el repositorio es accesible sin intentar clonar
- Evita que el script se quede colgado esperando credenciales
- Proporciona mensajes de error claros

### 2. Desactivación de Prompts Interactivos

```powershell
$env:GIT_TERMINAL_PROMPT = "0"  # Prevenir prompts interactivos
```

Esto fuerza a Git a fallar inmediatamente si necesita autenticación, en lugar de quedarse esperando input del usuario.

### 3. Mejor Manejo de Errores

- Verificación de que el directorio de clonación se creó correctamente
- Mensajes de error descriptivos con soluciones sugeridas
- Manejo de errores en la eliminación de carpetas anteriores

### 4. Optimización de Clonación

```powershell
git clone --depth 1 --single-branch $repoUrl $workDir
```

- `--depth 1`: Clona solo el último commit (más rápido)
- `--single-branch`: Reduce datos transferidos

## 🔧 Configuración de Autenticación

Para que el script funcione con repositorios privados, elige una de estas opciones:

### Opción 1: SSH (Recomendada)

1. Genera una clave SSH (si no tienes):
   ```powershell
   ssh-keygen -t ed25519 -C "tu-email@ejemplo.com"
   ```

2. Agrega la clave pública a GitHub:
   - Copia el contenido de `~/.ssh/id_ed25519.pub`
   - GitHub -> Settings -> SSH and GPG keys -> New SSH key

3. Modifica el script (`build-automation.ps1` línea 145):
   ```powershell
   $repoUrl = "git@github.com:Minorett/EIPSI-Forms-Plugin.git"
   ```

### Opción 2: Personal Access Token

1. Crea un token en GitHub:
   - GitHub -> Settings -> Developer settings -> Personal access tokens -> Tokens (classic)
   - Generate new token -> Selecciona scope "repo"

2. Modifica el script con el token:
   ```powershell
   $repoUrl = "https://ghp_TOKENTOKEN@github.com/Minorett/EIPSI-Forms-Plugin.git"
   ```

⚠️ **Seguridad**: Nunca commits archivos con tokens. Usa variables de entorno:
```powershell
$token = $env:GITHUB_TOKEN
$repoUrl = "https://$token@github.com/Minorett/EIPSI-Forms-Plugin.git"
```

### Opción 3: Git Credential Manager

```powershell
# Configurar Git Credential Manager
git config --global credential.helper manager

# El primer clone pedirá usuario/contraseña y las guardará en caché
git clone https://github.com/Minorett/EIPSI-Forms-Plugin.git
```

## 🧪 Prueba del Script

### Verificación de Requisitos Previos

```powershell
# Verificar Git
git --version

# Verificar npm
npm --version

# Verificar acceso al repositorio
git ls-remote https://github.com/Minorett/EIPSI-Forms-Plugin.git
```

### Ejecución del Script

```powershell
# Desde el directorio del proyecto
powershell -ExecutionPolicy Bypass -File scripts/build-automation.ps1

# Con parámetro para mantener terminal abierta
powershell -ExecutionPolicy Bypass -File scripts/build-automation.ps1 -NoExit
```

## 📁 Cambios en Archivos

### `scripts/build-automation.ps1`

| Líneas | Cambio |
|--------|--------|
| 1-43 | Documentación actualizada con opciones de autenticación |
| 136-152 | Configuración del repositorio simplificada y documentada |
| 154-236 | Sección de clonación completamente reescrita con pre-flight check |

### Variables Eliminadas
- `$targetPath` (no se usaba)
- `$folderName` (no se usaba)

### Variables Agregadas
- `$clonePath` - Path completo del directorio de clonación

## 🔍 Troubleshooting

### Error: "No se puede acceder al repositorio"

**Causa**: El repositorio es privado o la URL es incorrecta.

**Solución**:
1. Verifica que la URL sea correcta
2. Configura autenticación SSH o Token
3. Verifica que tengas permisos de lectura

### Error: "No se pudo eliminar la carpeta anterior"

**Causa**: Algún programa está usando archivos en la carpeta de trabajo.

**Solución**:
1. Cierra editores de código, terminales, etc.
2. En Windows, verifica que no haya procesos de Node.js en ejecución:
   ```powershell
   Get-Process node -ErrorAction SilentlyContinue | Stop-Process -Force
   ```
3. Intenta eliminar manualmente la carpeta `eipsi-forms-work`

### Error: "git clone falló con código de salida 128"

**Causa**: Error de autenticación o repositorio no encontrado.

**Solución**:
1. Verifica credenciales
2. Para SSH: `ssh -T git@github.com`
3. Para HTTPS: Verifica que el token no haya expirado

## 📝 Checklist de Verificación

- [ ] Git instalado (`git --version`)
- [ ] npm instalado (`npm --version`)
- [ ] Acceso al repositorio verificado (`git ls-remote <url>`)
- [ ] Autenticación configurada (SSH, Token o Credential Manager)
- [ ] Permisos de escritura en el directorio de trabajo
- [ ] Script ejecutado sin errores
- [ ] Repositorio clonado correctamente
- [ ] Build completado exitosamente

## 📞 Soporte

Si el problema persiste después de seguir esta guía:

1. Verifica logs detallados: Guarda la salida del script
   ```powershell
   .\scripts\build-automation.ps1 2>&1 | Tee-Object -FilePath "build-log.txt"
   ```

2. Prueba clonar manualmente:
   ```powershell
   git clone https://github.com/Minorett/EIPSI-Forms-Plugin.git test-clone
   ```

3. Verifica conectividad:
   ```powershell
   Test-NetConnection github.com -Port 443
   ```

---

**Versión del Fix**: 1.0  
**Fecha**: 2025-02-20  
**Autor**: EIPSI Forms Dev Team
