# 🎲 Bloque de Aleatorización de Formularios

## Descripción

Bloque Gutenberg independiente para configurar aleatorización de formularios en estudios clínicos. Genera automáticamente shortcodes y links directos que asignan formularios aleatoriamente a participantes.

## Features

### ✅ Configuración Visual Simple
- Añadir/remover formularios desde el editor
- Porcentajes automáticos (siempre suman 100%)
- Vista previa en tiempo real
- Drag & drop no necesario (UX simplificada)

### ✅ Asignaciones Manuales (Override Ético)
- Asignar participantes específicos a formularios concretos
- Útil para balanceo manual o casos especiales
- Validación de emails
- Sin duplicados

### ✅ Generación Automática
- **Shortcode**: `[eipsi_randomization id="xyz"]` para insertar en posts/páginas
- **Link Directo**: `https://sitio.com/?eipsi_rand=xyz` para compartir vía email/SMS
- Copiar al portapapeles con un clic

### ✅ Métodos de Aleatorización
- **Con seed reproducible**: Misma asignación para mismo participante (default)
- **Random puro**: Puede cambiar en cada acceso

### ✅ Persistencia
- Las asignaciones se guardan en base de datos
- Tracking de accesos
- Compatible con multisite

## Uso

### 1. Insertar Bloque

En el editor de WordPress:
1. Presionar `+` para agregar bloque
2. Buscar "Aleatorización" o "🎲"
3. Insertar el bloque

### 2. Configurar

**En el Panel Lateral:**
- Activar aleatorización
- Elegir método (seeded/pure-random)
- Mostrar/ocultar vista previa
- Mostrar/ocultar instrucciones en frontend

**En el Bloque:**
1. Seleccionar formularios del dropdown
2. Presionar "Añadir"
3. Los porcentajes se calculan automáticamente
4. (Opcional) Agregar asignaciones manuales

### 3. Generar Shortcode/Link

Cuando hay 2+ formularios configurados:
- **Shortcode**: Copiar y pegar en otro post/página
- **Link**: Compartir directamente con participantes

### 4. Publicar

Guardar/publicar la página. El shortcode estará activo.

## Ejemplo de Flujo

```
1. Investigador crea página "Estudio PHQ-9 v2"
2. Inserta bloque de aleatorización
3. Agrega:
   - PHQ-9 Standard (50%)
   - PHQ-9 Modificado (50%)
4. Copia el link: https://sitio.com/?eipsi_rand=rand_12345
5. Envía el link a participantes vía email
6. Cada participante recibe un formulario aleatorio
7. Mismo participante siempre recibe el mismo formulario
```

## Asignaciones Manuales

Para asignar un participante específico:

1. En "Asignaciones Manuales", ingresar email
2. Seleccionar formulario
3. Presionar "Añadir"

El participante con ese email **siempre** recibirá ese formulario (bypass de aleatorización).

## Shortcode Manual

Si necesitas insertar el shortcode manualmente:

```
[eipsi_randomization id="rand_12345"]
```

Donde `rand_12345` es el ID que aparece en el bloque.

## Query Params

También puedes pasar el email del participante en la URL:

```
https://sitio.com/?eipsi_rand=rand_12345&email=participante@example.com
```

Esto asegura persistencia de asignación vinculada al email.

## Base de Datos

Las asignaciones se guardan en:

```sql
wp_eipsi_randomization_assignments
- id (auto-increment)
- randomization_id (VARCHAR)
- participant_identifier (VARCHAR, email o IP)
- assigned_form_id (INT, post ID del formulario)
- assigned_at (DATETIME)
- last_access (DATETIME)
- access_count (INT)
```

## Seguridad

- Validación de emails
- Sanitización de inputs
- Prevención de duplicados
- Rate limiting (futuro)

## Roadmap

### v1.4
- [ ] Exportar configuración JSON
- [ ] Importar configuración JSON
- [ ] Dashboard de asignaciones en admin
- [ ] Gráfico de distribución

### v1.5
- [ ] Balanceo adaptativo (adaptive randomization)
- [ ] Estratificación por variables
- [ ] Block de randomización por bloques (block randomization)

## FAQ

**¿Qué pasa si un participante limpia cookies?**
Si usas método "seeded" y el participante accede con el mismo email, recibirá el mismo formulario. Si accede con IP diferente, podría recibir otro.

**¿Puedo cambiar los porcentajes después de publicar?**
Sí, pero solo afectará a nuevos participantes. Los ya asignados mantienen su formulario.

**¿Puedo usar múltiples bloques de aleatorización en la misma página?**
Sí, cada bloque es independiente con su propio `randomization_id`.

**¿Es compatible con GDPR?**
Sí. Los identificadores son emails (con consentimiento) o IPs (minimización de datos). Puedes configurar para NO guardar IPs.

## Soporte

Para bugs o feature requests, abrir issue en GitHub o contactar a:
- **Email**: mathias@enmediodelcontexto.com.ar
- **Instagram**: @enmediodel.contexto

---

**Versión**: 1.3.0  
**Autor**: Mathias N. Rojas de la Fuente  
**Licencia**: GPL v2+
