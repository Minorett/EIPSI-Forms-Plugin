# 📝 EJEMPLO VISUAL - Markdown en Consentimiento Informado

## 🎨 Ejemplo Completo de Uso Real

### LO QUE EL INVESTIGADOR ESCRIBE EN EL EDITOR:

```
*Consentimiento Informado para Participación en Estudio Clínico*

Declaro que he sido informado/a _completamente_ sobre la naturaleza y propósito de este estudio de investigación. He leído y entiendo *toda la información* proporcionada.

*Derechos del Participante:*

• _Voluntariedad_ - Mi participación es completamente voluntaria. Puedo negarme a participar sin que esto afecte mi atención médica.

• _Retiro_ - Puedo retirarme del estudio en cualquier momento sin necesidad de dar explicaciones.

• _Confidencialidad_ - Mis datos personales serán tratados de manera *confidencial* y de acuerdo con las normativas ANMAT y GDPR.

• _Anonimato_ - Mis respuestas no serán identificadas con mi nombre en los análisis ni publicaciones.

*_IMPORTANTE:_* Si tengo preguntas sobre el estudio, puedo contactar al investigador principal en cualquier momento.

Al marcar el checkbox a continuación, confirmo que:
- He leído y entendido *toda la información* proporcionada
- He tenido la oportunidad de hacer preguntas
- Acepto participar _voluntariamente_ en este estudio
```

---

### LO QUE VE EL INVESTIGADOR EN EL PREVIEW DEL EDITOR:

![Editor Preview]

> **Consentimiento Informado para Participación en Estudio Clínico**
>
> Declaro que he sido informado/a *completamente* sobre la naturaleza y propósito de este estudio de investigación. He leído y entiendo **toda la información** proporcionada.
>
> **Derechos del Participante:**
>
> • *Voluntariedad* - Mi participación es completamente voluntaria. Puedo negarme a participar sin que esto afecte mi atención médica.
>
> • *Retiro* - Puedo retirarme del estudio en cualquier momento sin necesidad de dar explicaciones.
>
> • *Confidencialidad* - Mis datos personales serán tratados de manera **confidencial** y de acuerdo con las normativas ANMAT y GDPR.
>
> • *Anonimato* - Mis respuestas no serán identificadas con mi nombre en los análisis ni publicaciones.
>
> ***IMPORTANTE:*** Si tengo preguntas sobre el estudio, puedo contactar al investigador principal en cualquier momento.
>
> Al marcar el checkbox a continuación, confirmo que:
> - He leído y entendido **toda la información** proporcionada
> - He tenido la oportunidad de hacer preguntas
> - Acepto participar *voluntariamente* en este estudio

[💡 Cheat Sheet visible aquí en el editor]

---

### LO QUE VE EL PACIENTE EN EL FRONTEND:

> **Consentimiento Informado para Participación en Estudio Clínico**
>
> Declaro que he sido informado/a *completamente* sobre la naturaleza y propósito de este estudio de investigación. He leído y entiendo **toda la información** proporcionada.
>
> **Derechos del Participante:**
>
> • *Voluntariedad* - Mi participación es completamente voluntaria. Puedo negarme a participar sin que esto afecte mi atención médica.
>
> • *Retiro* - Puedo retirarme del estudio en cualquier momento sin necesidad de dar explicaciones.
>
> • *Confidencialidad* - Mis datos personales serán tratados de manera **confidencial** y de acuerdo con las normativas ANMAT y GDPR.
>
> • *Anonimato* - Mis respuestas no serán identificadas con mi nombre en los análisis ni publicaciones.
>
> ***IMPORTANTE:*** Si tengo preguntas sobre el estudio, puedo contactar al investigador principal en cualquier momento.
>
> Al marcar el checkbox a continuación, confirmo que:
> - He leído y entendido **toda la información** proporcionada
> - He tenido la oportunidad de hacer preguntas
> - Acepto participar *voluntariamente* en este estudio
>
> ☐ He leído y acepto los términos y condiciones *

---

## 🎯 CASOS DE USO COMUNES

### CASO 1: Énfasis en Términos Clave

**Escribe:**
```
Tu participación es *completamente voluntaria*.
```

**Se renderiza:**
> Tu participación es **completamente voluntaria**.

---

### CASO 2: Énfasis en Conceptos Importantes

**Escribe:**
```
• _Confidencialidad_ - Tus datos serán protegidos
• _Voluntariedad_ - Puedes retirarte en cualquier momento
• _Anonimato_ - No se identificarán tus respuestas
```

**Se renderiza:**
> • *Confidencialidad* - Tus datos serán protegidos
> • *Voluntariedad* - Puedes retirarte en cualquier momento
> • *Anonimato* - No se identificarán tus respuestas

---

### CASO 3: Advertencias Críticas

**Escribe:**
```
*_ATENCIÓN:_* Lee cuidadosamente este documento antes de continuar.
```

**Se renderiza:**
> ***ATENCIÓN:*** Lee cuidadosamente este documento antes de continuar.

---

### CASO 4: Títulos y Secciones

**Escribe:**
```
*Derechos del Participante*

Tu participación es _voluntaria_ y puedes retirarte en cualquier momento sin _penalización_.

*Confidencialidad de Datos*

Tus datos serán tratados de manera *confidencial* según las normativas ANMAT.
```

**Se renderiza:**
> **Derechos del Participante**
>
> Tu participación es *voluntaria* y puedes retirarte en cualquier momento sin *penalización*.
>
> **Confidencialidad de Datos**
>
> Tus datos serán tratados de manera **confidencial** según las normativas ANMAT.

