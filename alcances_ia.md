# Acceso a Datos del Sistema por Servicios de IA

## 🛠️ 1. Servicio: Profesionalización de Observaciones Técnicas de Baja
Tiene acceso de lectura únicamente a la siguiente información del sistema:
- **Observación del operador**: Descripción manual o motivo informal de la baja ingresado por el técnico.

---

## 🧠 2. Servicio: Diagnóstico Técnico e Inteligencia de Consulta (Drawer de IA)
Tiene acceso de lectura a los siguientes datos y campos del equipo consultado:

### A. Datos Generales del Equipo
- **cod_informatica**: Código de informática del equipo.
- **categoria**: Categoría del equipo.
- **ip**: Dirección IP asignada.
- **nombre**: Hostname o nombre de red.
- **nombre_usuario**: Cuenta de usuario local asignada.
- **estado**: Estado administrativo (`LIBRE`, `EN USO`, `BAJA`).
- **estado_fisico**: Clasificación del estado físico (`BUENO`, `REGULAR`, `MALO`).
- **vida_util**: Vida útil estimada en años.
- **observacion**: Observación técnica del equipo.

### B. Especificaciones Técnicas (Características)
- Atributos de hardware registrados (ej. Procesador, Memoria RAM, Disco Duro, Marca, Modelo, etc.).

### C. Contexto de Software (Programas Instalados)
- Lista de programas de software asignados al equipo, incluyendo:
  - **nombre**: Nombre del programa.
  - **cod_informatica**: Código de informática del programa.
  - **tipo**: Categoría o tipo de software (Institucional, Navegador, Ofimática, Soporte, Antivirus, etc.).
  - **estado**: Estado del programa.

### D. Contexto de Custodia (Responsable Asignado)
- Información del custodio o responsable actual del equipo:
  - **nombre_completo**: Nombre completo del responsable.
  - **cargo**: Cargo que ocupa en la institución.
  - **celular**: Número de celular de contacto.
  - **correo**: Correo electrónico institucional.
  - **oficina**: Nombre de la oficina donde labora.
  - **area**: Área institucional a la que pertenece la oficina.

### E. Historial de Cambios (Movimientos)
- Historial de los últimos 15 movimientos o acciones registradas sobre el equipo:
  - **fecha_hora**: Fecha y hora del movimiento.
  - **accion**: Tipo de acción realizada (`CREACION`, `MODIFICACION`, `ELIMINACION`).
  - **descripcion**: Detalles específicos de los cambios realizados.

### F. Consulta del Operador
- **pregunta**: Texto o consulta redactada por el usuario en tiempo real desde el drawer.
