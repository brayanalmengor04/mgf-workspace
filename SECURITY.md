# Política de seguridad

## Versiones soportadas

Este proyecto se despliega de forma continua desde la rama `main` en Railway.  
Solo esa línea recibe correcciones de seguridad.

| Versión / entorno              | Soportada |
| ------------------------------ | --------- |
| `main` (producción en Railway) | Sí        |
| Ramas de desarrollo / PR       | No        |
| Despliegues locales o forks    | No        |

Si usas una copia antigua del repositorio o un fork sin actualizar, no se garantiza soporte de seguridad.

## Cómo reportar una vulnerabilidad

**No abras un issue público** si el reporte puede ayudar a explotar el sistema.

### Opción recomendada

Usa [**Security Advisories**](https://github.com/brayanalmengor04/mgf-workspace/security/advisories/new) de GitHub (reporte privado).

### Alternativa

Envía un correo a **brayanalmengorz@gmail.com** con el asunto:  
`[SEGURIDAD] MGF Workspace`

Incluye, si es posible:

- Descripción del problema
- Pasos para reproducirlo
- Impacto estimado (datos expuestos, acceso no autorizado, etc.)
- Versión o commit afectado
- Capturas o logs (sin contraseñas, tokens ni datos personales reales)

### Qué esperar

| Plazo        | Acción |
| ------------ | ------ |
| 48 horas     | Confirmación de recepción |
| 7 días       | Evaluación inicial y estado (válido / no válido / necesita más info) |
| Según impacto | Parche en `main` y aviso cuando esté desplegado |

Si el reporte no es aceptado, se explicará el motivo.  
Los reportes válidos pueden mencionarse en el changelog o en las notas de release, **sin** publicar detalles que faciliten un exploit antes de que los usuarios actualicen.

## Buenas prácticas para quien despliega

- No subas `.env`, claves de API (Brevo, base de datos) ni backups SQL al repositorio
- Rota credenciales si alguna vez se expusieron en un issue, commit o chat
- Mantén `main` actualizado en Railway tras publicar un fix de seguridad

Gracias por ayudar a mantener **MGF Workspace** seguro.