---

### CASO 5: Mezcla de Formatos en Una Línea

**Escribe:**
```
Este estudio es *aprobado por ANMAT*, garantiza _confidencialidad total_ y es *completamente anónimo*.
```

**Se renderiza:**
> Este estudio es **aprobado por ANMAT**, garantiza *confidencialidad total* y es **completamente anónimo**.

---

## ⚠️ EJEMPLOS DE VALIDACIÓN (Errores)

### ERROR 1: Asterisco Sin Cerrar

**Escribe:**
```
Esto es un *texto sin cerrar
```

**Resultado en editor:**
```
⚠️ Asteriscos desparejados: 1 total
```

**Solución:**
```
Esto es un *texto cerrado correctamente*
```

---

### ERROR 2: Guion Bajo Sin Cerrar

**Escribe:**
```
Esto es un _texto sin cerrar
```

**Resultado en editor:**
```
⚠️ Guiones bajos desparejados: 1 total
```

**Solución:**
```
Esto es un _texto cerrado correctamente_
```

---

### ERROR 3: Múltiples Errores

**Escribe:**
```
*Sin cerrar 1 y _sin cerrar 2
```

**Resultado en editor:**
```
⚠️ Asteriscos desparejados: 1 total, Guiones bajos desparejados: 1 total
```

**Solución:**
```
*Cerrado correctamente 1* y _cerrado correctamente 2_
```

---

## 🎨 VISUALIZACIÓN DEL CHEAT SHEET EN EL EDITOR

El investigador ve esto siempre en el editor (fondo azul claro):

```
┌─────────────────────────────────────────────────────────────┐
│ 💡 Formato de Texto:                                        │
│                                                             │
│ Escribe *tu texto* para negrita                            │
│ Escribe _tu texto_ para itálica                            │
│ Escribe *_tu texto_* para negrita e itálica                │
└─────────────────────────────────────────────────────────────┘
```

**Características:**
- Fondo: azul claro (#e7f3ff)
- Borde: azul (#b3d9ff)
- Texto: azul oscuro (#0056b3)
- Ejemplos en código con fondo blanco
- Siempre visible en el editor
- NO aparece en el frontend

---

## 🚨 VISUALIZACIÓN DEL WARNING EN EL EDITOR

Cuando hay errores, el investigador ve esto (fondo amarillo):

```
┌─────────────────────────────────────────────────────────────┐
│ ⚠️ Asteriscos desparejados: 1 total                         │
└─────────────────────────────────────────────────────────────┘
```

**Características:**
- Fondo: amarillo claro (#fff3cd)
- Borde: amarillo (#ffc107)
- Texto: marrón oscuro (#856404)
- Aparece solo cuando hay errores
- Desaparece automáticamente al corregir
- NO aparece en el frontend

---

## 📊 COMPARACIÓN: ANTES vs DESPUÉS

### ANTES (sin markdown):
```
CONSENTIMIENTO INFORMADO

Declaro que he sido informado completamente sobre este estudio.
Mis datos serán tratados de manera confidencial.
Mi participación es voluntaria.
```

→ **Problema:** Texto plano, sin jerarquía visual, difícil de leer

---

### DESPUÉS (con markdown):
```
*Consentimiento Informado*

Declaro que he sido informado _completamente_ sobre este estudio.
Mis datos serán tratados de manera *confidencial*.
Mi participación es *voluntaria*.
```

→ **Renderiza como:**

> **Consentimiento Informado**
>
> Declaro que he sido informado *completamente* sobre este estudio.
> Mis datos serán tratados de manera **confidencial**.
> Mi participación es **voluntaria**.

→ **Ventaja:** Jerarquía visual clara, términos clave destacados, profesional

---

## 🎉 BENEFICIO PARA EL INVESTIGADOR

### SIN MARKDOWN (método antiguo):
1. Escribir texto plano
2. Copiar a Word
3. Aplicar formato en Word
4. Tomar captura de pantalla
5. Subir imagen
6. **Resultado:** Texto no es seleccionable, no es accesible (lectores de pantalla)

### CON MARKDOWN (nuevo método):
1. Escribir `*texto importante*` y `_texto enfatizado_`
2. Ver preview en tiempo real
3. Publicar
4. **Resultado:** Texto profesional, seleccionable, accesible, SEO-friendly

---

## ✅ CHECKLIST DE VENTAJAS

- ✅ **Sintaxis intuitiva** (markdown estándar)
- ✅ **Preview dinámico** (WYSIWYG)
- ✅ **Validación en tiempo real** (evita errores)
- ✅ **Cheat sheet visible** (no necesita recordar sintaxis)
- ✅ **Seguridad garantizada** (XSS prevention)
- ✅ **Accesibilidad** (lectores de pantalla)
- ✅ **SEO-friendly** (texto indexable)
- ✅ **Copy-paste friendly** (texto seleccionable)
- ✅ **Zero learning curve** (sintaxis natural)
- ✅ **Professional output** (HTML semántico)

---

## 🎯 MENSAJE FINAL PARA INVESTIGADORES

### Antes:
> "Necesito contratar un diseñador para dar formato al consentimiento"

### Ahora:
> "Escribo `*importante*` y listo, se ve profesional automáticamente"

---

**«Por fin alguien entendió cómo trabajo de verdad con mis pacientes»**

→ Zero fear
→ Zero friction
→ Zero excuses

---

**Versión:** 1.2.3
**Fecha:** 2025-01-10
**Feature:** Markdown Dinámico en Bloque de Consentimiento
